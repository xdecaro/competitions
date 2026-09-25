<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;

final class com_xdecarocompetitionsInstallerScript
{
    public function postflight($type, $parent): void
    {
        if (!in_array((string) $type, ['install', 'update', 'discover_install'], true)) {
            return;
        }

        try {
            /** @var DatabaseInterface $db */
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $this->ensureFederationOrganizationLinkSchema($db);
            $this->ensureTeamOrganizationLinkSchema($db);

            try {
                $this->syncUndeterminedTeamFederations($db);
            } catch (\Throwable $syncError) {
                Log::add(
                    'Competitions federation backfill warning: ' . $syncError->getMessage(),
                    Log::WARNING,
                    'competitions'
                );
            }
        } catch (\Throwable $e) {
            Log::add(
                'Competitions schema repair warning: ' . $e->getMessage(),
                Log::ERROR,
                'competitions'
            );

            throw $e;
        }
    }

    private function ensureFederationOrganizationLinkSchema(DatabaseInterface $db): void
    {
        $table = $db->replacePrefix('#__xdecarocompetitions_federations');
        $columns = $db->getTableColumns($table, false);

        if (!array_key_exists('organization_uuid', $columns)) {
            $db->setQuery(
                'ALTER TABLE ' . $db->quoteName($table)
                . ' ADD COLUMN ' . $db->quoteName('organization_uuid')
                . ' CHAR(36) NULL AFTER ' . $db->quoteName('id')
            )->execute();
        }

        $indexes = (array) $db->setQuery(
            'SHOW INDEX FROM ' . $db->quoteName($table)
        )->loadObjectList();

        $hasUniqueIndex = false;

        foreach ($indexes as $index) {
            if ((string) ($index->Key_name ?? '') === 'uq_competitions_federations_organization_uuid') {
                $hasUniqueIndex = true;
                break;
            }
        }

        if (!$hasUniqueIndex) {
            $db->setQuery(
                'ALTER TABLE ' . $db->quoteName($table)
                . ' ADD UNIQUE KEY ' . $db->quoteName('uq_competitions_federations_organization_uuid')
                . ' (' . $db->quoteName('organization_uuid') . ')'
            )->execute();
        }
    }

    private function syncUndeterminedTeamFederations(DatabaseInterface $db): void
    {
        $component = Factory::getApplication()->bootComponent('com_xdecaroorganizations');

        if (!is_object($component) || !method_exists($component, 'getOrganizationProviderService')) {
            return;
        }

        $provider = $component->getOrganizationProviderService();

        if (!is_object($provider) || !method_exists($provider, 'getAffiliations')) {
            return;
        }

        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('f.id'),
                $db->quoteName('f.organization_uuid'),
                $db->quoteName('c.iso3'),
                $db->quoteName('c.code'),
            ])
            ->from($db->quoteName('#__xdecarocompetitions_federations', 'f'))
            ->leftJoin(
                $db->quoteName('#__xdecarocompetitions_countries', 'c')
                . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('f.country_id')
            )
            ->where($db->quoteName('f.organization_uuid') . ' IS NOT NULL')
            ->where($db->quoteName('f.organization_uuid') . " <> ''")
            ->where($db->quoteName('f.state') . ' <> -2');

        $federations = [];

        foreach ($db->setQuery($query)->loadObjectList() ?: [] as $federation) {
            $uuid = strtolower(trim((string) ($federation->organization_uuid ?? '')));

            if ($uuid === '') {
                continue;
            }

            $countryCode = strtoupper(trim((string) (($federation->iso3 ?? '') ?: ($federation->code ?? '')));

            $federations[$uuid] = [
                'id' => (int) $federation->id,
                'country_code' => $countryCode !== '' && strlen($countryCode) <= 3 ? $countryCode : null,
            ];
        }

        if (!$federations) {
            return;
        }

        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('id'),
                $db->quoteName('organization_uuid'),
            ])
            ->from($db->quoteName('#__xdecarocompetitions_teams'))
            ->where($db->quoteName('team_type') . ' = ' . $db->quote('club'))
            ->where($db->quoteName('federation_id') . ' = 0')
            ->where($db->quoteName('organization_uuid') . ' IS NOT NULL')
            ->where($db->quoteName('organization_uuid') . " <> ''")
            ->where($db->quoteName('state') . ' <> -2');

        $teams = $db->setQuery($query)->loadObjectList() ?: [];
        $updated = 0;
        $userId = (int) Factory::getApplication()->getIdentity()->id;
        $modified = Factory::getDate()->toSql();

        foreach ($teams as $team) {
            $clubUuid = strtolower(trim((string) ($team->organization_uuid ?? '')));

            if ($clubUuid === '') {
                continue;
            }

            try {
                $rows = (array) $provider->getAffiliations($clubUuid, true);
            } catch (\Throwable) {
                continue;
            }

            $targets = [];

            foreach ($rows as $row) {
                if (!is_array($row)
                    || (int) ($row['state'] ?? 0) !== 1
                    || strtolower(trim((string) ($row['relation_type'] ?? ''))) !== 'sports_affiliation'
                    || strtolower(trim((string) ($row['target_type'] ?? ''))) !== 'federation') {
                    continue;
                }

                $targetUuid = strtolower(trim((string) ($row['target_uuid'] ?? '')));

                if ($targetUuid !== '') {
                    $targets[$targetUuid] = true;
                }
            }

            if (count($targets) !== 1) {
                continue;
            }

            $targetUuid = (string) array_key_first($targets);

            if (!isset($federations[$targetUuid])) {
                continue;
            }

            $mapping = $federations[$targetUuid];
            $teamId = (int) ($team->id ?? 0);

            if ($teamId <= 0 || (int) $mapping['id'] <= 0) {
                continue;
            }

            $sets = [
                $db->quoteName('federation_id') . ' = ' . (int) $mapping['id'],
                $db->quoteName('modified') . ' = ' . $db->quote($modified),
                $db->quoteName('modified_by') . ' = ' . $userId,
            ];

            if ($mapping['country_code'] !== null) {
                $sets[] = $db->quoteName('country_code') . ' = ' . $db->quote((string) $mapping['country_code']);
            } else {
                $sets[] = $db->quoteName('country_code') . ' = NULL';
            }

            $update = $db->getQuery(true)
                ->update($db->quoteName('#__xdecarocompetitions_teams'))
                ->set($sets)
                ->where($db->quoteName('id') . ' = ' . $teamId)
                ->where($db->quoteName('federation_id') . ' = 0');

            $db->setQuery($update)->execute();
            $updated++;
        }

        if ($updated > 0) {
            Log::add(
                'Competitions federation backfill updated ' . $updated . ' linked club team(s).',
                Log::INFO,
                'competitions'
            );
        }
    }

    private function ensureTeamOrganizationLinkSchema(DatabaseInterface $db): void
    {
        $table = $db->replacePrefix('#__xdecarocompetitions_teams');
        $columns = $db->getTableColumns($table, false);

        if (!array_key_exists('organization_uuid', $columns)) {
            $db->setQuery(
                'ALTER TABLE ' . $db->quoteName($table)
                . ' ADD COLUMN ' . $db->quoteName('organization_uuid')
                . ' CHAR(36) NULL AFTER ' . $db->quoteName('id')
            )->execute();
        }

        $indexes = (array) $db->setQuery(
            'SHOW INDEX FROM ' . $db->quoteName($table)
        )->loadObjectList();

        $hasUniqueIndex = false;

        foreach ($indexes as $index) {
            if ((string) ($index->Key_name ?? '') === 'uq_competitions_teams_organization_uuid') {
                $hasUniqueIndex = true;
                break;
            }
        }

        if (!$hasUniqueIndex) {
            $db->setQuery(
                'ALTER TABLE ' . $db->quoteName($table)
                . ' ADD UNIQUE KEY ' . $db->quoteName('uq_competitions_teams_organization_uuid')
                . ' (' . $db->quoteName('organization_uuid') . ')'
            )->execute();
        }
    }
}

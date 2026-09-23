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
}

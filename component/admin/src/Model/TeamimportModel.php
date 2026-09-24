<?php
namespace xdecaro\Component\Competitions\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\ParameterType;
use Throwable;
use xdecaro\Component\Competitions\Administrator\Service\OrganizationsIntegrationService;

final class TeamimportModel extends BaseDatabaseModel
{
    public function getPreviewRows(): array
    {
        $integration = new OrganizationsIntegrationService();

        try {
            // Load the complete provider window once. Filtering is performed
            // client-side so checkbox selections survive every search change.
            $organizations = $integration->searchClubs('', 200);
        } catch (Throwable $e) {
            $this->setError($e->getMessage());
            return [];
        }

        if (!$organizations) {
            return [];
        }

        $db = $this->getDatabase();
        $existing = $this->existingTeamsByOrganizationUuid();
        $federations = $this->competitionFederationsByOrganizationUuid();
        $rows = [];

        foreach ($organizations as $organization) {
            $uuid = strtolower(trim((string) ($organization['uuid'] ?? '')));

            if ($uuid === '') {
                continue;
            }

            $row = [
                'uuid' => $uuid,
                'name' => trim((string) ($organization['name'] ?? '')),
                'code' => trim((string) ($organization['code'] ?? '')),
                'country_name' => trim((string) ($organization['country_name'] ?? '')),
                'country_code' => trim((string) ($organization['country_code'] ?? '')),
                'team_id' => (int) ($existing[$uuid]['id'] ?? 0),
                'team_approval_status' => (string) ($existing[$uuid]['approval_status'] ?? ''),
                'federation_name' => '',
                'federation_code' => '',
                'federation_status' => 'none',
                'status' => 'new',
                'selectable' => true,
            ];

            if ($row['team_id'] > 0) {
                $row['status'] = 'imported';
                $row['selectable'] = false;
            }

            try {
                $affiliations = $integration->getActiveSportsFederations($uuid);
            } catch (Throwable) {
                $affiliations = null;
            }

            if ($affiliations === null) {
                $row['federation_status'] = 'unavailable';

                if ($row['status'] !== 'imported') {
                    $row['status'] = 'unavailable';
                    $row['selectable'] = false;
                }
            } elseif (count($affiliations) > 1) {
                $row['federation_status'] = 'ambiguous';

                if ($row['status'] !== 'imported') {
                    $row['status'] = 'ambiguous';
                    $row['selectable'] = false;
                }
            } elseif (count($affiliations) === 1) {
                $targetUuid = strtolower(trim((string) ($affiliations[0]['target_uuid'] ?? '')));
                $row['federation_name'] = trim((string) ($affiliations[0]['target_name'] ?? ''));
                $row['federation_code'] = trim((string) ($affiliations[0]['target_code'] ?? ''));

                if ($targetUuid !== '' && isset($federations[$targetUuid])) {
                    $row['federation_status'] = 'mapped';
                    $row['federation_name'] = (string) $federations[$targetUuid]['name'];
                    $row['federation_code'] = (string) $federations[$targetUuid]['short_name'];

                    if ($row['status'] !== 'imported') {
                        $row['status'] = 'ready';
                    }
                } else {
                    $row['federation_status'] = 'unmapped';

                    if ($row['status'] !== 'imported') {
                        $row['status'] = 'unmapped';
                    }
                }
            } else {
                $row['federation_status'] = 'none';

                if ($row['status'] !== 'imported') {
                    $row['status'] = 'no_affiliation';
                }
            }

            $rows[] = $row;
        }

        usort(
            $rows,
            static fn (array $a, array $b): int => strcasecmp((string) $a['name'], (string) $b['name'])
        );

        return $rows;
    }

    public function getSummary(array $rows): array
    {
        $summary = [
            'total' => count($rows),
            'new' => 0,
            'ready' => 0,
            'no_affiliation' => 0,
            'unmapped' => 0,
            'ambiguous' => 0,
            'unavailable' => 0,
            'imported' => 0,
            'selectable' => 0,
        ];

        foreach ($rows as $row) {
            $status = (string) ($row['status'] ?? '');

            if (array_key_exists($status, $summary)) {
                $summary[$status]++;
            }

            if (!empty($row['selectable'])) {
                $summary['selectable']++;
            }
        }

        return $summary;
    }

    public function countClubsAtProviderLimit(array $rows): bool
    {
        return count($rows) >= 200;
    }

    private function existingTeamsByOrganizationUuid(): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('id'),
                $db->quoteName('organization_uuid'),
                $db->quoteName('approval_status'),
                $db->quoteName('state'),
            ])
            ->from($db->quoteName('#__xdecarocompetitions_teams'))
            ->where($db->quoteName('organization_uuid') . ' IS NOT NULL')
            ->where($db->quoteName('organization_uuid') . " <> ''");

        $map = [];

        foreach ($db->setQuery($query)->loadAssocList() ?: [] as $row) {
            $uuid = strtolower(trim((string) ($row['organization_uuid'] ?? '')));

            if ($uuid !== '') {
                $map[$uuid] = $row;
            }
        }

        return $map;
    }

    private function competitionFederationsByOrganizationUuid(): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('organization_uuid'),
                $db->quoteName('name'),
                $db->quoteName('short_name'),
            ])
            ->from($db->quoteName('#__xdecarocompetitions_federations'))
            ->where($db->quoteName('organization_uuid') . ' IS NOT NULL')
            ->where($db->quoteName('organization_uuid') . " <> ''")
            ->where($db->quoteName('state') . ' <> -2');

        $map = [];

        foreach ($db->setQuery($query)->loadAssocList() ?: [] as $row) {
            $uuid = strtolower(trim((string) ($row['organization_uuid'] ?? '')));

            if ($uuid !== '') {
                $map[$uuid] = $row;
            }
        }

        return $map;
    }
}

<?php
namespace xdecaro\Component\Competitions\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\ParameterType;
use xdecaro\Component\Competitions\Administrator\Helper\LiveSyncHelper;
use xdecaro\Component\Competitions\Administrator\Service\OrganizationsIntegrationService;

final class FederationModel extends BaseAdminModel
{
    private ?array $countryResolutionIndex = null;

    public function getTable($type = 'Federation', $prefix = 'Administrator', $config = []): Table
    {
        return parent::getTable($type, $prefix, $config);
    }

    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm('com_xdecarocompetitions.federation', 'federation', ['control' => 'jform', 'load_data' => $loadData]);
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_xdecarocompetitions.edit.federation.data', []);

        if (!$data) {
            $data = $this->getItem();
        }

        return $data;
    }

    /**
     * Map canonical Organizations federation UUIDs to local Competitions country IDs.
     *
     * Exact sovereign ISO alpha-2 mappings are preferred. If Organizations exposes a
     * sovereign country such as GB while Competitions uses a sports territory such as
     * England, the fallback is accepted only when exactly one local sports territory
     * is identified from explicit territory metadata or the federation name.
     *
     * @return array<string, int>
     */
    public function getOrganizationCountryMap(): array
    {
        $integration = new OrganizationsIntegrationService();

        if (!$integration->isAvailable()) {
            return [];
        }

        try {
            $organizations = $integration->searchFederations('', 200);
        } catch (\Throwable) {
            return [];
        }

        $map = [];

        foreach ($organizations as $organization) {
            $uuid = strtolower(trim((string) ($organization['uuid'] ?? '')));
            $countryId = $this->resolveCountryIdFromOrganization($organization);

            if ($uuid !== '' && $countryId > 0) {
                $map[$uuid] = $countryId;
            }
        }

        return $map;
    }

    public function save($data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $organizationUuid = strtolower(trim((string) ($data['organization_uuid'] ?? '')));
        $existingUuid = '';

        if ($id > 0) {
            $existing = $this->getItem($id);
            $existingUuid = strtolower(trim((string) ($existing->organization_uuid ?? '')));

            // Once a Competition federation is linked to its canonical
            // Organizations record, the relation is immutable from this form.
            // This prevents accidental federation swaps while preserving the
            // local Competition federation ID used by teams/history.
            if ($existingUuid !== '') {
                $organizationUuid = $existingUuid;
                $data['organization_uuid'] = $existingUuid;
            }
        }

        if ($organizationUuid !== '' && !$this->hasOrganizationUuidColumn()) {
            $this->setError(Text::_('COM_XDECAROCOMPETITIONS_ERROR_FEDERATION_LINK_SCHEMA_MISSING'));
            return false;
        }

        $integration = new OrganizationsIntegrationService();
        $available = $integration->isAvailable();

        if ($organizationUuid !== '') {
            if ($available) {
                try {
                    $organization = $integration->getFederation($organizationUuid);
                } catch (\Throwable $e) {
                    $this->setError(Text::_('COM_XDECAROCOMPETITIONS_ERROR_FEDERATION_ORGANIZATIONS_UNAVAILABLE'));
                    return false;
                }

                if (!$organization) {
                    $this->setError(Text::_('COM_XDECAROCOMPETITIONS_ERROR_FEDERATION_ORGANIZATION_INVALID'));
                    return false;
                }

                $data['organization_uuid'] = strtolower((string) $organization['uuid']);
                $data['name'] = mb_substr(trim((string) ($organization['name'] ?? '')), 0, 190);
                $data['short_name'] = $this->snapshotText($organization['code'] ?? null, 100);
                $data['logo'] = $this->snapshotText($organization['logo'] ?? null, 512);
                $data['website'] = $this->snapshotText($organization['website'] ?? null, 512);

                $countryId = $this->resolveCountryIdFromOrganization($organization);

                if ($countryId > 0) {
                    $data['country_id'] = $countryId;
                }

                $email = trim((string) ($organization['email'] ?? ''));
                $data['email'] = $email !== '' && mb_strlen($email) <= 190 ? $email : null;
            } elseif ($id <= 0 || $organizationUuid !== $existingUuid) {
                $this->setError(Text::_('COM_XDECAROCOMPETITIONS_ERROR_FEDERATION_ORGANIZATIONS_UNAVAILABLE'));
                return false;
            }
        } elseif ($available && ($id <= 0 || $existingUuid !== '')) {
            $this->setError(Text::_('COM_XDECAROCOMPETITIONS_ERROR_FEDERATION_ORGANIZATION_REQUIRED'));
            return false;
        }

        if (!parent::save($data)) {
            return false;
        }

        if ($organizationUuid !== '') {
            $savedId = (int) $this->getState($this->getName() . '.id');

            if ($savedId <= 0 || !$this->persistOrganizationUuid($savedId, $organizationUuid)) {
                $this->setError(Text::_('COM_XDECAROCOMPETITIONS_ERROR_FEDERATION_LINK_NOT_PERSISTED'));
                return false;
            }

            if ($available) {
                $this->syncUndeterminedLinkedClubs($integration, $organizationUuid, $savedId);
            }
        }

        return true;
    }

    protected function prepareTable($table): void
    {
        if (!$table->id && (int) $table->ordering === 0) {
            $table->ordering = $table->getNextOrder();
        }
    }

    private function hasOrganizationUuidColumn(): bool
    {
        $table = $this->getDatabase()->replacePrefix('#__xdecarocompetitions_federations');
        $columns = $this->getDatabase()->getTableColumns($table, false);

        return array_key_exists('organization_uuid', $columns);
    }

    private function persistOrganizationUuid(int $id, string $uuid): bool
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__xdecarocompetitions_federations'))
            ->set($db->quoteName('organization_uuid') . ' = :organizationUuid')
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':organizationUuid', $uuid)
            ->bind(':id', $id, \Joomla\Database\ParameterType::INTEGER);

        $db->setQuery($query)->execute();

        $query = $db->getQuery(true)
            ->select($db->quoteName('organization_uuid'))
            ->from($db->quoteName('#__xdecarocompetitions_federations'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $id, \Joomla\Database\ParameterType::INTEGER);

        return strtolower(trim((string) $db->setQuery($query, 0, 1)->loadResult())) === $uuid;
    }

    private function syncUndeterminedLinkedClubs(
        OrganizationsIntegrationService $integration,
        string $federationUuid,
        int $federationId
    ): void {
        $federationUuid = strtolower(trim($federationUuid));

        if ($federationUuid === '' || $federationId <= 0) {
            return;
        }

        $db = $this->getDatabase();
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

        if (!$teams) {
            return;
        }

        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('c.iso3'),
                $db->quoteName('c.code'),
            ])
            ->from($db->quoteName('#__xdecarocompetitions_federations', 'f'))
            ->innerJoin(
                $db->quoteName('#__xdecarocompetitions_countries', 'c')
                . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('f.country_id')
            )
            ->where($db->quoteName('f.id') . ' = :federationId')
            ->where($db->quoteName('f.state') . ' <> -2')
            ->where($db->quoteName('c.state') . ' <> -2')
            ->bind(':federationId', $federationId, ParameterType::INTEGER);

        $country = $db->setQuery($query, 0, 1)->loadObject();
        $countryCode = strtoupper(trim((string) (($country->iso3 ?? '') ?: ($country->code ?? ''))));
        $countryCode = $countryCode !== '' && strlen($countryCode) <= 3 ? $countryCode : null;
        $updatedIds = [];

        foreach ($teams as $team) {
            $clubUuid = strtolower(trim((string) ($team->organization_uuid ?? '')));

            if ($clubUuid === '') {
                continue;
            }

            try {
                $affiliations = $integration->getActiveSportsFederations($clubUuid);
            } catch (\Throwable) {
                continue;
            }

            if (count($affiliations) !== 1) {
                continue;
            }

            $targetUuid = strtolower(trim((string) ($affiliations[0]['target_uuid'] ?? '')));

            if ($targetUuid !== $federationUuid) {
                continue;
            }

            $teamId = (int) ($team->id ?? 0);

            if ($teamId <= 0) {
                continue;
            }

            $update = $db->getQuery(true)
                ->update($db->quoteName('#__xdecarocompetitions_teams'))
                ->set($db->quoteName('federation_id') . ' = :newFederationId')
                ->set(
                    $countryCode !== null
                        ? $db->quoteName('country_code') . ' = :countryCode'
                        : $db->quoteName('country_code') . ' = NULL'
                )
                ->where($db->quoteName('id') . ' = :teamId')
                ->where($db->quoteName('federation_id') . ' = 0')
                ->bind(':newFederationId', $federationId, ParameterType::INTEGER)
                ->bind(':teamId', $teamId, ParameterType::INTEGER);

            if ($countryCode !== null) {
                $update->bind(':countryCode', $countryCode, ParameterType::STRING);
            }

            $db->setQuery($update)->execute();
            $updatedIds[] = $teamId;
        }

        if (!$updatedIds) {
            return;
        }

        LiveSyncHelper::touchModified(
            $db,
            'team',
            $updatedIds,
            (int) Factory::getApplication()->getIdentity()->id
        );

        foreach ($updatedIds as $teamId) {
            LiveSyncHelper::record($db, 'team', $teamId, 'update');
        }
    }

    /**
     * Resolve a canonical Organizations federation to a local Competitions country.
     *
     * Sovereign ISO2 codes are exact. Sports territories are a separate namespace and
     * therefore are considered only when no exact ISO2 country exists. The fallback is
     * deliberately unique-only so a sovereign code such as GB is never blindly mapped
     * when multiple sports territories could be plausible.
     */
    private function resolveCountryIdFromOrganization(array $organization): int
    {
        $countryCode = strtoupper(trim((string) ($organization['country_code'] ?? '')));

        if ($countryCode !== '') {
            $countryId = $this->resolveCountryIdFromIso2($countryCode);

            if ($countryId > 0) {
                return $countryId;
            }
        }

        $territoryName = trim((string) ($organization['territory_name'] ?? ''));
        $organizationName = trim((string) ($organization['name'] ?? ''));

        return $this->findUniqueSportsTerritoryMatch($territoryName, $organizationName);
    }

    private function findUniqueSportsTerritoryMatch(string $territoryName, string $organizationName): int
    {
        $index = $this->getCountryResolutionIndex();
        $explicitTerritory = $this->normalizeTerritoryToken($territoryName);
        $normalizedOrganization = $this->normalizeTerritoryToken($organizationName);
        $matches = [];

        foreach ($index['sports'] as $territory) {
            $id = (int) ($territory['id'] ?? 0);
            $name = $this->normalizeTerritoryToken((string) ($territory['name'] ?? ''));
            $code = $this->normalizeTerritoryToken((string) ($territory['code'] ?? ''));

            if ($id <= 0 || $name === '') {
                continue;
            }

            $explicitMatch = $explicitTerritory !== ''
                && ($explicitTerritory === $name || ($code !== '' && $explicitTerritory === $code));

            $nameMatch = false;

            if (!$explicitMatch && $normalizedOrganization !== '') {
                $pattern = '/(?:^|\s)' . preg_quote($name, '/') . '(?:\s|$)/u';
                $nameMatch = preg_match($pattern, $normalizedOrganization) === 1;
            }

            if ($explicitMatch || $nameMatch) {
                $matches[$id] = true;
            }
        }

        return count($matches) === 1 ? (int) array_key_first($matches) : 0;
    }

    private function resolveCountryIdFromIso2(string $countryCode): int
    {
        $countryCode = strtoupper(trim($countryCode));

        if (!preg_match('/^[A-Z]{2}$/', $countryCode)) {
            return 0;
        }

        $index = $this->getCountryResolutionIndex();

        return (int) ($index['iso2'][$countryCode] ?? 0);
    }

    /**
     * @return array{iso2: array<string, int>, sports: array<int, array{id:int,name:string,code:string}>}
     */
    private function getCountryResolutionIndex(): array
    {
        if ($this->countryResolutionIndex !== null) {
            return $this->countryResolutionIndex;
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('id'),
                $db->quoteName('name'),
                $db->quoteName('code'),
                $db->quoteName('iso2'),
                $db->quoteName('entity_type'),
            ])
            ->from($db->quoteName('#__xdecarocompetitions_countries'))
            ->where($db->quoteName('state') . ' <> -2');

        $iso2 = [];
        $sports = [];

        foreach ($db->setQuery($query)->loadObjectList() ?: [] as $country) {
            $id = (int) ($country->id ?? 0);
            $alpha2 = strtoupper(trim((string) ($country->iso2 ?? '')));
            $entityType = strtolower(trim((string) ($country->entity_type ?? '')));

            if ($id <= 0) {
                continue;
            }

            if ($alpha2 !== '') {
                $iso2[$alpha2] = $id;
            }

            if ($entityType === 'sport_territory') {
                $sports[] = [
                    'id' => $id,
                    'name' => (string) ($country->name ?? ''),
                    'code' => (string) ($country->code ?? ''),
                ];
            }
        }

        $this->countryResolutionIndex = [
            'iso2' => $iso2,
            'sports' => $sports,
        ];

        return $this->countryResolutionIndex;
    }

    private function normalizeTerritoryToken(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    }

    private function snapshotText(mixed $value, int $maxLength): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, $maxLength);
    }
}

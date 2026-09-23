<?php
namespace xdecaro\Component\Competitions\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use RuntimeException;
use Throwable;

/**
 * Optional read-only bridge to Organizations.
 *
 * Competitions never queries Organizations private tables. Canonical
 * Organizations UUIDs are stored locally while federation/team identity fields
 * remain compatibility snapshots for existing Competitions relations.
 */
final class OrganizationsIntegrationService
{
    public function isAvailable(): bool
    {
        try {
            $provider = $this->provider();
            $provider->searchOrganizations(['type' => 'federation'], 1, false);
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function searchFederations(string $search = '', int $limit = 200): array
    {
        try {
            $filters = ['type' => 'federation'];
            $search = trim($search);

            if ($search !== '') {
                $filters['search'] = $search;
            }

            $rows = (array) $this->provider()->searchOrganizations(
                $filters,
                max(1, min(200, $limit)),
                false
            );

            return array_values(array_filter(
                $rows,
                static fn ($row): bool => is_array($row)
                    && strtolower((string) ($row['type'] ?? '')) === 'federation'
                    && !empty($row['uuid'])
            ));
        } catch (Throwable $e) {
            throw new RuntimeException(
                'Organizations federation search is unavailable: ' . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }
    }

    public function searchClubs(string $search = '', int $limit = 200): array
    {
        try {
            $filters = ['type' => 'club'];
            $search = trim($search);

            if ($search !== '') {
                $filters['search'] = $search;
            }

            $rows = (array) $this->provider()->searchOrganizations(
                $filters,
                max(1, min(200, $limit)),
                false
            );

            return array_values(array_filter(
                $rows,
                static fn ($row): bool => is_array($row)
                    && strtolower((string) ($row['type'] ?? '')) === 'club'
                    && !empty($row['uuid'])
            ));
        } catch (Throwable $e) {
            throw new RuntimeException(
                'Organizations club search is unavailable: ' . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }
    }

    /**
     * Resolve active sports federation affiliations for a canonical club.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getActiveSportsFederations(string $clubUuid): array
    {
        $clubUuid = strtolower(trim($clubUuid));

        if ($clubUuid === '') {
            return [];
        }

        $provider = $this->provider();

        if (!method_exists($provider, 'getAffiliations')) {
            throw new RuntimeException('Organizations affiliations provider is unavailable.');
        }

        try {
            $rows = (array) $provider->getAffiliations($clubUuid, true);
        } catch (Throwable $e) {
            throw new RuntimeException(
                'Organizations affiliation lookup is unavailable: ' . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }

        $result = [];
        $seen = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            if (strtolower(trim((string) ($row['relation_type'] ?? ''))) !== 'sports_affiliation') {
                continue;
            }

            if (strtolower(trim((string) ($row['target_type'] ?? ''))) !== 'federation') {
                continue;
            }

            $targetUuid = strtolower(trim((string) ($row['target_uuid'] ?? '')));

            if ($targetUuid === '' || isset($seen[$targetUuid])) {
                continue;
            }

            $seen[$targetUuid] = true;
            $result[] = $row;
        }

        return $result;
    }

    public function getClub(string $uuid): ?array
    {
        $uuid = strtolower(trim($uuid));

        if ($uuid === '') {
            return null;
        }

        try {
            $row = $this->provider()->getOrganization($uuid, false);
        } catch (Throwable $e) {
            throw new RuntimeException(
                'Organizations club lookup is unavailable: ' . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }

        if (!is_array($row) || strtolower((string) ($row['type'] ?? '')) !== 'club') {
            return null;
        }

        return $row;
    }

    public function getFederation(string $uuid): ?array
    {
        $uuid = strtolower(trim($uuid));

        if ($uuid === '') {
            return null;
        }

        try {
            $row = $this->provider()->getOrganization($uuid, false);
        } catch (Throwable $e) {
            throw new RuntimeException(
                'Organizations federation lookup is unavailable: ' . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }

        if (!is_array($row) || strtolower((string) ($row['type'] ?? '')) !== 'federation') {
            return null;
        }

        return $row;
    }

    private function provider(): object
    {
        try {
            $component = Factory::getApplication()->bootComponent('com_xdecaroorganizations');
        } catch (Throwable $e) {
            throw new RuntimeException('Organizations component could not be booted.', 0, $e);
        }

        if (!is_object($component) || !method_exists($component, 'getOrganizationProviderService')) {
            throw new RuntimeException('Organizations public provider is unavailable.');
        }

        $provider = $component->getOrganizationProviderService();

        if (!is_object($provider)
            || !method_exists($provider, 'getOrganization')
            || !method_exists($provider, 'searchOrganizations')) {
            throw new RuntimeException('Organizations public provider is incompatible.');
        }

        return $provider;
    }
}

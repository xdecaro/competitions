<?php
namespace xdecaro\Component\Competitions\Administrator\Service;

defined('_JEXEC') or die;

use DomainException;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use RuntimeException;
use Throwable;

final class DrawIntegrationService
{
    public const DRAW_COMPONENT = 'com_xdecarodraw';
    public const SOURCE_COMPONENT = 'com_xdecarocompetitions';
    public const MIN_DRAW_VERSION = '1.1.0';
    public const APPROVED_STATUS = 'approved';
    public const REQUEST_SCHEMA = 'xdecaro.draw.request.v1';
    public const RESULT_SCHEMA = 'xdecaro.draw.result.v1';

    private DatabaseInterface $db;

    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    public function getAvailability(): array
    {
        $extensionType = 'component';
        $drawElement = self::DRAW_COMPONENT;

        $query = $this->db->getQuery(true)
            ->select([
                $this->db->quoteName('enabled'),
                $this->db->quoteName('manifest_cache'),
            ])
            ->from($this->db->quoteName('#__extensions'))
            ->where($this->db->quoteName('type') . ' = :type')
            ->where($this->db->quoteName('element') . ' = :element')
            ->bind(':type', $extensionType)
            ->bind(':element', $drawElement)
            ->order($this->db->quoteName('extension_id') . ' DESC');

        $extension = $this->db->setQuery($query, 0, 1)->loadAssoc();

        if (!$extension || (int) ($extension['enabled'] ?? 0) !== 1) {
            return [
                'available' => false,
                'version' => '',
                'reason' => 'missing',
            ];
        }

        $manifest = json_decode((string) ($extension['manifest_cache'] ?? ''), true);
        $version = is_array($manifest) ? trim((string) ($manifest['version'] ?? '')) : '';

        if ($version === '' || version_compare($version, self::MIN_DRAW_VERSION, '<')) {
            return [
                'available' => false,
                'version' => $version,
                'reason' => 'incompatible',
            ];
        }

        try {
            $integration = $this->bootIntegration();
        } catch (Throwable) {
            return [
                'available' => false,
                'version' => $version,
                'reason' => 'unavailable',
            ];
        }

        if (!method_exists($integration, 'createFromPayload') || !method_exists($integration, 'getResult')) {
            return [
                'available' => false,
                'version' => $version,
                'reason' => 'incompatible',
            ];
        }

        return [
            'available' => true,
            'version' => $version,
            'reason' => '',
        ];
    }

    public function getSeasonContext(int $seasonId): array
    {
        $seasonId = max(1, $seasonId);
        $query = $this->db->getQuery(true)
            ->select([
                $this->db->quoteName('s.id'),
                $this->db->quoteName('s.name'),
                $this->db->quoteName('s.season_year'),
                $this->db->quoteName('s.start_date'),
                $this->db->quoteName('s.end_date'),
                $this->db->quoteName('s.host_city'),
                $this->db->quoteName('s.host_country_code'),
                $this->db->quoteName('t.id', 'tournament_id'),
                $this->db->quoteName('t.name', 'tournament_name'),
                $this->db->quoteName('t.code', 'tournament_code'),
            ])
            ->from($this->db->quoteName('#__xdecarocompetitions_seasons', 's'))
            ->join(
                'INNER',
                $this->db->quoteName('#__xdecarocompetitions_tournaments', 't')
                . ' ON ' . $this->db->quoteName('t.id') . ' = ' . $this->db->quoteName('s.tournament_id')
            )
            ->where($this->db->quoteName('s.id') . ' = :seasonId')
            ->where($this->db->quoteName('s.state') . ' <> -2')
            ->bind(':seasonId', $seasonId, ParameterType::INTEGER);

        $season = $this->db->setQuery($query, 0, 1)->loadAssoc();

        if (!$season) {
            throw new DomainException('Competition season not found.');
        }

        $approvedStatus = self::APPROVED_STATUS;
        $query = $this->db->getQuery(true)
            ->select([
                $this->db->quoteName('p.id', 'participation_id'),
                $this->db->quoteName('p.team_id'),
                $this->db->quoteName('p.status'),
                $this->db->quoteName('tm.name', 'team_name'),
                $this->db->quoteName('tm.short_name', 'team_short_name'),
                $this->db->quoteName('tm.country_code'),
            ])
            ->from($this->db->quoteName('#__xdecarocompetitions_participations', 'p'))
            ->join(
                'INNER',
                $this->db->quoteName('#__xdecarocompetitions_teams', 'tm')
                . ' ON ' . $this->db->quoteName('tm.id') . ' = ' . $this->db->quoteName('p.team_id')
            )
            ->where($this->db->quoteName('p.season_id') . ' = :participationSeasonId')
            ->where($this->db->quoteName('p.status') . ' = :approvedStatus')
            ->where($this->db->quoteName('p.state') . ' = 1')
            ->where($this->db->quoteName('tm.state') . ' = 1')
            ->bind(':participationSeasonId', $seasonId, ParameterType::INTEGER)
            ->bind(':approvedStatus', $approvedStatus)
            ->order($this->db->quoteName('tm.name') . ' ASC')
            ->order($this->db->quoteName('p.id') . ' ASC');

        $participations = $this->db->setQuery($query)->loadAssocList() ?: [];

        return [
            'season' => $season,
            'participations' => $participations,
            'approved_count' => count($participations),
            'availability' => $this->getAvailability(),
            'latest_link' => $this->getLatestLink($seasonId),
        ];
    }

    public function createSeasonDraw(int $seasonId, int $groupCount, int $actorId = 0): array
    {
        $availability = $this->getAvailability();
        if (empty($availability['available'])) {
            throw new DomainException('Draw 1.1.0 or newer is required for automatic draws.');
        }

        $context = $this->getSeasonContext($seasonId);
        $participantCount = (int) $context['approved_count'];

        if ($participantCount < 2) {
            throw new DomainException('At least two approved participations are required.');
        }
        if ($groupCount < 2 || $groupCount > $participantCount) {
            throw new DomainException('Group count must be between 2 and the number of approved participations.');
        }

        $payload = $this->buildPayload($context, $groupCount);
        $requestHash = $this->requestHash($payload, $groupCount);
        $latest = $this->getLatestLink((int) $context['season']['id']);

        if ($latest && (int) ($latest['state'] ?? 0) === 1 && hash_equals((string) ($latest['request_hash'] ?? ''), $requestHash)) {
            return [
                'draw_id' => (int) $latest['draw_id'],
                'status' => (string) ($latest['status'] ?? 'ready'),
                'reused' => true,
                'request_hash' => $requestHash,
            ];
        }

        $integration = $this->bootIntegration();
        $result = $integration->createFromPayload($payload, max(0, $actorId));
        $drawId = (int) ($result['draw_id'] ?? 0);

        if ($drawId < 1) {
            throw new RuntimeException('Draw did not return a valid draw id.');
        }

        $this->db->transactionStart();
        try {
            $query = $this->db->getQuery(true)
                ->update($this->db->quoteName('#__xdecarocompetitions_draw_links'))
                ->set($this->db->quoteName('state') . ' = 0')
                ->where($this->db->quoteName('season_id') . ' = :seasonId')
                ->where($this->db->quoteName('state') . ' = 1')
                ->bind(':seasonId', $seasonId, ParameterType::INTEGER);
            $this->db->setQuery($query)->execute();

            $row = (object) [
                'season_id' => $seasonId,
                'draw_id' => $drawId,
                'request_schema' => self::REQUEST_SCHEMA,
                'result_schema' => self::RESULT_SCHEMA,
                'request_hash' => $requestHash,
                'group_count' => $groupCount,
                'status' => (string) ($result['status'] ?? 'ready'),
                'state' => 1,
                'created' => Factory::getDate()->toSql(),
                'created_by' => max(0, $actorId),
            ];
            $this->db->insertObject('#__xdecarocompetitions_draw_links', $row);
            $this->db->transactionCommit();
        } catch (Throwable $e) {
            $this->db->transactionRollback();
            throw new RuntimeException('Draw #' . $drawId . ' was created but Competitions could not store the link.', 0, $e);
        }

        return [
            'draw_id' => $drawId,
            'status' => (string) ($result['status'] ?? 'ready'),
            'reused' => false,
            'request_hash' => $requestHash,
        ];
    }

    public function getLatestLink(int $seasonId): ?array
    {
        $boundSeasonId = max(1, $seasonId);
        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($this->db->quoteName('#__xdecarocompetitions_draw_links'))
            ->where($this->db->quoteName('season_id') . ' = :seasonId')
            ->order($this->db->quoteName('state') . ' DESC')
            ->order($this->db->quoteName('id') . ' DESC')
            ->bind(':seasonId', $boundSeasonId, ParameterType::INTEGER);

        $row = $this->db->setQuery($query, 0, 1)->loadAssoc();
        if (!$row) {
            return null;
        }

        $row['result_available'] = false;
        $row['published'] = false;

        try {
            $result = $this->bootIntegration()->getResult((int) $row['draw_id']);
            if ((string) ($result['schema'] ?? '') !== self::RESULT_SCHEMA) {
                throw new DomainException('Unsupported Draw result schema.');
            }
            $row['status'] = (string) ($result['status'] ?? $row['status']);
            $row['result_available'] = true;
            $row['published'] = !empty($result['published']);
            $row['result'] = $result;
        } catch (Throwable) {
            // Ready/draft draws have no public result yet. Existing local link stays valid.
        }

        return $row;
    }

    private function buildPayload(array $context, int $groupCount): array
    {
        $season = $context['season'];
        $entries = [];

        foreach ($context['participations'] as $index => $participation) {
            $metadata = [];
            $country = strtoupper(trim((string) ($participation['country_code'] ?? '')));
            if ($country !== '') {
                $metadata['country'] = $country;
            }

            $entries[] = [
                'key' => 'participation:' . (int) $participation['participation_id'],
                'source' => [
                    'component' => self::SOURCE_COMPONENT,
                    'entity' => 'participation',
                    'id' => (string) (int) $participation['participation_id'],
                ],
                'name' => (string) $participation['team_name'],
                'seed' => $index + 1,
                'metadata' => $metadata,
            ];
        }

        return [
            'schema' => 'xdecaro.draw.request.v1',
            'title' => trim((string) ($season['tournament_name'] ?? '') . ' — ' . (string) ($season['name'] ?? '') . ' — Draw'),
            'source' => [
                'component' => self::SOURCE_COMPONENT,
                'entity' => 'season',
                'id' => (string) (int) $season['id'],
            ],
            'mode' => 'groups',
            'entries' => $entries,
            'targets' => $this->buildTargets(count($entries), $groupCount),
            'constraints' => [],
        ];
    }

    private function buildTargets(int $entryCount, int $groupCount): array
    {
        $targets = [];
        for ($index = 0; $index < $entryCount; $index++) {
            $groupIndex = $index % $groupCount;
            $group = $this->groupLabel($groupIndex);
            $targets[] = [
                'type' => 'group',
                'key' => $group,
                'position' => intdiv($index, $groupCount) + 1,
                'metadata' => ['group' => $group],
            ];
        }

        return $targets;
    }

    private function groupLabel(int $index): string
    {
        $label = '';
        $value = $index + 1;
        while ($value > 0) {
            $value--;
            $label = chr(65 + ($value % 26)) . $label;
            $value = intdiv($value, 26);
        }

        return $label;
    }

    private function requestHash(array $payload, int $groupCount): string
    {
        $identity = [
            'source' => $payload['source'],
            'entries' => array_map(static fn (array $entry): array => [
                'key' => $entry['key'],
                'source' => $entry['source'],
                'name' => $entry['name'],
                'metadata' => $entry['metadata'],
            ], $payload['entries']),
            'group_count' => $groupCount,
        ];

        return hash('sha256', json_encode($identity, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    private function bootIntegration(): object
    {
        $component = Factory::getApplication()->bootComponent(self::DRAW_COMPONENT);
        if (!is_object($component) || !method_exists($component, 'getIntegrationService')) {
            throw new DomainException('Draw public integration facade is unavailable.');
        }

        $integration = $component->getIntegrationService();
        if (!is_object($integration)) {
            throw new DomainException('Draw public integration service is unavailable.');
        }

        return $integration;
    }
}
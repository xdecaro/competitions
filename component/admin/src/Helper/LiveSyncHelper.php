<?php
namespace Xdecaro\Component\Decarodcl\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class LiveSyncHelper
{
    private const LEGACY_LOCK = '__dcl_unmodified__';

    private const ENTITY_TABLES = [
        'organization' => '#__dcl_organizations',
        'zone' => '#__dcl_zones',
        'country' => '#__dcl_countries',
        'federation' => '#__dcl_federations',
        'tournament' => '#__dcl_tournaments',
        'season' => '#__dcl_seasons',
        'team' => '#__dcl_teams',
        'participation' => '#__dcl_participations',
        'player' => '#__dcl_players',
        'roster' => '#__dcl_rosters',
        'match' => '#__dcl_matches',
    ];

    private const VIEW_ENTITIES = [
        'organizations' => 'organization',
        'organization' => 'organization',
        'zones' => 'zone',
        'zone' => 'zone',
        'countries' => 'country',
        'country' => 'country',
        'federations' => 'federation',
        'federation' => 'federation',
        'tournaments' => 'tournament',
        'tournament' => 'tournament',
        'seasons' => 'season',
        'season' => 'season',
        'teams' => 'team',
        'team' => 'team',
        'participations' => 'participation',
        'participation' => 'participation',
        'players' => 'player',
        'player' => 'player',
        'rosters' => 'roster',
        'roster' => 'roster',
        'matches' => 'match',
        'match' => 'match',
    ];

    public static function normalizeEntity(string $value): string
    {
        $value = strtolower(trim($value));

        return self::VIEW_ENTITIES[$value] ?? (isset(self::ENTITY_TABLES[$value]) ? $value : '');
    }

    public static function entityFromModelName(string $modelName): string
    {
        return self::normalizeEntity($modelName);
    }

    public static function isSupportedEntity(string $entity): bool
    {
        return isset(self::ENTITY_TABLES[self::normalizeEntity($entity)]);
    }

    public static function sanitizeClientId(string $value): string
    {
        $value = preg_replace('/[^a-zA-Z0-9_.:-]/', '', trim($value)) ?? '';

        return substr($value, 0, 64);
    }

    public static function currentModified(DatabaseInterface $db, string $entity, int $entityId): ?string
    {
        $entity = self::normalizeEntity($entity);

        if ($entity === '' || $entityId <= 0) {
            return null;
        }

        $table = self::ENTITY_TABLES[$entity];
        $legacyLock = self::LEGACY_LOCK;
        $query = $db->getQuery(true)
            ->select(
                'COALESCE(' . $db->quoteName('modified') . ', ' . $db->quote($legacyLock) . ')'
            )
            ->from($db->quoteName($table))
            ->where($db->quoteName('id') . ' = :entityId')
            ->bind(':entityId', $entityId, ParameterType::INTEGER);

        $value = $db->setQuery($query, 0, 1)->loadResult();

        return $value === null ? null : (string) $value;
    }

    public static function record(DatabaseInterface $db, string $entity, int $entityId, string $action): void
    {
        $entity = self::normalizeEntity($entity);

        if ($entity === '' || $entityId <= 0) {
            return;
        }

        $action = strtolower(trim($action));

        if (!in_array($action, ['create', 'update', 'state', 'delete'], true)) {
            $action = 'update';
        }

        try {
            $app = Factory::getApplication();
            $clientId = self::sanitizeClientId($app->input->post->getString('dcl_client_id', ''));
            $changedAt = Factory::getDate()->toSql();
            $changedBy = (int) $app->getIdentity()->id;

            $query = $db->getQuery(true)
                ->insert($db->quoteName('#__dcl_changes'))
                ->columns([
                    $db->quoteName('entity_type'),
                    $db->quoteName('entity_id'),
                    $db->quoteName('action'),
                    $db->quoteName('changed_at'),
                    $db->quoteName('changed_by'),
                    $db->quoteName('client_id'),
                ])
                ->values(':entity, :entityId, :action, :changedAt, :changedBy, :clientId')
                ->bind(':entity', $entity)
                ->bind(':entityId', $entityId, ParameterType::INTEGER)
                ->bind(':action', $action)
                ->bind(':changedAt', $changedAt)
                ->bind(':changedBy', $changedBy, ParameterType::INTEGER)
                ->bind(':clientId', $clientId);

            $db->setQuery($query)->execute();
        } catch (\Throwable $e) {
            Log::add('Competitions live-sync change log warning: ' . $e->getMessage(), Log::WARNING, 'dcl');
        }
    }

    public static function latestChangeId(DatabaseInterface $db): int
    {
        try {
            $query = $db->getQuery(true)
                ->select('MAX(' . $db->quoteName('id') . ')')
                ->from($db->quoteName('#__dcl_changes'));

            return (int) $db->setQuery($query)->loadResult();
        } catch (\Throwable) {
            return 0;
        }
    }

    public static function listChanges(DatabaseInterface $db, int $sinceId, string $clientId): array
    {
        if ($sinceId <= 0) {
            return [];
        }

        $clientId = self::sanitizeClientId($clientId);
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('c.id'),
                $db->quoteName('c.entity_type'),
                $db->quoteName('c.entity_id'),
                $db->quoteName('c.action'),
                $db->quoteName('c.changed_at'),
                $db->quoteName('c.changed_by'),
                $db->quoteName('c.client_id'),
                $db->quoteName('u.name', 'changed_by_name'),
            ])
            ->from($db->quoteName('#__dcl_changes', 'c'))
            ->leftJoin(
                $db->quoteName('#__users', 'u')
                . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('c.changed_by')
            )
            ->where($db->quoteName('c.id') . ' > :sinceId')
            ->bind(':sinceId', $sinceId, ParameterType::INTEGER)
            ->order($db->quoteName('c.id') . ' ASC');

        if ($clientId !== '') {
            $query->where(
                '(' . $db->quoteName('c.client_id') . ' IS NULL'
                . ' OR ' . $db->quoteName('c.client_id') . " = ''"
                . ' OR ' . $db->quoteName('c.client_id') . ' <> :clientId)'
            )->bind(':clientId', $clientId);
        }

        try {
            return $db->setQuery($query, 0, 100)->loadAssocList() ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    public static function touchPresence(
        DatabaseInterface $db,
        string $entity,
        int $entityId,
        string $clientId,
        int $userId
    ): void {
        $entity = self::normalizeEntity($entity);
        $clientId = self::sanitizeClientId($clientId);

        if ($entity === '' || $entityId <= 0 || $clientId === '' || $userId <= 0) {
            return;
        }

        $touchedAt = Factory::getDate()->toSql();

        try {
            $query = $db->getQuery(true)
                ->select($db->quoteName('id'))
                ->from($db->quoteName('#__dcl_edit_sessions'))
                ->where($db->quoteName('entity_type') . ' = :entity')
                ->where($db->quoteName('entity_id') . ' = :entityId')
                ->where($db->quoteName('client_id') . ' = :clientId')
                ->bind(':entity', $entity)
                ->bind(':entityId', $entityId, ParameterType::INTEGER)
                ->bind(':clientId', $clientId);
            $sessionId = (int) $db->setQuery($query, 0, 1)->loadResult();

            if ($sessionId > 0) {
                $query = $db->getQuery(true)
                    ->update($db->quoteName('#__dcl_edit_sessions'))
                    ->set($db->quoteName('user_id') . ' = :userId')
                    ->set($db->quoteName('touched_at') . ' = :touchedAt')
                    ->where($db->quoteName('id') . ' = :sessionId')
                    ->bind(':userId', $userId, ParameterType::INTEGER)
                    ->bind(':touchedAt', $touchedAt)
                    ->bind(':sessionId', $sessionId, ParameterType::INTEGER);
                $db->setQuery($query)->execute();

                return;
            }

            $query = $db->getQuery(true)
                ->insert($db->quoteName('#__dcl_edit_sessions'))
                ->columns([
                    $db->quoteName('entity_type'),
                    $db->quoteName('entity_id'),
                    $db->quoteName('client_id'),
                    $db->quoteName('user_id'),
                    $db->quoteName('touched_at'),
                ])
                ->values(':entity, :entityId, :clientId, :userId, :touchedAt')
                ->bind(':entity', $entity)
                ->bind(':entityId', $entityId, ParameterType::INTEGER)
                ->bind(':clientId', $clientId)
                ->bind(':userId', $userId, ParameterType::INTEGER)
                ->bind(':touchedAt', $touchedAt);
            $db->setQuery($query)->execute();
        } catch (\Throwable) {
        }
    }

    public static function listPresence(DatabaseInterface $db, string $entity, int $entityId, string $clientId): array
    {
        $entity = self::normalizeEntity($entity);
        $clientId = self::sanitizeClientId($clientId);

        if ($entity === '' || $entityId <= 0) {
            return [];
        }

        $cutoff = Factory::getDate('-45 seconds')->toSql();
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('s.client_id'),
                $db->quoteName('s.user_id'),
                $db->quoteName('s.touched_at'),
                $db->quoteName('u.name', 'user_name'),
            ])
            ->from($db->quoteName('#__dcl_edit_sessions', 's'))
            ->leftJoin(
                $db->quoteName('#__users', 'u')
                . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('s.user_id')
            )
            ->where($db->quoteName('s.entity_type') . ' = :entity')
            ->where($db->quoteName('s.entity_id') . ' = :entityId')
            ->where($db->quoteName('s.touched_at') . ' >= :cutoff')
            ->bind(':entity', $entity)
            ->bind(':entityId', $entityId, ParameterType::INTEGER)
            ->bind(':cutoff', $cutoff)
            ->order($db->quoteName('s.touched_at') . ' DESC');

        if ($clientId !== '') {
            $query->where($db->quoteName('s.client_id') . ' <> :clientId')
                ->bind(':clientId', $clientId);
        }

        try {
            return $db->setQuery($query, 0, 10)->loadAssocList() ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    public static function cleanup(DatabaseInterface $db): void
    {
        try {
            $presenceCutoff = Factory::getDate('-2 minutes')->toSql();
            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__dcl_edit_sessions'))
                ->where($db->quoteName('touched_at') . ' < :presenceCutoff')
                ->bind(':presenceCutoff', $presenceCutoff);
            $db->setQuery($query)->execute();

            $changeCutoff = Factory::getDate('-30 days')->toSql();
            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__dcl_changes'))
                ->where($db->quoteName('changed_at') . ' < :changeCutoff')
                ->bind(':changeCutoff', $changeCutoff);
            $db->setQuery($query)->execute();
        } catch (\Throwable) {
        }
    }
}

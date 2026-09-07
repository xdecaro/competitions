<?php
namespace Xdecaro\Component\Decarodcl\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class TournamentScopeHelper
{
    public const SCOPES = ['international', 'zone', 'national', 'local'];
    public const PARTICIPANT_TYPES = ['club', 'national'];
    public const TEAM_TYPES = ['club', 'national'];

    public static function normalizeIds($values): array
    {
        $ids = [];

        foreach ((array) $values as $value) {
            $id = (int) $value;

            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }

    public static function load(DatabaseInterface $db, int $tournamentId): array
    {
        if ($tournamentId <= 0) {
            return ['country_ids' => [], 'zone_ids' => []];
        }

        $query = $db->getQuery(true)
            ->select($db->quoteName('country_id'))
            ->from($db->quoteName('#__dcl_tournament_countries'))
            ->where($db->quoteName('tournament_id') . ' = :tournamentId')
            ->order($db->quoteName('ordering') . ' ASC')
            ->bind(':tournamentId', $tournamentId, ParameterType::INTEGER);
        $countryIds = array_map('intval', $db->setQuery($query)->loadColumn() ?: []);

        $query = $db->getQuery(true)
            ->select($db->quoteName('zone_id'))
            ->from($db->quoteName('#__dcl_tournament_zones'))
            ->where($db->quoteName('tournament_id') . ' = :tournamentId')
            ->order($db->quoteName('ordering') . ' ASC')
            ->bind(':tournamentId', $tournamentId, ParameterType::INTEGER);
        $zoneIds = array_map('intval', $db->setQuery($query)->loadColumn() ?: []);

        return ['country_ids' => $countryIds, 'zone_ids' => $zoneIds];
    }

    public static function validateSelection(
        DatabaseInterface $db,
        string $scopeType,
        string $participantType,
        int $countryId,
        array $zoneIds
    ): array {
        LanguageHelper::load();
        $scopeType = strtolower(trim($scopeType));
        $participantType = strtolower(trim($participantType));
        $zoneIds = self::normalizeIds($zoneIds);

        if (!in_array($scopeType, self::SCOPES, true)) {
            throw new \RuntimeException(Text::_('COM_DECARODCL_ERROR_SCOPE_INVALID'));
        }

        if (!in_array($participantType, self::PARTICIPANT_TYPES, true)) {
            throw new \RuntimeException(Text::_('COM_DECARODCL_ERROR_PARTICIPANT_TYPE_INVALID'));
        }

        if (in_array($scopeType, ['national', 'local'], true)) {
            if ($countryId <= 0 || !self::countryExists($db, $countryId)) {
                throw new \RuntimeException(Text::_('COM_DECARODCL_ERROR_SCOPE_COUNTRY_REQUIRED'));
            }

            $zoneIds = [];
        } elseif ($scopeType === 'zone') {
            $countryId = 0;

            if (!$zoneIds) {
                throw new \RuntimeException(Text::_('COM_DECARODCL_ERROR_SCOPE_ZONE_REQUIRED'));
            }

            if (!self::zonesExist($db, $zoneIds)) {
                throw new \RuntimeException(Text::_('COM_DECARODCL_ERROR_SCOPE_ZONE_INVALID'));
            }
        } else {
            $countryId = 0;
            $zoneIds = [];
        }

        return [
            'scope_type' => $scopeType,
            'participant_type' => $participantType,
            'country_id' => $countryId,
            'zone_ids' => $zoneIds,
        ];
    }

    public static function sync(
        DatabaseInterface $db,
        int $tournamentId,
        string $scopeType,
        int $countryId,
        array $zoneIds
    ): void {
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__dcl_tournament_countries'))
            ->where($db->quoteName('tournament_id') . ' = :tournamentId')
            ->bind(':tournamentId', $tournamentId, ParameterType::INTEGER);
        $db->setQuery($query)->execute();

        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__dcl_tournament_zones'))
            ->where($db->quoteName('tournament_id') . ' = :tournamentId')
            ->bind(':tournamentId', $tournamentId, ParameterType::INTEGER);
        $db->setQuery($query)->execute();

        if (in_array($scopeType, ['national', 'local'], true) && $countryId > 0) {
            $query = $db->getQuery(true)
                ->insert($db->quoteName('#__dcl_tournament_countries'))
                ->columns([
                    $db->quoteName('tournament_id'),
                    $db->quoteName('country_id'),
                    $db->quoteName('ordering'),
                ])
                ->values(':tournamentId, :countryId, 1')
                ->bind(':tournamentId', $tournamentId, ParameterType::INTEGER)
                ->bind(':countryId', $countryId, ParameterType::INTEGER);
            $db->setQuery($query)->execute();
        }

        if ($scopeType === 'zone' && $zoneIds) {
            $query = $db->getQuery(true)
                ->insert($db->quoteName('#__dcl_tournament_zones'))
                ->columns([
                    $db->quoteName('tournament_id'),
                    $db->quoteName('zone_id'),
                    $db->quoteName('ordering'),
                ]);

            foreach ($zoneIds as $index => $zoneId) {
                $query->values((int) $tournamentId . ', ' . (int) $zoneId . ', ' . (int) ($index + 1));
            }

            $db->setQuery($query)->execute();
        }
    }

    public static function assertTeamEligible(DatabaseInterface $db, int $tournamentId, int $teamId): void
    {
        LanguageHelper::load();

        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('tm.team_type'),
                $db->quoteName('f.country_id'),
            ])
            ->from($db->quoteName('#__dcl_teams', 'tm'))
            ->innerJoin(
                $db->quoteName('#__dcl_federations', 'f')
                . ' ON ' . $db->quoteName('f.id') . ' = ' . $db->quoteName('tm.federation_id')
            )
            ->where($db->quoteName('tm.id') . ' = :teamId')
            ->where($db->quoteName('tm.state') . ' <> -2')
            ->where($db->quoteName('f.state') . ' <> -2')
            ->bind(':teamId', $teamId, ParameterType::INTEGER);
        $team = $db->setQuery($query, 0, 1)->loadObject();

        if (!$team) {
            throw new \RuntimeException(Text::_('COM_DECARODCL_ERROR_PARTICIPATION_TEAM_INVALID'));
        }

        self::assertTeamAttributesEligible(
            $db,
            $tournamentId,
            (string) $team->team_type,
            (int) $team->country_id
        );
    }

    public static function assertTeamAttributesEligible(
        DatabaseInterface $db,
        int $tournamentId,
        string $teamType,
        int $countryId
    ): void {
        LanguageHelper::load();
        $teamType = strtolower(trim($teamType));

        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('scope_type'),
                $db->quoteName('participant_type'),
            ])
            ->from($db->quoteName('#__dcl_tournaments'))
            ->where($db->quoteName('id') . ' = :tournamentId')
            ->where($db->quoteName('state') . ' <> -2')
            ->bind(':tournamentId', $tournamentId, ParameterType::INTEGER);
        $tournament = $db->setQuery($query, 0, 1)->loadObject();

        if (!$tournament) {
            throw new \RuntimeException(Text::_('COM_DECARODCL_ERROR_PARTICIPATION_SCOPE_INVALID'));
        }

        if ((string) $tournament->participant_type !== $teamType) {
            throw new \RuntimeException(Text::_('COM_DECARODCL_ERROR_PARTICIPATION_TEAM_TYPE_MISMATCH'));
        }

        self::assertCountryAllowed($db, $tournamentId, (string) $tournament->scope_type, $countryId);
    }

    public static function assertExistingParticipationsCompatible(DatabaseInterface $db, int $tournamentId): void
    {
        $query = $db->getQuery(true)
            ->select('DISTINCT ' . $db->quoteName('p.team_id'))
            ->from($db->quoteName('#__dcl_participations', 'p'))
            ->innerJoin(
                $db->quoteName('#__dcl_seasons', 's')
                . ' ON ' . $db->quoteName('s.id') . ' = ' . $db->quoteName('p.season_id')
            )
            ->where($db->quoteName('s.tournament_id') . ' = :tournamentId')
            ->where($db->quoteName('p.state') . ' <> -2')
            ->bind(':tournamentId', $tournamentId, ParameterType::INTEGER);

        foreach (array_map('intval', $db->setQuery($query)->loadColumn() ?: []) as $teamId) {
            try {
                self::assertTeamEligible($db, $tournamentId, $teamId);
            } catch (\RuntimeException $e) {
                LanguageHelper::load();
                throw new \RuntimeException(
                    Text::_('COM_DECARODCL_ERROR_SCOPE_EXISTING_PARTICIPATIONS') . ' ' . $e->getMessage()
                );
            }
        }
    }

    public static function assertHostCountryAllowed(DatabaseInterface $db, int $tournamentId, string $countryCode): void
    {
        $countryCode = strtoupper(trim($countryCode));

        if ($countryCode === '') {
            return;
        }

        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__dcl_countries'))
            ->where($db->quoteName('code') . ' = :countryCode')
            ->where($db->quoteName('state') . ' <> -2')
            ->bind(':countryCode', $countryCode);
        $countryId = (int) $db->setQuery($query, 0, 1)->loadResult();

        if ($countryId <= 0) {
            return;
        }

        $query = $db->getQuery(true)
            ->select($db->quoteName('scope_type'))
            ->from($db->quoteName('#__dcl_tournaments'))
            ->where($db->quoteName('id') . ' = :tournamentId')
            ->bind(':tournamentId', $tournamentId, ParameterType::INTEGER);
        $scopeType = (string) $db->setQuery($query, 0, 1)->loadResult();

        self::assertCountryAllowed($db, $tournamentId, $scopeType, $countryId, true);
    }

    private static function assertCountryAllowed(
        DatabaseInterface $db,
        int $tournamentId,
        string $scopeType,
        int $countryId,
        bool $hostContext = false
    ): void {
        if ($scopeType === 'international') {
            return;
        }

        if (in_array($scopeType, ['national', 'local'], true)) {
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__dcl_tournament_countries'))
                ->where($db->quoteName('tournament_id') . ' = :tournamentId')
                ->where($db->quoteName('country_id') . ' = :countryId')
                ->bind(':tournamentId', $tournamentId, ParameterType::INTEGER)
                ->bind(':countryId', $countryId, ParameterType::INTEGER);
            $allowed = (int) $db->setQuery($query)->loadResult() > 0;
        } elseif ($scopeType === 'zone') {
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__dcl_tournament_zones', 'tz'))
                ->innerJoin(
                    $db->quoteName('#__dcl_zone_countries', 'zc')
                    . ' ON ' . $db->quoteName('zc.zone_id') . ' = ' . $db->quoteName('tz.zone_id')
                )
                ->where($db->quoteName('tz.tournament_id') . ' = :tournamentId')
                ->where($db->quoteName('zc.country_id') . ' = :countryId')
                ->bind(':tournamentId', $tournamentId, ParameterType::INTEGER)
                ->bind(':countryId', $countryId, ParameterType::INTEGER);
            $allowed = (int) $db->setQuery($query)->loadResult() > 0;
        } else {
            $allowed = false;
        }

        if (!$allowed) {
            LanguageHelper::load();
            throw new \RuntimeException(
                Text::_($hostContext
                    ? 'COM_DECARODCL_ERROR_SEASON_HOST_OUTSIDE_SCOPE'
                    : 'COM_DECARODCL_ERROR_PARTICIPATION_COUNTRY_OUTSIDE_SCOPE')
            );
        }
    }

    private static function countryExists(DatabaseInterface $db, int $countryId): bool
    {
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__dcl_countries'))
            ->where($db->quoteName('id') . ' = :countryId')
            ->where($db->quoteName('state') . ' <> -2')
            ->bind(':countryId', $countryId, ParameterType::INTEGER);

        return (int) $db->setQuery($query)->loadResult() > 0;
    }

    private static function zonesExist(DatabaseInterface $db, array $zoneIds): bool
    {
        if (!$zoneIds) {
            return false;
        }

        $placeholders = [];
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__dcl_zones'))
            ->where($db->quoteName('state') . ' <> -2');

        foreach ($zoneIds as $index => $zoneId) {
            $placeholder = ':zone' . $index;
            $placeholders[] = $placeholder;
            $query->bind($placeholder, $zoneIds[$index], ParameterType::INTEGER);
        }

        $query->where($db->quoteName('id') . ' IN (' . implode(',', $placeholders) . ')');

        return (int) $db->setQuery($query)->loadResult() === count($zoneIds);
    }
}

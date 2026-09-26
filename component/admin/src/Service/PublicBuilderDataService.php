<?php
namespace xdecaro\Component\Competitions\Administrator\Service;

defined('_JEXEC') or die;

use DateTimeImmutable;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Stable read-only public data surface for presentation integrations.
 *
 * This service deliberately exposes only public competition data. It must not
 * become a shortcut around People, Organizations, approval, ACL or other
 * product boundaries.
 */
final class PublicBuilderDataService
{
    private const DEFAULT_LIMIT = 20;
    private const MAX_LIMIT = 250;

    /**
     * Canonical public standing field contract reserved for the future
     * Competitions standings engine. No scoring formula is duplicated here.
     */
    private const STANDING_FIELDS = [
        'position',
        'team_id',
        'team_name',
        'team_short_name',
        'team_logo',
        'played',
        'won',
        'drawn',
        'lost',
        'goals_for',
        'goals_against',
        'goal_difference',
        'points',
    ];

    public function __construct(private DatabaseInterface $db)
    {
    }

    public function getCurrentCompetitions(int $limit = 12): array
    {
        return $this->getCompetitionRows('current', null, null, null, null, $limit);
    }

    public function getUpcomingCompetitions(int $limit = 12): array
    {
        return $this->getCompetitionRows('upcoming', null, null, null, null, $limit);
    }

    public function getPreviousCompetitions(int $limit = 12): array
    {
        return $this->getCompetitionRows('previous', null, null, null, null, $limit);
    }

    public function getCompetitionsByYear(int $year, int $limit = 50): array
    {
        if ($year < 1900 || $year > 2200) {
            return [];
        }

        return $this->getCompetitionRows(null, $year, null, null, null, $limit);
    }

    public function getCompetitionsByTournament(int $tournamentId, int $limit = 50): array
    {
        if ($tournamentId < 1) {
            return [];
        }

        return $this->getCompetitionRows(null, null, $tournamentId, null, null, $limit);
    }

    public function getUpcomingSeasons(int $fromYear, int $toYear, int $limit = 100): array
    {
        $fromYear = max(1900, min(2200, $fromYear));
        $toYear = max(1900, min(2200, $toYear));

        if ($toYear < $fromYear) {
            [$fromYear, $toYear] = [$toYear, $fromYear];
        }

        return $this->getCompetitionRows(null, null, null, $fromYear, $toYear, $limit);
    }

    public function getParticipatingTeams(int $seasonId, bool $approvedOnly = true): array
    {
        if ($seasonId < 1) {
            return [];
        }

        $db = $this->db;
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('tm.id', 'id'),
                $db->quoteName('tm.name', 'name'),
                $db->quoteName('tm.short_name', 'short_name'),
                $db->quoteName('tm.alias', 'alias'),
                $db->quoteName('tm.logo', 'logo'),
                $db->quoteName('tm.country_code', 'country_code'),
                $db->quoteName('tm.city', 'city'),
                $db->quoteName('tm.federation_id', 'federation_id'),
                $db->quoteName('f.name', 'federation_name'),
                $db->quoteName('f.short_name', 'federation_short_name'),
                $db->quoteName('p.status', 'participation_status'),
            ])
            ->from($db->quoteName('#__xdecarocompetitions_participations', 'p'))
            ->innerJoin(
                $db->quoteName('#__xdecarocompetitions_teams', 'tm')
                . ' ON ' . $db->quoteName('tm.id') . ' = ' . $db->quoteName('p.team_id')
            )
            // LEFT JOIN is intentional: a missing federation must not hide a public team.
            ->leftJoin(
                $db->quoteName('#__xdecarocompetitions_federations', 'f')
                . ' ON ' . $db->quoteName('f.id') . ' = ' . $db->quoteName('tm.federation_id')
                . ' AND ' . $db->quoteName('f.state') . ' = 1'
            )
            ->where($db->quoteName('p.season_id') . ' = :seasonId')
            ->where($db->quoteName('p.state') . ' = 1')
            ->where($db->quoteName('tm.state') . ' = 1')
            ->where($db->quoteName('tm.approval_status') . ' = ' . $db->quote('approved'))
            ->bind(':seasonId', $seasonId, ParameterType::INTEGER)
            ->order($db->quoteName('tm.name') . ' ASC');

        // Public default contract: p.status = 'approved' and p.state = 1.
        if ($approvedOnly) {
            $query->where($db->quoteName('p.status') . ' = ' . $db->quote('approved'));
        }

        $rows = $db->setQuery($query)->loadAssocList() ?: [];

        return array_map([$this, 'normalizeTeam'], $rows);
    }

    public function getTeam(int $teamId): ?array
    {
        if ($teamId < 1) {
            return null;
        }

        $db = $this->db;
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('tm.id', 'id'),
                $db->quoteName('tm.name', 'name'),
                $db->quoteName('tm.short_name', 'short_name'),
                $db->quoteName('tm.alias', 'alias'),
                $db->quoteName('tm.logo', 'logo'),
                $db->quoteName('tm.country_code', 'country_code'),
                $db->quoteName('tm.city', 'city'),
                $db->quoteName('tm.federation_id', 'federation_id'),
                $db->quoteName('f.name', 'federation_name'),
                $db->quoteName('f.short_name', 'federation_short_name'),
            ])
            ->from($db->quoteName('#__xdecarocompetitions_teams', 'tm'))
            ->leftJoin(
                $db->quoteName('#__xdecarocompetitions_federations', 'f')
                . ' ON ' . $db->quoteName('f.id') . ' = ' . $db->quoteName('tm.federation_id')
                . ' AND ' . $db->quoteName('f.state') . ' = 1'
            )
            ->where($db->quoteName('tm.id') . ' = :teamId')
            ->where($db->quoteName('tm.state') . ' = 1')
            ->where($db->quoteName('tm.approval_status') . ' = ' . $db->quote('approved'))
            ->bind(':teamId', $teamId, ParameterType::INTEGER);

        $row = $db->setQuery($query, 0, 1)->loadAssoc();

        return $row ? $this->normalizeTeam($row) : null;
    }

    public function getUpcomingMatches(?int $seasonId = null, ?int $teamId = null, int $limit = 20): array
    {
        return $this->getMatchRows('upcoming', $seasonId, $teamId, null, $limit);
    }

    public function getLatestResults(?int $seasonId = null, ?int $teamId = null, int $limit = 20): array
    {
        return $this->getMatchRows('finished', $seasonId, $teamId, null, $limit);
    }

    public function getMatchesBySeason(int $seasonId, int $limit = 100): array
    {
        if ($seasonId < 1) {
            return [];
        }

        return $this->getMatchRows(null, $seasonId, null, null, $limit);
    }

    public function getMatch(int $matchId): ?array
    {
        if ($matchId < 1) {
            return null;
        }

        $rows = $this->getMatchRows(null, null, null, $matchId, 1);

        return $rows[0] ?? null;
    }

    /**
     * Returns the canonical competition standings once Competitions owns a
     * standings/scoring service. The current repository has match results and
     * coefficient rankings, but no authoritative standings/tie-break engine.
     * Returning an empty list is intentional: presentation integrations must
     * never invent 3/1/0 rules or tie-break logic.
     */
    public function getStandings(int $seasonId): array
    {
        if ($seasonId < 1) {
            return [];
        }

        // Public schema is reserved by self::STANDING_FIELDS.
        return [];
    }

    public function getRankings(?int $seasonId = null, int $limit = 100): array
    {
        $db = $this->db;
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('r.id', 'id'),
                $db->quoteName('r.tournament_id', 'tournament_id'),
                $db->quoteName('t.name', 'tournament_name'),
                $db->quoteName('r.ranking_type', 'ranking_type'),
                $db->quoteName('r.entity_id', 'entity_id'),
                $db->quoteName('r.season_end_id', 'season_end_id'),
                $db->quoteName('r.seasons_count', 'seasons_count'),
                $db->quoteName('r.coefficient_total', 'coefficient_total'),
                $db->quoteName('r.position', 'position'),
                $db->quoteName('r.calculated_at', 'calculated_at'),
                'CASE WHEN ' . $db->quoteName('r.ranking_type') . " = 'club' THEN " . $db->quoteName('tm.name')
                    . ' ELSE ' . $db->quoteName('f.name') . ' END AS ' . $db->quoteName('entity_name'),
                'CASE WHEN ' . $db->quoteName('r.ranking_type') . " = 'club' THEN " . $db->quoteName('tm.short_name')
                    . ' ELSE ' . $db->quoteName('f.short_name') . ' END AS ' . $db->quoteName('entity_short_name'),
            ])
            ->from($db->quoteName('#__xdecarocompetitions_rankings', 'r'))
            ->innerJoin(
                $db->quoteName('#__xdecarocompetitions_tournaments', 't')
                . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('r.tournament_id')
                . ' AND ' . $db->quoteName('t.state') . ' = 1'
            )
            ->leftJoin(
                $db->quoteName('#__xdecarocompetitions_teams', 'tm')
                . ' ON ' . $db->quoteName('r.ranking_type') . " = 'club'"
                . ' AND ' . $db->quoteName('tm.id') . ' = ' . $db->quoteName('r.entity_id')
                . ' AND ' . $db->quoteName('tm.state') . ' = 1'
            )
            ->leftJoin(
                $db->quoteName('#__xdecarocompetitions_federations', 'f')
                . ' ON ' . $db->quoteName('r.ranking_type') . " <> 'club'"
                . ' AND ' . $db->quoteName('f.id') . ' = ' . $db->quoteName('r.entity_id')
                . ' AND ' . $db->quoteName('f.state') . ' = 1'
            )
            ->order($db->quoteName('r.position') . ' ASC')
            ->order($db->quoteName('r.coefficient_total') . ' DESC');

        if ($seasonId !== null) {
            if ($seasonId < 1) {
                return [];
            }
            $query->where($db->quoteName('r.season_end_id') . ' = :seasonEndId')
                ->bind(':seasonEndId', $seasonId, ParameterType::INTEGER);
        }

        $rows = $db->setQuery($query, 0, $this->clampLimit($limit, 100))->loadAssocList() ?: [];

        foreach ($rows as &$row) {
            foreach (['id', 'tournament_id', 'entity_id', 'season_end_id', 'seasons_count', 'position'] as $key) {
                $row[$key] = isset($row[$key]) ? (int) $row[$key] : null;
            }
            $row['coefficient_total'] = isset($row['coefficient_total']) ? (float) $row['coefficient_total'] : 0.0;
        }
        unset($row);

        return $rows;
    }

    public function getCountries(int $limit = 250): array
    {
        $db = $this->db;
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('id', 'id'),
                $db->quoteName('name', 'name'),
                $db->quoteName('code', 'code'),
                $db->quoteName('iso2', 'iso2'),
                $db->quoteName('iso3', 'iso3'),
                $db->quoteName('entity_type', 'entity_type'),
                $db->quoteName('flag', 'flag'),
            ])
            ->from($db->quoteName('#__xdecarocompetitions_countries'))
            ->where($db->quoteName('state') . ' = 1')
            ->order($db->quoteName('ordering') . ' ASC')
            ->order($db->quoteName('name') . ' ASC');

        $rows = $db->setQuery($query, 0, $this->clampLimit($limit, 250))->loadAssocList() ?: [];
        foreach ($rows as &$row) {
            $row['id'] = (int) ($row['id'] ?? 0);
        }
        unset($row);

        return $rows;
    }

    public function getFederations(?int $countryId = null, int $limit = 250): array
    {
        $db = $this->db;
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('id', 'id'),
                $db->quoteName('country_id', 'country_id'),
                $db->quoteName('name', 'name'),
                $db->quoteName('short_name', 'short_name'),
                $db->quoteName('logo', 'logo'),
                $db->quoteName('website', 'website'),
            ])
            ->from($db->quoteName('#__xdecarocompetitions_federations'))
            ->where($db->quoteName('state') . ' = 1')
            ->order($db->quoteName('ordering') . ' ASC')
            ->order($db->quoteName('name') . ' ASC');

        if ($countryId !== null) {
            if ($countryId < 1) {
                return [];
            }
            $query->where($db->quoteName('country_id') . ' = :countryId')
                ->bind(':countryId', $countryId, ParameterType::INTEGER);
        }

        $rows = $db->setQuery($query, 0, $this->clampLimit($limit, 250))->loadAssocList() ?: [];
        foreach ($rows as &$row) {
            $row['id'] = (int) ($row['id'] ?? 0);
            $row['country_id'] = (int) ($row['country_id'] ?? 0);
        }
        unset($row);

        return $rows;
    }

    private function getCompetitionRows(
        ?string $temporal,
        ?int $year,
        ?int $tournamentId,
        ?int $fromYear,
        ?int $toYear,
        int $limit
    ): array {
        $db = $this->db;
        $today = (new DateTimeImmutable('today'))->format('Y-m-d');

        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('s.id', 'season_id'),
                $db->quoteName('t.id', 'tournament_id'),
                $db->quoteName('t.name', 'tournament_name'),
                $db->quoteName('t.code', 'tournament_code'),
                $db->quoteName('t.discipline', 'discipline'),
                $db->quoteName('t.gender', 'gender'),
                $db->quoteName('s.name', 'season_name'),
                $db->quoteName('s.season_year', 'season_year'),
                $db->quoteName('s.host_city', 'host_city'),
                $db->quoteName('s.host_country_code', 'host_country_code'),
                $db->quoteName('s.start_date', 'start_date'),
                $db->quoteName('s.end_date', 'end_date'),
                "COUNT(DISTINCT CASE WHEN p.status = 'approved' AND p.state = 1 THEN p.team_id END) AS " . $db->quoteName('team_count'),
            ])
            ->from($db->quoteName('#__xdecarocompetitions_seasons', 's'))
            ->innerJoin(
                $db->quoteName('#__xdecarocompetitions_tournaments', 't')
                . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('s.tournament_id')
            )
            ->leftJoin(
                $db->quoteName('#__xdecarocompetitions_participations', 'p')
                . ' ON ' . $db->quoteName('p.season_id') . ' = ' . $db->quoteName('s.id')
            )
            ->where($db->quoteName('s.state') . ' = 1')
            ->where($db->quoteName('t.state') . ' = 1')
            ->group([
                $db->quoteName('s.id'),
                $db->quoteName('t.id'),
                $db->quoteName('t.name'),
                $db->quoteName('t.code'),
                $db->quoteName('t.discipline'),
                $db->quoteName('t.gender'),
                $db->quoteName('s.name'),
                $db->quoteName('s.season_year'),
                $db->quoteName('s.host_city'),
                $db->quoteName('s.host_country_code'),
                $db->quoteName('s.start_date'),
                $db->quoteName('s.end_date'),
            ]);

        if ($year !== null) {
            $query->where($db->quoteName('s.season_year') . ' = :seasonYear')
                ->bind(':seasonYear', $year, ParameterType::INTEGER);
        }

        if ($tournamentId !== null) {
            $query->where($db->quoteName('t.id') . ' = :tournamentId')
                ->bind(':tournamentId', $tournamentId, ParameterType::INTEGER);
        }

        if ($fromYear !== null && $toYear !== null) {
            $query->where($db->quoteName('s.season_year') . ' BETWEEN :fromYear AND :toYear')
                ->bind(':fromYear', $fromYear, ParameterType::INTEGER)
                ->bind(':toYear', $toYear, ParameterType::INTEGER);
        }

        if ($temporal === 'upcoming') {
            $query->where($db->quoteName('s.start_date') . ' IS NOT NULL')
                ->where($db->quoteName('s.start_date') . ' > :todayUpcoming')
                ->bind(':todayUpcoming', $today);
        } elseif ($temporal === 'previous') {
            $query->where($db->quoteName('s.end_date') . ' IS NOT NULL')
                ->where($db->quoteName('s.end_date') . ' < :todayPrevious')
                ->bind(':todayPrevious', $today);
        } elseif ($temporal === 'current') {
            $query->where(
                '(' . $db->quoteName('s.start_date') . ' IS NOT NULL OR '
                . $db->quoteName('s.end_date') . ' IS NOT NULL)'
            )
                ->where(
                    '(' . $db->quoteName('s.start_date') . ' IS NULL OR '
                    . $db->quoteName('s.start_date') . ' <= :todayCurrentStart)'
                )
                ->where(
                    '(' . $db->quoteName('s.end_date') . ' IS NULL OR '
                    . $db->quoteName('s.end_date') . ' >= :todayCurrentEnd)'
                )
                ->bind(':todayCurrentStart', $today)
                ->bind(':todayCurrentEnd', $today);
        }

        if ($temporal === 'previous') {
            $query->order($db->quoteName('s.end_date') . ' DESC');
        } else {
            $query->order($db->quoteName('s.start_date') . ' ASC')
                ->order($db->quoteName('s.season_year') . ' ASC');
        }
        $query->order($db->quoteName('t.name') . ' ASC');

        $rows = $db->setQuery($query, 0, $this->clampLimit($limit, self::DEFAULT_LIMIT))->loadAssocList() ?: [];

        return array_map([$this, 'normalizeCompetition'], $rows);
    }

    private function getMatchRows(
        ?string $mode,
        ?int $seasonId,
        ?int $teamId,
        ?int $matchId,
        int $limit
    ): array {
        if ($seasonId !== null && $seasonId < 1) {
            return [];
        }
        if ($teamId !== null && $teamId < 1) {
            return [];
        }
        if ($matchId !== null && $matchId < 1) {
            return [];
        }

        $db = $this->db;
        $today = (new DateTimeImmutable('today'))->format('Y-m-d');
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('m.id', 'id'),
                $db->quoteName('m.season_id', 'season_id'),
                $db->quoteName('s.tournament_id', 'tournament_id'),
                $db->quoteName('t.name', 'tournament_name'),
                $db->quoteName('s.name', 'season_name'),
                $db->quoteName('s.season_year', 'season_year'),
                $db->quoteName('m.home_team_id', 'home_team_id'),
                $db->quoteName('home.name', 'home_team_name'),
                $db->quoteName('home.short_name', 'home_team_short_name'),
                $db->quoteName('home.logo', 'home_team_logo'),
                $db->quoteName('m.away_team_id', 'away_team_id'),
                $db->quoteName('away.name', 'away_team_name'),
                $db->quoteName('away.short_name', 'away_team_short_name'),
                $db->quoteName('away.logo', 'away_team_logo'),
                $db->quoteName('m.venue_id', 'venue_id'),
                $db->quoteName('v.name', 'venue_name'),
                $db->quoteName('v.city', 'venue_city'),
                $db->quoteName('m.match_date', 'match_date'),
                $db->quoteName('m.kickoff_time', 'kickoff_time'),
                $db->quoteName('m.stage', 'stage'),
                $db->quoteName('m.group_name', 'group_name'),
                $db->quoteName('m.round_name', 'round_name'),
                $db->quoteName('m.matchday', 'matchday'),
                $db->quoteName('m.status', 'status'),
                $db->quoteName('m.home_score', 'home_score'),
                $db->quoteName('m.away_score', 'away_score'),
                $db->quoteName('m.home_score_extra', 'home_score_extra'),
                $db->quoteName('m.away_score_extra', 'away_score_extra'),
                $db->quoteName('m.home_penalties', 'home_penalties'),
                $db->quoteName('m.away_penalties', 'away_penalties'),
            ])
            ->from($db->quoteName('#__xdecarocompetitions_matches', 'm'))
            ->innerJoin(
                $db->quoteName('#__xdecarocompetitions_seasons', 's')
                . ' ON ' . $db->quoteName('s.id') . ' = ' . $db->quoteName('m.season_id')
                . ' AND ' . $db->quoteName('s.state') . ' = 1'
            )
            ->innerJoin(
                $db->quoteName('#__xdecarocompetitions_tournaments', 't')
                . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('s.tournament_id')
                . ' AND ' . $db->quoteName('t.state') . ' = 1'
            )
            ->leftJoin(
                $db->quoteName('#__xdecarocompetitions_teams', 'home')
                . ' ON ' . $db->quoteName('home.id') . ' = ' . $db->quoteName('m.home_team_id')
                . ' AND ' . $db->quoteName('home.state') . ' = 1'
            )
            ->leftJoin(
                $db->quoteName('#__xdecarocompetitions_teams', 'away')
                . ' ON ' . $db->quoteName('away.id') . ' = ' . $db->quoteName('m.away_team_id')
                . ' AND ' . $db->quoteName('away.state') . ' = 1'
            )
            ->leftJoin(
                $db->quoteName('#__xdecarocompetitions_venues', 'v')
                . ' ON ' . $db->quoteName('v.id') . ' = ' . $db->quoteName('m.venue_id')
                . ' AND ' . $db->quoteName('v.state') . ' = 1'
            )
            ->where($db->quoteName('m.state') . ' = 1');

        if ($seasonId !== null) {
            $query->where($db->quoteName('m.season_id') . ' = :matchSeasonId')
                ->bind(':matchSeasonId', $seasonId, ParameterType::INTEGER);
        }

        if ($teamId !== null) {
            $query->where(
                '(' . $db->quoteName('m.home_team_id') . ' = :homeTeamId OR '
                . $db->quoteName('m.away_team_id') . ' = :awayTeamId)'
            )
                ->bind(':homeTeamId', $teamId, ParameterType::INTEGER)
                ->bind(':awayTeamId', $teamId, ParameterType::INTEGER);
        }

        if ($matchId !== null) {
            $query->where($db->quoteName('m.id') . ' = :matchId')
                ->bind(':matchId', $matchId, ParameterType::INTEGER);
        }

        if ($mode === 'finished') {
            $query->where($db->quoteName('m.status') . ' = ' . $db->quote('finished'))
                ->where($db->quoteName('m.home_score') . ' IS NOT NULL')
                ->where($db->quoteName('m.away_score') . ' IS NOT NULL')
                ->order($db->quoteName('m.match_date') . ' DESC')
                ->order($db->quoteName('m.kickoff_time') . ' DESC');
        } elseif ($mode === 'upcoming') {
            $query->where(
                $db->quoteName('m.status') . ' IN ('
                . $db->quote('scheduled') . ', '
                . $db->quote('live') . ', '
                . $db->quote('postponed') . ')'
            )
                ->where(
                    '(' . $db->quoteName('m.match_date') . ' IS NULL OR '
                    . $db->quoteName('m.match_date') . ' >= :matchToday)'
                )
                ->bind(':matchToday', $today)
                ->order('CASE WHEN ' . $db->quoteName('m.match_date') . ' IS NULL THEN 1 ELSE 0 END ASC')
                ->order($db->quoteName('m.match_date') . ' ASC')
                ->order($db->quoteName('m.kickoff_time') . ' ASC');
        } else {
            $query->order($db->quoteName('m.match_date') . ' DESC')
                ->order($db->quoteName('m.kickoff_time') . ' DESC');
        }

        $query->order($db->quoteName('m.id') . ' DESC');
        $rows = $db->setQuery($query, 0, $this->clampLimit($limit, self::DEFAULT_LIMIT))->loadAssocList() ?: [];

        return array_map([$this, 'normalizeMatch'], $rows);
    }

    private function normalizeCompetition(array $row): array
    {
        $year = isset($row['season_year']) && $row['season_year'] !== null ? (int) $row['season_year'] : null;
        $tournamentName = trim((string) ($row['tournament_name'] ?? ''));
        $seasonName = trim((string) ($row['season_name'] ?? ''));
        $title = $tournamentName;

        if ($year !== null) {
            $title = trim($tournamentName . ' ' . $year);
        } elseif ($seasonName !== '') {
            $title = trim($tournamentName . ' — ' . $seasonName);
        }

        return [
            'season_id' => (int) ($row['season_id'] ?? 0),
            'tournament_id' => (int) ($row['tournament_id'] ?? 0),
            'title' => $title,
            'tournament_name' => $tournamentName,
            'tournament_code' => $this->nullableString($row['tournament_code'] ?? null),
            'discipline' => $this->nullableString($row['discipline'] ?? null),
            'gender' => $this->nullableString($row['gender'] ?? null),
            'season_name' => $seasonName,
            'season_year' => $year,
            'host_city' => $this->nullableString($row['host_city'] ?? null),
            'host_country_code' => $this->nullableString($row['host_country_code'] ?? null),
            'start_date' => $this->nullableString($row['start_date'] ?? null),
            'end_date' => $this->nullableString($row['end_date'] ?? null),
            'temporal_status' => $this->temporalStatus($row['start_date'] ?? null, $row['end_date'] ?? null),
            'team_count' => (int) ($row['team_count'] ?? 0),
        ];
    }

    private function normalizeTeam(array $row): array
    {
        $normalized = [
            'id' => (int) ($row['id'] ?? 0),
            'name' => (string) ($row['name'] ?? ''),
            'short_name' => $this->nullableString($row['short_name'] ?? null),
            'alias' => $this->nullableString($row['alias'] ?? null),
            'logo' => $this->nullableString($row['logo'] ?? null),
            'country_code' => $this->nullableString($row['country_code'] ?? null),
            'city' => $this->nullableString($row['city'] ?? null),
            'federation_id' => (int) ($row['federation_id'] ?? 0),
            'federation_name' => $this->nullableString($row['federation_name'] ?? null),
            'federation_short_name' => $this->nullableString($row['federation_short_name'] ?? null),
        ];

        if (array_key_exists('participation_status', $row)) {
            $normalized['participation_status'] = $this->nullableString($row['participation_status']);
        }

        return $normalized;
    }

    private function normalizeMatch(array $row): array
    {
        $integerKeys = [
            'id', 'season_id', 'tournament_id', 'season_year', 'home_team_id',
            'away_team_id', 'venue_id', 'matchday', 'home_score', 'away_score',
            'home_score_extra', 'away_score_extra', 'home_penalties', 'away_penalties',
        ];

        foreach ($integerKeys as $key) {
            if (array_key_exists($key, $row)) {
                $row[$key] = $row[$key] === null ? null : (int) $row[$key];
            }
        }

        return $row;
    }

    private function temporalStatus(mixed $startDate, mixed $endDate): string
    {
        $start = $this->nullableString($startDate);
        $end = $this->nullableString($endDate);

        if ($start === null && $end === null) {
            return 'undated';
        }

        $today = (new DateTimeImmutable('today'))->format('Y-m-d');

        if ($start !== null && $start > $today) {
            return 'upcoming';
        }

        if ($end !== null && $end < $today) {
            return 'previous';
        }

        return 'current';
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function clampLimit(int $limit, int $default): int
    {
        if ($limit < 1) {
            return $default;
        }

        return min(self::MAX_LIMIT, $limit);
    }
}

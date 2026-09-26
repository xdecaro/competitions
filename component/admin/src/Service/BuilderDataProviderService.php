<?php
namespace xdecaro\Component\Competitions\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Read-only public data surface for presentation integrations such as YOOtheme.
 *
 * This service only reads Competitions-owned tables and only exposes published,
 * public-facing competition data. It intentionally contains no YOOtheme code.
 */
final class BuilderDataProviderService
{
    public function __construct(private DatabaseInterface $db)
    {
    }

    public function getCurrentCompetitions(int $limit = 20): array
    {
        $today = date('Y-m-d');
        $query = $this->baseSeasonQuery()
            ->where('(' . $this->db->quoteName('s.start_date') . ' IS NULL OR ' . $this->db->quoteName('s.start_date') . ' <= :todayStart)')
            ->where('(' . $this->db->quoteName('s.end_date') . ' IS NULL OR ' . $this->db->quoteName('s.end_date') . ' >= :todayEnd)')
            ->bind(':todayStart', $today)
            ->bind(':todayEnd', $today)
            ->order($this->db->quoteName('s.start_date') . ' ASC')
            ->order($this->db->quoteName('s.season_year') . ' ASC');

        return $this->load($query, $limit);
    }

    public function getUpcomingCompetitions(int $limit = 20): array
    {
        $today = date('Y-m-d');
        $query = $this->baseSeasonQuery()
            ->where($this->db->quoteName('s.start_date') . ' > :today')
            ->bind(':today', $today)
            ->order($this->db->quoteName('s.start_date') . ' ASC');

        return $this->load($query, $limit);
    }

    public function getPreviousCompetitions(int $limit = 20): array
    {
        $today = date('Y-m-d');
        $query = $this->baseSeasonQuery()
            ->where($this->db->quoteName('s.end_date') . ' < :today')
            ->bind(':today', $today)
            ->order($this->db->quoteName('s.end_date') . ' DESC');

        return $this->load($query, $limit);
    }

    public function getUpcomingMatches(int $limit = 20, int $seasonId = 0): array
    {
        $today = date('Y-m-d');
        $query = $this->baseMatchQuery()
            ->where($this->db->quoteName('m.match_date') . ' >= :today')
            ->where($this->db->quoteName('m.status') . ' IN (' . $this->db->quote('scheduled') . ', ' . $this->db->quote('live') . ', ' . $this->db->quote('postponed') . ')')
            ->bind(':today', $today)
            ->order($this->db->quoteName('m.match_date') . ' ASC')
            ->order($this->db->quoteName('m.kickoff_time') . ' ASC');

        if ($seasonId > 0) {
            $query->where($this->db->quoteName('m.season_id') . ' = :seasonId')
                ->bind(':seasonId', $seasonId, ParameterType::INTEGER);
        }

        return $this->load($query, $limit);
    }

    public function getLatestResults(int $limit = 20, int $seasonId = 0): array
    {
        $query = $this->baseMatchQuery()
            ->where($this->db->quoteName('m.status') . ' = ' . $this->db->quote('finished'))
            ->order($this->db->quoteName('m.match_date') . ' DESC')
            ->order($this->db->quoteName('m.kickoff_time') . ' DESC');

        if ($seasonId > 0) {
            $query->where($this->db->quoteName('m.season_id') . ' = :seasonId')
                ->bind(':seasonId', $seasonId, ParameterType::INTEGER);
        }

        return $this->load($query, $limit);
    }

    public function getParticipatingTeams(int $seasonId, int $limit = 100): array
    {
        if ($seasonId < 1) {
            return [];
        }

        $db = $this->db;
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('tm.id'),
                $db->quoteName('tm.name'),
                $db->quoteName('tm.short_name'),
                $db->quoteName('tm.alias'),
                $db->quoteName('tm.logo'),
                $db->quoteName('tm.country_code'),
                $db->quoteName('tm.city'),
                $db->quoteName('p.status', 'participation_status'),
                $db->quoteName('f.name', 'federation_name'),
                $db->quoteName('f.short_name', 'federation_short_name'),
            ])
            ->from($db->quoteName('#__xdecarocompetitions_participations', 'p'))
            ->innerJoin($db->quoteName('#__xdecarocompetitions_teams', 'tm') . ' ON ' . $db->quoteName('tm.id') . ' = ' . $db->quoteName('p.team_id'))
            ->leftJoin($db->quoteName('#__xdecarocompetitions_federations', 'f') . ' ON ' . $db->quoteName('f.id') . ' = ' . $db->quoteName('tm.federation_id'))
            ->where($db->quoteName('p.state') . ' = 1')
            ->where($db->quoteName('tm.state') . ' = 1')
            ->where($db->quoteName('tm.approval_status') . ' = ' . $db->quote('approved'))
            ->where($db->quoteName('p.season_id') . ' = :seasonId')
            ->bind(':seasonId', $seasonId, ParameterType::INTEGER)
            ->order($db->quoteName('tm.name') . ' ASC');

        return $this->load($query, $limit);
    }

    public function getCountries(int $limit = 250): array
    {
        $db = $this->db;
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('id'),
                $db->quoteName('name'),
                $db->quoteName('code'),
                $db->quoteName('iso2'),
                $db->quoteName('iso3'),
                $db->quoteName('entity_type'),
                $db->quoteName('flag'),
            ])
            ->from($db->quoteName('#__xdecarocompetitions_countries'))
            ->where($db->quoteName('state') . ' = 1')
            ->order($db->quoteName('ordering') . ' ASC')
            ->order($db->quoteName('name') . ' ASC');

        return $this->load($query, $limit);
    }

    public function getFederations(int $limit = 250): array
    {
        $db = $this->db;
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('f.id'),
                $db->quoteName('f.name'),
                $db->quoteName('f.short_name'),
                $db->quoteName('f.logo'),
                $db->quoteName('f.website'),
                $db->quoteName('c.name', 'country_name'),
                $db->quoteName('c.code', 'country_code'),
            ])
            ->from($db->quoteName('#__xdecarocompetitions_federations', 'f'))
            ->leftJoin($db->quoteName('#__xdecarocompetitions_countries', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('f.country_id'))
            ->where($db->quoteName('f.state') . ' = 1')
            ->order($db->quoteName('f.ordering') . ' ASC')
            ->order($db->quoteName('f.name') . ' ASC');

        return $this->load($query, $limit);
    }

    private function baseSeasonQuery()
    {
        $db = $this->db;

        return $db->getQuery(true)
            ->select([
                $db->quoteName('s.id'),
                $db->quoteName('s.name'),
                $db->quoteName('s.season_year'),
                $db->quoteName('s.host_city'),
                $db->quoteName('s.host_country_code'),
                $db->quoteName('s.start_date'),
                $db->quoteName('s.end_date'),
                $db->quoteName('t.id', 'tournament_id'),
                $db->quoteName('t.name', 'tournament_name'),
                $db->quoteName('t.code', 'tournament_code'),
                $db->quoteName('t.discipline'),
                $db->quoteName('t.gender'),
                $db->quoteName('t.scope_type'),
                $db->quoteName('t.participant_type'),
            ])
            ->from($db->quoteName('#__xdecarocompetitions_seasons', 's'))
            ->innerJoin($db->quoteName('#__xdecarocompetitions_tournaments', 't') . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('s.tournament_id'))
            ->where($db->quoteName('s.state') . ' = 1')
            ->where($db->quoteName('t.state') . ' = 1');
    }

    private function baseMatchQuery()
    {
        $db = $this->db;

        return $db->getQuery(true)
            ->select('m.*')
            ->select($db->quoteName('s.name', 'season_name'))
            ->select($db->quoteName('s.season_year', 'season_year'))
            ->select($db->quoteName('t.id', 'tournament_id'))
            ->select($db->quoteName('t.name', 'tournament_name'))
            ->select($db->quoteName('home.name', 'home_team_name'))
            ->select($db->quoteName('home.short_name', 'home_team_short_name'))
            ->select($db->quoteName('home.logo', 'home_team_logo'))
            ->select($db->quoteName('away.name', 'away_team_name'))
            ->select($db->quoteName('away.short_name', 'away_team_short_name'))
            ->select($db->quoteName('away.logo', 'away_team_logo'))
            ->select($db->quoteName('v.name', 'venue_name'))
            ->from($db->quoteName('#__xdecarocompetitions_matches', 'm'))
            ->innerJoin($db->quoteName('#__xdecarocompetitions_seasons', 's') . ' ON ' . $db->quoteName('s.id') . ' = ' . $db->quoteName('m.season_id'))
            ->innerJoin($db->quoteName('#__xdecarocompetitions_tournaments', 't') . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('s.tournament_id'))
            ->innerJoin($db->quoteName('#__xdecarocompetitions_teams', 'home') . ' ON ' . $db->quoteName('home.id') . ' = ' . $db->quoteName('m.home_team_id'))
            ->innerJoin($db->quoteName('#__xdecarocompetitions_teams', 'away') . ' ON ' . $db->quoteName('away.id') . ' = ' . $db->quoteName('m.away_team_id'))
            ->leftJoin($db->quoteName('#__xdecarocompetitions_venues', 'v') . ' ON ' . $db->quoteName('v.id') . ' = ' . $db->quoteName('m.venue_id'))
            ->where($db->quoteName('m.state') . ' = 1')
            ->where($db->quoteName('s.state') . ' = 1')
            ->where($db->quoteName('t.state') . ' = 1')
            ->where($db->quoteName('home.state') . ' = 1')
            ->where($db->quoteName('away.state') . ' = 1');
    }

    private function load($query, int $limit): array
    {
        $limit = max(1, min($limit, 500));
        return $this->db->setQuery($query, 0, $limit)->loadAssocList() ?: [];
    }
}

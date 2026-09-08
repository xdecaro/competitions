<?php
namespace Xdecaro\Component\Decarodcl\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

final class MatchesModel extends ListModel
{
    public function __construct($config = [])
    {
        $config['filter_fields'] ??= [
            'id', 'a.id',
            'match_date', 'a.match_date',
            'kickoff_time', 'a.kickoff_time',
            'status', 'a.status',
            'season_name', 's.name',
            'tournament_name', 't.name',
            'home_team_name', 'home.name',
            'away_team_name', 'away.name',
            'stage', 'a.stage',
            'state', 'a.state',
            'ordering', 'a.ordering',
        ];

        parent::__construct($config);
    }

    protected function getListQuery(): QueryInterface
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('a.*')
            ->select($db->quoteName('s.name', 'season_name'))
            ->select($db->quoteName('s.season_year', 'season_year'))
            ->select($db->quoteName('t.id', 'tournament_id'))
            ->select($db->quoteName('t.name', 'tournament_name'))
            ->select($db->quoteName('home.name', 'home_team_name'))
            ->select($db->quoteName('home.short_name', 'home_team_short_name'))
            ->select($db->quoteName('away.name', 'away_team_name'))
            ->select($db->quoteName('away.short_name', 'away_team_short_name'))
            ->select($db->quoteName('v.name', 'venue_name'))
            ->from($db->quoteName('#__decarocompetitions_matches', 'a'))
            ->innerJoin(
                $db->quoteName('#__decarocompetitions_seasons', 's')
                . ' ON ' . $db->quoteName('s.id') . ' = ' . $db->quoteName('a.season_id')
            )
            ->innerJoin(
                $db->quoteName('#__decarocompetitions_tournaments', 't')
                . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('s.tournament_id')
            )
            ->innerJoin(
                $db->quoteName('#__decarocompetitions_teams', 'home')
                . ' ON ' . $db->quoteName('home.id') . ' = ' . $db->quoteName('a.home_team_id')
            )
            ->innerJoin(
                $db->quoteName('#__decarocompetitions_teams', 'away')
                . ' ON ' . $db->quoteName('away.id') . ' = ' . $db->quoteName('a.away_team_id')
            )
            ->leftJoin(
                $db->quoteName('#__decarocompetitions_venues', 'v')
                . ' ON ' . $db->quoteName('v.id') . ' = ' . $db->quoteName('a.venue_id')
            );

        $search = trim((string) $this->getState('filter.search'));

        if ($search !== '') {
            $token = '%' . str_replace(' ', '%', $search) . '%';
            $query->where(
                '(' . $db->quoteName('home.name') . ' LIKE :search'
                . ' OR ' . $db->quoteName('away.name') . ' LIKE :search'
                . ' OR ' . $db->quoteName('t.name') . ' LIKE :search'
                . ' OR ' . $db->quoteName('s.name') . ' LIKE :search'
                . ' OR ' . $db->quoteName('a.stage') . ' LIKE :search'
                . ' OR ' . $db->quoteName('a.group_name') . ' LIKE :search'
                . ' OR ' . $db->quoteName('a.round_name') . ' LIKE :search'
                . ' OR ' . $db->quoteName('v.name') . ' LIKE :search)'
            )->bind(':search', $token);
        }

        $state = $this->getState('filter.state');

        if ($state !== '' && $state !== null) {
            $state = (int) $state;
            $query->where($db->quoteName('a.state') . ' = :state')
                ->bind(':state', $state, ParameterType::INTEGER);
        }

        $status = trim((string) $this->getState('filter.status'));

        if ($status !== '') {
            $query->where($db->quoteName('a.status') . ' = :status')
                ->bind(':status', $status);
        }

        $seasonId = (int) $this->getState('filter.season_id');

        if ($seasonId > 0) {
            $query->where($db->quoteName('a.season_id') . ' = :seasonId')
                ->bind(':seasonId', $seasonId, ParameterType::INTEGER);
        }

        $tournamentId = (int) $this->getState('filter.tournament_id');

        if ($tournamentId > 0) {
            $query->where($db->quoteName('s.tournament_id') . ' = :tournamentId')
                ->bind(':tournamentId', $tournamentId, ParameterType::INTEGER);
        }

        $teamId = (int) $this->getState('filter.team_id');

        if ($teamId > 0) {
            $query->where(
                '(' . $db->quoteName('a.home_team_id') . ' = :homeFilter'
                . ' OR ' . $db->quoteName('a.away_team_id') . ' = :awayFilter)'
            )
                ->bind(':homeFilter', $teamId, ParameterType::INTEGER)
                ->bind(':awayFilter', $teamId, ParameterType::INTEGER);
        }

        $allowed = [
            'a.id' => 'a.id',
            'a.match_date' => 'a.match_date',
            'a.kickoff_time' => 'a.kickoff_time',
            'a.status' => 'a.status',
            's.name' => 's.name',
            't.name' => 't.name',
            'home.name' => 'home.name',
            'away.name' => 'away.name',
            'a.stage' => 'a.stage',
            'a.state' => 'a.state',
            'a.ordering' => 'a.ordering',
        ];

        $requested = (string) $this->getState('list.ordering', 'a.match_date');
        $ordering = $allowed[$requested] ?? 'a.match_date';
        $direction = strtoupper((string) $this->getState('list.direction', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $query->order($db->quoteName($ordering) . ' ' . $direction)
            ->order($db->quoteName('a.kickoff_time') . ' ' . $direction)
            ->order($db->quoteName('a.id') . ' DESC');

        return $query;
    }

    public function getTournamentOptions(): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([$db->quoteName('id'), $db->quoteName('name')])
            ->from($db->quoteName('#__decarocompetitions_tournaments'))
            ->where($db->quoteName('state') . ' <> -2')
            ->order($db->quoteName('name') . ' ASC');

        return $db->setQuery($query)->loadObjectList() ?: [];
    }

    public function getSeasonOptions(): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('s.id'),
                $db->quoteName('s.name'),
                $db->quoteName('s.season_year'),
                $db->quoteName('t.name', 'tournament_name'),
            ])
            ->from($db->quoteName('#__decarocompetitions_seasons', 's'))
            ->innerJoin(
                $db->quoteName('#__decarocompetitions_tournaments', 't')
                . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('s.tournament_id')
            )
            ->where($db->quoteName('s.state') . ' <> -2')
            ->where($db->quoteName('t.state') . ' <> -2')
            ->order($db->quoteName('s.season_year') . ' DESC')
            ->order($db->quoteName('t.name') . ' ASC')
            ->order($db->quoteName('s.name') . ' ASC');

        return $db->setQuery($query)->loadObjectList() ?: [];
    }

    public function getTeamOptions(): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([$db->quoteName('id'), $db->quoteName('name'), $db->quoteName('short_name')])
            ->from($db->quoteName('#__decarocompetitions_teams'))
            ->where($db->quoteName('state') . ' <> -2')
            ->where($db->quoteName('approval_status') . ' = ' . $db->quote('approved'))
            ->order($db->quoteName('name') . ' ASC');

        return $db->setQuery($query)->loadObjectList() ?: [];
    }

    protected function populateState($ordering = 'a.match_date', $direction = 'DESC'): void
    {
        $this->setState(
            'filter.search',
            $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string')
        );
        $this->setState(
            'filter.state',
            $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '', 'string')
        );
        $this->setState(
            'filter.status',
            $this->getUserStateFromRequest($this->context . '.filter.status', 'filter_status', '', 'cmd')
        );
        $this->setState(
            'filter.season_id',
            $this->getUserStateFromRequest($this->context . '.filter.season_id', 'filter_season_id', 0, 'int')
        );
        $this->setState(
            'filter.tournament_id',
            $this->getUserStateFromRequest($this->context . '.filter.tournament_id', 'filter_tournament_id', 0, 'int')
        );
        $this->setState(
            'filter.team_id',
            $this->getUserStateFromRequest($this->context . '.filter.team_id', 'filter_team_id', 0, 'int')
        );

        parent::populateState($ordering, $direction);
    }
}

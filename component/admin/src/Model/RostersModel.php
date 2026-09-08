<?php
namespace Xdecaro\Component\Decarodcl\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

final class RostersModel extends ListModel
{
    public function __construct($config = [])
    {
        $config['filter_fields'] ??= [
            'id', 'a.id',
            'player_name', 'p.last_name',
            'team_name', 'tm.name',
            'season_name', 's.name',
            'tournament_name', 't.name',
            'shirt_number', 'a.shirt_number',
            'role', 'a.role',
            'status', 'a.status',
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
            ->select($db->quoteName('p.first_name', 'player_first_name'))
            ->select($db->quoteName('p.last_name', 'player_last_name'))
            ->select($db->quoteName('p.external_ref', 'player_external_ref'))
            ->select($db->quoteName('p.approval_status', 'player_approval_status'))
            ->select($db->quoteName('tm.name', 'team_name'))
            ->select($db->quoteName('pr.status', 'participation_status'))
            ->select($db->quoteName('s.id', 'season_id'))
            ->select($db->quoteName('s.name', 'season_name'))
            ->select($db->quoteName('s.season_year', 'season_year'))
            ->select($db->quoteName('t.name', 'tournament_name'))
            ->from($db->quoteName('#__decarocompetitions_rosters', 'a'))
            ->innerJoin(
                $db->quoteName('#__decarocompetitions_participations', 'pr')
                . ' ON ' . $db->quoteName('pr.id') . ' = ' . $db->quoteName('a.participation_id')
            )
            ->innerJoin(
                $db->quoteName('#__decarocompetitions_teams', 'tm')
                . ' ON ' . $db->quoteName('tm.id') . ' = ' . $db->quoteName('a.team_id')
            )
            ->innerJoin(
                $db->quoteName('#__decarocompetitions_players', 'p')
                . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('a.player_id')
            )
            ->innerJoin(
                $db->quoteName('#__decarocompetitions_seasons', 's')
                . ' ON ' . $db->quoteName('s.id') . ' = ' . $db->quoteName('pr.season_id')
            )
            ->innerJoin(
                $db->quoteName('#__decarocompetitions_tournaments', 't')
                . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('s.tournament_id')
            );

        $search = trim((string) $this->getState('filter.search'));

        if ($search !== '') {
            $token = '%' . str_replace(' ', '%', $search) . '%';
            $query->where(
                '(' . $db->quoteName('p.first_name') . ' LIKE :search'
                . ' OR ' . $db->quoteName('p.last_name') . ' LIKE :search'
                . ' OR ' . $db->quoteName('p.external_ref') . ' LIKE :search'
                . ' OR ' . $db->quoteName('tm.name') . ' LIKE :search'
                . ' OR ' . $db->quoteName('s.name') . ' LIKE :search'
                . ' OR ' . $db->quoteName('t.name') . ' LIKE :search'
                . ' OR ' . $db->quoteName('a.role') . ' LIKE :search)'
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

        $teamId = (int) $this->getState('filter.team_id');

        if ($teamId > 0) {
            $query->where($db->quoteName('a.team_id') . ' = :teamId')
                ->bind(':teamId', $teamId, ParameterType::INTEGER);
        }

        $seasonId = (int) $this->getState('filter.season_id');

        if ($seasonId > 0) {
            $query->where($db->quoteName('pr.season_id') . ' = :seasonId')
                ->bind(':seasonId', $seasonId, ParameterType::INTEGER);
        }

        $allowed = [
            'a.id' => 'a.id',
            'p.last_name' => 'p.last_name',
            'tm.name' => 'tm.name',
            's.name' => 's.name',
            't.name' => 't.name',
            'a.shirt_number' => 'a.shirt_number',
            'a.role' => 'a.role',
            'a.status' => 'a.status',
            'a.state' => 'a.state',
            'a.ordering' => 'a.ordering',
        ];

        $requested = (string) $this->getState('list.ordering', 's.season_year');
        $ordering = $allowed[$requested] ?? 's.season_year';
        $direction = strtoupper((string) $this->getState('list.direction', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $query->order($db->quoteName($ordering) . ' ' . $direction)
            ->order($db->quoteName('tm.name') . ' ASC')
            ->order($db->quoteName('p.last_name') . ' ASC')
            ->order($db->quoteName('p.first_name') . ' ASC');

        return $query;
    }

    public function getTeamOptions(): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([$db->quoteName('id'), $db->quoteName('name')])
            ->from($db->quoteName('#__decarocompetitions_teams'))
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

    protected function populateState($ordering = 's.season_year', $direction = 'DESC'): void
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
            'filter.team_id',
            $this->getUserStateFromRequest($this->context . '.filter.team_id', 'filter_team_id', 0, 'int')
        );
        $this->setState(
            'filter.season_id',
            $this->getUserStateFromRequest($this->context . '.filter.season_id', 'filter_season_id', 0, 'int')
        );

        parent::populateState($ordering, $direction);
    }
}

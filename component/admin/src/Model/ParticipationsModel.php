<?php
namespace Xdecaro\Component\Decarodcl\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

final class ParticipationsModel extends ListModel
{
    public function __construct($config = [])
    {
        $config['filter_fields'] ??= [
            'id', 'a.id',
            'team_name', 'tm.name',
            'season_name', 's.name',
            'season_year', 's.season_year',
            'tournament_name', 't.name',
            'status', 'a.status',
            'submitted_at', 'a.submitted_at',
            'reviewed_at', 'a.reviewed_at',
            'state', 'a.state',
        ];

        parent::__construct($config);
    }

    protected function getListQuery(): QueryInterface
    {
        $db = $this->getDatabase();

        $query = $db->getQuery(true)
            ->select('a.*')
            ->select($db->quoteName('tm.name', 'team_name'))
            ->select($db->quoteName('tm.short_name', 'team_short_name'))
            ->select($db->quoteName('tm.approval_status', 'team_approval_status'))
            ->select($db->quoteName('s.name', 'season_name'))
            ->select($db->quoteName('s.season_year', 'season_year'))
            ->select($db->quoteName('t.id', 'tournament_id'))
            ->select($db->quoteName('t.name', 'tournament_name'))
            ->select($db->quoteName('t.code', 'tournament_code'))
            ->select($db->quoteName('u.name', 'reviewer_name'))
            ->from($db->quoteName('#__dcl_participations', 'a'))
            ->leftJoin(
                $db->quoteName('#__dcl_teams', 'tm')
                . ' ON ' . $db->quoteName('tm.id') . ' = ' . $db->quoteName('a.team_id')
            )
            ->leftJoin(
                $db->quoteName('#__dcl_seasons', 's')
                . ' ON ' . $db->quoteName('s.id') . ' = ' . $db->quoteName('a.season_id')
            )
            ->leftJoin(
                $db->quoteName('#__dcl_tournaments', 't')
                . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('s.tournament_id')
            )
            ->leftJoin(
                $db->quoteName('#__users', 'u')
                . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('a.reviewed_by')
            );

        $search = trim((string) $this->getState('filter.search'));

        if ($search !== '') {
            $token = '%' . str_replace(' ', '%', $search) . '%';
            $query->where(
                '(' . $db->quoteName('tm.name') . ' LIKE :search'
                . ' OR ' . $db->quoteName('tm.short_name') . ' LIKE :search'
                . ' OR ' . $db->quoteName('s.name') . ' LIKE :search'
                . ' OR ' . $db->quoteName('t.name') . ' LIKE :search'
                . ' OR ' . $db->quoteName('t.code') . ' LIKE :search)'
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

        $tournamentId = (int) $this->getState('filter.tournament_id');

        if ($tournamentId > 0) {
            $query->where($db->quoteName('t.id') . ' = :tournamentId')
                ->bind(':tournamentId', $tournamentId, ParameterType::INTEGER);
        }

        $seasonId = (int) $this->getState('filter.season_id');

        if ($seasonId > 0) {
            $query->where($db->quoteName('a.season_id') . ' = :seasonId')
                ->bind(':seasonId', $seasonId, ParameterType::INTEGER);
        }

        $allowed = [
            'a.id' => 'a.id',
            'tm.name' => 'tm.name',
            's.name' => 's.name',
            's.season_year' => 's.season_year',
            't.name' => 't.name',
            'a.status' => 'a.status',
            'a.submitted_at' => 'a.submitted_at',
            'a.reviewed_at' => 'a.reviewed_at',
            'a.state' => 'a.state',
        ];

        $requested = (string) $this->getState('list.ordering', 'a.id');
        $ordering = $allowed[$requested] ?? 'a.id';
        $direction = strtoupper((string) $this->getState('list.direction', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $query->order($db->quoteName($ordering) . ' ' . $direction)
            ->order($db->quoteName('a.id') . ' DESC');

        return $query;
    }

    public function getTournamentOptions(): array
    {
        $db = $this->getDatabase();

        $query = $db->getQuery(true)
            ->select([$db->quoteName('id'), $db->quoteName('name'), $db->quoteName('code')])
            ->from($db->quoteName('#__dcl_tournaments'))
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
            ->from($db->quoteName('#__dcl_seasons', 's'))
            ->leftJoin(
                $db->quoteName('#__dcl_tournaments', 't')
                . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('s.tournament_id')
            )
            ->where($db->quoteName('s.state') . ' <> -2');

        $tournamentId = (int) $this->getState('filter.tournament_id');

        if ($tournamentId > 0) {
            $query->where($db->quoteName('s.tournament_id') . ' = :optionTournamentId')
                ->bind(':optionTournamentId', $tournamentId, ParameterType::INTEGER);
        }

        $query->order($db->quoteName('s.season_year') . ' DESC')
            ->order($db->quoteName('s.name') . ' ASC');

        return $db->setQuery($query)->loadObjectList() ?: [];
    }

    protected function populateState($ordering = 'a.id', $direction = 'DESC'): void
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
            'filter.tournament_id',
            $this->getUserStateFromRequest(
                $this->context . '.filter.tournament_id',
                'filter_tournament_id',
                0,
                'int'
            )
        );
        $this->setState(
            'filter.season_id',
            $this->getUserStateFromRequest($this->context . '.filter.season_id', 'filter_season_id', 0, 'int')
        );

        parent::populateState($ordering, $direction);
    }
}

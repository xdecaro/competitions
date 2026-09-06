<?php
namespace Xdecaro\Component\Decarodcl\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

final class SeasonsModel extends ListModel
{
    public function __construct($config = [])
    {
        $config['filter_fields'] ??= [
            'id', 'a.id',
            'name', 'a.name',
            'season_year', 'a.season_year',
            'tournament_name', 't.name',
            'host_city', 'a.host_city',
            'host_country_name', 'c.name',
            'start_date', 'a.start_date',
            'end_date', 'a.end_date',
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
            ->select($db->quoteName('t.name', 'tournament_name'))
            ->select($db->quoteName('t.code', 'tournament_code'))
            ->select($db->quoteName('c.name', 'host_country_name'))
            ->from($db->quoteName('#__dcl_seasons', 'a'))
            ->leftJoin(
                $db->quoteName('#__dcl_tournaments', 't')
                . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('a.tournament_id')
            )
            ->leftJoin(
                $db->quoteName('#__dcl_countries', 'c')
                . ' ON ' . $db->quoteName('c.code') . ' = ' . $db->quoteName('a.host_country_code')
            );

        $search = trim((string) $this->getState('filter.search'));

        if ($search !== '') {
            $token = '%' . str_replace(' ', '%', $search) . '%';
            $query->where(
                '(' . $db->quoteName('a.name') . ' LIKE :search'
                . ' OR ' . $db->quoteName('t.name') . ' LIKE :search'
                . ' OR ' . $db->quoteName('t.code') . ' LIKE :search'
                . ' OR ' . $db->quoteName('a.host_city') . ' LIKE :search'
                . ' OR ' . $db->quoteName('c.name') . ' LIKE :search)'
            )->bind(':search', $token);
        }

        $state = $this->getState('filter.state');

        if ($state !== '' && $state !== null) {
            $state = (int) $state;
            $query->where($db->quoteName('a.state') . ' = :state')
                ->bind(':state', $state, ParameterType::INTEGER);
        }

        $tournamentId = (int) $this->getState('filter.tournament_id');

        if ($tournamentId > 0) {
            $query->where($db->quoteName('a.tournament_id') . ' = :tournamentId')
                ->bind(':tournamentId', $tournamentId, ParameterType::INTEGER);
        }

        $year = (int) $this->getState('filter.season_year');

        if ($year > 0) {
            $query->where($db->quoteName('a.season_year') . ' = :seasonYear')
                ->bind(':seasonYear', $year, ParameterType::INTEGER);
        }

        $allowed = [
            'a.id' => 'a.id',
            'a.name' => 'a.name',
            'a.season_year' => 'a.season_year',
            't.name' => 't.name',
            'a.host_city' => 'a.host_city',
            'c.name' => 'c.name',
            'a.start_date' => 'a.start_date',
            'a.end_date' => 'a.end_date',
            'a.state' => 'a.state',
            'a.ordering' => 'a.ordering',
        ];

        $requested = (string) $this->getState('list.ordering', 'a.start_date');
        $ordering = $allowed[$requested] ?? 'a.start_date';
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
            ->order($db->quoteName('ordering') . ' ASC')
            ->order($db->quoteName('name') . ' ASC');

        return $db->setQuery($query)->loadObjectList() ?: [];
    }

    public function getYearOptions(): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('DISTINCT ' . $db->quoteName('season_year'))
            ->from($db->quoteName('#__dcl_seasons'))
            ->where($db->quoteName('state') . ' <> -2')
            ->where($db->quoteName('season_year') . ' IS NOT NULL')
            ->order($db->quoteName('season_year') . ' DESC');

        return array_map('intval', $db->setQuery($query)->loadColumn() ?: []);
    }

    protected function populateState($ordering = 'a.start_date', $direction = 'DESC'): void
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
            'filter.tournament_id',
            $this->getUserStateFromRequest($this->context . '.filter.tournament_id', 'filter_tournament_id', 0, 'int')
        );
        $this->setState(
            'filter.season_year',
            $this->getUserStateFromRequest($this->context . '.filter.season_year', 'filter_season_year', 0, 'int')
        );

        parent::populateState($ordering, $direction);
    }
}

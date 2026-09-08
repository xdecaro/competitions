<?php
namespace Xdecaro\Component\Competitions\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

final class TournamentsModel extends ListModel
{
    public function __construct($config = [])
    {
        $config['filter_fields'] ??= [
            'id', 'a.id',
            'name', 'a.name',
            'code', 'a.code',
            'discipline', 'a.discipline',
            'gender', 'a.gender',
            'scope_type', 'a.scope_type',
            'participant_type', 'a.participant_type',
            'state', 'a.state',
            'ordering', 'a.ordering',
            'seasons_count',
        ];

        parent::__construct($config);
    }

    protected function getListQuery(): QueryInterface
    {
        $db = $this->getDatabase();

        $countryNames = $db->getQuery(true)
            ->select("GROUP_CONCAT(" . $db->quoteName('c.name') . " ORDER BY " . $db->quoteName('tc.ordering') . " SEPARATOR ', ')")
            ->from($db->quoteName('#__xdecarocompetitions_tournament_countries', 'tc'))
            ->innerJoin(
                $db->quoteName('#__xdecarocompetitions_countries', 'c')
                . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('tc.country_id')
            )
            ->where($db->quoteName('tc.tournament_id') . ' = ' . $db->quoteName('a.id'));

        $zoneNames = $db->getQuery(true)
            ->select("GROUP_CONCAT(" . $db->quoteName('z.name') . " ORDER BY " . $db->quoteName('tz.ordering') . " SEPARATOR ', ')")
            ->from($db->quoteName('#__xdecarocompetitions_tournament_zones', 'tz'))
            ->innerJoin(
                $db->quoteName('#__xdecarocompetitions_zones', 'z')
                . ' ON ' . $db->quoteName('z.id') . ' = ' . $db->quoteName('tz.zone_id')
            )
            ->where($db->quoteName('tz.tournament_id') . ' = ' . $db->quoteName('a.id'));

        $query = $db->getQuery(true)
            ->select('a.*')
            ->select('COUNT(s.id) AS ' . $db->quoteName('seasons_count'))
            ->select('(' . $countryNames . ') AS ' . $db->quoteName('scope_countries'))
            ->select('(' . $zoneNames . ') AS ' . $db->quoteName('scope_zones'))
            ->from($db->quoteName('#__xdecarocompetitions_tournaments', 'a'))
            ->leftJoin(
                $db->quoteName('#__xdecarocompetitions_seasons', 's')
                . ' ON ' . $db->quoteName('s.tournament_id') . ' = ' . $db->quoteName('a.id')
                . ' AND ' . $db->quoteName('s.state') . ' <> -2'
            )
            ->group('a.id');

        $search = trim((string) $this->getState('filter.search'));

        if ($search !== '') {
            $token = '%' . str_replace(' ', '%', $search) . '%';
            $query->where(
                '(' . $db->quoteName('a.name') . ' LIKE :search'
                . ' OR ' . $db->quoteName('a.code') . ' LIKE :search'
                . ' OR ' . $db->quoteName('a.discipline') . ' LIKE :search'
                . ' OR ' . $db->quoteName('a.gender') . ' LIKE :search'
                . ' OR ' . $db->quoteName('a.scope_type') . ' LIKE :search'
                . ' OR ' . $db->quoteName('a.participant_type') . ' LIKE :search'
                . ' OR ' . $db->quoteName('a.local_area') . ' LIKE :search)'
            )->bind(':search', $token);
        }

        $state = $this->getState('filter.state');

        if ($state !== '' && $state !== null) {
            $state = (int) $state;
            $query->where($db->quoteName('a.state') . ' = :state')
                ->bind(':state', $state, ParameterType::INTEGER);
        }

        $discipline = trim((string) $this->getState('filter.discipline'));

        if ($discipline !== '') {
            $query->where($db->quoteName('a.discipline') . ' = :discipline')
                ->bind(':discipline', $discipline);
        }

        $gender = trim((string) $this->getState('filter.gender'));

        if ($gender !== '') {
            $query->where($db->quoteName('a.gender') . ' = :gender')
                ->bind(':gender', $gender);
        }

        $scopeType = trim((string) $this->getState('filter.scope_type'));

        if ($scopeType !== '') {
            $query->where($db->quoteName('a.scope_type') . ' = :scopeType')
                ->bind(':scopeType', $scopeType);
        }

        $participantType = trim((string) $this->getState('filter.participant_type'));

        if ($participantType !== '') {
            $query->where($db->quoteName('a.participant_type') . ' = :participantType')
                ->bind(':participantType', $participantType);
        }

        $allowed = [
            'a.id' => 'a.id',
            'a.name' => 'a.name',
            'a.code' => 'a.code',
            'a.discipline' => 'a.discipline',
            'a.gender' => 'a.gender',
            'a.scope_type' => 'a.scope_type',
            'a.participant_type' => 'a.participant_type',
            'a.state' => 'a.state',
            'a.ordering' => 'a.ordering',
            'seasons_count' => 'seasons_count',
        ];

        $requested = (string) $this->getState('list.ordering', 'a.ordering');
        $ordering = $allowed[$requested] ?? 'a.ordering';
        $direction = strtoupper((string) $this->getState('list.direction', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

        $query->order($db->quoteName($ordering) . ' ' . $direction)
            ->order($db->quoteName('a.name') . ' ASC');

        return $query;
    }

    public function getDisciplineOptions(): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('DISTINCT ' . $db->quoteName('discipline'))
            ->from($db->quoteName('#__xdecarocompetitions_tournaments'))
            ->where($db->quoteName('state') . ' <> -2')
            ->where($db->quoteName('discipline') . ' IS NOT NULL')
            ->where($db->quoteName('discipline') . " <> ''")
            ->order($db->quoteName('discipline') . ' ASC');

        return array_values(array_filter($db->setQuery($query)->loadColumn() ?: []));
    }

    protected function populateState($ordering = 'a.ordering', $direction = 'ASC'): void
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
            'filter.discipline',
            $this->getUserStateFromRequest($this->context . '.filter.discipline', 'filter_discipline', '', 'string')
        );
        $this->setState(
            'filter.gender',
            $this->getUserStateFromRequest($this->context . '.filter.gender', 'filter_gender', '', 'cmd')
        );
        $this->setState(
            'filter.scope_type',
            $this->getUserStateFromRequest($this->context . '.filter.scope_type', 'filter_scope_type', '', 'cmd')
        );
        $this->setState(
            'filter.participant_type',
            $this->getUserStateFromRequest(
                $this->context . '.filter.participant_type',
                'filter_participant_type',
                '',
                'cmd'
            )
        );

        parent::populateState($ordering, $direction);
    }
}

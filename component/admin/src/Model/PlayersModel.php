<?php
namespace xdecaro\Component\Competitions\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

final class PlayersModel extends ListModel
{
    public function __construct($config = [])
    {
        $config['filter_fields'] ??= [
            'id', 'a.id',
            'first_name', 'a.first_name',
            'last_name', 'a.last_name',
            'external_ref', 'a.external_ref',
            'birth_date', 'a.birth_date',
            'nationality_name', 'c.name',
            'nationality_code', 'a.nationality_code',
            'approval_status', 'a.approval_status',
            'state', 'a.state',
            'rosters_count',
        ];

        parent::__construct($config);
    }

    protected function getListQuery(): QueryInterface
    {
        $db = $this->getDatabase();

        $rostersSubquery = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__xdecarocompetitions_rosters', 'r'))
            ->where($db->quoteName('r.player_id') . ' = ' . $db->quoteName('a.id'))
            ->where($db->quoteName('r.state') . ' <> -2');

        $query = $db->getQuery(true)
            ->select('a.*')
            ->select($db->quoteName('c.name', 'nationality_name'))
            ->select($db->quoteName('c.code', 'resolved_nationality_code'))
            ->select('(' . $rostersSubquery . ') AS ' . $db->quoteName('rosters_count'))
            ->from($db->quoteName('#__xdecarocompetitions_players', 'a'))
            ->leftJoin(
                $db->quoteName('#__xdecarocompetitions_countries', 'c')
                . ' ON ' . $db->quoteName('c.code') . ' = ' . $db->quoteName('a.nationality_code')
            );

        $search = trim((string) $this->getState('filter.search'));

        if ($search !== '') {
            $token = '%' . str_replace(' ', '%', $search) . '%';
            $query->where(
                '(' . $db->quoteName('a.first_name') . ' LIKE :search'
                . ' OR ' . $db->quoteName('a.last_name') . ' LIKE :search'
                . ' OR ' . $db->quoteName('a.external_ref') . ' LIKE :search'
                . ' OR ' . $db->quoteName('c.name') . ' LIKE :search'
                . ' OR ' . $db->quoteName('c.code') . ' LIKE :search)'
            )->bind(':search', $token);
        }

        $state = $this->getState('filter.state');

        if ($state !== '' && $state !== null) {
            $state = (int) $state;
            $query->where($db->quoteName('a.state') . ' = :state')
                ->bind(':state', $state, ParameterType::INTEGER);
        }

        $approvalStatus = trim((string) $this->getState('filter.approval_status'));

        if ($approvalStatus !== '') {
            $query->where($db->quoteName('a.approval_status') . ' = :approvalStatus')
                ->bind(':approvalStatus', $approvalStatus);
        }

        $nationalityCode = strtoupper(trim((string) $this->getState('filter.nationality_code')));

        if ($nationalityCode !== '') {
            $query->where($db->quoteName('a.nationality_code') . ' = :nationalityCode')
                ->bind(':nationalityCode', $nationalityCode);
        }

        $allowed = [
            'a.id' => 'a.id',
            'a.first_name' => 'a.first_name',
            'a.last_name' => 'a.last_name',
            'a.external_ref' => 'a.external_ref',
            'a.birth_date' => 'a.birth_date',
            'c.name' => 'c.name',
            'a.nationality_code' => 'a.nationality_code',
            'a.approval_status' => 'a.approval_status',
            'a.state' => 'a.state',
            'rosters_count' => 'rosters_count',
        ];

        $requested = (string) $this->getState('list.ordering', 'a.last_name');
        $ordering = $allowed[$requested] ?? 'a.last_name';
        $direction = strtoupper((string) $this->getState('list.direction', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

        $query->order($db->quoteName($ordering) . ' ' . $direction)
            ->order($db->quoteName('a.first_name') . ' ASC');

        return $query;
    }

    public function getCountryOptions(): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([$db->quoteName('code'), $db->quoteName('name')])
            ->from($db->quoteName('#__xdecarocompetitions_countries'))
            ->where($db->quoteName('state') . ' <> -2')
            ->where('CHAR_LENGTH(' . $db->quoteName('code') . ') <= 3')
            ->order($db->quoteName('name') . ' ASC');

        return $db->setQuery($query)->loadObjectList() ?: [];
    }

    protected function populateState($ordering = 'a.last_name', $direction = 'ASC'): void
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
            'filter.approval_status',
            $this->getUserStateFromRequest(
                $this->context . '.filter.approval_status',
                'filter_approval_status',
                '',
                'cmd'
            )
        );
        $this->setState(
            'filter.nationality_code',
            $this->getUserStateFromRequest(
                $this->context . '.filter.nationality_code',
                'filter_nationality_code',
                '',
                'cmd'
            )
        );

        parent::populateState($ordering, $direction);
    }
}

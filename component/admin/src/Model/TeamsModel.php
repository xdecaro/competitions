<?php
namespace Xdecaro\Component\Decarodcl\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

final class TeamsModel extends ListModel
{
    public function __construct($config = [])
    {
        $config['filter_fields'] ??= [
            'id', 'a.id',
            'name', 'a.name',
            'short_name', 'a.short_name',
            'alias', 'a.alias',
            'team_type', 'a.team_type',
            'federation_name', 'f.name',
            'country_name', 'c.name',
            'manager_name', 'u.name',
            'approval_status', 'a.approval_status',
            'state', 'a.state',
            'ordering', 'a.ordering',
            'participations_count',
        ];

        parent::__construct($config);
    }

    protected function getListQuery(): QueryInterface
    {
        $db = $this->getDatabase();

        $participationsSubquery = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__dcl_participations', 'p'))
            ->where($db->quoteName('p.team_id') . ' = ' . $db->quoteName('a.id'))
            ->where($db->quoteName('p.state') . ' <> -2');

        $query = $db->getQuery(true)
            ->select('a.*')
            ->select($db->quoteName('f.name', 'federation_name'))
            ->select($db->quoteName('f.short_name', 'federation_short_name'))
            ->select($db->quoteName('c.id', 'country_id'))
            ->select($db->quoteName('c.name', 'country_name'))
            ->select($db->quoteName('c.code', 'resolved_country_code'))
            ->select($db->quoteName('u.name', 'manager_name'))
            ->select($db->quoteName('u.email', 'manager_email'))
            ->select('(' . $participationsSubquery . ') AS ' . $db->quoteName('participations_count'))
            ->from($db->quoteName('#__dcl_teams', 'a'))
            ->leftJoin(
                $db->quoteName('#__dcl_federations', 'f')
                . ' ON ' . $db->quoteName('f.id') . ' = ' . $db->quoteName('a.federation_id')
            )
            ->leftJoin(
                $db->quoteName('#__dcl_countries', 'c')
                . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('f.country_id')
            )
            ->leftJoin(
                $db->quoteName('#__users', 'u')
                . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('a.owner_user_id')
            );

        $search = trim((string) $this->getState('filter.search'));

        if ($search !== '') {
            $token = '%' . str_replace(' ', '%', $search) . '%';
            $query->where(
                '(' . $db->quoteName('a.name') . ' LIKE :search'
                . ' OR ' . $db->quoteName('a.short_name') . ' LIKE :search'
                . ' OR ' . $db->quoteName('a.alias') . ' LIKE :search'
                . ' OR ' . $db->quoteName('a.team_type') . ' LIKE :search'
                . ' OR ' . $db->quoteName('f.name') . ' LIKE :search'
                . ' OR ' . $db->quoteName('c.name') . ' LIKE :search'
                . ' OR ' . $db->quoteName('u.name') . ' LIKE :search'
                . ' OR ' . $db->quoteName('u.email') . ' LIKE :search)'
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

        $teamType = trim((string) $this->getState('filter.team_type'));

        if ($teamType !== '') {
            $query->where($db->quoteName('a.team_type') . ' = :teamType')
                ->bind(':teamType', $teamType);
        }

        $federationId = (int) $this->getState('filter.federation_id');

        if ($federationId > 0) {
            $query->where($db->quoteName('a.federation_id') . ' = :federationId')
                ->bind(':federationId', $federationId, ParameterType::INTEGER);
        }

        $countryId = (int) $this->getState('filter.country_id');

        if ($countryId > 0) {
            $query->where($db->quoteName('c.id') . ' = :countryId')
                ->bind(':countryId', $countryId, ParameterType::INTEGER);
        }

        $allowed = [
            'a.id' => 'a.id',
            'a.name' => 'a.name',
            'a.short_name' => 'a.short_name',
            'a.alias' => 'a.alias',
            'a.team_type' => 'a.team_type',
            'f.name' => 'f.name',
            'c.name' => 'c.name',
            'u.name' => 'u.name',
            'a.approval_status' => 'a.approval_status',
            'a.state' => 'a.state',
            'a.ordering' => 'a.ordering',
            'participations_count' => 'participations_count',
        ];

        $requested = (string) $this->getState('list.ordering', 'a.ordering');
        $ordering = $allowed[$requested] ?? 'a.ordering';
        $direction = strtoupper((string) $this->getState('list.direction', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

        $query->order($db->quoteName($ordering) . ' ' . $direction)
            ->order($db->quoteName('a.name') . ' ASC');

        return $query;
    }

    public function getFederationOptions(): array
    {
        $db = $this->getDatabase();

        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('f.id'),
                $db->quoteName('f.name'),
                $db->quoteName('f.short_name'),
                $db->quoteName('c.name', 'country_name'),
                $db->quoteName('c.code', 'country_code'),
            ])
            ->from($db->quoteName('#__dcl_federations', 'f'))
            ->leftJoin(
                $db->quoteName('#__dcl_countries', 'c')
                . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('f.country_id')
            )
            ->where($db->quoteName('f.state') . ' <> -2')
            ->order($db->quoteName('c.name') . ' ASC')
            ->order($db->quoteName('f.name') . ' ASC');

        return $db->setQuery($query)->loadObjectList() ?: [];
    }

    public function getCountryOptions(): array
    {
        $db = $this->getDatabase();

        $query = $db->getQuery(true)
            ->select([$db->quoteName('id'), $db->quoteName('name'), $db->quoteName('code')])
            ->from($db->quoteName('#__dcl_countries'))
            ->where($db->quoteName('state') . ' <> -2')
            ->order($db->quoteName('name') . ' ASC');

        return $db->setQuery($query)->loadObjectList() ?: [];
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
            'filter.approval_status',
            $this->getUserStateFromRequest(
                $this->context . '.filter.approval_status',
                'filter_approval_status',
                '',
                'cmd'
            )
        );
        $this->setState(
            'filter.team_type',
            $this->getUserStateFromRequest($this->context . '.filter.team_type', 'filter_team_type', '', 'cmd')
        );
        $this->setState(
            'filter.federation_id',
            $this->getUserStateFromRequest(
                $this->context . '.filter.federation_id',
                'filter_federation_id',
                0,
                'int'
            )
        );
        $this->setState(
            'filter.country_id',
            $this->getUserStateFromRequest($this->context . '.filter.country_id', 'filter_country_id', 0, 'int')
        );

        parent::populateState($ordering, $direction);
    }
}

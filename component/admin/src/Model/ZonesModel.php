<?php
namespace Xdecaro\Component\Decarodcl\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

final class ZonesModel extends ListModel
{
    public function __construct($config = [])
    {
        $config['filter_fields'] ??= [
            'id', 'a.id',
            'name', 'a.name',
            'code', 'a.code',
            'organization_name', 'o.name',
            'countries_count',
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
            ->select($db->quoteName('o.name', 'organization_name'))
            ->select($db->quoteName('o.short_name', 'organization_short_name'))
            ->select('COUNT(DISTINCT zc.country_id) AS ' . $db->quoteName('countries_count'))
            ->select("GROUP_CONCAT(DISTINCT c.name ORDER BY c.name ASC SEPARATOR ' • ') AS " . $db->quoteName('country_names'))
            ->from($db->quoteName('#__dcl_zones', 'a'))
            ->leftJoin($db->quoteName('#__dcl_organizations', 'o') . ' ON ' . $db->quoteName('o.id') . ' = ' . $db->quoteName('a.organization_id'))
            ->leftJoin($db->quoteName('#__dcl_zone_countries', 'zc') . ' ON ' . $db->quoteName('zc.zone_id') . ' = ' . $db->quoteName('a.id'))
            ->leftJoin($db->quoteName('#__dcl_countries', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('zc.country_id') . ' AND ' . $db->quoteName('c.state') . ' <> -2')
            ->group($db->quoteName('a.id'));

        $search = trim((string) $this->getState('filter.search'));

        if ($search !== '') {
            $token = '%' . str_replace(' ', '%', $search) . '%';
            $query->where(
                '(' . $db->quoteName('a.name') . ' LIKE :search'
                . ' OR ' . $db->quoteName('a.code') . ' LIKE :search'
                . ' OR ' . $db->quoteName('o.name') . ' LIKE :search'
                . ' OR ' . $db->quoteName('o.short_name') . ' LIKE :search'
                . ' OR ' . $db->quoteName('c.name') . ' LIKE :search)'
            )->bind(':search', $token);
        }

        $state = $this->getState('filter.state');

        if ($state !== '' && $state !== null) {
            $state = (int) $state;
            $query->where($db->quoteName('a.state') . ' = :state')
                ->bind(':state', $state, ParameterType::INTEGER);
        }

        $organizationId = (int) $this->getState('filter.organization_id');

        if ($organizationId > 0) {
            $query->where($db->quoteName('a.organization_id') . ' = :organizationId')
                ->bind(':organizationId', $organizationId, ParameterType::INTEGER);
        }

        $allowed = [
            'a.id' => 'a.id',
            'a.name' => 'a.name',
            'a.code' => 'a.code',
            'o.name' => 'o.name',
            'countries_count' => 'countries_count',
            'a.state' => 'a.state',
            'a.ordering' => 'a.ordering',
        ];
        $requested = (string) $this->getState('list.ordering', 'a.ordering');
        $ordering = $allowed[$requested] ?? 'a.ordering';
        $direction = strtoupper((string) $this->getState('list.direction', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

        $query->order($db->quoteName($ordering) . ' ' . $direction)
            ->order($db->quoteName('a.name') . ' ASC');

        return $query;
    }

    public function getOrganizationOptions(): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('id'),
                $db->quoteName('name'),
                $db->quoteName('short_name'),
            ])
            ->from($db->quoteName('#__dcl_organizations'))
            ->where($db->quoteName('state') . ' <> -2')
            ->order($db->quoteName('ordering') . ' ASC')
            ->order($db->quoteName('name') . ' ASC');

        return $db->setQuery($query)->loadObjectList() ?: [];
    }

    protected function populateState($ordering = 'a.ordering', $direction = 'ASC'): void
    {
        $this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string'));
        $this->setState('filter.state', $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '', 'string'));
        $this->setState('filter.organization_id', $this->getUserStateFromRequest($this->context . '.filter.organization_id', 'filter_organization_id', 0, 'int'));

        parent::populateState($ordering, $direction);
    }
}

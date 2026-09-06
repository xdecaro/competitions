<?php
namespace Xdecaro\Component\Decarodcl\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

final class CountriesModel extends ListModel
{
    public function __construct($config = [])
    {
        $config['filter_fields'] ??= [
            'id', 'a.id',
            'name', 'a.name',
            'code', 'a.code',
            'entity_type', 'a.entity_type',
            'state', 'a.state',
            'ordering', 'a.ordering',
            'federations_count',
            'zones_count',
        ];
        parent::__construct($config);
    }

    protected function getListQuery(): QueryInterface
    {
        $db = $this->getDatabase();

        $query = $db->getQuery(true)
            ->select('a.*')
            ->select('COUNT(DISTINCT f.id) AS ' . $db->quoteName('federations_count'))
            ->select('COUNT(DISTINCT z.id) AS ' . $db->quoteName('zones_count'))
            ->select("GROUP_CONCAT(DISTINCT z.name ORDER BY z.name ASC SEPARATOR ' • ') AS " . $db->quoteName('zone_names'))
            ->from($db->quoteName('#__dcl_countries', 'a'))
            ->leftJoin(
                $db->quoteName('#__dcl_federations', 'f')
                . ' ON ' . $db->quoteName('f.country_id') . ' = ' . $db->quoteName('a.id')
                . ' AND ' . $db->quoteName('f.state') . ' <> -2'
            )
            ->leftJoin(
                $db->quoteName('#__dcl_zone_countries', 'zc')
                . ' ON ' . $db->quoteName('zc.country_id') . ' = ' . $db->quoteName('a.id')
            )
            ->leftJoin(
                $db->quoteName('#__dcl_zones', 'z')
                . ' ON ' . $db->quoteName('z.id') . ' = ' . $db->quoteName('zc.zone_id')
                . ' AND ' . $db->quoteName('z.state') . ' <> -2'
            )
            ->group($db->quoteName('a.id'));

        $search = trim((string) $this->getState('filter.search'));
        if ($search !== '') {
            $token = '%' . str_replace(' ', '%', $search) . '%';
            $query->where(
                '(' . $db->quoteName('a.name') . ' LIKE :search'
                . ' OR ' . $db->quoteName('a.code') . ' LIKE :search'
                . ' OR ' . $db->quoteName('a.iso2') . ' LIKE :search'
                . ' OR ' . $db->quoteName('a.iso3') . ' LIKE :search'
                . ' OR ' . $db->quoteName('z.name') . ' LIKE :search)'
            )->bind(':search', $token);
        }

        $state = $this->getState('filter.state');
        if ($state !== '' && $state !== null) {
            $state = (int) $state;
            $query->where($db->quoteName('a.state') . ' = :state')
                ->bind(':state', $state, ParameterType::INTEGER);
        }

        $type = trim((string) $this->getState('filter.entity_type'));
        if ($type !== '') {
            $query->where($db->quoteName('a.entity_type') . ' = :entityType')
                ->bind(':entityType', $type);
        }

        $zoneId = (int) $this->getState('filter.zone_id');
        if ($zoneId > 0) {
            $query->where(
                'EXISTS (SELECT 1 FROM ' . $db->quoteName('#__dcl_zone_countries', 'zcf')
                . ' WHERE ' . $db->quoteName('zcf.country_id') . ' = ' . $db->quoteName('a.id')
                . ' AND ' . $db->quoteName('zcf.zone_id') . ' = :zoneId)'
            )->bind(':zoneId', $zoneId, ParameterType::INTEGER);
        }

        $allowed = [
            'a.id' => 'a.id',
            'a.name' => 'a.name',
            'a.code' => 'a.code',
            'a.entity_type' => 'a.entity_type',
            'a.state' => 'a.state',
            'a.ordering' => 'a.ordering',
            'federations_count' => 'federations_count',
            'zones_count' => 'zones_count',
        ];
        $requested = (string) $this->getState('list.ordering', 'a.ordering');
        $ordering = $allowed[$requested] ?? 'a.ordering';
        $direction = strtoupper((string) $this->getState('list.direction', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

        $query->order($db->quoteName($ordering) . ' ' . $direction)
            ->order($db->quoteName('a.name') . ' ASC');

        return $query;
    }

    public function getZoneOptions(): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('z.id'),
                $db->quoteName('z.name'),
                $db->quoteName('z.code'),
                $db->quoteName('o.name', 'organization_name'),
            ])
            ->from($db->quoteName('#__dcl_zones', 'z'))
            ->leftJoin($db->quoteName('#__dcl_organizations', 'o') . ' ON ' . $db->quoteName('o.id') . ' = ' . $db->quoteName('z.organization_id'))
            ->where($db->quoteName('z.state') . ' <> -2')
            ->order($db->quoteName('z.ordering') . ' ASC')
            ->order($db->quoteName('z.name') . ' ASC');

        return $db->setQuery($query)->loadObjectList() ?: [];
    }

    protected function populateState($ordering = 'a.ordering', $direction = 'ASC'): void
    {
        $this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string'));
        $this->setState('filter.state', $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '', 'string'));
        $this->setState('filter.entity_type', $this->getUserStateFromRequest($this->context . '.filter.entity_type', 'filter_entity_type', '', 'cmd'));
        $this->setState('filter.zone_id', $this->getUserStateFromRequest($this->context . '.filter.zone_id', 'filter_zone_id', 0, 'int'));

        parent::populateState($ordering, $direction);
    }
}

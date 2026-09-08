<?php
namespace Xdecaro\Component\Competitions\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

final class OrganizationsModel extends ListModel
{
    public function __construct($config = [])
    {
        $config['filter_fields'] ??= ['id','a.id','name','a.name','short_name','a.short_name','country_name','c.name','state','a.state','ordering','a.ordering'];
        parent::__construct($config);
    }

    protected function getListQuery(): QueryInterface
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('a.*')
            ->select($db->quoteName('c.name', 'country_name'))
            ->select($db->quoteName('c.code', 'country_code'))
            ->from($db->quoteName('#__xdecarocompetitions_organizations', 'a'))
            ->leftJoin($db->quoteName('#__xdecarocompetitions_countries', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('a.country_id'));

        $search = trim((string) $this->getState('filter.search'));
        if ($search !== '') {
            $token = '%' . str_replace(' ', '%', $search) . '%';
            $query->where('(' . $db->quoteName('a.name') . ' LIKE :search OR ' . $db->quoteName('a.short_name') . ' LIKE :search OR ' . $db->quoteName('c.name') . ' LIKE :search OR ' . $db->quoteName('c.code') . ' LIKE :search)')->bind(':search', $token);
        }

        $state = $this->getState('filter.state');
        if ($state !== '' && $state !== null) {
            $state = (int) $state;
            $query->where($db->quoteName('a.state') . ' = :state')->bind(':state', $state, ParameterType::INTEGER);
        }

        $countryId = (int) $this->getState('filter.country_id');
        if ($countryId > 0) {
            $query->where($db->quoteName('a.country_id') . ' = :countryId')->bind(':countryId', $countryId, ParameterType::INTEGER);
        }

        $allowed = ['a.id'=>'a.id','a.name'=>'a.name','a.short_name'=>'a.short_name','c.name'=>'c.name','a.state'=>'a.state','a.ordering'=>'a.ordering'];
        $requested = (string) $this->getState('list.ordering', 'a.ordering');
        $ordering = $allowed[$requested] ?? 'a.ordering';
        $direction = strtoupper((string) $this->getState('list.direction', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
        $query->order($db->quoteName($ordering) . ' ' . $direction)->order($db->quoteName('a.name') . ' ASC');

        return $query;
    }

    public function getCountryOptions(): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)->select([$db->quoteName('id'),$db->quoteName('name'),$db->quoteName('code')])->from($db->quoteName('#__xdecarocompetitions_countries'))->where($db->quoteName('state') . ' <> -2')->order($db->quoteName('name') . ' ASC');
        return $db->setQuery($query)->loadObjectList() ?: [];
    }

    protected function populateState($ordering = 'a.ordering', $direction = 'ASC'): void
    {
        $this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string'));
        $this->setState('filter.state', $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '', 'string'));
        $this->setState('filter.country_id', $this->getUserStateFromRequest($this->context . '.filter.country_id', 'filter_country_id', 0, 'int'));
        parent::populateState($ordering, $direction);
    }
}

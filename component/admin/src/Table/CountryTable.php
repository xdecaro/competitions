<?php
namespace Xdecaro\Component\Decarodcl\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\ParameterType;

final class CountryTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__decarocompetitions_countries', 'id', $db);
    }

    public function check(): bool
    {
        $this->name = trim((string) $this->name);
        $this->code = strtoupper(trim((string) $this->code));
        $this->iso2 = strtoupper(trim((string) $this->iso2)) ?: null;
        $this->iso3 = strtoupper(trim((string) $this->iso3)) ?: null;
        $this->entity_type = in_array((string) $this->entity_type, ['country', 'sport_territory'], true)
            ? (string) $this->entity_type
            : 'country';

        if ($this->name === '') {
            $this->setError(Text::_('COM_DECARODCL_ERROR_COUNTRY_NAME_REQUIRED'));
            return false;
        }

        if (!preg_match('/^[A-Z0-9_-]{2,10}$/', $this->code)) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_COUNTRY_CODE_INVALID'));
            return false;
        }

        if ($this->iso2 !== null && !preg_match('/^[A-Z]{2}$/', $this->iso2)) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_ISO2_INVALID'));
            return false;
        }

        if ($this->iso3 !== null && !preg_match('/^[A-Z]{3}$/', $this->iso3)) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_ISO3_INVALID'));
            return false;
        }

        $db = $this->getDbo();
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__decarocompetitions_countries'))
            ->where($db->quoteName('code') . ' = :code')
            ->where($db->quoteName('id') . ' <> :id')
            ->bind(':code', $this->code)
            ->bind(':id', $this->id, ParameterType::INTEGER);

        if ((int) $db->setQuery($query)->loadResult() > 0) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_COUNTRY_CODE_DUPLICATE'));
            return false;
        }

        return parent::check();
    }

    public function store($updateNulls = true): bool
    {
        $now = Factory::getDate()->toSql();
        $userId = (int) Factory::getApplication()->getIdentity()->id;

        if (!$this->id) {
            $this->created = $this->created ?: $now;
            $this->created_by = $this->created_by ?: $userId;
        }

        $this->modified = $now;
        $this->modified_by = $userId;

        return parent::store($updateNulls);
    }
}

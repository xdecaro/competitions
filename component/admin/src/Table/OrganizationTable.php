<?php
namespace xdecaro\Component\Competitions\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\ParameterType;
use xdecaro\Component\Competitions\Administrator\Helper\LanguageHelper;

final class OrganizationTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__xdecarocompetitions_organizations', 'id', $db);
    }

    public function check(): bool
    {
        LanguageHelper::load();
        $this->name = trim((string) $this->name);
        $this->short_name = trim((string) $this->short_name) ?: null;
        $this->country_id = (int) $this->country_id;
        $this->logo = trim((string) $this->logo) ?: null;
        $this->website = trim((string) $this->website) ?: null;
        $this->email = trim((string) $this->email) ?: null;

        if ($this->name === '') {
            $this->setError(Text::_('COM_XDECAROCOMPETITIONS_ERROR_ORGANIZATION_NAME_REQUIRED'));
            return false;
        }
        if ($this->email !== null && filter_var($this->email, FILTER_VALIDATE_EMAIL) === false) {
            $this->setError(Text::_('COM_XDECAROCOMPETITIONS_ERROR_EMAIL_INVALID'));
            return false;
        }
        if ($this->website !== null && filter_var($this->website, FILTER_VALIDATE_URL) === false) {
            $this->setError(Text::_('COM_XDECAROCOMPETITIONS_ERROR_URL_INVALID'));
            return false;
        }
        if ($this->country_id > 0) {
            $db = $this->getDbo();
            $query = $db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__xdecarocompetitions_countries'))->where($db->quoteName('id') . ' = :countryId')->where($db->quoteName('state') . ' <> -2')->bind(':countryId', $this->country_id, ParameterType::INTEGER);
            if ((int) $db->setQuery($query)->loadResult() === 0) {
                $this->setError(Text::_('COM_XDECAROCOMPETITIONS_ERROR_ORGANIZATION_COUNTRY_INVALID'));
                return false;
            }
        }
        $this->enforceStatePermission();
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

    private function enforceStatePermission(): void
    {
        $identity = Factory::getApplication()->getIdentity();
        if ($identity->authorise('core.edit.state', 'com_xdecarocompetitions')) {
            return;
        }
        if (!$this->id) {
            $this->state = 0;
            return;
        }
        $db = $this->getDbo();
        $query = $db->getQuery(true)->select($db->quoteName('state'))->from($db->quoteName('#__xdecarocompetitions_organizations'))->where($db->quoteName('id') . ' = :id')->bind(':id', $this->id, ParameterType::INTEGER);
        $currentState = $db->setQuery($query)->loadResult();
        if ($currentState !== null) {
            $this->state = (int) $currentState;
        }
    }
}

<?php
namespace Xdecaro\Component\Competitions\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\ParameterType;
use Xdecaro\Component\Competitions\Administrator\Helper\LanguageHelper;

final class ZoneTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__xdecarocompetitions_zones', 'id', $db);
    }

    public function check(): bool
    {
        LanguageHelper::load();

        $this->name = trim((string) $this->name);
        $this->code = strtoupper(trim((string) $this->code));
        $this->code = preg_replace('/\s+/', '_', $this->code) ?: '';
        $this->organization_id = (int) $this->organization_id;

        if ($this->name === '') {
            $this->setError(Text::_('COM_XDECAROCOMPETITIONS_ERROR_ZONE_NAME_REQUIRED'));
            return false;
        }

        if (!preg_match('/^[A-Z0-9][A-Z0-9_-]{1,49}$/', $this->code)) {
            $this->setError(Text::_('COM_XDECAROCOMPETITIONS_ERROR_ZONE_CODE_INVALID'));
            return false;
        }

        $db = $this->getDbo();

        if ($this->organization_id > 0) {
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__xdecarocompetitions_organizations'))
                ->where($db->quoteName('id') . ' = :organizationId')
                ->where($db->quoteName('state') . ' <> -2')
                ->bind(':organizationId', $this->organization_id, ParameterType::INTEGER);

            if ((int) $db->setQuery($query)->loadResult() === 0) {
                $this->setError(Text::_('COM_XDECAROCOMPETITIONS_ERROR_ZONE_ORGANIZATION_INVALID'));
                return false;
            }
        }

        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__xdecarocompetitions_zones'))
            ->where($db->quoteName('organization_id') . ' = :organizationId')
            ->where($db->quoteName('code') . ' = :code')
            ->bind(':organizationId', $this->organization_id, ParameterType::INTEGER)
            ->bind(':code', $this->code);

        if ((int) $this->id > 0) {
            $query->where($db->quoteName('id') . ' <> :id')
                ->bind(':id', $this->id, ParameterType::INTEGER);
        }

        if ((int) $db->setQuery($query)->loadResult() > 0) {
            $this->setError(Text::_('COM_XDECAROCOMPETITIONS_ERROR_ZONE_CODE_DUPLICATE'));
            return false;
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
        $query = $db->getQuery(true)
            ->select($db->quoteName('state'))
            ->from($db->quoteName('#__xdecarocompetitions_zones'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $this->id, ParameterType::INTEGER);
        $currentState = $db->setQuery($query)->loadResult();

        if ($currentState !== null) {
            $this->state = (int) $currentState;
        }
    }
}

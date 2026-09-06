<?php
namespace Xdecaro\Component\Decarodcl\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\ParameterType;

final class PlayerTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__dcl_players', 'id', $db);
    }

    public function check(): bool
    {
        $this->first_name = trim((string) $this->first_name);
        $this->last_name = trim((string) $this->last_name);
        $this->external_ref = trim((string) $this->external_ref) ?: null;
        $this->birth_date = trim((string) $this->birth_date) ?: null;
        $this->nationality_code = strtoupper(trim((string) $this->nationality_code)) ?: null;
        $this->approval_status = trim((string) $this->approval_status) ?: 'pending';

        if ($this->first_name === '') {
            $this->setError(Text::_('COM_DECARODCL_ERROR_PLAYER_FIRST_NAME_REQUIRED'));
            return false;
        }

        if ($this->last_name === '') {
            $this->setError(Text::_('COM_DECARODCL_ERROR_PLAYER_LAST_NAME_REQUIRED'));
            return false;
        }

        if (!in_array($this->approval_status, ['pending', 'approved', 'rejected'], true)) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_PLAYER_APPROVAL_INVALID'));
            return false;
        }

        if ($this->birth_date !== null) {
            $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $this->birth_date);

            if ($date === false || $date->format('Y-m-d') !== $this->birth_date) {
                $this->setError(Text::_('COM_DECARODCL_ERROR_PLAYER_BIRTH_DATE_INVALID'));
                return false;
            }

            if ($this->birth_date > Factory::getDate()->format('Y-m-d')) {
                $this->setError(Text::_('COM_DECARODCL_ERROR_PLAYER_BIRTH_DATE_FUTURE'));
                return false;
            }
        }

        $db = $this->getDbo();

        if ($this->nationality_code !== null) {
            if (strlen($this->nationality_code) > 3) {
                $this->setError(Text::_('COM_DECARODCL_ERROR_PLAYER_NATIONALITY_INVALID'));
                return false;
            }

            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__dcl_countries'))
                ->where($db->quoteName('code') . ' = :nationalityCode')
                ->where($db->quoteName('state') . ' <> -2')
                ->bind(':nationalityCode', $this->nationality_code);

            if ((int) $db->setQuery($query)->loadResult() === 0) {
                $this->setError(Text::_('COM_DECARODCL_ERROR_PLAYER_NATIONALITY_INVALID'));
                return false;
            }
        }

        if ($this->external_ref !== null) {
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__dcl_players'))
                ->where($db->quoteName('external_ref') . ' = :externalRef')
                ->where($db->quoteName('id') . ' <> :id')
                ->bind(':externalRef', $this->external_ref)
                ->bind(':id', $this->id, ParameterType::INTEGER);

            if ((int) $db->setQuery($query)->loadResult() > 0) {
                $this->setError(Text::_('COM_DECARODCL_ERROR_PLAYER_EXTERNAL_REF_DUPLICATE'));
                return false;
            }
        }

        if (!$this->canChangeApprovalStatus()) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_APPROVAL_PERMISSION'));
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

    private function canChangeApprovalStatus(): bool
    {
        $identity = Factory::getApplication()->getIdentity();

        if ($identity->authorise('core.edit.state', 'com_decarodcl')) {
            return true;
        }

        if (!$this->id) {
            return $this->approval_status === 'pending';
        }

        $db = $this->getDbo();
        $query = $db->getQuery(true)
            ->select($db->quoteName('approval_status'))
            ->from($db->quoteName('#__dcl_players'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $this->id, ParameterType::INTEGER);

        return (string) $db->setQuery($query)->loadResult() === $this->approval_status;
    }

    private function enforceStatePermission(): void
    {
        $identity = Factory::getApplication()->getIdentity();

        if ($identity->authorise('core.edit.state', 'com_decarodcl')) {
            return;
        }

        if (!$this->id) {
            $this->state = 0;
            return;
        }

        $db = $this->getDbo();
        $query = $db->getQuery(true)
            ->select($db->quoteName('state'))
            ->from($db->quoteName('#__dcl_players'))
            ->where($db->quoteName('id') . ' = :stateId')
            ->bind(':stateId', $this->id, ParameterType::INTEGER);

        $currentState = $db->setQuery($query)->loadResult();

        if ($currentState !== null) {
            $this->state = (int) $currentState;
        }
    }
}

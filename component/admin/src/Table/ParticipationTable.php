<?php
namespace Xdecaro\Component\Decarodcl\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\ParameterType;

final class ParticipationTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__dcl_participations', 'id', $db);
    }

    public function check(): bool
    {
        $this->team_id = (int) $this->team_id;
        $this->season_id = (int) $this->season_id;
        $this->status = trim((string) $this->status) ?: 'draft';
        $this->review_note = trim((string) $this->review_note) ?: null;

        if ($this->team_id <= 0) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_PARTICIPATION_TEAM_REQUIRED'));
            return false;
        }

        if ($this->season_id <= 0) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_PARTICIPATION_SEASON_REQUIRED'));
            return false;
        }

        if (!in_array($this->status, ['draft', 'submitted', 'approved', 'rejected'], true)) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_PARTICIPATION_STATUS_INVALID'));
            return false;
        }

        if (!$this->canChangeStatus()) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_APPROVAL_PERMISSION'));
            return false;
        }

        $this->enforceStatePermission();

        $db = $this->getDbo();

        $query = $db->getQuery(true)
            ->select([$db->quoteName('approval_status'), $db->quoteName('state')])
            ->from($db->quoteName('#__dcl_teams'))
            ->where($db->quoteName('id') . ' = :teamId')
            ->where($db->quoteName('state') . ' <> -2')
            ->bind(':teamId', $this->team_id, ParameterType::INTEGER);

        $team = $db->setQuery($query)->loadObject();

        if (!$team) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_PARTICIPATION_TEAM_INVALID'));
            return false;
        }

        if ($this->status === 'approved' && (string) $team->approval_status !== 'approved') {
            $this->setError(Text::_('COM_DECARODCL_ERROR_PARTICIPATION_TEAM_NOT_APPROVED'));
            return false;
        }

        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__dcl_seasons'))
            ->where($db->quoteName('id') . ' = :seasonId')
            ->where($db->quoteName('state') . ' <> -2')
            ->bind(':seasonId', $this->season_id, ParameterType::INTEGER);

        if ((int) $db->setQuery($query)->loadResult() === 0) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_PARTICIPATION_SEASON_INVALID'));
            return false;
        }

        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__dcl_participations'))
            ->where($db->quoteName('team_id') . ' = :teamId')
            ->where($db->quoteName('season_id') . ' = :seasonId')
            ->where($db->quoteName('id') . ' <> :id')
            ->bind(':teamId', $this->team_id, ParameterType::INTEGER)
            ->bind(':seasonId', $this->season_id, ParameterType::INTEGER)
            ->bind(':id', $this->id, ParameterType::INTEGER);

        if ((int) $db->setQuery($query)->loadResult() > 0) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_PARTICIPATION_DUPLICATE'));
            return false;
        }

        return parent::check();
    }

    public function store($updateNulls = true): bool
    {
        $now = Factory::getDate()->toSql();
        $userId = (int) Factory::getApplication()->getIdentity()->id;
        $previous = null;

        if ($this->id) {
            $db = $this->getDbo();
            $query = $db->getQuery(true)
                ->select([
                    $db->quoteName('status'),
                    $db->quoteName('submitted_at'),
                    $db->quoteName('reviewed_at'),
                    $db->quoteName('reviewed_by'),
                ])
                ->from($db->quoteName('#__dcl_participations'))
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':id', $this->id, ParameterType::INTEGER);
            $previous = $db->setQuery($query)->loadObject();
        }

        if (!$this->id) {
            $this->created = $this->created ?: $now;
            $this->created_by = $this->created_by ?: $userId;
        }

        if (
            in_array($this->status, ['submitted', 'approved', 'rejected'], true)
            && empty($this->submitted_at)
        ) {
            $this->submitted_at = $previous?->submitted_at ?? $now;
        }

        if (in_array($this->status, ['approved', 'rejected'], true)) {
            if (!$previous || (string) $previous->status !== $this->status || empty($this->reviewed_at)) {
                $this->reviewed_at = $now;
                $this->reviewed_by = $userId;
            }
        } else {
            $this->reviewed_at = null;
            $this->reviewed_by = 0;
        }

        $this->modified = $now;
        $this->modified_by = $userId;

        return parent::store($updateNulls);
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
            ->from($db->quoteName('#__dcl_participations'))
            ->where($db->quoteName('id') . ' = :stateId')
            ->bind(':stateId', $this->id, ParameterType::INTEGER);

        $currentState = $db->setQuery($query)->loadResult();

        if ($currentState !== null) {
            $this->state = (int) $currentState;
        }
    }

    private function canChangeStatus(): bool
    {
        $identity = Factory::getApplication()->getIdentity();

        if ($identity->authorise('core.edit.state', 'com_decarodcl')) {
            return true;
        }

        if (!$this->id) {
            return $this->status === 'draft';
        }

        $db = $this->getDbo();
        $query = $db->getQuery(true)
            ->select($db->quoteName('status'))
            ->from($db->quoteName('#__dcl_participations'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $this->id, ParameterType::INTEGER);

        return (string) $db->setQuery($query)->loadResult() === $this->status;
    }
}

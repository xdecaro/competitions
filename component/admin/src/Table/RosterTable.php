<?php
namespace Xdecaro\Component\Decarodcl\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\ParameterType;

final class RosterTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__decarocompetitions_rosters', 'id', $db);
    }

    public function check(): bool
    {
        $this->participation_id = (int) $this->participation_id;
        $this->player_id = (int) $this->player_id;
        $this->shirt_number = trim((string) $this->shirt_number) === '' ? null : (int) $this->shirt_number;
        $this->role = trim((string) $this->role) ?: null;
        $this->status = trim((string) $this->status) ?: 'pending';
        $this->review_note = trim((string) $this->review_note) ?: null;

        if ($this->participation_id <= 0) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_ROSTER_PARTICIPATION_REQUIRED'));
            return false;
        }

        if ($this->player_id <= 0) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_ROSTER_PLAYER_REQUIRED'));
            return false;
        }

        if ($this->shirt_number !== null && ($this->shirt_number < 0 || $this->shirt_number > 999)) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_ROSTER_SHIRT_NUMBER_INVALID'));
            return false;
        }

        if (!in_array($this->status, ['pending', 'approved', 'rejected'], true)) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_ROSTER_STATUS_INVALID'));
            return false;
        }

        if (!$this->canChangeStatus()) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_APPROVAL_PERMISSION'));
            return false;
        }

        $this->enforceStatePermission();

        $db = $this->getDbo();

        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('p.team_id'),
                $db->quoteName('p.status'),
                $db->quoteName('p.state'),
                $db->quoteName('tm.approval_status', 'team_approval_status'),
            ])
            ->from($db->quoteName('#__decarocompetitions_participations', 'p'))
            ->innerJoin(
                $db->quoteName('#__decarocompetitions_teams', 'tm')
                . ' ON ' . $db->quoteName('tm.id') . ' = ' . $db->quoteName('p.team_id')
            )
            ->where($db->quoteName('p.id') . ' = :participationId')
            ->where($db->quoteName('p.state') . ' <> -2')
            ->where($db->quoteName('tm.state') . ' <> -2')
            ->bind(':participationId', $this->participation_id, ParameterType::INTEGER);

        $participation = $db->setQuery($query)->loadObject();

        if (!$participation) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_ROSTER_PARTICIPATION_INVALID'));
            return false;
        }

        // team_id is deliberately derived server-side from the participation.
        // Never trust a posted team_id because it is redundant and could be manipulated.
        $this->team_id = (int) $participation->team_id;

        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('approval_status'),
                $db->quoteName('state'),
            ])
            ->from($db->quoteName('#__decarocompetitions_players'))
            ->where($db->quoteName('id') . ' = :playerId')
            ->where($db->quoteName('state') . ' <> -2')
            ->bind(':playerId', $this->player_id, ParameterType::INTEGER);

        $player = $db->setQuery($query)->loadObject();

        if (!$player) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_ROSTER_PLAYER_INVALID'));
            return false;
        }

        if ($this->status === 'approved' && (string) $participation->status !== 'approved') {
            $this->setError(Text::_('COM_DECARODCL_ERROR_ROSTER_PARTICIPATION_NOT_APPROVED'));
            return false;
        }

        if ($this->status === 'approved' && (string) $participation->team_approval_status !== 'approved') {
            $this->setError(Text::_('COM_DECARODCL_ERROR_ROSTER_TEAM_NOT_APPROVED'));
            return false;
        }

        if ($this->status === 'approved' && (string) $player->approval_status !== 'approved') {
            $this->setError(Text::_('COM_DECARODCL_ERROR_ROSTER_PLAYER_NOT_APPROVED'));
            return false;
        }

        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__decarocompetitions_rosters'))
            ->where($db->quoteName('participation_id') . ' = :participationId')
            ->where($db->quoteName('player_id') . ' = :playerId')
            ->where($db->quoteName('id') . ' <> :id')
            ->bind(':participationId', $this->participation_id, ParameterType::INTEGER)
            ->bind(':playerId', $this->player_id, ParameterType::INTEGER)
            ->bind(':id', $this->id, ParameterType::INTEGER);

        if ((int) $db->setQuery($query)->loadResult() > 0) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_ROSTER_DUPLICATE'));
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

    private function canChangeStatus(): bool
    {
        $identity = Factory::getApplication()->getIdentity();

        if ($identity->authorise('core.edit.state', 'com_decarodcl')) {
            return true;
        }

        if (!$this->id) {
            return $this->status === 'pending';
        }

        $db = $this->getDbo();
        $query = $db->getQuery(true)
            ->select($db->quoteName('status'))
            ->from($db->quoteName('#__decarocompetitions_rosters'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $this->id, ParameterType::INTEGER);

        return (string) $db->setQuery($query)->loadResult() === $this->status;
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
            ->from($db->quoteName('#__decarocompetitions_rosters'))
            ->where($db->quoteName('id') . ' = :stateId')
            ->bind(':stateId', $this->id, ParameterType::INTEGER);

        $currentState = $db->setQuery($query)->loadResult();

        if ($currentState !== null) {
            $this->state = (int) $currentState;
        }
    }
}

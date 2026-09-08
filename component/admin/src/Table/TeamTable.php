<?php
namespace Xdecaro\Component\Decarodcl\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\ParameterType;
use Xdecaro\Component\Decarodcl\Administrator\Helper\TournamentScopeHelper;

final class TeamTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__decarocompetitions_teams', 'id', $db);
    }

    public function check(): bool
    {
        $this->name = trim((string) $this->name);
        $this->short_name = trim((string) $this->short_name) ?: null;
        $this->city = trim((string) $this->city) ?: null;
        $this->email = trim((string) $this->email) ?: null;
        $this->phone = trim((string) $this->phone) ?: null;
        $this->website = trim((string) $this->website) ?: null;
        $this->rejection_reason = trim((string) $this->rejection_reason) ?: null;
        $this->owner_user_id = (int) $this->owner_user_id;
        $this->federation_id = (int) $this->federation_id;
        $this->team_type = strtolower(trim((string) $this->team_type)) ?: 'club';
        $this->approval_status = trim((string) $this->approval_status) ?: 'pending';

        if ($this->name === '') {
            $this->setError(Text::_('COM_DECARODCL_ERROR_TEAM_NAME_REQUIRED'));
            return false;
        }

        if ($this->federation_id <= 0) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_TEAM_FEDERATION_REQUIRED'));
            return false;
        }

        if (!in_array($this->team_type, TournamentScopeHelper::TEAM_TYPES, true)) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_TEAM_TYPE_INVALID'));
            return false;
        }

        if (!in_array($this->approval_status, ['pending', 'approved', 'rejected'], true)) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_TEAM_APPROVAL_INVALID'));
            return false;
        }

        if ($this->email !== null && filter_var($this->email, FILTER_VALIDATE_EMAIL) === false) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_EMAIL_INVALID'));
            return false;
        }

        if ($this->website !== null && filter_var($this->website, FILTER_VALIDATE_URL) === false) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_URL_INVALID'));
            return false;
        }

        $db = $this->getDbo();

        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('c.id', 'country_id'),
                $db->quoteName('c.code'),
                $db->quoteName('c.iso3'),
            ])
            ->from($db->quoteName('#__decarocompetitions_federations', 'f'))
            ->innerJoin(
                $db->quoteName('#__decarocompetitions_countries', 'c')
                . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('f.country_id')
            )
            ->where($db->quoteName('f.id') . ' = :federationId')
            ->where($db->quoteName('f.state') . ' <> -2')
            ->where($db->quoteName('c.state') . ' <> -2')
            ->bind(':federationId', $this->federation_id, ParameterType::INTEGER);

        $country = $db->setQuery($query)->loadObject();

        if (!$country) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_TEAM_FEDERATION_INVALID'));
            return false;
        }

        $legacyCountryCode = strtoupper(trim((string) ($country->iso3 ?: $country->code)));
        $this->country_code = strlen($legacyCountryCode) <= 3 ? $legacyCountryCode : null;

        if ($this->id) {
            $query = $db->getQuery(true)
                ->select('DISTINCT ' . $db->quoteName('s.tournament_id'))
                ->from($db->quoteName('#__decarocompetitions_participations', 'p'))
                ->innerJoin(
                    $db->quoteName('#__decarocompetitions_seasons', 's')
                    . ' ON ' . $db->quoteName('s.id') . ' = ' . $db->quoteName('p.season_id')
                )
                ->where($db->quoteName('p.team_id') . ' = :teamId')
                ->where($db->quoteName('p.state') . ' <> -2')
                ->where($db->quoteName('s.state') . ' <> -2')
                ->bind(':teamId', $this->id, ParameterType::INTEGER);

            foreach (array_map('intval', $db->setQuery($query)->loadColumn() ?: []) as $tournamentId) {
                try {
                    TournamentScopeHelper::assertTeamAttributesEligible(
                        $db,
                        $tournamentId,
                        $this->team_type,
                        (int) $country->country_id
                    );
                } catch (\RuntimeException $e) {
                    $this->setError($e->getMessage());
                    return false;
                }
            }
        }

        if ($this->owner_user_id > 0) {
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__users'))
                ->where($db->quoteName('id') . ' = :ownerUserId')
                ->bind(':ownerUserId', $this->owner_user_id, ParameterType::INTEGER);

            if ((int) $db->setQuery($query)->loadResult() === 0) {
                $this->setError(Text::_('COM_DECARODCL_ERROR_TEAM_MANAGER_INVALID'));
                return false;
            }
        }

        if (!$this->canChangeApprovalStatus()) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_APPROVAL_PERMISSION'));
            return false;
        }

        if (!$this->canChangeOwner()) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_TEAM_MANAGER_PERMISSION'));
            return false;
        }

        $this->enforceStatePermission();

        $rawAlias = trim((string) $this->alias);
        $aliasWasProvided = $rawAlias !== '';
        $baseAlias = ApplicationHelper::stringURLSafe($rawAlias !== '' ? $rawAlias : $this->name);

        if ($baseAlias === '') {
            $baseAlias = 'team';
        }

        $candidate = $baseAlias;
        $suffix = 2;

        while ($this->aliasExists($candidate)) {
            if ($aliasWasProvided) {
                $this->setError(Text::_('COM_DECARODCL_ERROR_TEAM_ALIAS_DUPLICATE'));
                return false;
            }

            $candidate = $baseAlias . '-' . $suffix;
            $suffix++;
        }

        $this->alias = $candidate;

        if ($this->approval_status !== 'rejected') {
            $this->rejection_reason = null;
        }

        return parent::check();
    }

    public function store($updateNulls = true): bool
    {
        $now = Factory::getDate()->toSql();
        $userId = (int) Factory::getApplication()->getIdentity()->id;
        $previousStatus = null;

        if ($this->id) {
            $db = $this->getDbo();
            $query = $db->getQuery(true)
                ->select($db->quoteName('approval_status'))
                ->from($db->quoteName('#__decarocompetitions_teams'))
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':id', $this->id, ParameterType::INTEGER);
            $previousStatus = $db->setQuery($query)->loadResult();
        }

        if (!$this->id) {
            $this->created = $this->created ?: $now;
            $this->created_by = $this->created_by ?: $userId;
        }

        if ($this->approval_status === 'approved') {
            if ($previousStatus !== 'approved' || empty($this->approved_at)) {
                $this->approved_at = $now;
                $this->approved_by = $userId;
            }
        } else {
            $this->approved_at = null;
            $this->approved_by = 0;
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
            ->from($db->quoteName('#__decarocompetitions_teams'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $this->id, ParameterType::INTEGER);

        return (string) $db->setQuery($query)->loadResult() === $this->approval_status;
    }

    private function canChangeOwner(): bool
    {
        $identity = Factory::getApplication()->getIdentity();

        if ($identity->authorise('core.edit.state', 'com_decarodcl')) {
            return true;
        }

        if (!$this->id) {
            return $this->owner_user_id === 0;
        }

        $db = $this->getDbo();
        $query = $db->getQuery(true)
            ->select($db->quoteName('owner_user_id'))
            ->from($db->quoteName('#__decarocompetitions_teams'))
            ->where($db->quoteName('id') . ' = :ownerId')
            ->bind(':ownerId', $this->id, ParameterType::INTEGER);

        return (int) $db->setQuery($query)->loadResult() === $this->owner_user_id;
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
            ->from($db->quoteName('#__decarocompetitions_teams'))
            ->where($db->quoteName('id') . ' = :stateId')
            ->bind(':stateId', $this->id, ParameterType::INTEGER);

        $currentState = $db->setQuery($query)->loadResult();

        if ($currentState !== null) {
            $this->state = (int) $currentState;
        }
    }

    private function aliasExists(string $alias): bool
    {
        $db = $this->getDbo();

        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__decarocompetitions_teams'))
            ->where($db->quoteName('alias') . ' = :alias')
            ->where($db->quoteName('id') . ' <> :id')
            ->bind(':alias', $alias)
            ->bind(':id', $this->id, ParameterType::INTEGER);

        return (int) $db->setQuery($query)->loadResult() > 0;
    }
}

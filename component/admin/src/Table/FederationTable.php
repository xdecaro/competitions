<?php
namespace Xdecaro\Component\Decarodcl\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\ParameterType;
use Xdecaro\Component\Decarodcl\Administrator\Helper\TournamentScopeHelper;

final class FederationTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__dcl_federations', 'id', $db);
    }

    public function check(): bool
    {
        $this->name = trim((string) $this->name);
        $this->short_name = trim((string) $this->short_name) ?: null;
        $this->website = trim((string) $this->website) ?: null;
        $this->email = trim((string) $this->email) ?: null;
        $this->country_id = (int) $this->country_id;

        if ($this->name === '') {
            $this->setError(Text::_('COM_DECARODCL_ERROR_FEDERATION_NAME_REQUIRED'));
            return false;
        }

        if ($this->country_id <= 0) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_FEDERATION_COUNTRY_REQUIRED'));
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
            ->select('COUNT(*)')
            ->from($db->quoteName('#__dcl_countries'))
            ->where($db->quoteName('id') . ' = :countryId')
            ->where($db->quoteName('state') . ' <> -2')
            ->bind(':countryId', $this->country_id, ParameterType::INTEGER);

        if ((int) $db->setQuery($query)->loadResult() === 0) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_FEDERATION_COUNTRY_INVALID'));
            return false;
        }

        if ($this->id) {
            $query = $db->getQuery(true)
                ->select([
                    $db->quoteName('tm.id', 'team_id'),
                    $db->quoteName('tm.team_type'),
                    $db->quoteName('s.tournament_id'),
                ])
                ->from($db->quoteName('#__dcl_teams', 'tm'))
                ->innerJoin(
                    $db->quoteName('#__dcl_participations', 'p')
                    . ' ON ' . $db->quoteName('p.team_id') . ' = ' . $db->quoteName('tm.id')
                )
                ->innerJoin(
                    $db->quoteName('#__dcl_seasons', 's')
                    . ' ON ' . $db->quoteName('s.id') . ' = ' . $db->quoteName('p.season_id')
                )
                ->where($db->quoteName('tm.federation_id') . ' = :federationId')
                ->where($db->quoteName('tm.state') . ' <> -2')
                ->where($db->quoteName('p.state') . ' <> -2')
                ->where($db->quoteName('s.state') . ' <> -2')
                ->group([
                    $db->quoteName('tm.id'),
                    $db->quoteName('tm.team_type'),
                    $db->quoteName('s.tournament_id'),
                ])
                ->bind(':federationId', $this->id, ParameterType::INTEGER);

            foreach ($db->setQuery($query)->loadObjectList() ?: [] as $dependency) {
                try {
                    TournamentScopeHelper::assertTeamAttributesEligible(
                        $db,
                        (int) $dependency->tournament_id,
                        (string) $dependency->team_type,
                        $this->country_id
                    );
                } catch (\RuntimeException $e) {
                    $this->setError($e->getMessage());
                    return false;
                }
            }
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

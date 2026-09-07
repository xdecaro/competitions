<?php
namespace Xdecaro\Component\Decarodcl\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\ParameterType;
use Xdecaro\Component\Decarodcl\Administrator\Helper\TournamentScopeHelper;

final class SeasonTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__dcl_seasons', 'id', $db);
    }

    public function check(): bool
    {
        $this->name = trim((string) $this->name);
        $this->tournament_id = (int) $this->tournament_id;
        $this->season_year = (int) $this->season_year ?: null;
        $this->host_city = trim((string) $this->host_city) ?: null;
        $this->host_country_code = strtoupper(trim((string) $this->host_country_code)) ?: null;
        $this->start_date = trim((string) $this->start_date) ?: null;
        $this->end_date = trim((string) $this->end_date) ?: null;

        if ($this->name === '') {
            $this->setError(Text::_('COM_DECARODCL_ERROR_SEASON_NAME_REQUIRED'));
            return false;
        }

        if ($this->tournament_id <= 0) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_SEASON_TOURNAMENT_REQUIRED'));
            return false;
        }

        if ($this->season_year === null || $this->season_year < 1900 || $this->season_year > 2200) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_SEASON_YEAR_INVALID'));
            return false;
        }

        if ($this->start_date !== null && !$this->isValidDate($this->start_date)) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_START_DATE_INVALID'));
            return false;
        }

        if ($this->end_date !== null && !$this->isValidDate($this->end_date)) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_END_DATE_INVALID'));
            return false;
        }

        if (
            $this->start_date !== null
            && $this->end_date !== null
            && strcmp($this->end_date, $this->start_date) < 0
        ) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_END_BEFORE_START'));
            return false;
        }

        $db = $this->getDbo();

        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__dcl_tournaments'))
            ->where($db->quoteName('id') . ' = :tournamentId')
            ->where($db->quoteName('state') . ' <> -2')
            ->bind(':tournamentId', $this->tournament_id, ParameterType::INTEGER);

        if ((int) $db->setQuery($query)->loadResult() === 0) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_SEASON_TOURNAMENT_INVALID'));
            return false;
        }

        if ($this->host_country_code !== null) {
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__dcl_countries'))
                ->where($db->quoteName('code') . ' = :countryCode')
                ->where($db->quoteName('state') . ' <> -2')
                ->bind(':countryCode', $this->host_country_code);

            if ((int) $db->setQuery($query)->loadResult() === 0) {
                $this->setError(Text::_('COM_DECARODCL_ERROR_SEASON_COUNTRY_INVALID'));
                return false;
            }

            try {
                TournamentScopeHelper::assertHostCountryAllowed(
                    $db,
                    $this->tournament_id,
                    $this->host_country_code
                );
            } catch (\RuntimeException $e) {
                $this->setError($e->getMessage());
                return false;
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

    private function isValidDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }
}

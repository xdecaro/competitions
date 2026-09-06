<?php
namespace Xdecaro\Component\Decarodcl\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\ParameterType;
use Xdecaro\Component\Decarodcl\Administrator\Helper\LanguageHelper;

final class MatchTable extends Table
{
    private const STATUSES = ['scheduled', 'live', 'finished', 'postponed', 'cancelled'];

    private const SCORE_FIELDS = [
        'home_score',
        'away_score',
        'home_score_extra',
        'away_score_extra',
        'home_penalties',
        'away_penalties',
    ];

    private const SMALLINT_UNSIGNED_MAX = 65535;
    private const INT_UNSIGNED_MAX = 4294967295;

    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__dcl_matches', 'id', $db);
    }

    public function check(): bool
    {
        LanguageHelper::load();

        $this->season_id = max(0, (int) $this->season_id);
        $this->home_team_id = max(0, (int) $this->home_team_id);
        $this->away_team_id = max(0, (int) $this->away_team_id);
        $this->venue_id = max(0, (int) $this->venue_id);
        $this->article_id = max(0, (int) $this->article_id);
        $this->stage = trim((string) $this->stage) ?: null;
        $this->group_name = trim((string) $this->group_name) ?: null;
        $this->round_name = trim((string) $this->round_name) ?: null;
        $this->status = trim((string) $this->status) ?: 'scheduled';
        $this->notes = trim((string) $this->notes) ?: null;
        $this->match_date = trim((string) $this->match_date) ?: null;
        $this->kickoff_time = trim((string) $this->kickoff_time) ?: null;

        $this->matchday = $this->normalizeNullableUnsignedInteger($this->matchday, self::SMALLINT_UNSIGNED_MAX);
        $this->attendance = $this->normalizeNullableUnsignedInteger($this->attendance, self::INT_UNSIGNED_MAX);

        if ($this->matchday === false || $this->attendance === false) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_MATCH_SCORE_INVALID'));
            return false;
        }

        foreach (self::SCORE_FIELDS as $field) {
            $value = $this->normalizeNullableUnsignedInteger($this->{$field}, self::SMALLINT_UNSIGNED_MAX);

            if ($value === false) {
                $this->setError(Text::_('COM_DECARODCL_ERROR_MATCH_SCORE_INVALID'));
                return false;
            }

            $this->{$field} = $value;
        }

        if ($this->season_id <= 0) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_MATCH_SEASON_REQUIRED'));
            return false;
        }

        if ($this->home_team_id <= 0) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_MATCH_HOME_TEAM_REQUIRED'));
            return false;
        }

        if ($this->away_team_id <= 0) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_MATCH_AWAY_TEAM_REQUIRED'));
            return false;
        }

        if ($this->home_team_id === $this->away_team_id) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_MATCH_SAME_TEAM'));
            return false;
        }

        if (!in_array($this->status, self::STATUSES, true)) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_MATCH_STATUS_INVALID'));
            return false;
        }

        if ($this->match_date !== null && !$this->isValidDate($this->match_date)) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_MATCH_DATE_INVALID'));
            return false;
        }

        if ($this->kickoff_time !== null) {
            $normalizedTime = $this->normalizeTime($this->kickoff_time);

            if ($normalizedTime === null) {
                $this->setError(Text::_('COM_DECARODCL_ERROR_MATCH_TIME_INVALID'));
                return false;
            }

            $this->kickoff_time = $normalizedTime;
        }

        $db = $this->getDbo();

        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__dcl_seasons'))
            ->where($db->quoteName('id') . ' = :seasonId')
            ->where($db->quoteName('state') . ' <> -2')
            ->bind(':seasonId', $this->season_id, ParameterType::INTEGER);

        if ((int) $db->setQuery($query)->loadResult() === 0) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_MATCH_SEASON_INVALID'));
            return false;
        }

        if (
            !$this->isApprovedParticipant($this->home_team_id, $this->season_id)
            || !$this->isApprovedParticipant($this->away_team_id, $this->season_id)
        ) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_MATCH_TEAM_NOT_APPROVED_PARTICIPANT'));
            return false;
        }

        if ($this->venue_id > 0) {
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__dcl_venues'))
                ->where($db->quoteName('id') . ' = :venueId')
                ->where($db->quoteName('state') . ' <> -2')
                ->bind(':venueId', $this->venue_id, ParameterType::INTEGER);

            if ((int) $db->setQuery($query)->loadResult() === 0) {
                $this->setError(Text::_('COM_DECARODCL_ERROR_MATCH_VENUE_INVALID'));
                return false;
            }
        }

        if ($this->article_id > 0) {
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__content'))
                ->where($db->quoteName('id') . ' = :articleId')
                ->where($db->quoteName('state') . ' <> -2')
                ->bind(':articleId', $this->article_id, ParameterType::INTEGER);

            if ((int) $db->setQuery($query)->loadResult() === 0) {
                $this->setError(Text::_('COM_DECARODCL_ERROR_MATCH_ARTICLE_INVALID'));
                return false;
            }

            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__dcl_matches'))
                ->where($db->quoteName('article_id') . ' = :articleId')
                ->where($db->quoteName('id') . ' <> :matchId')
                ->bind(':articleId', $this->article_id, ParameterType::INTEGER)
                ->bind(':matchId', $this->id, ParameterType::INTEGER);

            if ((int) $db->setQuery($query)->loadResult() > 0) {
                $this->setError(Text::_('COM_DECARODCL_ERROR_MATCH_ARTICLE_DUPLICATE'));
                return false;
            }
        }

        if (
            !$this->validatePair($this->home_score, $this->away_score)
            || !$this->validatePair($this->home_score_extra, $this->away_score_extra)
            || !$this->validatePair($this->home_penalties, $this->away_penalties)
        ) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_MATCH_SCORE_PAIR'));
            return false;
        }

        if ($this->status === 'finished' && ($this->home_score === null || $this->away_score === null)) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_MATCH_FINISHED_SCORE_REQUIRED'));
            return false;
        }

        if (
            $this->home_penalties !== null
            && $this->away_penalties !== null
            && $this->home_penalties === $this->away_penalties
        ) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_MATCH_PENALTIES_TIE'));
            return false;
        }

        $this->winner_team_id = $this->status === 'finished' ? $this->deriveWinner() : 0;
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

    private function isApprovedParticipant(int $teamId, int $seasonId): bool
    {
        $db = $this->getDbo();
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__dcl_participations', 'p'))
            ->innerJoin(
                $db->quoteName('#__dcl_teams', 'tm')
                . ' ON ' . $db->quoteName('tm.id') . ' = ' . $db->quoteName('p.team_id')
            )
            ->where($db->quoteName('p.team_id') . ' = :teamId')
            ->where($db->quoteName('p.season_id') . ' = :seasonId')
            ->where($db->quoteName('p.status') . ' = ' . $db->quote('approved'))
            ->where($db->quoteName('p.state') . ' <> -2')
            ->where($db->quoteName('tm.approval_status') . ' = ' . $db->quote('approved'))
            ->where($db->quoteName('tm.state') . ' <> -2')
            ->bind(':teamId', $teamId, ParameterType::INTEGER)
            ->bind(':seasonId', $seasonId, ParameterType::INTEGER);

        return (int) $db->setQuery($query)->loadResult() > 0;
    }

    private function normalizeNullableUnsignedInteger(mixed $value, int $max): int|null|false
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return $value >= 0 && $value <= $max ? $value : false;
        }

        $value = trim((string) $value);

        if ($value === '' || !preg_match('/^\d+$/', $value)) {
            return false;
        }

        $number = (int) $value;

        return $number <= $max ? $number : false;
    }

    private function validatePair(?int $home, ?int $away): bool
    {
        return ($home === null && $away === null) || ($home !== null && $away !== null);
    }

    private function deriveWinner(): int
    {
        if ($this->home_penalties !== null && $this->away_penalties !== null) {
            return $this->home_penalties > $this->away_penalties
                ? $this->home_team_id
                : $this->away_team_id;
        }

        if ($this->home_score_extra !== null && $this->away_score_extra !== null) {
            if ($this->home_score_extra > $this->away_score_extra) {
                return $this->home_team_id;
            }

            if ($this->away_score_extra > $this->home_score_extra) {
                return $this->away_team_id;
            }
        }

        if ($this->home_score !== null && $this->away_score !== null) {
            if ($this->home_score > $this->away_score) {
                return $this->home_team_id;
            }

            if ($this->away_score > $this->home_score) {
                return $this->away_team_id;
            }
        }

        return 0;
    }

    private function isValidDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private function normalizeTime(string $value): ?string
    {
        foreach (['!H:i', '!H:i:s'] as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $value);

            if ($date === false) {
                continue;
            }

            $expected = $format === '!H:i' ? 'H:i' : 'H:i:s';

            if ($date->format($expected) === $value) {
                return $date->format('H:i:s');
            }
        }

        return null;
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
            ->from($db->quoteName('#__dcl_matches'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $this->id, ParameterType::INTEGER);

        $currentState = $db->setQuery($query)->loadResult();

        if ($currentState !== null) {
            $this->state = (int) $currentState;
        }
    }
}

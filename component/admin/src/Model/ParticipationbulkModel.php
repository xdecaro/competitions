<?php
namespace xdecaro\Component\Competitions\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\ParameterType;

final class ParticipationbulkModel extends BaseDatabaseModel
{
    public function getSeasonOptions(): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('s.id'),
                $db->quoteName('s.name'),
                $db->quoteName('s.season_year'),
                $db->quoteName('t.name', 'tournament_name'),
            ])
            ->from($db->quoteName('#__xdecarocompetitions_seasons', 's'))
            ->leftJoin($db->quoteName('#__xdecarocompetitions_tournaments', 't') . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('s.tournament_id'))
            ->where($db->quoteName('s.state') . ' <> -2')
            ->order($db->quoteName('s.season_year') . ' DESC')
            ->order($db->quoteName('t.name') . ' ASC');

        return $db->setQuery($query)->loadObjectList() ?: [];
    }

    public function getAvailableTeams(int $seasonId): array
    {
        if ($seasonId <= 0) {
            return [];
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('tm.id'),
                $db->quoteName('tm.name'),
                $db->quoteName('tm.short_name'),
                $db->quoteName('tm.country_code'),
                $db->quoteName('tm.approval_status'),
                $db->quoteName('f.name', 'federation_name'),
                $db->quoteName('f.short_name', 'federation_code'),
            ])
            ->from($db->quoteName('#__xdecarocompetitions_teams', 'tm'))
            ->leftJoin($db->quoteName('#__xdecarocompetitions_federations', 'f') . ' ON ' . $db->quoteName('f.id') . ' = ' . $db->quoteName('tm.federation_id'))
            ->where($db->quoteName('tm.state') . ' <> -2')
            ->where('NOT EXISTS (SELECT 1 FROM ' . $db->quoteName('#__xdecarocompetitions_participations', 'p')
                . ' WHERE ' . $db->quoteName('p.team_id') . ' = ' . $db->quoteName('tm.id')
                . ' AND ' . $db->quoteName('p.season_id') . ' = :seasonId)')
            ->bind(':seasonId', $seasonId, ParameterType::INTEGER)
            ->order($db->quoteName('tm.name') . ' ASC');

        return $db->setQuery($query)->loadObjectList() ?: [];
    }

    /**
     * Keep only existing Competition teams whose normalized display name is
     * present in the uploaded source list. No team is created or modified.
     *
     * @param array<int, object> $teams
     * @param array<int, string> $sourceNames
     * @return array<int, object>
     */
    public function filterTeamsBySourceNames(array $teams, array $sourceNames): array
    {
        if (!$sourceNames) {
            return $teams;
        }

        $wanted = [];
        foreach ($sourceNames as $name) {
            $normalized = $this->normalizeTeamName((string) $name);
            if ($normalized !== '') {
                $wanted[$normalized] = true;
            }
        }

        if (!$wanted) {
            return [];
        }

        return array_values(array_filter(
            $teams,
            fn (object $team): bool => isset($wanted[$this->normalizeTeamName((string) ($team->name ?? ''))])
        ));
    }

    /** @return array{source:int,matched:int,unmatched:int} */
    public function getSourceFilterSummary(array $teams, array $sourceNames): array
    {
        $source = [];
        foreach ($sourceNames as $name) {
            $normalized = $this->normalizeTeamName((string) $name);
            if ($normalized !== '') {
                $source[$normalized] = true;
            }
        }

        $matched = [];
        foreach ($teams as $team) {
            $normalized = $this->normalizeTeamName((string) ($team->name ?? ''));
            if ($normalized !== '' && isset($source[$normalized])) {
                $matched[$normalized] = true;
            }
        }

        return [
            'source' => count($source),
            'matched' => count($matched),
            'unmatched' => max(0, count($source) - count($matched)),
        ];
    }

    /**
     * Return the original CSV labels that do not map to any available team.
     * This is intentionally exact source text so an operator can diagnose the mismatch.
     *
     * @param array<int, object> $teams
     * @param array<int, string> $sourceNames
     * @return array<int, string>
     */
    public function getUnmatchedSourceNames(array $teams, array $sourceNames): array
    {
        if (!$sourceNames) {
            return [];
        }

        $available = [];
        foreach ($teams as $team) {
            $normalized = $this->normalizeTeamName((string) ($team->name ?? ''));
            if ($normalized !== '') {
                $available[$normalized] = true;
            }
        }

        $unmatched = [];
        $seen = [];
        foreach ($sourceNames as $name) {
            $original = trim((string) $name);
            $normalized = $this->normalizeTeamName($original);
            if ($normalized === '' || isset($seen[$normalized])) {
                continue;
            }
            $seen[$normalized] = true;

            if (!isset($available[$normalized])) {
                $unmatched[] = $original;
            }
        }

        return $unmatched;
    }

    public function normalizeTeamName(string $value): string
    {
        $value = html_entity_decode(trim($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = mb_strtoupper($value, 'UTF-8');
        $value = strtr($value, [
            'Ä' => 'A',
            'Ö' => 'O',
            'Ü' => 'U',
            'ẞ' => 'SS',
            'ß' => 'SS',
            'Æ' => 'AE',
            'Œ' => 'OE',
            'Ø' => 'O',
            'Ł' => 'L',
            'Đ' => 'D',
        ]);

        if (function_exists('iconv')) {
            $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if (is_string($ascii) && $ascii !== '') {
                $value = $ascii;
            }
        }

        $value = strtoupper($value);
        $value = preg_replace('/[^A-Z0-9]+/', '', $value) ?? $value;

        return trim($value);
    }

    public function getSeasonLabel(int $seasonId): string
    {
        if ($seasonId <= 0) {
            return '';
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('s.name'),
                $db->quoteName('s.season_year'),
                $db->quoteName('t.name', 'tournament_name'),
            ])
            ->from($db->quoteName('#__xdecarocompetitions_seasons', 's'))
            ->leftJoin($db->quoteName('#__xdecarocompetitions_tournaments', 't') . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('s.tournament_id'))
            ->where($db->quoteName('s.id') . ' = :seasonId')
            ->where($db->quoteName('s.state') . ' <> -2')
            ->bind(':seasonId', $seasonId, ParameterType::INTEGER);
        $row = $db->setQuery($query, 0, 1)->loadObject();

        if (!$row) {
            return '';
        }

        $label = trim((string) $row->tournament_name);
        $season = trim((string) $row->name);
        $year = (int) $row->season_year;

        return trim($label . ($season !== '' ? ' — ' . $season : '') . ($year > 0 ? ' (' . $year . ')' : ''));
    }
}

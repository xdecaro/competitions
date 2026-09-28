<?php
namespace xdecaro\Component\Competitions\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\ParameterType;

final class RosterbulkModel extends BaseDatabaseModel
{
    /** @return array<int, object> */
    public function getParticipationOptions(): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('p.id'),
                $db->quoteName('p.team_id'),
                $db->quoteName('tm.name', 'team_name'),
                $db->quoteName('s.name', 'season_name'),
                $db->quoteName('s.season_year'),
                $db->quoteName('t.name', 'tournament_name'),
            ])
            ->from($db->quoteName('#__xdecarocompetitions_participations', 'p'))
            ->innerJoin($db->quoteName('#__xdecarocompetitions_teams', 'tm') . ' ON ' . $db->quoteName('tm.id') . ' = ' . $db->quoteName('p.team_id'))
            ->innerJoin($db->quoteName('#__xdecarocompetitions_seasons', 's') . ' ON ' . $db->quoteName('s.id') . ' = ' . $db->quoteName('p.season_id'))
            ->innerJoin($db->quoteName('#__xdecarocompetitions_tournaments', 't') . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('s.tournament_id'))
            ->where($db->quoteName('p.state') . ' <> -2')
            ->where($db->quoteName('tm.state') . ' <> -2')
            ->where($db->quoteName('s.state') . ' <> -2')
            ->where($db->quoteName('t.state') . ' <> -2')
            ->order($db->quoteName('s.season_year') . ' DESC, ' . $db->quoteName('t.name') . ' ASC, ' . $db->quoteName('tm.name') . ' ASC');

        return (array) $db->setQuery($query)->loadObjectList();
    }

    public function getParticipation(int $participationId): ?object
    {
        if ($participationId <= 0) {
            return null;
        }

        foreach ($this->getParticipationOptions() as $option) {
            if ((int) $option->id === $participationId) {
                return $option;
            }
        }

        return null;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array{items:array<int,array<string,mixed>>,matched:int,existing:int,unmatched:array<int,string>}
     */
    public function matchCsvRows(int $participationId, array $rows): array
    {
        $participation = $this->getParticipation($participationId);
        if (!$participation) {
            return ['items' => [], 'matched' => 0, 'existing' => 0, 'unmatched' => array_values(array_map(static fn ($r) => (string) ($r['name'] ?? ''), $rows))];
        }

        $teamKey = $this->normalizeName((string) $participation->team_name);
        $teamRows = [];
        foreach ($rows as $row) {
            $rowTeam = $this->normalizeName((string) ($row['team'] ?? ''));
            if ($rowTeam !== '' && $rowTeam !== $teamKey) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $key = $this->normalizeName($name);
            if ($key === '') {
                continue;
            }
            if (!isset($teamRows[$key])) {
                $teamRows[$key] = $row;
            } else {
                if (empty($teamRows[$key]['shirt_number']) && !empty($row['shirt_number'])) {
                    $teamRows[$key]['shirt_number'] = $row['shirt_number'];
                }
                $roles = array_values(array_unique(array_filter([
                    trim((string) ($teamRows[$key]['role'] ?? '')),
                    trim((string) ($row['role'] ?? '')),
                ])));
                $teamRows[$key]['role'] = implode(' / ', $roles);
            }
        }

        if ($teamRows === []) {
            return ['items' => [], 'matched' => 0, 'existing' => 0, 'unmatched' => []];
        }

        $db = $this->getDatabase();
        $players = (array) $db->setQuery(
            $db->getQuery(true)
                ->select([
                    $db->quoteName('id'),
                    $db->quoteName('person_uuid'),
                    $db->quoteName('first_name'),
                    $db->quoteName('last_name'),
                    $db->quoteName('approval_status'),
                ])
                ->from($db->quoteName('#__xdecarocompetitions_players'))
                ->where($db->quoteName('state') . ' <> -2')
                ->where($db->quoteName('person_uuid') . ' IS NOT NULL')
        )->loadObjectList();

        $byName = [];
        foreach ($players as $player) {
            $firstLast = $this->normalizeName(trim((string) $player->first_name . ' ' . (string) $player->last_name));
            $lastFirst = $this->normalizeName(trim((string) $player->last_name . ' ' . (string) $player->first_name));
            foreach (array_unique([$firstLast, $lastFirst]) as $key) {
                if ($key !== '') {
                    $byName[$key][] = $player;
                }
            }
        }

        $existingPlayerIds = [];
        $query = $db->getQuery(true)
            ->select($db->quoteName('player_id'))
            ->from($db->quoteName('#__xdecarocompetitions_rosters'))
            ->where($db->quoteName('participation_id') . ' = :participation_id')
            ->bind(':participation_id', $participationId, ParameterType::INTEGER);
        foreach ((array) $db->setQuery($query)->loadColumn() as $playerId) {
            $existingPlayerIds[(int) $playerId] = true;
        }

        $items = [];
        $unmatched = [];
        $matched = 0;
        $existing = 0;

        foreach ($teamRows as $key => $row) {
            $candidates = $byName[$key] ?? [];
            if (count($candidates) !== 1) {
                $unmatched[] = (string) ($row['name'] ?? '');
                continue;
            }

            $player = $candidates[0];
            $matched++;
            if (isset($existingPlayerIds[(int) $player->id])) {
                $existing++;
                continue;
            }

            $items[] = [
                'player_id' => (int) $player->id,
                'person_uuid' => (string) $player->person_uuid,
                'name' => trim((string) $player->first_name . ' ' . (string) $player->last_name),
                'approval_status' => (string) $player->approval_status,
                'shirt_number' => $this->cleanShirtNumber($row['shirt_number'] ?? null),
                'role' => mb_substr(trim((string) ($row['role'] ?? '')), 0, 50, 'UTF-8'),
            ];
        }

        return [
            'items' => $items,
            'matched' => $matched,
            'existing' => $existing,
            'unmatched' => array_values(array_filter($unmatched)),
        ];
    }

    public function normalizeName(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $map = ['Ä'=>'A','Ö'=>'O','Ü'=>'U','ä'=>'A','ö'=>'O','ü'=>'U','ß'=>'SS','Æ'=>'AE','æ'=>'AE','Ø'=>'O','ø'=>'O','Å'=>'A','å'=>'A'];
        $value = strtr($value, $map);
        if (class_exists('Transliterator')) {
            $trans = \Transliterator::create('NFD; [:Nonspacing Mark:] Remove; NFC');
            if ($trans) {
                $value = (string) $trans->transliterate($value);
            }
        }
        $value = mb_strtoupper($value, 'UTF-8');
        return preg_replace('/[^A-Z0-9]+/u', '', $value) ?? $value;
    }

    private function cleanShirtNumber(mixed $value): ?int
    {
        $value = trim((string) $value);
        if ($value === '' || !preg_match('/^\d{1,3}$/', $value)) {
            return null;
        }
        $number = (int) $value;
        return $number >= 0 && $number <= 999 ? $number : null;
    }
}

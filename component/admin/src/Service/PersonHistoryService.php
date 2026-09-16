<?php
namespace xdecaro\Component\Competitions\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Read-only public history of Competitions roster memberships linked to a People UUID. */
final class PersonHistoryService
{
    public function __construct(private DatabaseInterface $db)
    {
    }

    public function getHistoryByPersonUuid(string $personUuid): array
    {
        $personUuid = strtolower(trim($personUuid));

        if ($personUuid === '') {
            return [];
        }

        $query = $this->db->getQuery(true)
            ->select([
                'p.id AS player_id',
                'r.id AS roster_id',
                'r.participation_id',
                'r.team_id',
                'tm.name AS team_name',
                's.id AS season_id',
                's.name AS season_name',
                's.season_year',
                't.id AS tournament_id',
                't.name AS competition_name',
                'r.role',
                'r.shirt_number',
                'r.status',
                's.start_date',
                's.end_date',
            ])
            ->from($this->db->quoteName('#__xdecarocompetitions_players', 'p'))
            ->join('INNER', $this->db->quoteName('#__xdecarocompetitions_rosters', 'r') . ' ON r.player_id = p.id')
            ->join('INNER', $this->db->quoteName('#__xdecarocompetitions_participations', 'pa') . ' ON pa.id = r.participation_id')
            ->join('INNER', $this->db->quoteName('#__xdecarocompetitions_teams', 'tm') . ' ON tm.id = r.team_id')
            ->join('INNER', $this->db->quoteName('#__xdecarocompetitions_seasons', 's') . ' ON s.id = pa.season_id')
            ->join('INNER', $this->db->quoteName('#__xdecarocompetitions_tournaments', 't') . ' ON t.id = s.tournament_id')
            ->where('LOWER(p.person_uuid) = :person_uuid')
            ->where('p.state <> -2')
            ->where('r.state <> -2')
            ->bind(':person_uuid', $personUuid, ParameterType::STRING)
            ->order('s.start_date IS NULL ASC')
            ->order('s.start_date DESC')
            ->order('s.season_year IS NULL ASC')
            ->order('s.season_year DESC')
            ->order('s.id DESC')
            ->order('r.id DESC');

        $rows = (array) $this->db->setQuery($query)->loadAssocList();

        return array_map(static function (array $row): array {
            return [
                'player_id' => (int) $row['player_id'],
                'roster_id' => (int) $row['roster_id'],
                'participation_id' => (int) $row['participation_id'],
                'team_id' => (int) $row['team_id'],
                'team_name' => self::nullableString($row['team_name'] ?? null),
                'season_id' => (int) $row['season_id'],
                'season_name' => self::nullableString($row['season_name'] ?? null),
                'season_year' => self::nullableInt($row['season_year'] ?? null),
                'tournament_id' => (int) $row['tournament_id'],
                'competition_name' => self::nullableString($row['competition_name'] ?? null),
                'role' => self::nullableString($row['role'] ?? null),
                'shirt_number' => self::nullableInt($row['shirt_number'] ?? null),
                'status' => self::nullableString($row['status'] ?? null),
                'start_date' => self::nullableString($row['start_date'] ?? null),
                'end_date' => self::nullableString($row['end_date'] ?? null),
            ];
        }, $rows);
    }

    private static function nullableInt(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }
}

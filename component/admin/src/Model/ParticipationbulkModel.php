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

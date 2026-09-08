<?php
namespace Xdecaro\Component\Competitions\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;

final class DashboardModel extends BaseDatabaseModel
{
    public function getCounts(): array
    {
        $tables = [
            'organizations' => '#__xdecarocompetitions_organizations',
            'zones' => '#__xdecarocompetitions_zones',
            'countries' => '#__xdecarocompetitions_countries',
            'federations' => '#__xdecarocompetitions_federations',
            'tournaments' => '#__xdecarocompetitions_tournaments',
            'seasons' => '#__xdecarocompetitions_seasons',
            'teams' => '#__xdecarocompetitions_teams',
            'participations' => '#__xdecarocompetitions_participations',
            'players' => '#__xdecarocompetitions_players',
            'rosters' => '#__xdecarocompetitions_rosters',
            'matches' => '#__xdecarocompetitions_matches',
            'events' => '#__xdecarocompetitions_match_events',
        ];

        $db = $this->getDatabase();
        $counts = [];

        foreach ($tables as $key => $table) {
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName($table))
                ->where($db->quoteName('state') . ' <> -2');

            $counts[$key] = (int) $db->setQuery($query)->loadResult();
        }

        return $counts;
    }
}

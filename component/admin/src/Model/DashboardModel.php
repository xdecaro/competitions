<?php
namespace Xdecaro\Component\Decarodcl\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;

final class DashboardModel extends BaseDatabaseModel
{
    public function getCounts(): array
    {
        $tables = [
            'organizations' => '#__decarocompetitions_organizations',
            'zones' => '#__decarocompetitions_zones',
            'countries' => '#__decarocompetitions_countries',
            'federations' => '#__decarocompetitions_federations',
            'tournaments' => '#__decarocompetitions_tournaments',
            'seasons' => '#__decarocompetitions_seasons',
            'teams' => '#__decarocompetitions_teams',
            'participations' => '#__decarocompetitions_participations',
            'players' => '#__decarocompetitions_players',
            'rosters' => '#__decarocompetitions_rosters',
            'matches' => '#__decarocompetitions_matches',
            'events' => '#__decarocompetitions_match_events',
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

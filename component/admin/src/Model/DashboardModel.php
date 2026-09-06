<?php
namespace Xdecaro\Component\Decarodcl\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;

final class DashboardModel extends BaseDatabaseModel
{
    public function getCounts(): array
    {
        $tables = [
            'organizations' => '#__dcl_organizations',
            'zones' => '#__dcl_zones',
            'countries' => '#__dcl_countries',
            'federations' => '#__dcl_federations',
            'tournaments' => '#__dcl_tournaments',
            'seasons' => '#__dcl_seasons',
            'teams' => '#__dcl_teams',
            'participations' => '#__dcl_participations',
            'players' => '#__dcl_players',
            'rosters' => '#__dcl_rosters',
            'matches' => '#__dcl_matches',
            'events' => '#__dcl_match_events',
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

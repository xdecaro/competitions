<?php
namespace Xdecaro\Component\Decarodcl\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;

final class DashboardModel extends BaseDatabaseModel
{
    public function getCounts(): array
    {
        $tables = [
            'countries' => '#__dcl_countries',
            'federations' => '#__dcl_federations',
            'teams' => '#__dcl_teams',
            'players' => '#__dcl_players',
            'tournaments' => '#__dcl_tournaments',
            'seasons' => '#__dcl_seasons',
            'participations' => '#__dcl_participations',
            'events' => '#__dcl_match_events',
        ];

        $db = $this->getDatabase();
        $counts = [];

        foreach ($tables as $key => $table) {
            $query = $db->getQuery(true)->select('COUNT(*)')->from($db->quoteName($table));
            $counts[$key] = (int) $db->setQuery($query)->loadResult();
        }

        return $counts;
    }
}

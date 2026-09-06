<?php
namespace Xdecaro\Component\Decarodcl\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;

final class PageHeaderHelper
{
    public static function render(string $view, string $layout = 'default', int $id = 0): string
    {
        LanguageHelper::load();

        $lists = [
            'dashboard' => ['COM_DECARODCL_PAGE_COMPETITIONS', 'COM_DECARODCL_DASHBOARD', 'COM_DECARODCL_PAGE_DASHBOARD_DESC'],
            'organizations' => ['COM_DECARODCL_ORGANIZATIONS', 'COM_DECARODCL_ORGANIZATIONS', 'COM_DECARODCL_PAGE_ORGANIZATIONS_DESC'],
            'zones' => ['COM_DECARODCL_ZONES', 'COM_DECARODCL_ZONES', 'COM_DECARODCL_PAGE_ZONES_DESC'],
            'countries' => ['COM_DECARODCL_COUNTRIES', 'COM_DECARODCL_COUNTRIES', 'COM_DECARODCL_PAGE_COUNTRIES_DESC'],
            'federations' => ['COM_DECARODCL_FEDERATIONS', 'COM_DECARODCL_FEDERATIONS', 'COM_DECARODCL_PAGE_FEDERATIONS_DESC'],
            'tournaments' => ['COM_DECARODCL_TOURNAMENTS', 'COM_DECARODCL_TOURNAMENTS', 'COM_DECARODCL_PAGE_TOURNAMENTS_DESC'],
            'seasons' => ['COM_DECARODCL_SEASONS', 'COM_DECARODCL_SEASONS', 'COM_DECARODCL_PAGE_SEASONS_DESC'],
            'teams' => ['COM_DECARODCL_TEAMS', 'COM_DECARODCL_TEAMS', 'COM_DECARODCL_PAGE_TEAMS_DESC'],
            'participations' => ['COM_DECARODCL_PARTICIPATIONS', 'COM_DECARODCL_PARTICIPATIONS', 'COM_DECARODCL_PAGE_PARTICIPATIONS_DESC'],
            'players' => ['COM_DECARODCL_PLAYERS', 'COM_DECARODCL_PLAYERS', 'COM_DECARODCL_PAGE_PLAYERS_DESC'],
            'rosters' => ['COM_DECARODCL_ROSTERS', 'COM_DECARODCL_ROSTERS', 'COM_DECARODCL_PAGE_ROSTERS_DESC'],
            'matches' => ['COM_DECARODCL_MATCHES', 'COM_DECARODCL_MATCHES', 'COM_DECARODCL_PAGE_MATCHES_DESC'],
            'information' => ['COM_DECARODCL_INFORMATION', 'COM_DECARODCL_INFORMATION', 'COM_DECARODCL_PAGE_INFORMATION_DESC'],
        ];

        $forms = [
            'organization' => ['COM_DECARODCL_ORGANIZATIONS', 'COM_DECARODCL_ORGANIZATION_NEW', 'COM_DECARODCL_ORGANIZATION_EDIT', 'COM_DECARODCL_PAGE_ORGANIZATIONS_DESC'],
            'zone' => ['COM_DECARODCL_ZONES', 'COM_DECARODCL_ZONE_NEW', 'COM_DECARODCL_ZONE_EDIT', 'COM_DECARODCL_PAGE_ZONES_DESC'],
            'country' => ['COM_DECARODCL_COUNTRIES', 'COM_DECARODCL_COUNTRY_NEW', 'COM_DECARODCL_COUNTRY_EDIT', 'COM_DECARODCL_PAGE_COUNTRIES_DESC'],
            'federation' => ['COM_DECARODCL_FEDERATIONS', 'COM_DECARODCL_FEDERATION_NEW', 'COM_DECARODCL_FEDERATION_EDIT', 'COM_DECARODCL_PAGE_FEDERATIONS_DESC'],
            'tournament' => ['COM_DECARODCL_TOURNAMENTS', 'COM_DECARODCL_TOURNAMENT_NEW', 'COM_DECARODCL_TOURNAMENT_EDIT', 'COM_DECARODCL_PAGE_TOURNAMENTS_DESC'],
            'season' => ['COM_DECARODCL_SEASONS', 'COM_DECARODCL_SEASON_NEW', 'COM_DECARODCL_SEASON_EDIT', 'COM_DECARODCL_PAGE_SEASONS_DESC'],
            'team' => ['COM_DECARODCL_TEAMS', 'COM_DECARODCL_TEAM_NEW', 'COM_DECARODCL_TEAM_EDIT', 'COM_DECARODCL_PAGE_TEAMS_DESC'],
            'participation' => ['COM_DECARODCL_PARTICIPATIONS', 'COM_DECARODCL_PARTICIPATION_NEW', 'COM_DECARODCL_PARTICIPATION_EDIT', 'COM_DECARODCL_PAGE_PARTICIPATIONS_DESC'],
            'player' => ['COM_DECARODCL_PLAYERS', 'COM_DECARODCL_PLAYER_NEW', 'COM_DECARODCL_PLAYER_EDIT', 'COM_DECARODCL_PAGE_PLAYERS_DESC'],
            'roster' => ['COM_DECARODCL_ROSTERS', 'COM_DECARODCL_ROSTER_NEW', 'COM_DECARODCL_ROSTER_EDIT', 'COM_DECARODCL_PAGE_ROSTERS_DESC'],
            'match' => ['COM_DECARODCL_MATCHES', 'COM_DECARODCL_MATCH_NEW', 'COM_DECARODCL_MATCH_EDIT', 'COM_DECARODCL_PAGE_MATCHES_DESC'],
        ];

        if (isset($forms[$view]) && $layout === 'edit') {
            [$eyebrowKey, $newKey, $editKey, $descriptionKey] = $forms[$view];
            $data = [
                'eyebrow' => Text::_($eyebrowKey),
                'title' => Text::_($id > 0 ? $editKey : $newKey),
                'description' => Text::_($descriptionKey),
            ];
        } elseif (isset($lists[$view])) {
            [$eyebrowKey, $titleKey, $descriptionKey] = $lists[$view];
            $data = [
                'eyebrow' => Text::_($eyebrowKey),
                'title' => Text::_($titleKey),
                'description' => Text::_($descriptionKey),
            ];
        } else {
            return '';
        }

        return LayoutHelper::render('page.header', $data, JPATH_COMPONENT_ADMINISTRATOR . '/layouts');
    }
}

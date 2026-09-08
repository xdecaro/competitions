<?php
namespace Xdecaro\Component\Decarodcl\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class ScopeController extends BaseController
{
    public function eligibleTeams(): void
    {
        $app = Factory::getApplication();

        if (!Session::checkToken('post')) {
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
        }

        if (!$app->getIdentity()->authorise('core.manage', 'com_decarodcl')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $seasonId = max(0, $app->input->post->getInt('season_id', 0));

        if ($seasonId <= 0) {
            $this->respond([]);
        }

        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('t.id'),
                $db->quoteName('t.scope_type'),
                $db->quoteName('t.participant_type'),
            ])
            ->from($db->quoteName('#__decarocompetitions_seasons', 's'))
            ->innerJoin(
                $db->quoteName('#__decarocompetitions_tournaments', 't')
                . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('s.tournament_id')
            )
            ->where($db->quoteName('s.id') . ' = :seasonId')
            ->where($db->quoteName('s.state') . ' <> -2')
            ->where($db->quoteName('t.state') . ' <> -2')
            ->bind(':seasonId', $seasonId, ParameterType::INTEGER);
        $tournament = $db->setQuery($query, 0, 1)->loadObject();

        if (!$tournament) {
            $this->respond([]);
        }

        $participantType = (string) $tournament->participant_type;
        $tournamentId = (int) $tournament->id;
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('tm.id'),
                $db->quoteName('tm.name'),
                $db->quoteName('tm.short_name'),
                $db->quoteName('tm.approval_status'),
                $db->quoteName('f.name', 'federation_name'),
                $db->quoteName('c.name', 'country_name'),
                $db->quoteName('c.code', 'country_code'),
            ])
            ->from($db->quoteName('#__decarocompetitions_teams', 'tm'))
            ->innerJoin(
                $db->quoteName('#__decarocompetitions_federations', 'f')
                . ' ON ' . $db->quoteName('f.id') . ' = ' . $db->quoteName('tm.federation_id')
            )
            ->innerJoin(
                $db->quoteName('#__decarocompetitions_countries', 'c')
                . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('f.country_id')
            )
            ->where($db->quoteName('tm.state') . ' <> -2')
            ->where($db->quoteName('f.state') . ' <> -2')
            ->where($db->quoteName('c.state') . ' <> -2')
            ->where($db->quoteName('tm.team_type') . ' = :participantType')
            ->bind(':participantType', $participantType)
            ->order($db->quoteName('c.name') . ' ASC')
            ->order($db->quoteName('tm.name') . ' ASC');

        if (in_array((string) $tournament->scope_type, ['national', 'local'], true)) {
            $countryScope = $db->getQuery(true)
                ->select('1')
                ->from($db->quoteName('#__decarocompetitions_tournament_countries', 'tc'))
                ->where($db->quoteName('tc.tournament_id') . ' = :scopeTournamentId')
                ->where($db->quoteName('tc.country_id') . ' = ' . $db->quoteName('f.country_id'));
            $query->where('EXISTS (' . $countryScope . ')')
                ->bind(':scopeTournamentId', $tournamentId, ParameterType::INTEGER);
        } elseif ((string) $tournament->scope_type === 'zone') {
            $zoneScope = $db->getQuery(true)
                ->select('1')
                ->from($db->quoteName('#__decarocompetitions_tournament_zones', 'tz'))
                ->innerJoin(
                    $db->quoteName('#__decarocompetitions_zone_countries', 'zc')
                    . ' ON ' . $db->quoteName('zc.zone_id') . ' = ' . $db->quoteName('tz.zone_id')
                )
                ->where($db->quoteName('tz.tournament_id') . ' = :scopeTournamentId')
                ->where($db->quoteName('zc.country_id') . ' = ' . $db->quoteName('f.country_id'));
            $query->where('EXISTS (' . $zoneScope . ')')
                ->bind(':scopeTournamentId', $tournamentId, ParameterType::INTEGER);
        }

        $teams = [];

        foreach ($db->setQuery($query)->loadObjectList() ?: [] as $team) {
            $teams[] = [
                'id' => (int) $team->id,
                'name' => (string) $team->name,
                'short_name' => (string) ($team->short_name ?? ''),
                'country' => (string) $team->country_name,
                'country_code' => (string) $team->country_code,
                'federation' => (string) $team->federation_name,
                'approval_status' => (string) $team->approval_status,
            ];
        }

        $this->respond($teams);
    }

    private function respond(array $teams): never
    {
        $app = Factory::getApplication();
        $app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
        echo new JsonResponse(['teams' => $teams]);
        $app->close();
    }
}

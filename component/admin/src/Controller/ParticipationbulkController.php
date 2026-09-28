<?php
namespace xdecaro\Component\Competitions\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Throwable;

final class ParticipationbulkController extends BaseController
{
    protected $option = 'com_xdecarocompetitions';

    public function addSelected(): void
    {
        $app = Factory::getApplication();
        $user = $app->getIdentity();

        if (!$user->authorise('core.create', 'com_xdecarocompetitions')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $this->checkToken();

        $seasonId = $this->input->post->getInt('season_id');
        $teamIds = array_values(array_unique(array_filter(array_map(
            'intval',
            (array) $this->input->post->get('cid', [], 'array')
        ), static fn (int $id): bool => $id > 0)));

        $redirect = 'index.php?option=com_xdecarocompetitions&view=participationbulk&season_id=' . $seasonId;

        if ($seasonId <= 0 || !$teamIds) {
            $this->setMessage('Seleziona una stagione e almeno una squadra.', 'warning');
            $this->setRedirect($redirect);
            return;
        }

        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        if (!$this->seasonExists($db, $seasonId)) {
            $this->setMessage('La stagione selezionata non è valida.', 'warning');
            $this->setRedirect('index.php?option=com_xdecarocompetitions&view=participationbulk');
            return;
        }

        $component = $app->bootComponent('com_xdecarocompetitions');
        $mvcFactory = $component->getMVCFactory();
        $created = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($teamIds as $teamId) {
            if (!$this->teamExists($db, $teamId) || $this->participationExists($db, $teamId, $seasonId)) {
                $skipped++;
                continue;
            }

            try {
                $model = $mvcFactory->createModel('Participation', 'Administrator', ['ignore_request' => true]);

                if (!$model || !$model->save([
                    'id' => 0,
                    'team_id' => $teamId,
                    'season_id' => $seasonId,
                    'status' => 'draft',
                    'submitted_at' => null,
                    'reviewed_at' => null,
                    'reviewed_by' => 0,
                    'review_note' => '',
                    'state' => 1,
                ])) {
                    throw new \RuntimeException($model ? (string) $model->getError() : 'Participation model unavailable.');
                }

                $created++;
            } catch (Throwable $e) {
                $failed++;
                $app->enqueueMessage(trim($e->getMessage()), 'warning');
            }
        }

        $this->setMessage(
            sprintf('Partecipazioni create: %d. Saltate: %d. Errori: %d.', $created, $skipped, $failed),
            $failed > 0 ? 'warning' : 'message'
        );
        $this->setRedirect(
            'index.php?option=com_xdecarocompetitions&view=participations&filter_season_id=' . $seasonId
        );
    }

    private function seasonExists(DatabaseInterface $db, int $seasonId): bool
    {
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__xdecarocompetitions_seasons'))
            ->where($db->quoteName('id') . ' = :id')
            ->where($db->quoteName('state') . ' <> -2')
            ->bind(':id', $seasonId, ParameterType::INTEGER);

        return (int) $db->setQuery($query)->loadResult() > 0;
    }

    private function teamExists(DatabaseInterface $db, int $teamId): bool
    {
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__xdecarocompetitions_teams'))
            ->where($db->quoteName('id') . ' = :id')
            ->where($db->quoteName('state') . ' <> -2')
            ->bind(':id', $teamId, ParameterType::INTEGER);

        return (int) $db->setQuery($query)->loadResult() > 0;
    }

    private function participationExists(DatabaseInterface $db, int $teamId, int $seasonId): bool
    {
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__xdecarocompetitions_participations'))
            ->where($db->quoteName('team_id') . ' = :teamId')
            ->where($db->quoteName('season_id') . ' = :seasonId')
            ->bind(':teamId', $teamId, ParameterType::INTEGER)
            ->bind(':seasonId', $seasonId, ParameterType::INTEGER);

        return (int) $db->setQuery($query)->loadResult() > 0;
    }
}

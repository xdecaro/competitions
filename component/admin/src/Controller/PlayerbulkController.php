<?php
namespace xdecaro\Component\Competitions\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Throwable;

final class PlayerbulkController extends BaseController
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

        $uuids = array_values(array_unique(array_filter(array_map(
            static fn ($uuid): string => strtolower(trim((string) $uuid)),
            (array) $this->input->post->get('person_uuid', [], 'array')
        ), static fn (string $uuid): bool => (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid))));

        $search = trim($this->input->post->getString('search', ''));
        $redirect = 'index.php?option=com_xdecarocompetitions&view=playerbulk'
            . ($search !== '' ? '&search=' . rawurlencode($search) : '');

        if (!$uuids) {
            $this->setMessage('Seleziona almeno una persona.', 'warning');
            $this->setRedirect($redirect);
            return;
        }

        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $component = $app->bootComponent('com_xdecarocompetitions');
        $mvcFactory = $component->getMVCFactory();
        $created = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($uuids as $uuid) {
            if ($this->playerExists($db, $uuid)) {
                $skipped++;
                continue;
            }

            try {
                $model = $mvcFactory->createModel('Player', 'Administrator', ['ignore_request' => true]);

                if (!$model || !$model->save([
                    'id' => 0,
                    'person_uuid' => $uuid,
                    'approval_status' => 'pending',
                    'state' => 1,
                ])) {
                    throw new \RuntimeException($model ? (string) $model->getError() : 'Player model unavailable.');
                }

                $created++;
            } catch (Throwable $e) {
                $failed++;
                $app->enqueueMessage(trim($e->getMessage()), 'warning');
            }
        }

        $this->setMessage(
            sprintf('Giocatori creati: %d. Saltati: %d. Errori: %d.', $created, $skipped, $failed),
            $failed > 0 ? 'warning' : 'message'
        );
        $this->setRedirect('index.php?option=com_xdecarocompetitions&view=players');
    }

    private function playerExists(DatabaseInterface $db, string $uuid): bool
    {
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__xdecarocompetitions_players'))
            ->where($db->quoteName('person_uuid') . ' = :personUuid')
            ->where($db->quoteName('state') . ' <> -2')
            ->bind(':personUuid', $uuid, ParameterType::STRING);

        return (int) $db->setQuery($query)->loadResult() > 0;
    }
}

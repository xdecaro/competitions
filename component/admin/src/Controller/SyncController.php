<?php
namespace Xdecaro\Component\Competitions\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use Xdecaro\Component\Competitions\Administrator\Helper\LiveSyncHelper;

final class SyncController extends BaseController
{
    public function poll(): void
    {
        $app = Factory::getApplication();

        if (!Session::checkToken('post')) {
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
        }

        $identity = $app->getIdentity();

        if (!$identity->authorise('core.manage', 'com_xdecarocompetitions')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $sinceId = max(0, $app->input->post->getInt('since', 0));
        $entity = LiveSyncHelper::normalizeEntity($app->input->post->getCmd('entity', ''));
        $entityId = max(0, $app->input->post->getInt('entity_id', 0));
        $clientId = LiveSyncHelper::sanitizeClientId($app->input->post->getString('client_id', ''));
        $isEditing = (bool) $app->input->post->getInt('editing', 0);

        if ($sinceId === 0) {
            LiveSyncHelper::cleanup($db);
        }

        if ($isEditing && $entity !== '' && $entityId > 0 && $clientId !== '') {
            LiveSyncHelper::touchPresence($db, $entity, $entityId, $clientId, (int) $identity->id);
        }

        $latestId = LiveSyncHelper::latestChangeId($db);
        $changes = $sinceId > 0 ? LiveSyncHelper::listChanges($db, $sinceId, $clientId) : [];
        $currentModified = $entity !== '' && $entityId > 0
            ? LiveSyncHelper::currentModified($db, $entity, $entityId)
            : null;
        $presence = $isEditing && $entity !== '' && $entityId > 0
            ? LiveSyncHelper::listPresence($db, $entity, $entityId, $clientId)
            : [];

        $payload = [
            'latest_id' => max($latestId, $sinceId),
            'changes' => $changes,
            'current_modified' => $currentModified,
            'presence' => $presence,
        ];

        $app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
        echo new JsonResponse($payload);
        $app->close();
    }
}

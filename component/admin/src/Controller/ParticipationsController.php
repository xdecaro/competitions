<?php
namespace xdecaro\Component\Competitions\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Throwable;
use xdecaro\Component\Competitions\Administrator\Extension\CompetitionsComponent;

final class ParticipationsController extends AdminController
{
    protected $option = 'com_xdecarocompetitions';

    public function getModel($name = 'Participation', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }

    public function publish(): void
    {
        if (!Factory::getApplication()->getIdentity()->authorise('core.edit.state', 'com_xdecarocompetitions')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        parent::publish();
    }

    public function delete(): void
    {
        if (!Factory::getApplication()->getIdentity()->authorise('core.delete', 'com_xdecarocompetitions')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        parent::delete();
    }

    public function createDraw(): void
    {
        $app = Factory::getApplication();
        $user = $app->getIdentity();
        $seasonId = $app->input->post->getInt('season_id');

        if (!Session::checkToken('post')) {
            throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
        }
        if (!$user->authorise('core.create', 'com_xdecarocompetitions')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
        if (!$user->authorise('core.create', 'com_xdecarodraw')) {
            $app->enqueueMessage(Text::_('COM_XDECAROCOMPETITIONS_DRAW_ERR_DRAW_ACL'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_xdecarocompetitions&view=drawsetup&season_id=' . max(1, $seasonId), false));
            return;
        }

        try {
            $groupCount = $app->input->post->getInt('group_count');
            $result = $this->component()->getDrawIntegrationService()->createSeasonDraw(
                $seasonId,
                $groupCount,
                (int) $user->id
            );
            $drawId = (int) ($result['draw_id'] ?? 0);
            $app->enqueueMessage(
                !empty($result['reused'])
                    ? Text::_('COM_XDECAROCOMPETITIONS_DRAW_MSG_EXISTING_OPENED')
                    : Text::_('COM_XDECAROCOMPETITIONS_DRAW_MSG_CREATED')
            );
            $this->setRedirect(Route::_('index.php?option=com_xdecarodraw&view=draw&id=' . $drawId, false));
        } catch (Throwable $e) {
            $app->enqueueMessage($e->getMessage(), 'error');
            $this->setRedirect(Route::_('index.php?option=com_xdecarocompetitions&view=drawsetup&season_id=' . max(1, $seasonId), false));
        }
    }

    private function component(): CompetitionsComponent
    {
        $component = Factory::getApplication()->bootComponent('com_competitions');
        if (!$component instanceof CompetitionsComponent) {
            throw new \RuntimeException('Competitions component facade unavailable.');
        }

        return $component;
    }
}
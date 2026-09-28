<?php
namespace xdecaro\Component\Competitions\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use xdecaro\Component\Competitions\Administrator\Helper\LanguageHelper;

final class RostersController extends AdminController
{
    protected $option = 'com_xdecarocompetitions';

    public function getModel($name = 'Roster', $prefix = 'Administrator', $config = ['ignore_request' => true])
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

    public function approve(): void
    {
        $this->updateWorkflowStatus('approved', 'COM_XDECAROCOMPETITIONS_ROSTER_WORKFLOW_APPROVED');
    }

    public function pending(): void
    {
        $this->updateWorkflowStatus('pending', 'COM_XDECAROCOMPETITIONS_ROSTER_WORKFLOW_PENDING');
    }

    public function submit(): void
    {
        $this->updateWorkflowStatus('submitted', 'COM_XDECAROCOMPETITIONS_ROSTER_WORKFLOW_SUBMITTED');
    }

    public function reject(): void
    {
        $this->updateWorkflowStatus('rejected', 'COM_XDECAROCOMPETITIONS_ROSTER_WORKFLOW_REJECTED');
    }

    public function delete(): void
    {
        if (!Factory::getApplication()->getIdentity()->authorise('core.delete', 'com_xdecarocompetitions')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        parent::delete();
    }

    private function updateWorkflowStatus(string $status, string $messageKey): void
    {
        LanguageHelper::load();
        $app = Factory::getApplication();

        if (!$app->getIdentity()->authorise('core.edit.state', 'com_xdecarocompetitions')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $this->checkToken();

        $ids = array_values(array_unique(array_filter(array_map(
            'intval',
            (array) $this->input->post->get('cid', [], 'array')
        ))));
        $redirect = 'index.php?option=' . $this->option . '&view=rosters';

        if (!$ids) {
            $this->setMessage(Text::_('COM_XDECAROCOMPETITIONS_ERROR_NO_ROSTERS_SELECTED'), 'warning');
            $this->setRedirect($redirect);
            return;
        }

        $model = $this->getModel();

        if (!$model->setWorkflowStatus($ids, $status)) {
            $this->setMessage((string) $model->getError(), 'error');
            $this->setRedirect($redirect);
            return;
        }

        $this->setMessage(Text::_($messageKey));
        $this->setRedirect($redirect);
    }
}

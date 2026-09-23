<?php
namespace xdecaro\Component\Competitions\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use xdecaro\Component\Competitions\Administrator\Helper\LanguageHelper;

final class TeamsController extends AdminController
{
    protected $option = 'com_xdecarocompetitions';

    public function getModel($name = 'Team', $prefix = 'Administrator', $config = ['ignore_request' => true])
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
        $this->updateApprovalStatus('approved', 'COM_XDECAROCOMPETITIONS_APPROVAL_UPDATED_APPROVED');
    }

    public function pending(): void
    {
        $this->updateApprovalStatus('pending', 'COM_XDECAROCOMPETITIONS_APPROVAL_UPDATED_PENDING');
    }

    public function reject(): void
    {
        $this->updateApprovalStatus('rejected', 'COM_XDECAROCOMPETITIONS_APPROVAL_UPDATED_REJECTED');
    }

    public function delete(): void
    {
        if (!Factory::getApplication()->getIdentity()->authorise('core.delete', 'com_xdecarocompetitions')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        parent::delete();
    }
}

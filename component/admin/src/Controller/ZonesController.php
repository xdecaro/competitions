<?php
namespace Xdecaro\Component\Decarodcl\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;

final class ZonesController extends AdminController
{
    public function getModel($name = 'Zone', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }

    public function publish(): void
    {
        if (!Factory::getApplication()->getIdentity()->authorise('core.edit.state', 'com_decarodcl')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        parent::publish();
    }

    public function delete(): void
    {
        if (!Factory::getApplication()->getIdentity()->authorise('core.delete', 'com_decarodcl')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        parent::delete();
    }
}

<?php
namespace Xdecaro\Component\Decarodcl\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\FormController;

final class ParticipationController extends FormController
{
    protected function allowAdd($data = []): bool
    {
        return Factory::getApplication()->getIdentity()->authorise('core.create', 'com_decarodcl');
    }

    protected function allowEdit($data = [], $key = 'id'): bool
    {
        return Factory::getApplication()->getIdentity()->authorise('core.edit', 'com_decarodcl');
    }
}

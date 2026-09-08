<?php
namespace xdecaro\Component\Competitions\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\FormController;

final class TournamentController extends FormController
{
    protected function allowAdd($data = []): bool
    {
        return Factory::getApplication()->getIdentity()->authorise('core.create', 'com_xdecarocompetitions');
    }

    protected function allowEdit($data = [], $key = 'id'): bool
    {
        return Factory::getApplication()->getIdentity()->authorise('core.edit', 'com_xdecarocompetitions');
    }
}

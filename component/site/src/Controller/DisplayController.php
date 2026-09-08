<?php
namespace xdecaro\Component\Competitions\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;

final class DisplayController extends BaseController
{
    public function display($cachable = false, $urlparams = []): static
    {
        throw new \RuntimeException('Competitions public pages are provided by Joomla articles and YOOtheme layouts.', 404);
    }
}

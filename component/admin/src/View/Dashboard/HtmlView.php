<?php
namespace Xdecaro\Component\Decarodcl\Administrator\View\Dashboard;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Xdecaro\Component\Decarodcl\Administrator\Helper\UiHelper;

final class HtmlView extends BaseHtmlView
{
    public array $counts = [];

    public function display($tpl = null): void
    {
        if (!Factory::getApplication()->getIdentity()->authorise('core.manage', 'com_decarodcl')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        UiHelper::loadAssets();
        $this->counts = $this->getModel()->getCounts();

        ToolbarHelper::title(Text::_('COM_DECARODCL_DASHBOARD'), 'home');

        if (Factory::getApplication()->getIdentity()->authorise('core.admin', 'com_decarodcl')) {
            ToolbarHelper::preferences('com_decarodcl');
        }

        $this->addTemplatePath(JPATH_COMPONENT_ADMINISTRATOR . '/tmpl/dashboard');

        parent::display($tpl);
    }
}

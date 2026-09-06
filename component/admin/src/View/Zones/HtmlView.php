<?php
namespace Xdecaro\Component\Decarodcl\Administrator\View\Zones;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Xdecaro\Component\Decarodcl\Administrator\Helper\UiHelper;

final class HtmlView extends BaseHtmlView
{
    public $items;
    public $pagination;
    public $state;
    public array $organizationOptions = [];

    public function display($tpl = null): void
    {
        $user = Factory::getApplication()->getIdentity();

        if (!$user->authorise('core.manage', 'com_decarodcl')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        UiHelper::loadAssets();

        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->state = $this->get('State');
        $this->organizationOptions = $this->getModel()->getOrganizationOptions();

        if (count($errors = $this->get('Errors'))) {
            throw new \RuntimeException(implode("\n", $errors));
        }

        ToolbarHelper::title(Text::_('COM_DECARODCL_ZONES'), 'globe');

        if ($user->authorise('core.create', 'com_decarodcl')) {
            ToolbarHelper::addNew('zone.add');
        }

        if ($user->authorise('core.edit', 'com_decarodcl')) {
            ToolbarHelper::editList('zone.edit');
        }

        if ($user->authorise('core.edit.state', 'com_decarodcl')) {
            ToolbarHelper::publish('zones.publish', 'JTOOLBAR_PUBLISH', true);
            ToolbarHelper::unpublish('zones.unpublish', 'JTOOLBAR_UNPUBLISH', true);
        }

        if ($user->authorise('core.delete', 'com_decarodcl')) {
            ToolbarHelper::trash('zones.trash');
        }

        $this->addTemplatePath(JPATH_COMPONENT_ADMINISTRATOR . '/tmpl/zones');

        parent::display($tpl);
    }
}

<?php
namespace Xdecaro\Component\Decarodcl\Administrator\View\Countries;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
    public $items;
    public $pagination;
    public $state;

    public function display($tpl = null): void
    {
        $user = Factory::getApplication()->getIdentity();

        if (!$user->authorise('core.manage', 'com_decarodcl')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->state = $this->get('State');

        if (count($errors = $this->get('Errors'))) {
            throw new \RuntimeException(implode("\n", $errors));
        }

        ToolbarHelper::title(Text::_('COM_DECARODCL_COUNTRIES'), 'flag');

        if ($user->authorise('core.create', 'com_decarodcl')) {
            ToolbarHelper::addNew('country.add');
        }
        if ($user->authorise('core.edit', 'com_decarodcl')) {
            ToolbarHelper::editList('country.edit');
        }
        if ($user->authorise('core.edit.state', 'com_decarodcl')) {
            ToolbarHelper::publish('countries.publish', 'JTOOLBAR_PUBLISH', true);
            ToolbarHelper::unpublish('countries.unpublish', 'JTOOLBAR_UNPUBLISH', true);
        }
        if ($user->authorise('core.delete', 'com_decarodcl')) {
            ToolbarHelper::trash('countries.trash');
        }

        parent::display($tpl);
    }
}

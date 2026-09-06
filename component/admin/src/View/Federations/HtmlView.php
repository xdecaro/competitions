<?php
namespace Xdecaro\Component\Decarodcl\Administrator\View\Federations;

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
    public array $countryOptions = [];

    public function display($tpl = null): void
    {
        $user = Factory::getApplication()->getIdentity();

        if (!$user->authorise('core.manage', 'com_decarodcl')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->state = $this->get('State');
        $this->countryOptions = $this->getModel()->getCountryOptions();

        if (count($errors = $this->get('Errors'))) {
            throw new \RuntimeException(implode("\n", $errors));
        }

        ToolbarHelper::title(Text::_('COM_DECARODCL_FEDERATIONS'), 'users');

        if ($user->authorise('core.create', 'com_decarodcl')) {
            ToolbarHelper::addNew('federation.add');
        }
        if ($user->authorise('core.edit', 'com_decarodcl')) {
            ToolbarHelper::editList('federation.edit');
        }
        if ($user->authorise('core.edit.state', 'com_decarodcl')) {
            ToolbarHelper::publish('federations.publish', 'JTOOLBAR_PUBLISH', true);
            ToolbarHelper::unpublish('federations.unpublish', 'JTOOLBAR_UNPUBLISH', true);
        }
        if ($user->authorise('core.delete', 'com_decarodcl')) {
            ToolbarHelper::trash('federations.trash');
        }

        parent::display($tpl);
    }
}

<?php
namespace xdecaro\Component\Competitions\Administrator\View\Tournaments;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use xdecaro\Component\Competitions\Administrator\Helper\UiHelper;

final class HtmlView extends BaseHtmlView
{
    public $items;
    public $pagination;
    public $state;
    public array $disciplineOptions = [];

    public function display($tpl = null): void
    {
        $user = Factory::getApplication()->getIdentity();

        if (!$user->authorise('core.manage', 'com_xdecarocompetitions')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        UiHelper::loadAssets();

        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->state = $this->get('State');
        $this->disciplineOptions = $this->getModel()->getDisciplineOptions();

        if (count($errors = $this->get('Errors'))) {
            throw new \RuntimeException(implode("\n", $errors));
        }

        ToolbarHelper::title(Text::_('COM_XDECAROCOMPETITIONS_TOURNAMENTS'), 'trophy');

        if ($user->authorise('core.create', 'com_xdecarocompetitions')) {
            ToolbarHelper::addNew('tournament.add');
        }

        if ($user->authorise('core.edit', 'com_xdecarocompetitions')) {
            ToolbarHelper::editList('tournament.edit');
        }

        if ($user->authorise('core.edit.state', 'com_xdecarocompetitions')) {
            ToolbarHelper::publish('tournaments.publish', 'JTOOLBAR_PUBLISH', true);
            ToolbarHelper::unpublish('tournaments.unpublish', 'JTOOLBAR_UNPUBLISH', true);
        }

        if ($user->authorise('core.delete', 'com_xdecarocompetitions')) {
            ToolbarHelper::trash('tournaments.trash');
        }

        $this->addTemplatePath(JPATH_COMPONENT_ADMINISTRATOR . '/tmpl/tournaments');

        parent::display($tpl);
    }
}

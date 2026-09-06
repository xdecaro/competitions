<?php
namespace Xdecaro\Component\Decarodcl\Administrator\View\Participations;

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
    public array $tournamentOptions = [];
    public array $seasonOptions = [];

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
        $this->tournamentOptions = $this->getModel()->getTournamentOptions();
        $this->seasonOptions = $this->getModel()->getSeasonOptions();

        if (count($errors = $this->get('Errors'))) {
            throw new \RuntimeException(implode("\n", $errors));
        }

        ToolbarHelper::title(Text::_('COM_DECARODCL_PARTICIPATIONS'), 'list');

        if ($user->authorise('core.create', 'com_decarodcl')) {
            ToolbarHelper::addNew('participation.add');
        }

        if ($user->authorise('core.edit', 'com_decarodcl')) {
            ToolbarHelper::editList('participation.edit');
        }

        if ($user->authorise('core.edit.state', 'com_decarodcl')) {
            ToolbarHelper::publish('participations.publish', 'JTOOLBAR_PUBLISH', true);
            ToolbarHelper::unpublish('participations.unpublish', 'JTOOLBAR_UNPUBLISH', true);
        }

        if ($user->authorise('core.delete', 'com_decarodcl')) {
            ToolbarHelper::trash('participations.trash');
        }

        $this->addTemplatePath(JPATH_COMPONENT_ADMINISTRATOR . '/tmpl/participations');

        parent::display($tpl);
    }
}

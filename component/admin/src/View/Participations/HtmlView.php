<?php
namespace xdecaro\Component\Competitions\Administrator\View\Participations;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;
use xdecaro\Component\Competitions\Administrator\Helper\UiHelper;

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

        if (!$user->authorise('core.manage', 'com_xdecarocompetitions')) {
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

        ToolbarHelper::title(Text::_('COM_XDECAROCOMPETITIONS_PARTICIPATIONS'), 'list');

        if ($user->authorise('core.create', 'com_xdecarocompetitions')) {
            ToolbarHelper::addNew('participation.add');
            $seasonId = (int) $this->state->get('filter.season_id', 0);
            $bulkUrl = 'index.php?option=com_xdecarocompetitions&view=participationbulk';

            if ($seasonId > 0) {
                $bulkUrl .= '&season_id=' . $seasonId;
            }

            ToolbarHelper::link(Route::_($bulkUrl), 'Aggiungi squadre', 'plus');
        }

        if ($user->authorise('core.edit', 'com_xdecarocompetitions')) {
            ToolbarHelper::editList('participation.edit');
        }

        if ($user->authorise('core.edit.state', 'com_xdecarocompetitions')) {
            ToolbarHelper::custom('participations.approve', 'checkmark', 'checkmark', Text::_('COM_XDECAROCOMPETITIONS_TOOLBAR_APPROVE'), true);
            ToolbarHelper::custom('participations.pending', 'clock', 'clock', Text::_('COM_XDECAROCOMPETITIONS_TOOLBAR_PENDING'), true);
            ToolbarHelper::custom('participations.reject', 'cancel', 'cancel', Text::_('COM_XDECAROCOMPETITIONS_TOOLBAR_REJECT'), true);
            ToolbarHelper::publish('participations.publish', 'JTOOLBAR_PUBLISH', true);
            ToolbarHelper::unpublish('participations.unpublish', 'JTOOLBAR_UNPUBLISH', true);
        }

        if ($user->authorise('core.delete', 'com_xdecarocompetitions')) {
            ToolbarHelper::trash('participations.trash');
        }

        $this->addTemplatePath(JPATH_COMPONENT_ADMINISTRATOR . '/tmpl/participations');

        parent::display($tpl);
    }
}

<?php
namespace xdecaro\Component\Competitions\Administrator\View\Drawsetup;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;
use xdecaro\Component\Competitions\Administrator\Extension\CompetitionsComponent;
use xdecaro\Component\Competitions\Administrator\Helper\UiHelper;

final class HtmlView extends BaseHtmlView
{
    public array $context = [];
    public int $seasonId = 0;
    public bool $canCreate = false;
    public bool $canCreateDraw = false;

    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        $user = $app->getIdentity();

        if (!$user->authorise('core.manage', 'com_xdecarocompetitions')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $this->seasonId = max(1, $app->input->getInt('season_id'));
        $this->canCreate = $user->authorise('core.create', 'com_xdecarocompetitions');
        $this->canCreateDraw = $user->authorise('core.create', 'com_xdecarodraw');

        $component = $app->bootComponent('com_competitions');
        if (!$component instanceof CompetitionsComponent) {
            throw new \RuntimeException('Competitions component facade unavailable.');
        }

        $this->context = $component->getDrawIntegrationService()->getSeasonContext($this->seasonId);
        UiHelper::loadAssets();

        ToolbarHelper::title(Text::_('COM_XDECAROCOMPETITIONS_DRAW_SETUP_TITLE'), 'shuffle');
        ToolbarHelper::link(
            Route::_('index.php?option=com_xdecarocompetitions&view=participations&filter_season_id=' . $this->seasonId, false),
            Text::_('JTOOLBAR_BACK'),
            'arrow-left'
        );

        $this->addTemplatePath(JPATH_COMPONENT_ADMINISTRATOR . '/tmpl/drawsetup');
        parent::display($tpl);
    }
}
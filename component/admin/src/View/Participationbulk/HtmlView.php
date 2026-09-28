<?php
namespace xdecaro\Component\Competitions\Administrator\View\Participationbulk;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;
use xdecaro\Component\Competitions\Administrator\Helper\UiHelper;

final class HtmlView extends BaseHtmlView
{
    public array $seasonOptions = [];
    public array $items = [];
    public int $seasonId = 0;
    public string $seasonLabel = '';
    public array $sourceTeamNames = [];
    public string $sourceFileName = '';
    public array $sourceSummary = ['source' => 0, 'matched' => 0, 'unmatched' => 0];
    public array $unmatchedSourceNames = [];

    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        $user = $app->getIdentity();

        if (!$user->authorise('core.create', 'com_xdecarocompetitions')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        UiHelper::loadAssets();
        $this->seasonId = $app->input->getInt('season_id');

        if ($this->seasonId <= 0) {
            $this->seasonId = $app->input->getInt('filter_season_id');
        }

        $model = $this->getModel();
        $this->seasonOptions = $model->getSeasonOptions();
        $allAvailable = $model->getAvailableTeams($this->seasonId);
        $this->seasonLabel = $model->getSeasonLabel($this->seasonId);

        $sourceSeasonId = (int) $app->getUserState('com_xdecarocompetitions.participationbulk.source_season_id', 0);
        if ($this->seasonId > 0 && $sourceSeasonId === $this->seasonId) {
            $this->sourceTeamNames = (array) $app->getUserState(
                'com_xdecarocompetitions.participationbulk.source_team_names',
                []
            );
            $this->sourceFileName = (string) $app->getUserState(
                'com_xdecarocompetitions.participationbulk.source_file_name',
                ''
            );
        }

        $this->sourceSummary = $model->getSourceFilterSummary($allAvailable, $this->sourceTeamNames);
        $this->unmatchedSourceNames = $model->getUnmatchedSourceNames($allAvailable, $this->sourceTeamNames);
        $this->items = $model->filterTeamsBySourceNames($allAvailable, $this->sourceTeamNames);

        if (count($errors = $this->get('Errors'))) {
            throw new \RuntimeException(implode("\n", $errors));
        }

        ToolbarHelper::title('Aggiungi squadre alla stagione', 'plus');
        ToolbarHelper::custom(
            'participationbulk.addSelected',
            'plus',
            'plus',
            'Aggiungi selezionate',
            true
        );
        ToolbarHelper::link(
            Route::_('index.php?option=com_xdecarocompetitions&view=participations'),
            Text::_('JTOOLBAR_CLOSE'),
            'cancel'
        );

        $this->addTemplatePath(JPATH_COMPONENT_ADMINISTRATOR . '/tmpl/participationbulk');
        parent::display($tpl);
    }
}

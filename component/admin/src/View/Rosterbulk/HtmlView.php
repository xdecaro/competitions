<?php
namespace xdecaro\Component\Competitions\Administrator\View\Rosterbulk;

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
    public array $participationOptions = [];
    public int $seasonId = 0;
    public string $rosterTarget = '';
    public int $participationId = 0;
    public ?object $participation = null;
    public array $items = [];
    public array $unmatched = [];
    public array $groups = [];
    public array $unmatchedTeams = [];
    public array $sourceSummary = [
        'teams_in_file' => 0,
        'matched_teams' => 0,
        'players' => 0,
        'existing' => 0,
        'available' => 0,
        'unmatched' => 0,
    ];
    public string $sourceFileName = '';

    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        $user = $app->getIdentity();
        if (!$user->authorise('core.create', 'com_xdecarocompetitions')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        UiHelper::loadAssets();
        $model = $this->getModel();
        $this->seasonOptions = $model->getSeasonOptions();
        $this->participationOptions = $model->getParticipationOptions();
        $this->seasonId = (int) $app->input->getInt('season_id', 0);
        $this->rosterTarget = trim((string) $app->input->getCmd('roster_target', ''));

        // Preserve links produced by the previous single-participation workflow.
        $legacyParticipationId = (int) $app->input->getInt('participation_id', 0);
        if ($this->rosterTarget === '' && $legacyParticipationId > 0) {
            $legacyParticipation = $model->getParticipation($legacyParticipationId);
            if ($legacyParticipation) {
                $this->rosterTarget = (string) $legacyParticipationId;
                $this->seasonId = (int) ($legacyParticipation->season_id ?? 0);
            }
        }

        if ($this->rosterTarget !== 'all' && ctype_digit($this->rosterTarget)) {
            $candidateId = (int) $this->rosterTarget;
            $candidate = $model->getParticipation($candidateId);
            if ($candidate && (int) ($candidate->season_id ?? 0) === $this->seasonId) {
                $this->participationId = $candidateId;
                $this->participation = $candidate;
            } else {
                $this->rosterTarget = '';
            }
        } elseif ($this->rosterTarget !== 'all') {
            $this->rosterTarget = '';
        }

        $sourceSeasonId = (int) $app->getUserState('com_xdecarocompetitions.rosterbulk.source_season_id', 0);
        $sourceTarget = (string) $app->getUserState('com_xdecarocompetitions.rosterbulk.source_target', '');
        $hasMatchingSource = $sourceSeasonId === $this->seasonId
            && $sourceTarget === $this->rosterTarget
            && $this->seasonId > 0
            && $this->rosterTarget !== '';

        $rows = $hasMatchingSource
            ? (array) $app->getUserState('com_xdecarocompetitions.rosterbulk.source_rows', [])
            : [];
        $this->sourceFileName = $hasMatchingSource
            ? (string) $app->getUserState('com_xdecarocompetitions.rosterbulk.source_file_name', '')
            : '';

        if ($rows !== [] && $this->rosterTarget === 'all') {
            $match = $model->matchSeasonCsvRows($this->seasonId, $rows);
            $this->groups = (array) $match['groups'];
            $this->unmatchedTeams = (array) $match['unmatched_teams'];
            $this->sourceSummary = (array) $match['summary'];
        } elseif ($rows !== [] && $this->participation) {
            $match = $model->matchCsvRows($this->participationId, $rows);
            $this->items = (array) $match['items'];
            $this->unmatched = (array) $match['unmatched'];
            $teamKey = $model->normalizeName((string) $this->participation->team_name);
            $teamRows = 0;
            foreach ($rows as $row) {
                $rowTeam = $model->normalizeName((string) ($row['team'] ?? ''));
                if ($rowTeam === '' || $rowTeam === $teamKey) {
                    $teamRows++;
                }
            }

            $items = [];
            foreach ($this->items as $item) {
                $item['participation_id'] = $this->participationId;
                $item['team_name'] = (string) $this->participation->team_name;
                $items[] = $item;
            }
            $this->items = $items;
            $this->groups = [[
                'participation_id' => $this->participationId,
                'team_id' => (int) $this->participation->team_id,
                'team_name' => (string) $this->participation->team_name,
                'source' => $teamRows,
                'matched' => (int) $match['matched'],
                'existing' => (int) $match['existing'],
                'available' => count($this->items),
                'unmatched' => $this->unmatched,
                'items' => $this->items,
            ]];
            $this->sourceSummary = [
                'teams_in_file' => $teamRows > 0 ? 1 : 0,
                'matched_teams' => $teamRows > 0 ? 1 : 0,
                'players' => $teamRows,
                'existing' => (int) $match['existing'],
                'available' => count($this->items),
                'unmatched' => count($this->unmatched),
            ];
        }

        ToolbarHelper::title('Aggiungi rosa da file', 'users');
        ToolbarHelper::custom('rosterbulk.addSelected', 'plus', 'plus', 'Aggiungi selezionati', true);
        ToolbarHelper::link(Route::_('index.php?option=com_xdecarocompetitions&view=rosters'), Text::_('JTOOLBAR_CLOSE'), 'cancel');
        $this->addTemplatePath(JPATH_COMPONENT_ADMINISTRATOR . '/tmpl/rosterbulk');
        parent::display($tpl);
    }
}

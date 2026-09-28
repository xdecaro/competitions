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
    public array $participationOptions = [];
    public int $participationId = 0;
    public ?object $participation = null;
    public array $items = [];
    public array $unmatched = [];
    public array $sourceSummary = ['source' => 0, 'team_rows' => 0, 'matched' => 0, 'existing' => 0, 'available' => 0, 'unmatched' => 0];
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
        $this->participationOptions = $model->getParticipationOptions();
        $this->participationId = $app->input->getInt('participation_id');
        $this->participation = $model->getParticipation($this->participationId);

        $sourcePid = (int) $app->getUserState('com_xdecarocompetitions.rosterbulk.source_participation_id', 0);
        $rows = $sourcePid === $this->participationId
            ? (array) $app->getUserState('com_xdecarocompetitions.rosterbulk.source_rows', [])
            : [];
        $this->sourceFileName = $sourcePid === $this->participationId
            ? (string) $app->getUserState('com_xdecarocompetitions.rosterbulk.source_file_name', '')
            : '';

        if ($this->participation && $rows) {
            $match = $model->matchCsvRows($this->participationId, $rows);
            $this->items = $match['items'];
            $this->unmatched = $match['unmatched'];
            $teamKey = $model->normalizeName((string) $this->participation->team_name);
            $teamRows = 0;
            foreach ($rows as $row) {
                $rowTeam = $model->normalizeName((string) ($row['team'] ?? ''));
                if ($rowTeam === '' || $rowTeam === $teamKey) $teamRows++;
            }
            $this->sourceSummary = [
                'source' => count($rows),
                'team_rows' => $teamRows,
                'matched' => (int) $match['matched'],
                'existing' => (int) $match['existing'],
                'available' => count($this->items),
                'unmatched' => count($this->unmatched),
            ];
        }

        ToolbarHelper::title('Aggiungi rosa da CSV', 'users');
        ToolbarHelper::custom('rosterbulk.addSelected', 'plus', 'plus', 'Aggiungi selezionati', true);
        ToolbarHelper::link(Route::_('index.php?option=com_xdecarocompetitions&view=rosters'), Text::_('JTOOLBAR_CLOSE'), 'cancel');
        $this->addTemplatePath(JPATH_COMPONENT_ADMINISTRATOR . '/tmpl/rosterbulk');
        parent::display($tpl);
    }
}

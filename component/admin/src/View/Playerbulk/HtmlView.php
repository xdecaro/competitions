<?php
namespace xdecaro\Component\Competitions\Administrator\View\Playerbulk;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;
use xdecaro\Component\Competitions\Administrator\Helper\UiHelper;

final class HtmlView extends BaseHtmlView
{
    public array $items = [];
    public string $search = '';
    public array $sourcePersonNames = [];
    public string $sourceFileName = '';
    public array $sourceSummary = ['source' => 0, 'matched' => 0, 'unmatched' => 0];
    public array $unmatchedSourceNames = [];
    public int $alreadyLinked = 0;

    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        $user = $app->getIdentity();

        if (!$user->authorise('core.create', 'com_xdecarocompetitions')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        UiHelper::loadAssets();
        $this->search = trim($app->input->getString('search', ''));
        $this->sourcePersonNames = (array) $app->getUserState(
            'com_xdecarocompetitions.playerbulk.source_person_names',
            []
        );
        $this->sourceFileName = (string) $app->getUserState(
            'com_xdecarocompetitions.playerbulk.source_file_name',
            ''
        );

        $model = $this->getModel();
        if ($this->sourcePersonNames) {
            $matchedPeople = $model->getPeopleForSourceNames($this->sourcePersonNames);
            $this->sourceSummary = $model->getSourceFilterSummary($matchedPeople, $this->sourcePersonNames);
            $this->unmatchedSourceNames = $model->getUnmatchedSourceNames($matchedPeople, $this->sourcePersonNames);
            $this->items = $model->excludeLinkedPeople($matchedPeople);
            $this->alreadyLinked = max(0, (int) $this->sourceSummary['matched'] - count($this->items));
        } else {
            $this->items = $model->getAvailablePeople($this->search);
        }

        if (count($errors = $this->get('Errors'))) {
            throw new \RuntimeException(implode("\n", $errors));
        }

        ToolbarHelper::title('Aggiungi giocatori da People', 'users');
        ToolbarHelper::custom(
            'playerbulk.addSelected',
            'plus',
            'plus',
            'Aggiungi selezionati',
            true
        );
        ToolbarHelper::link(
            Route::_('index.php?option=com_xdecarocompetitions&view=players'),
            Text::_('JTOOLBAR_CLOSE'),
            'cancel'
        );

        $this->addTemplatePath(JPATH_COMPONENT_ADMINISTRATOR . '/tmpl/playerbulk');
        parent::display($tpl);
    }
}

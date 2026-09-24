<?php
namespace xdecaro\Component\Competitions\Administrator\View\Teamimport;

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
    public array $summary = [];
    public bool $providerLimitReached = false;

    public function display($tpl = null): void
    {
        $user = Factory::getApplication()->getIdentity();

        if (!$user->authorise('core.create', 'com_xdecarocompetitions')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        UiHelper::loadTeamImportAsset();

        $this->items = $this->getModel()->getPreviewRows();
        $this->summary = $this->getModel()->getSummary($this->items);
        $this->providerLimitReached = $this->getModel()->countClubsAtProviderLimit($this->items);

        if (count($errors = $this->get('Errors'))) {
            throw new \RuntimeException(implode("\n", $errors));
        }

        ToolbarHelper::title(Text::_('COM_XDECAROCOMPETITIONS_TEAMIMPORT_TITLE'), 'upload');
        ToolbarHelper::custom(
            'teamimport.importSelected',
            'upload',
            'upload',
            Text::_('COM_XDECAROCOMPETITIONS_TEAMIMPORT_IMPORT_SELECTED'),
            true
        );
        ToolbarHelper::link(
            Route::_('index.php?option=com_xdecarocompetitions&view=teams'),
            Text::_('JTOOLBAR_CLOSE'),
            'cancel'
        );

        parent::display($tpl);
    }
}

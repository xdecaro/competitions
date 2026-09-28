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

    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        $user = $app->getIdentity();

        if (!$user->authorise('core.create', 'com_xdecarocompetitions')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        UiHelper::loadAssets();
        $this->search = trim($app->input->getString('search', ''));
        $this->items = $this->getModel()->getAvailablePeople($this->search);

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

<?php
namespace xdecaro\Component\Competitions\Administrator\View\Country;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use xdecaro\Component\Competitions\Administrator\Helper\UiHelper;

final class HtmlView extends BaseHtmlView
{
    public $form;
    public $item;
    public $state;

    public function display($tpl = null): void
    {
        $user = Factory::getApplication()->getIdentity();

        if (!$user->authorise('core.manage', 'com_xdecarocompetitions')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        UiHelper::loadAssets();
        $this->form = $this->get('Form');
        $this->item = $this->get('Item');
        $this->state = $this->get('State');

        if (count($errors = $this->get('Errors'))) {
            throw new \RuntimeException(implode("\n", $errors));
        }

        $isNew = empty($this->item->id);
        ToolbarHelper::title(
            $isNew ? Text::_('COM_XDECAROCOMPETITIONS_COUNTRY_NEW') : Text::_('COM_XDECAROCOMPETITIONS_COUNTRY_EDIT'),
            'flag'
        );

        if ($user->authorise($isNew ? 'core.create' : 'core.edit', 'com_xdecarocompetitions')) {
            ToolbarHelper::apply('country.apply');
            ToolbarHelper::save('country.save');
            ToolbarHelper::save2new('country.save2new');
        }

        ToolbarHelper::cancel('country.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');

        $this->addTemplatePath(JPATH_COMPONENT_ADMINISTRATOR . '/tmpl/country');

        parent::display($tpl);
    }
}

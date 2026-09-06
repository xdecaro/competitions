<?php
namespace Xdecaro\Component\Decarodcl\Administrator\View\Federation;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
    public $form;
    public $item;
    public $state;

    public function display($tpl = null): void
    {
        $user = Factory::getApplication()->getIdentity();

        if (!$user->authorise('core.manage', 'com_decarodcl')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $this->form = $this->get('Form');
        $this->item = $this->get('Item');
        $this->state = $this->get('State');

        if (count($errors = $this->get('Errors'))) {
            throw new \RuntimeException(implode("\n", $errors));
        }

        $isNew = empty($this->item->id);
        ToolbarHelper::title(
            $isNew ? Text::_('COM_DECARODCL_FEDERATION_NEW') : Text::_('COM_DECARODCL_FEDERATION_EDIT'),
            'users'
        );

        if ($user->authorise($isNew ? 'core.create' : 'core.edit', 'com_decarodcl')) {
            ToolbarHelper::apply('federation.apply');
            ToolbarHelper::save('federation.save');
            ToolbarHelper::save2new('federation.save2new');
        }

        ToolbarHelper::cancel('federation.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');

        parent::display($tpl);
    }
}

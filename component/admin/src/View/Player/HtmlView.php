<?php
namespace xdecaro\Component\Competitions\Administrator\View\Player;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Throwable;
use xdecaro\Component\Competitions\Administrator\Helper\UiHelper;

final class HtmlView extends BaseHtmlView
{
    public $form;
    public $item;
    public $state;
    public ?array $person = null;

    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        $user = $app->getIdentity();

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

        $personUuid = strtolower(trim((string) ($this->item->person_uuid ?? '')));
        if ($personUuid !== '') {
            try {
                $component = $app->bootComponent('com_xdecarocompetitions');
                if (is_object($component) && method_exists($component, 'getPeopleIntegrationService')) {
                    $this->person = $component->getPeopleIntegrationService()->getPerson($personUuid, false);
                }
            } catch (Throwable) {
                $this->person = null;
            }
        }

        // The People UUID is rendered explicitly by the template so the identity picker
        // and the normal Joomla fieldset never submit duplicate inputs.
        $this->form->removeField('person_uuid');

        $isNew = empty($this->item->id);

        ToolbarHelper::title(
            $isNew ? Text::_('COM_XDECAROCOMPETITIONS_PLAYER_NEW') : Text::_('COM_XDECAROCOMPETITIONS_PLAYER_EDIT'),
            'user'
        );

        if ($user->authorise($isNew ? 'core.create' : 'core.edit', 'com_xdecarocompetitions')) {
            ToolbarHelper::apply('player.apply');
            ToolbarHelper::save('player.save');
            ToolbarHelper::save2new('player.save2new');
        }

        ToolbarHelper::cancel('player.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');

        $this->addTemplatePath(JPATH_COMPONENT_ADMINISTRATOR . '/tmpl/player');

        parent::display($tpl);
    }
}

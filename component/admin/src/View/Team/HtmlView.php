<?php
namespace xdecaro\Component\Competitions\Administrator\View\Team;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use xdecaro\Component\Competitions\Administrator\Helper\UiHelper;
use xdecaro\Component\Competitions\Administrator\Service\OrganizationsIntegrationService;

final class HtmlView extends BaseHtmlView
{
    public $form;
    public $item;
    public $state;
    public bool $organizationsAvailable = false;
    public ?array $organizationData = null;
    public array $sportsFederationAffiliations = [];
    public bool $sportsAffiliationsReadable = false;

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

        $organizations = new OrganizationsIntegrationService();
        $this->organizationsAvailable = $organizations->isAvailable();

        if ($this->organizationsAvailable && !empty($this->item->organization_uuid)) {
            try {
                $this->organizationData = $organizations->getClub((string) $this->item->organization_uuid);
            } catch (\Throwable) {
                $this->organizationData = null;
            }

            try {
                $this->sportsFederationAffiliations = $organizations->getActiveSportsFederations(
                    (string) $this->item->organization_uuid
                );
                $this->sportsAffiliationsReadable = true;
            } catch (\Throwable) {
                $this->sportsFederationAffiliations = [];
                $this->sportsAffiliationsReadable = false;
            }
        }

        if (count($errors = $this->get('Errors'))) {
            throw new \RuntimeException(implode("\n", $errors));
        }

        $isNew = empty($this->item->id);

        ToolbarHelper::title(
            $isNew ? Text::_('COM_XDECAROCOMPETITIONS_TEAM_NEW') : Text::_('COM_XDECAROCOMPETITIONS_TEAM_EDIT'),
            'users'
        );

        if ($user->authorise($isNew ? 'core.create' : 'core.edit', 'com_xdecarocompetitions')) {
            ToolbarHelper::apply('team.apply');
            ToolbarHelper::save('team.save');
            ToolbarHelper::save2new('team.save2new');
        }

        ToolbarHelper::cancel('team.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');

        $this->addTemplatePath(JPATH_COMPONENT_ADMINISTRATOR . '/tmpl/team');

        parent::display($tpl);
    }
}

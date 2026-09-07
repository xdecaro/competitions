<?php
namespace Xdecaro\Component\Decarodcl\Administrator\View\Information;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Xdecaro\Component\Decarodcl\Administrator\Helper\UiHelper;

final class HtmlView extends BaseHtmlView
{
    public array $info = [];
    public bool $canManageInstaller = false;

    public function display($tpl = null): void
    {
        $user = Factory::getApplication()->getIdentity();

        if (!$user->authorise('core.manage', 'com_decarodcl')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        UiHelper::loadAssets();

        $document = Factory::getApplication()->getDocument();
        $wa = $document->getWebAssetManager();
        $wa->getRegistry()->addExtensionRegistryFile('com_decarodcl');
        $wa->useStyle('com_decarodcl.information');
        $wa->useScript('com_decarodcl.information');

        foreach ([
            'COM_DECARODCL_INFO_COPIED',
            'COM_DECARODCL_INFO_COPY_FAILED',
            'COM_DECARODCL_INFO_DOWNLOADED',
        ] as $key) {
            Text::script($key);
        }

        $this->info = $this->get('Info');
        $this->canManageInstaller = $user->authorise('core.manage', 'com_installer');

        if (count($errors = $this->get('Errors'))) {
            throw new \RuntimeException(implode("\n", $errors));
        }

        ToolbarHelper::title(Text::_('COM_DECARODCL_INFORMATION'), 'info-circle');
        $this->addTemplatePath(JPATH_COMPONENT_ADMINISTRATOR . '/tmpl/information');

        parent::display($tpl);
    }
}

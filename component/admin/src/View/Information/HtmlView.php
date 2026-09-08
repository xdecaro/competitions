<?php
namespace Xdecaro\Component\Competitions\Administrator\View\Information;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\WebAsset\WebAssetManager;
use Xdecaro\Component\Competitions\Administrator\Helper\UiHelper;

final class HtmlView extends BaseHtmlView
{
    private const MINIMUM_CORE_UI_VERSION = '1.1.0';

    public array $info = [];
    public bool $canManageInstaller = false;
    public bool $coreUiActive = false;

    public function display($tpl = null): void
    {
        $user = Factory::getApplication()->getIdentity();

        if (!$user->authorise('core.manage', 'com_xdecarocompetitions')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        UiHelper::loadAssets();

        $document = Factory::getApplication()->getDocument();
        $wa = $document->getWebAssetManager();
        $wa->getRegistry()->addExtensionRegistryFile('com_xdecarocompetitions');

        $this->coreUiActive = $this->enableCoreUi($wa);

        if ($this->coreUiActive) {
            $wa->useStyle('com_xdecarocompetitions.core-bridge');
            $this->setLayout('core');
        }

        $wa->useStyle('com_xdecarocompetitions.information');
        $wa->useScript('com_xdecarocompetitions.information');

        foreach ([
            'COM_XDECAROCOMPETITIONS_INFO_COPIED',
            'COM_XDECAROCOMPETITIONS_INFO_COPY_FAILED',
            'COM_XDECAROCOMPETITIONS_INFO_DOWNLOADED',
        ] as $key) {
            Text::script($key);
        }

        $this->info = $this->get('Info');
        $this->canManageInstaller = $user->authorise('core.manage', 'com_installer');

        if (count($errors = $this->get('Errors'))) {
            throw new \RuntimeException(implode("\n", $errors));
        }

        ToolbarHelper::title(Text::_('COM_XDECAROCOMPETITIONS_INFORMATION'), 'info-circle');
        $this->addTemplatePath(JPATH_COMPONENT_ADMINISTRATOR . '/tmpl/information');

        parent::display($tpl);
    }

    private function enableCoreUi(WebAssetManager $webAssets): bool
    {
        if (!class_exists(\Xdecaro\Core\Version::class)
            || version_compare(\Xdecaro\Core\Version::VERSION, self::MINIMUM_CORE_UI_VERSION, '<')
            || !class_exists(\Xdecaro\Core\Asset\AssetService::class)) {
            return false;
        }

        try {
            $assetService = new \Xdecaro\Core\Asset\AssetService();

            return $assetService->useComponents($webAssets);
        } catch (\Throwable $exception) {
            // Core by xdecaro remains optional: UI integration failure must not block Competitions.
            return false;
        }
    }
}

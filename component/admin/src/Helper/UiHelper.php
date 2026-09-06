<?php
namespace Xdecaro\Component\Decarodcl\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;

final class UiHelper
{
    public static function loadAssets(): void
    {
        LanguageHelper::load();

        $document = Factory::getApplication()->getDocument();
        $wa = $document->getWebAssetManager();
        $styleName = 'com_decarodcl.admin.runtime';
        $scriptName = 'com_decarodcl.live-sync.runtime';

        if (!$wa->assetExists('style', $styleName)) {
            $wa->registerStyle($styleName, 'com_decarodcl/admin.css', ['version' => '0.10.0']);
        }

        if (!$wa->assetExists('script', $scriptName)) {
            $wa->registerScript(
                $scriptName,
                'com_decarodcl/live-sync.js',
                ['version' => '0.10.0'],
                ['defer' => true]
            );
        }

        $document->addScriptOptions('com_decarodcl.liveSync', [
            'endpoint' => 'index.php?option=com_decarodcl&task=sync.poll&format=json',
            'token' => Session::getFormToken(),
            'interval' => 5000,
            'strings' => [
                'presence' => Text::_('COM_DECARODCL_LIVE_PRESENCE'),
                'conflict' => Text::_('COM_DECARODCL_LIVE_CONFLICT'),
                'reload' => Text::_('COM_DECARODCL_LIVE_RELOAD'),
            ],
        ]);

        $wa->useStyle($styleName);
        $wa->useScript($scriptName);
    }
}

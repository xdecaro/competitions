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
        $syncStyleName = 'com_decarodcl.live-sync-style.runtime';
        $syncScriptName = 'com_decarodcl.live-sync.runtime';
        $scopeScriptName = 'com_decarodcl.scope.runtime';

        if (!$wa->assetExists('style', $styleName)) {
            $wa->registerStyle($styleName, 'com_decarodcl/admin.css', ['version' => '0.10.0']);
        }

        if (!$wa->assetExists('style', $syncStyleName)) {
            $wa->registerStyle($syncStyleName, 'com_decarodcl/live-sync.css', ['version' => '0.10.0']);
        }

        if (!$wa->assetExists('script', $syncScriptName)) {
            $wa->registerScript(
                $syncScriptName,
                'com_decarodcl/live-sync.js',
                ['version' => '0.10.0'],
                ['defer' => true]
            );
        }

        if (!$wa->assetExists('script', $scopeScriptName)) {
            $wa->registerScript(
                $scopeScriptName,
                'com_decarodcl/scope.js',
                ['version' => '0.10.0'],
                ['defer' => true]
            );
        }

        $token = Session::getFormToken();
        $document->addScriptOptions('com_decarodcl.liveSync', [
            'endpoint' => 'index.php?option=com_decarodcl&task=sync.poll&format=json',
            'token' => $token,
            'interval' => 5000,
            'strings' => [
                'presence' => Text::_('COM_DECARODCL_LIVE_PRESENCE'),
                'conflict' => Text::_('COM_DECARODCL_LIVE_CONFLICT'),
                'reload' => Text::_('COM_DECARODCL_LIVE_RELOAD'),
            ],
        ]);
        $document->addScriptOptions('com_decarodcl.scope', [
            'endpoint' => 'index.php?option=com_decarodcl&task=scope.eligibleTeams&format=json',
            'token' => $token,
            'strings' => [
                'selectSeason' => Text::_('COM_DECARODCL_PARTICIPATION_SELECT_SEASON_FIRST'),
                'loading' => Text::_('COM_DECARODCL_PARTICIPATION_LOADING_TEAMS'),
                'noTeams' => Text::_('COM_DECARODCL_PARTICIPATION_NO_ELIGIBLE_TEAMS'),
                'selectTeam' => Text::_('JSELECT'),
            ],
        ]);

        $wa->useStyle($styleName);
        $wa->useStyle($syncStyleName);
        $wa->useScript($syncScriptName);
        $wa->useScript($scopeScriptName);
    }
}

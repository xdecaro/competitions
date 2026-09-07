<?php
namespace Xdecaro\Component\Decarodcl\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;

final class UiHelper
{
    private const VERSION = '0.10.4';

    public static function loadAssets(): void
    {
        LanguageHelper::load();

        $document = Factory::getApplication()->getDocument();
        $wa = $document->getWebAssetManager();
        $styleName = 'com_decarodcl.admin.runtime';
        $filterStyleName = 'com_decarodcl.filterbar-style.runtime';
        $filterScriptName = 'com_decarodcl.filterbar.runtime';
        $syncStyleName = 'com_decarodcl.live-sync-style.runtime';
        $syncScriptName = 'com_decarodcl.live-sync.runtime';
        $scopeScriptName = 'com_decarodcl.scope.runtime';

        if (!$wa->assetExists('style', $styleName)) {
            $wa->registerStyle($styleName, 'com_decarodcl/admin.css', ['version' => self::VERSION]);
        }

        if (!$wa->assetExists('style', $filterStyleName)) {
            $wa->registerStyle(
                $filterStyleName,
                'com_decarodcl/filterbar.css',
                ['version' => self::VERSION],
                [],
                [$styleName]
            );
        }

        if (!$wa->assetExists('style', $syncStyleName)) {
            $wa->registerStyle($syncStyleName, 'com_decarodcl/live-sync.css', ['version' => self::VERSION]);
        }

        if (!$wa->assetExists('script', $filterScriptName)) {
            $wa->registerScript(
                $filterScriptName,
                'com_decarodcl/filterbar.js',
                ['version' => self::VERSION],
                ['defer' => true]
            );
        }

        if (!$wa->assetExists('script', $syncScriptName)) {
            $wa->registerScript(
                $syncScriptName,
                'com_decarodcl/live-sync.js',
                ['version' => self::VERSION],
                ['defer' => true]
            );
        }

        if (!$wa->assetExists('script', $scopeScriptName)) {
            $wa->registerScript(
                $scopeScriptName,
                'com_decarodcl/scope.js',
                ['version' => self::VERSION],
                ['defer' => true]
            );
        }

        $token = Session::getFormToken();
        $document->addScriptOptions('com_decarodcl.filterbar', [
            'strings' => [
                'filters' => Text::_('COM_DECARODCL_FILTERBAR_FILTERS'),
                'clear' => Text::_('COM_DECARODCL_FILTERBAR_CLEAR'),
                'show' => Text::_('COM_DECARODCL_FILTERBAR_SHOW'),
                'hide' => Text::_('COM_DECARODCL_FILTERBAR_HIDE'),
                'remove' => Text::_('COM_DECARODCL_FILTERBAR_REMOVE'),
            ],
        ]);
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
        $wa->useStyle($filterStyleName);
        $wa->useStyle($syncStyleName);
        $wa->useScript($filterScriptName);
        $wa->useScript($syncScriptName);
        $wa->useScript($scopeScriptName);
    }
}

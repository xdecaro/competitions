<?php
namespace Xdecaro\Component\Competitions\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;

final class UiHelper
{
    private const VERSION = '0.12.0';

    public static function loadAssets(): void
    {
        LanguageHelper::load();

        $document = Factory::getApplication()->getDocument();
        $wa = $document->getWebAssetManager();
        $styleName = 'com_xdecarocompetitions.admin.runtime';
        $filterStyleName = 'com_xdecarocompetitions.filterbar-style.runtime';
        $filterScriptName = 'com_xdecarocompetitions.filterbar.runtime';
        $syncStyleName = 'com_xdecarocompetitions.live-sync-style.runtime';
        $syncScriptName = 'com_xdecarocompetitions.live-sync.runtime';
        $scopeScriptName = 'com_xdecarocompetitions.scope.runtime';

        if (!$wa->assetExists('style', $styleName)) {
            $wa->registerStyle($styleName, 'com_xdecarocompetitions/admin.css', ['version' => self::VERSION]);
        }

        if (!$wa->assetExists('style', $filterStyleName)) {
            $wa->registerStyle(
                $filterStyleName,
                'com_xdecarocompetitions/filterbar.css',
                ['version' => self::VERSION],
                [],
                [$styleName]
            );
        }

        if (!$wa->assetExists('style', $syncStyleName)) {
            $wa->registerStyle($syncStyleName, 'com_xdecarocompetitions/live-sync.css', ['version' => self::VERSION]);
        }

        if (!$wa->assetExists('script', $filterScriptName)) {
            $wa->registerScript(
                $filterScriptName,
                'com_xdecarocompetitions/filterbar.js',
                ['version' => self::VERSION],
                ['defer' => true]
            );
        }

        if (!$wa->assetExists('script', $syncScriptName)) {
            $wa->registerScript(
                $syncScriptName,
                'com_xdecarocompetitions/live-sync.js',
                ['version' => self::VERSION],
                ['defer' => true]
            );
        }

        if (!$wa->assetExists('script', $scopeScriptName)) {
            $wa->registerScript(
                $scopeScriptName,
                'com_xdecarocompetitions/scope.js',
                ['version' => self::VERSION],
                ['defer' => true]
            );
        }

        $token = Session::getFormToken();
        $document->addScriptOptions('com_xdecarocompetitions.filterbar', [
            'strings' => [
                'filters' => Text::_('COM_XDECAROCOMPETITIONS_FILTERBAR_FILTERS'),
                'clear' => Text::_('COM_XDECAROCOMPETITIONS_FILTERBAR_CLEAR'),
                'show' => Text::_('COM_XDECAROCOMPETITIONS_FILTERBAR_SHOW'),
                'hide' => Text::_('COM_XDECAROCOMPETITIONS_FILTERBAR_HIDE'),
                'remove' => Text::_('COM_XDECAROCOMPETITIONS_FILTERBAR_REMOVE'),
            ],
        ]);
        $document->addScriptOptions('com_xdecarocompetitions.liveSync', [
            'endpoint' => 'index.php?option=com_xdecarocompetitions&task=sync.poll&format=json',
            'token' => $token,
            'interval' => 5000,
            'strings' => [
                'presence' => Text::_('COM_XDECAROCOMPETITIONS_LIVE_PRESENCE'),
                'conflict' => Text::_('COM_XDECAROCOMPETITIONS_LIVE_CONFLICT'),
                'reload' => Text::_('COM_XDECAROCOMPETITIONS_LIVE_RELOAD'),
            ],
        ]);
        $document->addScriptOptions('com_xdecarocompetitions.scope', [
            'endpoint' => 'index.php?option=com_xdecarocompetitions&task=scope.eligibleTeams&format=json',
            'token' => $token,
            'strings' => [
                'selectSeason' => Text::_('COM_XDECAROCOMPETITIONS_PARTICIPATION_SELECT_SEASON_FIRST'),
                'loading' => Text::_('COM_XDECAROCOMPETITIONS_PARTICIPATION_LOADING_TEAMS'),
                'noTeams' => Text::_('COM_XDECAROCOMPETITIONS_PARTICIPATION_NO_ELIGIBLE_TEAMS'),
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

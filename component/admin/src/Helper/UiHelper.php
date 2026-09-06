<?php
namespace Xdecaro\Component\Decarodcl\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;

final class UiHelper
{
    public static function loadAssets(): void
    {
        $document = Factory::getApplication()->getDocument();
        $wa = $document->getWebAssetManager();
        $assetName = 'com_decarodcl.admin.runtime';

        if (!$wa->assetExists('style', $assetName)) {
            $wa->registerStyle($assetName, 'com_decarodcl/admin.css', ['version' => '0.5.0']);
        }

        $wa->useStyle($assetName);
    }
}

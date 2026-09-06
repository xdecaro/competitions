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

        // Register the administrator stylesheet explicitly instead of relying
        // on automatic extension-registry discovery. This avoids stale or
        // undiscovered Web Asset registry entries during component rendering.
        if (!$wa->assetExists('style', $assetName)) {
            $wa->registerStyle(
                $assetName,
                'com_decarodcl/admin.css',
                ['version' => '0.3.5']
            );
        }

        $wa->useStyle($assetName);
    }
}

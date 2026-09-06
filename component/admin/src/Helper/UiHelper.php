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

        if (!$wa->assetExists('style', 'com_decarodcl.admin')) {
            $wa->getRegistry()->addExtensionRegistryFile('com_decarodcl');
        }

        // Defensive fallback: keep the administrator usable even if the
        // extension asset registry has not been discovered yet.
        if (!$wa->assetExists('style', 'com_decarodcl.admin')) {
            $wa->registerStyle(
                'com_decarodcl.admin',
                'com_decarodcl/css/admin.css',
                ['version' => '0.3.4']
            );
        }

        $wa->useStyle('com_decarodcl.admin');
    }
}

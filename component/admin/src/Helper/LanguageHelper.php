<?php
namespace Xdecaro\Component\Decarodcl\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;

final class LanguageHelper
{
    public static function load(): void
    {
        $language = Factory::getApplication()->getLanguage();
        $language->load('com_decarodcl.070', JPATH_ADMINISTRATOR, null, true);
        $language->load('com_decarodcl.071', JPATH_ADMINISTRATOR, null, true);
        $language->load('com_decarodcl.080', JPATH_ADMINISTRATOR, null, true);
    }
}

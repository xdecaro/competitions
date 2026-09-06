<?php
namespace Xdecaro\Component\Decarodcl\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;

final class LanguageHelper
{
    public static function load(): void
    {
        Factory::getApplication()
            ->getLanguage()
            ->load('com_decarodcl.070', JPATH_ADMINISTRATOR, null, true);
    }
}

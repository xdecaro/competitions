<?php
namespace Xdecaro\Component\Competitions\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;

final class LanguageHelper
{
    public static function load(): void
    {
        $language = Factory::getApplication()->getLanguage();
        $language->load('com_xdecarocompetitions.070', JPATH_ADMINISTRATOR, null, true);
        $language->load('com_xdecarocompetitions.071', JPATH_ADMINISTRATOR, null, true);
        $language->load('com_xdecarocompetitions.080', JPATH_ADMINISTRATOR, null, true);
        $language->load('com_xdecarocompetitions.082', JPATH_ADMINISTRATOR, null, true);
        $language->load('com_xdecarocompetitions.090', JPATH_ADMINISTRATOR, null, true);
        $language->load('com_xdecarocompetitions.100', JPATH_ADMINISTRATOR, null, true);
        $language->load('com_xdecarocompetitions.121', JPATH_ADMINISTRATOR, null, true);
    }
}

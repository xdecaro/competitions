<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;

/**
 * Safe transition from the old DCL 0.1/0.2 package.
 *
 * Legacy DCL Core is disabled, never deleted. Existing DCL tables and data are
 * preserved. The old package row is intentionally left untouched.
 */
final class PkgDecarodclInstallerScript
{
    public function postflight($type, $parent): void
    {
        try {
            /** @var DatabaseInterface $db */
            $db = Factory::getContainer()->get(DatabaseInterface::class);

            $query = $db->getQuery(true)
                ->update($db->quoteName('#__extensions'))
                ->set($db->quoteName('enabled') . ' = 1')
                ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
                ->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('decarodcl'));
            $db->setQuery($query)->execute();

            $query = $db->getQuery(true)
                ->update($db->quoteName('#__extensions'))
                ->set($db->quoteName('enabled') . ' = 0')
                ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
                ->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('dclcore'));
            $db->setQuery($query)->execute();
        } catch (Throwable $e) {
            Log::add('DCL package transition warning: ' . $e->getMessage(), Log::WARNING, 'dcl');
        }
    }
}

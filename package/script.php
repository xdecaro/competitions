<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;

final class PkgXdecarocompetitionsInstallerScript
{
    public function postflight($type, $parent): void
    {
        if (!in_array((string) $type, ['install', 'discover_install'], true)) {
            return;
        }

        try {
            /** @var DatabaseInterface $db */
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            foreach ([['system','xdecarocompetitions'],['xdecaroanalytics','competitions'],['task','xdecarocompetitions']] as [$folder,$element]) {
                $query = $db->getQuery(true)
                    ->update($db->quoteName('#__extensions'))
                    ->set($db->quoteName('enabled') . ' = 1')
                    ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
                    ->where($db->quoteName('folder') . ' = ' . $db->quote($folder))
                    ->where($db->quoteName('element') . ' = ' . $db->quote($element));
                $db->setQuery($query)->execute();
            }
        } catch (Throwable $e) {
            Log::add('Competitions package postflight warning: ' . $e->getMessage(), Log::WARNING, 'competitions');
        }
    }
}

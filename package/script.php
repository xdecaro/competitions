<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Safe transition and update-site repair for the Competitions package.
 *
 * Legacy DCL Core is disabled, never deleted. Existing DCL tables and data are
 * preserved. The package update server association is repaired on every
 * install/update so Joomla can discover future Competitions releases.
 */
final class PkgDecarodclInstallerScript
{
    private const UPDATE_SITE_NAME = 'Competitions Package Updates';
    private const UPDATE_SITE_URL = 'https://raw.githubusercontent.com/xdecaro/dcl/main/updates/pkg_decarodcl.xml';

    public function postflight($type, $parent): void
    {
        try {
            /** @var DatabaseInterface $db */
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $this->enableCurrentPlugin($db);
            $this->disableLegacyPlugin($db);
            $this->ensureUpdateSite($db);
        } catch (Throwable $e) {
            Log::add('Competitions package postflight warning: ' . $e->getMessage(), Log::WARNING, 'dcl');
        }
    }

    private function enableCurrentPlugin(DatabaseInterface $db): void
    {
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__extensions'))
            ->set($db->quoteName('enabled') . ' = 1')
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('decarodcl'));
        $db->setQuery($query)->execute();
    }

    private function disableLegacyPlugin(DatabaseInterface $db): void
    {
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__extensions'))
            ->set($db->quoteName('enabled') . ' = 0')
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('dclcore'));
        $db->setQuery($query)->execute();
    }

    private function ensureUpdateSite(DatabaseInterface $db): void
    {
        $query = $db->getQuery(true)
            ->select($db->quoteName('extension_id'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('package'))
            ->where('(' . $db->quoteName('element') . ' = ' . $db->quote('pkg_decarodcl') . ' OR ' . $db->quoteName('name') . ' = ' . $db->quote('PKG_DECARODCL') . ')');
        $extensionId = (int) $db->setQuery($query, 0, 1)->loadResult();

        if ($extensionId <= 0) {
            Log::add('Competitions package update site was not associated because pkg_decarodcl was not found in #__extensions.', Log::WARNING, 'dcl');
            return;
        }

        $query = $db->getQuery(true)
            ->select($db->quoteName('update_site_id'))
            ->from($db->quoteName('#__update_sites'))
            ->where($db->quoteName('location') . ' = :location')
            ->bind(':location', self::UPDATE_SITE_URL);
        $updateSiteId = (int) $db->setQuery($query, 0, 1)->loadResult();

        if ($updateSiteId <= 0) {
            $query = $db->getQuery(true)
                ->insert($db->quoteName('#__update_sites'))
                ->columns([
                    $db->quoteName('name'),
                    $db->quoteName('type'),
                    $db->quoteName('location'),
                    $db->quoteName('enabled'),
                ])
                ->values(':name, :type, :location, 1')
                ->bind(':name', self::UPDATE_SITE_NAME)
                ->bind(':type', 'extension')
                ->bind(':location', self::UPDATE_SITE_URL);
            $db->setQuery($query)->execute();
            $updateSiteId = (int) $db->insertid();
        } else {
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__update_sites'))
                ->set($db->quoteName('name') . ' = :name')
                ->set($db->quoteName('type') . ' = :type')
                ->set($db->quoteName('enabled') . ' = 1')
                ->where($db->quoteName('update_site_id') . ' = :updateSiteId')
                ->bind(':name', self::UPDATE_SITE_NAME)
                ->bind(':type', 'extension')
                ->bind(':updateSiteId', $updateSiteId, ParameterType::INTEGER);
            $db->setQuery($query)->execute();
        }

        if ($updateSiteId <= 0) {
            throw new RuntimeException('Unable to create the Competitions update site.');
        }

        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__update_sites_extensions'))
            ->where($db->quoteName('update_site_id') . ' = :updateSiteId')
            ->where($db->quoteName('extension_id') . ' = :extensionId')
            ->bind(':updateSiteId', $updateSiteId, ParameterType::INTEGER)
            ->bind(':extensionId', $extensionId, ParameterType::INTEGER);

        if ((int) $db->setQuery($query)->loadResult() === 0) {
            $query = $db->getQuery(true)
                ->insert($db->quoteName('#__update_sites_extensions'))
                ->columns([$db->quoteName('update_site_id'), $db->quoteName('extension_id')])
                ->values(':updateSiteId, :extensionId')
                ->bind(':updateSiteId', $updateSiteId, ParameterType::INTEGER)
                ->bind(':extensionId', $extensionId, ParameterType::INTEGER);
            $db->setQuery($query)->execute();
        }
    }
}

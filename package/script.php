<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Safe transition and update-site repair for the Competitions package.
 *
 * Historical Joomla identifiers are preserved. This script only repairs
 * package/update metadata, enables the current lightweight system plugin and
 * disables the obsolete dclcore plugin. Sports tables and domain data are
 * never deleted or rewritten here.
 */
final class PkgDecarodclInstallerScript
{
    private const CURRENT_PACKAGE_ELEMENT = 'pkg_decarodcl';
    private const UPDATE_SITE_NAME = 'Competitions Package Updates';
    private const UPDATE_SITE_URL = 'https://raw.githubusercontent.com/xdecaro/competitions/main/updates/pkg_decarodcl.xml';
    private const LEGACY_UPDATE_SITE_URL = 'https://raw.githubusercontent.com/xdecaro/dcl/main/updates/pkg_decarodcl.xml';

    /** @var array<int, array{type:string,element:string,folder:?string,client_id:?int}> */
    private const CURRENT_CHILDREN = [
        ['type' => 'component', 'element' => 'com_decarodcl', 'folder' => null, 'client_id' => 1],
        ['type' => 'plugin', 'element' => 'decarodcl', 'folder' => 'system', 'client_id' => 0],
        ['type' => 'module', 'element' => 'mod_dcl_matchtimeline', 'folder' => null, 'client_id' => 0],
        ['type' => 'module', 'element' => 'mod_dcl_countriesfederations', 'folder' => null, 'client_id' => 0],
    ];

    public function postflight($type, $parent): void
    {
        try {
            /** @var DatabaseInterface $db */
            $db = Factory::getContainer()->get(DatabaseInterface::class);

            $canonicalPackageId = $this->getCanonicalPackageId($db);

            if ($canonicalPackageId > 0) {
                $this->normalizeCurrentChildren($db, $canonicalPackageId);
                $this->cleanupLegacyPackageMetadata($db, $canonicalPackageId);
            } else {
                Log::add(
                    'Competitions package metadata repair skipped because pkg_decarodcl was not found in #__extensions.',
                    Log::WARNING,
                    'dcl'
                );
            }

            $this->enableCurrentPlugin($db);
            $this->disableLegacyPlugin($db);
            $this->ensureUpdateSite($db, $canonicalPackageId);
            $this->removeLegacyUpdateSiteAssociations($db, $canonicalPackageId);
            $this->removeUnusedLegacyUpdateSites($db);
        } catch (Throwable $e) {
            Log::add('Competitions package postflight warning: ' . $e->getMessage(), Log::WARNING, 'dcl');
        }
    }

    private function getCanonicalPackageId(DatabaseInterface $db): int
    {
        $query = $db->getQuery(true)
            ->select($db->quoteName('extension_id'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('package'))
            ->where($db->quoteName('element') . ' = ' . $db->quote(self::CURRENT_PACKAGE_ELEMENT))
            ->order($db->quoteName('extension_id') . ' DESC');

        return (int) $db->setQuery($query, 0, 1)->loadResult();
    }

    private function normalizeCurrentChildren(DatabaseInterface $db, int $packageId): void
    {
        foreach (self::CURRENT_CHILDREN as $child) {
            // Joomla DatabaseQuery::bind() binds by reference. Always bind local
            // variables rather than array-access expressions.
            $childType = $child['type'];
            $childElement = $child['element'];

            $query = $db->getQuery(true)
                ->update($db->quoteName('#__extensions'))
                ->set($db->quoteName('package_id') . ' = :packageId')
                ->where($db->quoteName('type') . ' = :type')
                ->where($db->quoteName('element') . ' = :element')
                ->bind(':packageId', $packageId, ParameterType::INTEGER)
                ->bind(':type', $childType)
                ->bind(':element', $childElement);

            if ($child['folder'] !== null) {
                $folder = $child['folder'];
                $query->where($db->quoteName('folder') . ' = :folder')->bind(':folder', $folder);
            }

            if ($child['client_id'] !== null) {
                $clientId = $child['client_id'];
                $query->where($db->quoteName('client_id') . ' = :clientId')
                    ->bind(':clientId', $clientId, ParameterType::INTEGER);
            }

            $db->setQuery($query)->execute();
        }
    }

    private function cleanupLegacyPackageMetadata(DatabaseInterface $db, int $canonicalPackageId): void
    {
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('extension_id'),
                $db->quoteName('element'),
                $db->quoteName('name'),
            ])
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('package'))
            ->where($db->quoteName('extension_id') . ' <> :canonicalPackageId')
            ->where(
                '('
                . $db->quoteName('element') . ' IN (' . $db->quote('pkg_decarodcl') . ', ' . $db->quote('pkg_dcl') . ')'
                . ' OR ' . $db->quoteName('name') . ' IN (' . $db->quote('PKG_DECARODCL') . ', ' . $db->quote('PKG_DCL') . ')'
                . ')'
            )
            ->bind(':canonicalPackageId', $canonicalPackageId, ParameterType::INTEGER);

        $legacyPackages = (array) $db->setQuery($query)->loadObjectList();

        foreach ($legacyPackages as $legacyPackage) {
            $legacyId = (int) ($legacyPackage->extension_id ?? 0);

            if ($legacyId <= 0) {
                continue;
            }

            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('package_id') . ' = :legacyId')
                ->bind(':legacyId', $legacyId, ParameterType::INTEGER);
            $childCount = (int) $db->setQuery($query)->loadResult();

            if ($childCount > 0) {
                Log::add(
                    sprintf(
                        'Legacy Competitions package metadata %d was preserved because %d extension(s) still reference it.',
                        $legacyId,
                        $childCount
                    ),
                    Log::WARNING,
                    'dcl'
                );
                continue;
            }

            $this->deleteExtensionUpdateMetadata($db, $legacyId);

            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__extensions'))
                ->where($db->quoteName('extension_id') . ' = :legacyId')
                ->where($db->quoteName('type') . ' = ' . $db->quote('package'))
                ->bind(':legacyId', $legacyId, ParameterType::INTEGER);
            $db->setQuery($query)->execute();

            Log::add(
                sprintf('Removed orphan legacy Competitions package metadata %d without uninstalling child extensions or data.', $legacyId),
                Log::INFO,
                'dcl'
            );
        }
    }

    private function deleteExtensionUpdateMetadata(DatabaseInterface $db, int $extensionId): void
    {
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__update_sites_extensions'))
            ->where($db->quoteName('extension_id') . ' = :extensionId')
            ->bind(':extensionId', $extensionId, ParameterType::INTEGER);
        $db->setQuery($query)->execute();

        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__updates'))
            ->where($db->quoteName('extension_id') . ' = :extensionId')
            ->bind(':extensionId', $extensionId, ParameterType::INTEGER);
        $db->setQuery($query)->execute();
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

    private function ensureUpdateSite(DatabaseInterface $db, int $extensionId): void
    {
        if ($extensionId <= 0) {
            return;
        }

        $location = self::UPDATE_SITE_URL;
        $legacyLocation = self::LEGACY_UPDATE_SITE_URL;
        $siteName = self::UPDATE_SITE_NAME;
        $siteType = 'extension';

        $query = $db->getQuery(true)
            ->select($db->quoteName('update_site_id'))
            ->from($db->quoteName('#__update_sites'))
            ->where($db->quoteName('location') . ' = :location')
            ->bind(':location', $location);
        $updateSiteId = (int) $db->setQuery($query, 0, 1)->loadResult();

        if ($updateSiteId <= 0) {
            $query = $db->getQuery(true)
                ->select($db->quoteName('update_site_id'))
                ->from($db->quoteName('#__update_sites'))
                ->where($db->quoteName('location') . ' = :legacyLocation')
                ->bind(':legacyLocation', $legacyLocation);
            $updateSiteId = (int) $db->setQuery($query, 0, 1)->loadResult();
        }

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
                ->bind(':name', $siteName)
                ->bind(':type', $siteType)
                ->bind(':location', $location);
            $db->setQuery($query)->execute();
            $updateSiteId = (int) $db->insertid();
        } else {
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__update_sites'))
                ->set($db->quoteName('name') . ' = :name')
                ->set($db->quoteName('type') . ' = :type')
                ->set($db->quoteName('location') . ' = :location')
                ->set($db->quoteName('enabled') . ' = 1')
                ->where($db->quoteName('update_site_id') . ' = :updateSiteId')
                ->bind(':name', $siteName)
                ->bind(':type', $siteType)
                ->bind(':location', $location)
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

    private function removeLegacyUpdateSiteAssociations(DatabaseInterface $db, int $extensionId): void
    {
        if ($extensionId <= 0) {
            return;
        }

        $legacyLocation = self::LEGACY_UPDATE_SITE_URL;
        $query = $db->getQuery(true)
            ->select($db->quoteName('update_site_id'))
            ->from($db->quoteName('#__update_sites'))
            ->where($db->quoteName('location') . ' = :legacyLocation')
            ->bind(':legacyLocation', $legacyLocation);
        $legacyIds = array_map('intval', (array) $db->setQuery($query)->loadColumn());

        foreach ($legacyIds as $legacyId) {
            if ($legacyId <= 0) {
                continue;
            }

            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__update_sites_extensions'))
                ->where($db->quoteName('update_site_id') . ' = :legacyId')
                ->where($db->quoteName('extension_id') . ' = :extensionId')
                ->bind(':legacyId', $legacyId, ParameterType::INTEGER)
                ->bind(':extensionId', $extensionId, ParameterType::INTEGER);
            $db->setQuery($query)->execute();
        }
    }

    private function removeUnusedLegacyUpdateSites(DatabaseInterface $db): void
    {
        $legacyLocation = self::LEGACY_UPDATE_SITE_URL;
        $query = $db->getQuery(true)
            ->select($db->quoteName('update_site_id'))
            ->from($db->quoteName('#__update_sites'))
            ->where($db->quoteName('location') . ' = :legacyLocation')
            ->bind(':legacyLocation', $legacyLocation);
        $legacyIds = array_map('intval', (array) $db->setQuery($query)->loadColumn());

        foreach ($legacyIds as $legacyId) {
            if ($legacyId <= 0) {
                continue;
            }

            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__update_sites_extensions'))
                ->where($db->quoteName('update_site_id') . ' = :legacyId')
                ->bind(':legacyId', $legacyId, ParameterType::INTEGER);

            if ((int) $db->setQuery($query)->loadResult() !== 0) {
                continue;
            }

            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__update_sites'))
                ->where($db->quoteName('update_site_id') . ' = :legacyId')
                ->bind(':legacyId', $legacyId, ParameterType::INTEGER);
            $db->setQuery($query)->execute();
        }
    }
}

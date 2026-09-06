<?php
namespace Xdecaro\Component\Decarodcl\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Version;
use Joomla\Database\ParameterType;

final class InformationModel extends BaseDatabaseModel
{
    public const UPDATE_SITE_URL = 'https://raw.githubusercontent.com/xdecaro/dcl/main/updates/pkg_decarodcl.xml';

    public function getInfo(): array
    {
        $db = $this->getDatabase();

        $component = $this->getExtension('component', 'com_decarodcl');
        $package = $this->getExtension('package', 'pkg_decarodcl');
        $plugin = $this->getExtension('plugin', 'decarodcl', 'system');
        $timelineModule = $this->getExtension('module', 'mod_dcl_matchtimeline', null, 0);
        $countriesModule = $this->getExtension('module', 'mod_dcl_countriesfederations', null, 0);

        $versions = [
            'package' => $this->getManifestVersion($package),
            'component' => $this->getManifestVersion($component),
            'plugin' => $this->getManifestVersion($plugin),
            'timeline_module' => $this->getManifestVersion($timelineModule),
            'countries_module' => $this->getManifestVersion($countriesModule),
        ];

        $installedVersion = $versions['package'] ?: $versions['component'] ?: '0.0.0';
        $installationConsistent = $installedVersion !== '0.0.0';

        foreach ($versions as $version) {
            if ($version === '' || $version !== $installedVersion) {
                $installationConsistent = false;
                break;
            }
        }

        $update = null;
        $updateSite = null;

        if ($package !== null) {
            $extensionId = (int) $package->extension_id;
            $location = self::UPDATE_SITE_URL;

            $query = $db->getQuery(true)
                ->select([$db->quoteName('version'), $db->quoteName('detailsurl')])
                ->from($db->quoteName('#__updates'))
                ->where($db->quoteName('extension_id') . ' = :extensionId')
                ->bind(':extensionId', $extensionId, ParameterType::INTEGER)
                ->order($db->quoteName('update_id') . ' DESC');
            $update = $db->setQuery($query, 0, 1)->loadObject();

            $query = $db->getQuery(true)
                ->select([
                    $db->quoteName('s.update_site_id'),
                    $db->quoteName('s.name'),
                    $db->quoteName('s.location'),
                    $db->quoteName('s.enabled'),
                    $db->quoteName('s.last_check_timestamp'),
                ])
                ->from($db->quoteName('#__update_sites', 's'))
                ->innerJoin($db->quoteName('#__update_sites_extensions', 'm') . ' ON ' . $db->quoteName('m.update_site_id') . ' = ' . $db->quoteName('s.update_site_id'))
                ->where($db->quoteName('m.extension_id') . ' = :extensionId')
                ->where($db->quoteName('s.location') . ' = :location')
                ->bind(':extensionId', $extensionId, ParameterType::INTEGER)
                ->bind(':location', $location);
            $updateSite = $db->setQuery($query, 0, 1)->loadObject();
        }

        $latestVersion = $update !== null ? trim((string) $update->version) : '';
        $updateAvailable = $latestVersion !== '' && version_compare($latestVersion, $installedVersion, 'gt');

        return [
            'installed_version' => $installedVersion,
            'extension_versions' => $versions,
            'installation_consistent' => $installationConsistent,
            'latest_version' => $latestVersion,
            'update_available' => $updateAvailable,
            'update_site_enabled' => $updateSite !== null && (int) $updateSite->enabled === 1,
            'update_site_url' => self::UPDATE_SITE_URL,
            'last_check_timestamp' => $updateSite !== null ? (int) $updateSite->last_check_timestamp : 0,
            'joomla_version' => (new Version())->getShortVersion(),
            'php_version' => PHP_VERSION,
            'database_type' => method_exists($db, 'getServerType') ? (string) $db->getServerType() : (string) $db->getName(),
            'database_version' => (string) $db->getVersion(),
            'component_id' => 'com_decarodcl',
            'package_id' => 'pkg_decarodcl',
            'repository' => 'xdecaro/dcl',
        ];
    }

    private function getExtension(string $type, string $element, ?string $folder = null, ?int $clientId = null): ?object
    {
        $db = $this->getDatabase();
        $typeValue = $type;
        $elementValue = $element;

        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('extension_id'),
                $db->quoteName('manifest_cache'),
            ])
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = :type')
            ->where($db->quoteName('element') . ' = :element')
            ->bind(':type', $typeValue)
            ->bind(':element', $elementValue);

        if ($folder !== null) {
            $folderValue = $folder;
            $query->where($db->quoteName('folder') . ' = :folder')
                ->bind(':folder', $folderValue);
        }

        if ($clientId !== null) {
            $clientIdValue = $clientId;
            $query->where($db->quoteName('client_id') . ' = :clientId')
                ->bind(':clientId', $clientIdValue, ParameterType::INTEGER);
        }

        $row = $db->setQuery($query, 0, 1)->loadObject();

        return $row ?: null;
    }

    private function getManifestVersion(?object $extension): string
    {
        if ($extension === null || empty($extension->manifest_cache)) {
            return '';
        }

        $manifest = json_decode((string) $extension->manifest_cache, true);

        return is_array($manifest) ? trim((string) ($manifest['version'] ?? '')) : '';
    }
}

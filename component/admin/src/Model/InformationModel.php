<?php
namespace Xdecaro\Component\Decarodcl\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Version;
use Joomla\Database\ParameterType;
use Throwable;

final class InformationModel extends BaseDatabaseModel
{
    public const UPDATE_SITE_URL = 'https://raw.githubusercontent.com/xdecaro/competitions/main/updates/pkg_decarodcl.xml';
    public const MINIMUM_JOOMLA = '6.0.0';
    public const MINIMUM_PHP = '8.3.0';

    private const EXPECTED_TABLES = [
        '#__dcl_countries',
        '#__dcl_federations',
        '#__dcl_organizations',
        '#__dcl_zones',
        '#__dcl_tournaments',
        '#__dcl_seasons',
        '#__dcl_teams',
        '#__dcl_participations',
        '#__dcl_players',
        '#__dcl_rosters',
        '#__dcl_matches',
        '#__dcl_changes',
        '#__dcl_edit_sessions',
    ];

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

        $tableHealth = $this->getTableHealth();
        $tablesPresent = !empty($tableHealth['all_present']);
        $databaseAligned = $tablesPresent;

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
                ->innerJoin(
                    $db->quoteName('#__update_sites_extensions', 'm')
                    . ' ON ' . $db->quoteName('m.update_site_id') . ' = ' . $db->quoteName('s.update_site_id')
                )
                ->where($db->quoteName('m.extension_id') . ' = :extensionId')
                ->where($db->quoteName('s.location') . ' = :location')
                ->bind(':extensionId', $extensionId, ParameterType::INTEGER)
                ->bind(':location', $location);
            $updateSite = $db->setQuery($query, 0, 1)->loadObject();
        }

        $latestVersion = $update !== null ? trim((string) $update->version) : '';
        $updateAvailable = $latestVersion !== '' && version_compare($latestVersion, $installedVersion, 'gt');
        $lastCheckTimestamp = $updateSite !== null ? max(0, (int) $updateSite->last_check_timestamp) : 0;
        $updateSiteEnabled = $updateSite !== null && (int) $updateSite->enabled === 1;
        $updateState = $updateAvailable ? 'available' : ($updateSiteEnabled ? 'current' : 'inactive');

        $joomlaVersion = (new Version())->getShortVersion();
        $phpVersion = PHP_VERSION;
        $environmentCompatible = version_compare($joomlaVersion, self::MINIMUM_JOOMLA, '>=')
            && version_compare($phpVersion, self::MINIMUM_PHP, '>=');
        $packageDetected = $package !== null && $versions['package'] !== '';
        $pluginEnabled = $plugin !== null && (int) ($plugin->enabled ?? 0) === 1;

        $coreIntegration = $this->getCoreIntegration();
        $integrations = [
            $coreIntegration,
            $this->getRelatedComponent('Forms by xdecaro', 'com_decaroforms', 'xdecaro/forms', '#__decaroforms_forms'),
            $this->getRelatedComponent('Courses by xdecaro', 'com_decarocourses', 'xdecaro/courses', '#__decarocourses_courses'),
        ];

        $criticalIssues = [];
        if (!$installationConsistent) {
            $criticalIssues[] = 'versions';
        }
        if (!$databaseAligned) {
            $criticalIssues[] = 'database';
        }
        if (!$environmentCompatible) {
            $criticalIssues[] = 'environment';
        }
        if (!$packageDetected) {
            $criticalIssues[] = 'package';
        }

        $warnings = [];
        if (!$updateSiteEnabled) {
            $warnings[] = 'update_site';
        }
        if (!$pluginEnabled) {
            $warnings[] = 'plugin';
        }
        if ($updateAvailable) {
            $warnings[] = 'update_available';
        }
        if (!empty($coreIntegration['installed']) && empty($coreIntegration['api_available'])) {
            $warnings[] = 'core_api';
        }

        return [
            'installed_version' => $installedVersion,
            'extension_versions' => $versions,
            'installation_consistent' => $installationConsistent,
            'package_detected' => $packageDetected,
            'plugin_enabled' => $pluginEnabled,
            'latest_version' => $latestVersion,
            'update_available' => $updateAvailable,
            'update_state' => $updateState,
            'update_site_enabled' => $updateSiteEnabled,
            'update_site_url' => self::UPDATE_SITE_URL,
            'last_check_timestamp' => $lastCheckTimestamp,
            'joomla_version' => $joomlaVersion,
            'minimum_joomla' => self::MINIMUM_JOOMLA,
            'php_version' => $phpVersion,
            'minimum_php' => self::MINIMUM_PHP,
            'database_type' => method_exists($db, 'getServerType') ? (string) $db->getServerType() : (string) $db->getName(),
            'database_version' => (string) $db->getVersion(),
            'table_health' => $tableHealth,
            'tables_present' => $tablesPresent,
            'database_aligned' => $databaseAligned,
            'environment_compatible' => $environmentCompatible,
            'core' => $coreIntegration,
            'integrations' => $integrations,
            'critical_issues' => $criticalIssues,
            'warnings' => $warnings,
            'component_id' => 'com_decarodcl',
            'package_id' => 'pkg_decarodcl',
            'repository' => 'xdecaro/competitions',
        ];
    }

    private function getExtension(string $type, string $element, ?string $folder = null, ?int $clientId = null): ?object
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('extension_id'),
                $db->quoteName('manifest_cache'),
                $db->quoteName('enabled'),
                $db->quoteName('client_id'),
            ])
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = :type')
            ->where($db->quoteName('element') . ' = :element')
            ->bind(':type', $type)
            ->bind(':element', $element);

        if ($folder !== null) {
            $query->where($db->quoteName('folder') . ' = :folder')->bind(':folder', $folder);
        }
        if ($clientId !== null) {
            $query->where($db->quoteName('client_id') . ' = :clientId')->bind(':clientId', $clientId, ParameterType::INTEGER);
        }

        try {
            return $db->setQuery($query, 0, 1)->loadObject() ?: null;
        } catch (Throwable) {
            return null;
        }
    }

    private function getManifestVersion(?object $extension): string
    {
        if ($extension === null || empty($extension->manifest_cache)) {
            return '';
        }
        $manifest = json_decode((string) $extension->manifest_cache, true);
        return is_array($manifest) ? trim((string) ($manifest['version'] ?? '')) : '';
    }

    private function getTableHealth(): array
    {
        $db = $this->getDatabase();
        try {
            $available = array_flip($db->getTableList());
        } catch (Throwable) {
            $available = [];
        }

        $tables = [];
        foreach (self::EXPECTED_TABLES as $table) {
            $tables[$table] = isset($available[$db->replacePrefix($table)]);
        }

        $presentCount = count(array_filter($tables));
        return [
            'tables' => $tables,
            'present_count' => $presentCount,
            'expected_count' => count($tables),
            'all_present' => $presentCount === count($tables),
        ];
    }

    private function getCoreIntegration(): array
    {
        $package = $this->getExtension('package', 'pkg_xdecarocore');
        $version = $this->getManifestVersion($package);
        $versionClassAvailable = class_exists(\Xdecaro\Core\Version::class);

        if ($version === '' && $versionClassAvailable) {
            $version = trim((string) \Xdecaro\Core\Version::VERSION);
        }

        $apiAvailable = class_exists(\Xdecaro\Core\Integration\EntityReference::class)
            && class_exists(\Xdecaro\Core\Integration\RelationReference::class);

        return [
            'name' => 'Core by xdecaro',
            'element' => 'pkg_xdecarocore',
            'repository' => 'xdecaro/core',
            'installed' => $package !== null || $versionClassAvailable,
            'version' => $version,
            'available_count' => 0,
            'required' => false,
            'metric_type' => 'api',
            'api_available' => $apiAvailable,
        ];
    }

    private function getRelatedComponent(string $name, string $element, string $repository, string $countTable): array
    {
        $extension = $this->getExtension('component', $element);
        $count = 0;
        $db = $this->getDatabase();
        try {
            $realTable = $db->replacePrefix($countTable);
            if (in_array($realTable, $db->getTableList(), true)) {
                $count = (int) $db->setQuery(
                    $db->getQuery(true)->select('COUNT(*)')->from($db->quoteName($countTable))
                )->loadResult();
            }
        } catch (Throwable) {
            $count = 0;
        }

        return [
            'name' => $name,
            'element' => $element,
            'repository' => $repository,
            'installed' => $extension !== null,
            'version' => $this->getManifestVersion($extension),
            'available_count' => $count,
            'required' => false,
            'metric_type' => 'count',
        ];
    }
}

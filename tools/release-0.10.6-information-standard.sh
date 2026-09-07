#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OLD="0.10.5"
NEW="0.10.6"
cd "$ROOT"

python3 - <<'PY'
from pathlib import Path
import json, re

root = Path('.')
old = '0.10.5'
new = '0.10.6'

model = r'''<?php
namespace Xdecaro\Component\Decarodcl\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Version;
use Joomla\Database\ParameterType;
use Throwable;

final class InformationModel extends BaseDatabaseModel
{
    public const UPDATE_SITE_URL = 'https://raw.githubusercontent.com/xdecaro/dcl/main/updates/pkg_decarodcl.xml';
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
        $updateState = $updateAvailable ? 'available' : ($updateSiteEnabled && $lastCheckTimestamp > 0 ? 'current' : ($updateSiteEnabled ? 'current' : 'inactive'));

        $joomlaVersion = (new Version())->getShortVersion();
        $phpVersion = PHP_VERSION;
        $environmentCompatible = version_compare($joomlaVersion, self::MINIMUM_JOOMLA, '>=')
            && version_compare($phpVersion, self::MINIMUM_PHP, '>=');
        $packageDetected = $package !== null && $versions['package'] !== '';
        $pluginEnabled = $plugin !== null && (int) ($plugin->enabled ?? 0) === 1;

        $integrations = [
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
            'integrations' => $integrations,
            'critical_issues' => $criticalIssues,
            'warnings' => $warnings,
            'component_id' => 'com_decarodcl',
            'package_id' => 'pkg_decarodcl',
            'repository' => 'xdecaro/dcl',
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
        ];
    }
}
'''
(root / 'component/admin/src/Model/InformationModel.php').write_text(model, encoding='utf-8')

template = r'''<?php
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$info = $this->info;
$installedVersion = (string) ($info['installed_version'] ?? '0.0.0');
$versions = (array) ($info['extension_versions'] ?? []);
$installationConsistent = !empty($info['installation_consistent']);
$packageDetected = !empty($info['package_detected']);
$pluginEnabled = !empty($info['plugin_enabled']);
$tablesPresent = !empty($info['tables_present']);
$databaseAligned = !empty($info['database_aligned']);
$environmentCompatible = !empty($info['environment_compatible']);
$updateSiteEnabled = !empty($info['update_site_enabled']);
$updateAvailable = !empty($info['update_available']);
$updateState = (string) ($info['update_state'] ?? 'inactive');
$latestVersion = (string) ($info['latest_version'] ?? '');
$lastCheck = (int) ($info['last_check_timestamp'] ?? 0);
$tableHealth = (array) ($info['table_health'] ?? []);
$integrations = (array) ($info['integrations'] ?? []);
$criticalIssues = (array) ($info['critical_issues'] ?? []);
$warnings = (array) ($info['warnings'] ?? []);
$systemOk = count($criticalIssues) === 0;

$versionBadge = static function (string $version) use ($installedVersion): string {
    if ($version === '') {
        return '<span class="dcl-badge is-muted">' . Text::_('COM_DECARODCL_INFO_NOT_INSTALLED') . '</span>';
    }
    $class = $version === $installedVersion ? 'is-success' : 'is-warning';
    return '<span class="dcl-badge ' . $class . '">' . htmlspecialchars($version, ENT_QUOTES, 'UTF-8') . '</span>';
};

$updateLabelKey = match ($updateState) {
    'current' => 'COM_DECARODCL_INFO_UP_TO_DATE',
    'available' => 'COM_DECARODCL_INFO_UPDATE_AVAILABLE',
    default => 'COM_DECARODCL_INFO_INACTIVE',
};
$updateBadgeClass = match ($updateState) {
    'current' => 'is-success',
    'available' => 'is-warning',
    default => 'is-muted',
};

$diagnosticLines = [
    'Competitions ' . $installedVersion,
    'Joomla: ' . (string) ($info['joomla_version'] ?? '—'),
    'PHP: ' . (string) ($info['php_version'] ?? '—'),
    'Database: ' . trim((string) ($info['database_type'] ?? '') . ' ' . (string) ($info['database_version'] ?? '')),
    'Componente: ' . ((string) ($versions['component'] ?? '') ?: '—'),
    'Pacchetto: ' . ((string) ($versions['package'] ?? '') ?: '—'),
    'Plugin sistema: ' . ((string) ($versions['plugin'] ?? '') ?: '—'),
    'Modulo Timeline: ' . ((string) ($versions['timeline_module'] ?? '') ?: '—'),
    'Modulo Paesi/Federazioni: ' . ((string) ($versions['countries_module'] ?? '') ?: '—'),
    'Tabelle: ' . (int) ($tableHealth['present_count'] ?? 0) . '/' . (int) ($tableHealth['expected_count'] ?? 0),
    'Update server: ' . ($updateSiteEnabled ? 'attivo' : 'non attivo'),
    'Problemi critici: ' . count($criticalIssues),
    'Avvisi: ' . count($warnings),
];
$diagnosticText = implode("\n", $diagnosticLines);
?>
<div class="dcl-admin dcl-information-page">
    <?php if (!$systemOk) : ?>
        <div class="alert alert-danger dcl-information-note" role="alert">
            <strong><?= Text::_('COM_DECARODCL_INFO_ATTENTION_REQUIRED'); ?></strong>
            <?= Text::_('COM_DECARODCL_INFO_INSTALLATION_INCONSISTENT_DESC'); ?>
        </div>
    <?php elseif (!$updateSiteEnabled) : ?>
        <div class="alert alert-warning dcl-information-note" role="alert"><?= Text::_('COM_DECARODCL_INFO_UPDATE_SITE_WARNING'); ?></div>
    <?php elseif ($updateAvailable) : ?>
        <div class="alert alert-info dcl-information-note" role="status"><?= Text::sprintf('COM_DECARODCL_INFO_UPDATE_AVAILABLE_DESC', $this->escape($latestVersion)); ?></div>
    <?php endif; ?>

    <div class="dcl-information-summary" aria-label="<?= $this->escape(Text::_('COM_DECARODCL_INFO_QUICK_STATUS')); ?>">
        <strong>Competitions <?= $this->escape($installedVersion); ?></strong>
        <span class="dcl-badge <?= $updateBadgeClass; ?>"><?= Text::_($updateLabelKey); ?></span>
        <span class="dcl-badge <?= $systemOk ? 'is-success' : 'is-danger'; ?>"><?= Text::_($systemOk ? 'COM_DECARODCL_INFO_SYSTEM_OK' : 'COM_DECARODCL_INFO_SYSTEM_CHECK'); ?></span>
    </div>

    <div class="dcl-information-grid">
        <section class="dcl-card dcl-information-card">
            <div class="dcl-card-head">
                <div><span class="dcl-eyebrow"><?= Text::_('COM_DECARODCL_INFO_PRODUCT_SECTION'); ?></span><h2><?= Text::_('COM_DECARODCL_INFO_VERSIONS_TITLE'); ?></h2></div>
                <span class="dcl-badge <?= $installationConsistent ? 'is-success' : 'is-warning'; ?>"><?= Text::_($installationConsistent ? 'COM_DECARODCL_INFO_INSTALLATION_CONSISTENT' : 'COM_DECARODCL_INFO_INSTALLATION_INCONSISTENT'); ?></span>
            </div>
            <dl class="dcl-information-list">
                <div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_COMPONENT_VERSION'); ?></dt><dd><?= $versionBadge((string) ($versions['component'] ?? '')); ?></dd></div>
                <div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_PACKAGE_VERSION'); ?></dt><dd><?= $versionBadge((string) ($versions['package'] ?? '')); ?></dd></div>
                <div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_PLUGIN_VERSION'); ?></dt><dd><?= $versionBadge((string) ($versions['plugin'] ?? '')); ?></dd></div>
                <div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_COMPONENT_ID'); ?></dt><dd><code>com_decarodcl</code></dd></div>
                <div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_PACKAGE_ID'); ?></dt><dd><code>pkg_decarodcl</code></dd></div>
                <div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_DEVELOPER'); ?></dt><dd>Luca De Caro</dd></div>
                <div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_REPOSITORY'); ?></dt><dd><a href="https://github.com/xdecaro/dcl" target="_blank" rel="noopener noreferrer">xdecaro/dcl <span aria-hidden="true">↗</span></a></dd></div>
                <div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_LICENSE'); ?></dt><dd>GNU GPL v2 or later</dd></div>
            </dl>
        </section>

        <section class="dcl-card dcl-information-card">
            <div class="dcl-card-head"><div><span class="dcl-eyebrow"><?= Text::_('COM_DECARODCL_INFO_ENVIRONMENT_SECTION'); ?></span><h2><?= Text::_('COM_DECARODCL_INFO_SYSTEM_TITLE'); ?></h2></div><span class="dcl-badge <?= $environmentCompatible ? 'is-success' : 'is-danger'; ?>"><?= Text::_($environmentCompatible ? 'COM_DECARODCL_INFO_COMPATIBLE' : 'COM_DECARODCL_INFO_INCOMPATIBLE'); ?></span></div>
            <dl class="dcl-information-list">
                <div class="dcl-information-row"><dt>Joomla</dt><dd><?= $this->escape((string) ($info['joomla_version'] ?? '—')); ?></dd></div>
                <div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_MINIMUM_JOOMLA'); ?></dt><dd><?= $this->escape((string) ($info['minimum_joomla'] ?? '—')); ?></dd></div>
                <div class="dcl-information-row"><dt>PHP</dt><dd><?= $this->escape((string) ($info['php_version'] ?? '—')); ?></dd></div>
                <div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_MINIMUM_PHP'); ?></dt><dd><?= $this->escape((string) ($info['minimum_php'] ?? '—')); ?></dd></div>
                <div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_DATABASE'); ?></dt><dd><?= $this->escape(trim((string) ($info['database_type'] ?? '') . ' ' . (string) ($info['database_version'] ?? ''))); ?></dd></div>
                <div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_TABLES'); ?></dt><dd><span class="dcl-badge <?= $tablesPresent ? 'is-success' : 'is-danger'; ?>"><?= (int) ($tableHealth['present_count'] ?? 0); ?>/<?= (int) ($tableHealth['expected_count'] ?? 0); ?> <?= Text::_('COM_DECARODCL_INFO_PRESENT'); ?></span></dd></div>
                <div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_INSTALLATION_STATUS'); ?></dt><dd><span class="dcl-badge <?= $databaseAligned ? 'is-success' : 'is-danger'; ?>"><?= Text::_($databaseAligned ? 'COM_DECARODCL_INFO_ALIGNED' : 'COM_DECARODCL_INFO_CHECK_REQUIRED'); ?></span></dd></div>
            </dl>
        </section>

        <section class="dcl-card dcl-information-card">
            <div class="dcl-card-head"><div><span class="dcl-eyebrow"><?= Text::_('COM_DECARODCL_INFO_EXTENSIONS_SECTION'); ?></span><h2><?= Text::_('COM_DECARODCL_INFO_INCLUDED_EXTENSIONS'); ?></h2></div></div>
            <dl class="dcl-information-list">
                <div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_SYSTEM_PLUGIN'); ?></dt><dd><span class="dcl-badge <?= ($versions['plugin'] ?? '') !== '' ? 'is-success' : 'is-danger'; ?>"><?= Text::_(($versions['plugin'] ?? '') !== '' ? 'COM_DECARODCL_INFO_INSTALLED' : 'COM_DECARODCL_INFO_NOT_INSTALLED'); ?></span></dd></div>
                <div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_VERSION'); ?></dt><dd><?= $versionBadge((string) ($versions['plugin'] ?? '')); ?></dd></div>
                <div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_TIMELINE_MODULE'); ?></dt><dd><span class="dcl-badge <?= ($versions['timeline_module'] ?? '') !== '' ? 'is-success' : 'is-muted'; ?>"><?= Text::_(($versions['timeline_module'] ?? '') !== '' ? 'COM_DECARODCL_INFO_INSTALLED' : 'COM_DECARODCL_INFO_NOT_INSTALLED'); ?></span></dd></div>
                <div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_VERSION'); ?></dt><dd><?= $versionBadge((string) ($versions['timeline_module'] ?? '')); ?></dd></div>
                <div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_COUNTRIES_MODULE'); ?></dt><dd><span class="dcl-badge <?= ($versions['countries_module'] ?? '') !== '' ? 'is-success' : 'is-muted'; ?>"><?= Text::_(($versions['countries_module'] ?? '') !== '' ? 'COM_DECARODCL_INFO_INSTALLED' : 'COM_DECARODCL_INFO_NOT_INSTALLED'); ?></span></dd></div>
                <div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_VERSION'); ?></dt><dd><?= $versionBadge((string) ($versions['countries_module'] ?? '')); ?></dd></div>
            </dl>
        </section>

        <section class="dcl-card dcl-information-card">
            <div class="dcl-card-head"><div><span class="dcl-eyebrow"><?= Text::_('COM_DECARODCL_INFO_UPDATES_SECTION'); ?></span><h2><?= Text::_('COM_DECARODCL_INFO_UPDATE_CHANNEL_TITLE'); ?></h2></div><span class="dcl-badge <?= $updateSiteEnabled ? 'is-success' : 'is-danger'; ?>"><?= Text::_($updateSiteEnabled ? 'COM_DECARODCL_INFO_ACTIVE' : 'COM_DECARODCL_INFO_INACTIVE'); ?></span></div>
            <dl class="dcl-information-list">
                <div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_UPDATE_SERVER_STATUS'); ?></dt><dd><span class="dcl-badge <?= $updateSiteEnabled ? 'is-success' : 'is-danger'; ?>"><?= Text::_($updateSiteEnabled ? 'COM_DECARODCL_INFO_CONFIGURED' : 'COM_DECARODCL_INFO_NOT_CONFIGURED'); ?></span></dd></div>
                <div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_CHANNEL'); ?></dt><dd><?= Text::_('COM_DECARODCL_INFO_STABLE'); ?></dd></div>
                <div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_INSTALLED_VERSION'); ?></dt><dd><?= $this->escape($installedVersion); ?></dd></div>
                <div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_LATEST_DETECTED'); ?></dt><dd><?= $latestVersion !== '' ? $this->escape($latestVersion) : Text::_('COM_DECARODCL_INFO_NO_UPDATE_DETECTED'); ?></dd></div>
                <div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_LAST_CHECK'); ?></dt><dd><?= $lastCheck > 0 ? $this->escape(date('d/m/Y H:i', $lastCheck)) : Text::_('COM_DECARODCL_INFO_NEVER'); ?></dd></div>
                <div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_STATUS'); ?></dt><dd><span class="dcl-badge <?= $updateBadgeClass; ?>"><?= Text::_($updateLabelKey); ?></span></dd></div>
            </dl>
            <?php if ($this->canManageInstaller) : ?><div class="dcl-information-actions"><a class="btn btn-primary" href="<?= Route::_('index.php?option=com_installer&view=update'); ?>"><?= Text::_('COM_DECARODCL_INFO_OPEN_UPDATES'); ?></a><a class="btn btn-secondary" href="<?= Route::_('index.php?option=com_installer&view=updatesites'); ?>"><?= Text::_('COM_DECARODCL_INFO_OPEN_UPDATE_SITES'); ?></a></div><?php endif; ?>
        </section>

        <section class="dcl-card dcl-information-card dcl-field-span-2 dcl-information-full">
            <div class="dcl-card-head"><div><span class="dcl-eyebrow"><?= Text::_('COM_DECARODCL_INFO_INTEGRATIONS_SECTION'); ?></span><h2><?= Text::_('COM_DECARODCL_INFO_CONNECTED_COMPONENTS'); ?></h2></div></div>
            <p class="dcl-information-intro"><?= Text::_('COM_DECARODCL_INFO_CONNECTED_COMPONENTS_DESC'); ?></p>
            <div class="dcl-information-integrations">
                <?php foreach ($integrations as $integration) : $installed = !empty($integration['installed']); ?>
                    <article class="dcl-information-integration">
                        <div class="dcl-information-integration-main"><strong><?= $this->escape((string) ($integration['name'] ?? '')); ?></strong><span><?= Text::_('COM_DECARODCL_INFO_OPTIONAL_INTEGRATION'); ?> · <code><?= $this->escape((string) ($integration['element'] ?? '')); ?></code></span></div>
                        <div class="dcl-information-integration-metrics"><div><small><?= Text::_('COM_DECARODCL_INFO_INSTALLED_VERSION'); ?></small><strong><?= $installed && (string) ($integration['version'] ?? '') !== '' ? $this->escape((string) $integration['version']) : '—'; ?></strong></div><div><small><?= Text::_('COM_DECARODCL_INFO_AVAILABLE_MODULES'); ?></small><strong><?= (int) ($integration['available_count'] ?? 0); ?></strong></div></div>
                        <div class="dcl-information-integration-badges"><span class="dcl-badge <?= $installed ? 'is-success' : 'is-muted'; ?>"><?= Text::_($installed ? 'COM_DECARODCL_INFO_INSTALLED' : 'COM_DECARODCL_INFO_NOT_INSTALLED'); ?></span><span class="dcl-badge is-muted"><?= Text::_('COM_DECARODCL_INFO_OPTIONAL'); ?></span></div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="dcl-card dcl-information-card dcl-field-span-2 dcl-information-full">
            <div class="dcl-information-diagnostic-head"><div><span class="dcl-eyebrow"><?= Text::_('COM_DECARODCL_INFO_DIAGNOSTICS_SECTION'); ?></span><h2><?= Text::_('COM_DECARODCL_INFO_HEALTH_TITLE'); ?></h2><p><?= Text::_('COM_DECARODCL_INFO_DIAGNOSTICS_PRIVACY'); ?></p></div><span class="dcl-badge <?= $systemOk ? 'is-success' : 'is-danger'; ?>"><?= Text::_($systemOk ? 'COM_DECARODCL_INFO_NO_CRITICAL_ISSUES' : 'COM_DECARODCL_INFO_ATTENTION_REQUIRED'); ?></span></div>
            <div class="dcl-information-checks">
                <div class="dcl-information-check <?= $installationConsistent ? 'is-ok' : 'is-error'; ?>"><span aria-hidden="true"><?= $installationConsistent ? '✓' : '!'; ?></span><?= Text::_('COM_DECARODCL_INFO_CHECK_VERSIONS'); ?></div>
                <div class="dcl-information-check <?= $databaseAligned ? 'is-ok' : 'is-error'; ?>"><span aria-hidden="true"><?= $databaseAligned ? '✓' : '!'; ?></span><?= Text::_('COM_DECARODCL_INFO_CHECK_DATABASE'); ?></div>
                <div class="dcl-information-check <?= $tablesPresent ? 'is-ok' : 'is-error'; ?>"><span aria-hidden="true"><?= $tablesPresent ? '✓' : '!'; ?></span><?= Text::_('COM_DECARODCL_INFO_CHECK_TABLES'); ?></div>
                <div class="dcl-information-check <?= $updateSiteEnabled ? 'is-ok' : 'is-warning'; ?>"><span aria-hidden="true"><?= $updateSiteEnabled ? '✓' : '!'; ?></span><?= Text::_('COM_DECARODCL_INFO_CHECK_UPDATE_SERVER'); ?></div>
                <div class="dcl-information-check <?= $environmentCompatible ? 'is-ok' : 'is-error'; ?>"><span aria-hidden="true"><?= $environmentCompatible ? '✓' : '!'; ?></span><?= Text::_('COM_DECARODCL_INFO_CHECK_ENVIRONMENT'); ?></div>
                <div class="dcl-information-check <?= $packageDetected ? 'is-ok' : 'is-error'; ?>"><span aria-hidden="true"><?= $packageDetected ? '✓' : '!'; ?></span><?= Text::_('COM_DECARODCL_INFO_CHECK_PACKAGE'); ?></div>
            </div>
            <details class="dcl-information-details"><summary><?= Text::_('COM_DECARODCL_INFO_TECHNICAL_DETAILS'); ?></summary><dl class="dcl-information-list"><div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_COMPONENT_ID'); ?></dt><dd><code>com_decarodcl</code></dd></div><div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_PACKAGE_ID'); ?></dt><dd><code>pkg_decarodcl</code></dd></div><div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_TABLES'); ?></dt><dd><?= (int) ($tableHealth['present_count'] ?? 0); ?>/<?= (int) ($tableHealth['expected_count'] ?? 0); ?></dd></div><div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_DATABASE'); ?></dt><dd><?= $this->escape(trim((string) ($info['database_type'] ?? '') . ' ' . (string) ($info['database_version'] ?? ''))); ?></dd></div><div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_UPDATE_SERVER'); ?></dt><dd><code><?= $this->escape((string) ($info['update_site_url'] ?? '')); ?></code></dd></div></dl></details>
            <pre id="dcl-information-diagnostic-data" hidden><?= $this->escape($diagnosticText); ?></pre>
            <div class="dcl-information-actions dcl-information-diagnostic-actions"><button type="button" class="btn btn-outline-secondary" data-dcl-info-copy><?= Text::_('COM_DECARODCL_INFO_COPY_DIAGNOSTICS'); ?></button><button type="button" class="btn btn-outline-secondary" data-dcl-info-download data-version="<?= $this->escape($installedVersion); ?>"><?= Text::_('COM_DECARODCL_INFO_DOWNLOAD_DIAGNOSTICS'); ?></button><a class="btn btn-outline-secondary" href="https://github.com/xdecaro/dcl/releases" target="_blank" rel="noopener noreferrer"><?= Text::_('COM_DECARODCL_INFO_GITHUB_RELEASES'); ?></a></div>
            <div class="dcl-information-feedback" data-dcl-info-feedback aria-live="polite"></div>
        </section>
    </div>
</div>
'''
(root / 'component/admin/tmpl/information/default.php').write_text(template, encoding='utf-8')

css = r'''.dcl-page-header--information {
  width: 100%;
  max-width: 1180px;
  margin: 6px auto 22px;
}
.dcl-page-header--information .dcl-page-header__eyebrow { font-size: 11px; letter-spacing: .085em; }
.dcl-page-header--information .dcl-page-header__title { margin: 4px 0 5px; font-size: clamp(26px, 3vw, 30px); line-height: 1.16; }
.dcl-page-header--information .dcl-page-header__description { max-width: none; font-size: 13px; line-height: 1.55; }

.dcl-information-page {
  width: 100%;
  max-width: 1180px;
  margin: 0 auto;
  padding: 0 0 34px;
}
.dcl-information-page .dcl-information-note { margin: 0 0 16px; border-radius: 10px; }
.dcl-information-summary {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
  margin: 0 0 16px;
  padding: 11px 14px;
  border: 1px solid var(--dcl-border);
  border-radius: 11px;
  background: var(--dcl-surface);
}
.dcl-information-summary strong { margin-right: auto; color: var(--dcl-text); font-size: 13px; }
.dcl-information-page .dcl-information-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
.dcl-information-page .dcl-card { min-width: 0; padding: 18px; border-radius: 12px; box-shadow: 0 2px 8px rgba(15, 23, 42, .025); }
.dcl-information-page .dcl-card-head { gap: 14px; margin-bottom: 12px; }
.dcl-information-page .dcl-card-head h2,
.dcl-information-diagnostic-head h2 { margin: 3px 0 0; font-size: 18px; font-weight: 700; line-height: 1.2; }
.dcl-information-page .dcl-eyebrow { font-size: 11px; letter-spacing: .085em; }
.dcl-information-page .dcl-badge { min-height: 24px; padding: 4px 9px; font-size: 11px; line-height: 1.15; }
.dcl-information-page .dcl-information-list { margin: 5px 0 0; }
.dcl-information-page .dcl-information-row { grid-template-columns: minmax(145px, 42%) minmax(0, 1fr); gap: 16px; min-height: 40px; padding: 8px 0; }
.dcl-information-page .dcl-information-row dt { font-size: 12px; }
.dcl-information-page .dcl-information-row dd { font-size: 12px; line-height: 1.5; }
.dcl-information-page a:not(.btn) { color: var(--dcl-primary); text-decoration: none; }
.dcl-information-page a:not(.btn):hover,
.dcl-information-page a:not(.btn):focus-visible { text-decoration: underline; }
.dcl-information-page a:focus-visible,
.dcl-information-page button:focus-visible,
.dcl-information-details summary:focus-visible { outline: 2px solid var(--dcl-primary); outline-offset: 2px; }
.dcl-information-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-top: 15px; }
.dcl-information-actions .btn { min-height: 39px; padding: 8px 16px; border-radius: 8px; font-size: 12px; font-weight: 650; line-height: 18px; }
.dcl-information-intro { margin: -2px 0 12px; color: var(--dcl-muted); font-size: 13px; line-height: 1.55; }
.dcl-information-integrations { border-top: 0; }
.dcl-information-integration {
  display: grid;
  grid-template-columns: minmax(240px, 1.25fr) minmax(230px, .85fr) auto;
  align-items: center;
  gap: 20px;
  padding: 14px 0;
  border-top: 1px solid var(--dcl-border);
}
.dcl-information-integration:last-child { padding-bottom: 2px; }
.dcl-information-integration-main { min-width: 0; }
.dcl-information-integration-main > strong { display: block; color: var(--dcl-text); font-size: 13px; font-weight: 700; }
.dcl-information-integration-main > span { display: block; margin-top: 3px; color: var(--dcl-muted); font-size: 11px; }
.dcl-information-integration-metrics { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; }
.dcl-information-integration-metrics small { display: block; color: var(--dcl-muted); font-size: 10px; }
.dcl-information-integration-metrics strong { display: block; margin-top: 2px; color: var(--dcl-text); font-size: 12px; }
.dcl-information-integration-badges { display: flex; justify-content: flex-end; flex-wrap: wrap; gap: 6px; }
.dcl-information-diagnostic-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 18px; }
.dcl-information-diagnostic-head p { max-width: 760px; margin: -2px 0 12px; color: var(--dcl-muted); font-size: 13px; line-height: 1.55; }
.dcl-information-checks { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px 20px; margin-top: 16px; }
.dcl-information-check { display: flex; align-items: center; gap: 8px; min-width: 0; color: var(--dcl-text); font-size: 12px; }
.dcl-information-check > span { display: inline-flex; flex: 0 0 19px; align-items: center; justify-content: center; width: 19px; height: 19px; border-radius: 999px; font-size: 11px; font-weight: 800; }
.dcl-information-check.is-ok > span { background: var(--dcl-success-soft); color: var(--dcl-success); }
.dcl-information-check.is-warning > span { background: var(--dcl-warning-soft); color: var(--dcl-warning); }
.dcl-information-check.is-error > span { background: var(--dcl-danger-soft); color: var(--dcl-danger); }
.dcl-information-details { margin-top: 17px; margin-bottom: 32px; padding: 11px 16px; border: 1px solid var(--dcl-border); border-radius: 7px; background: var(--dcl-surface-soft); }
.dcl-information-details summary { padding: 0; color: var(--dcl-text); font-size: 12px; font-weight: 700; line-height: 18px; cursor: pointer; }
.dcl-information-details[open] summary { padding-bottom: 10px; border-bottom: 1px solid var(--dcl-border); }
.dcl-information-details .dcl-information-list { padding: 0 12px 4px; }
.dcl-information-diagnostic-actions { margin-top: 15px; }
.dcl-information-feedback { margin-top: 7px; color: var(--dcl-muted); font-size: 11px; line-height: 1.5; }
[data-bs-theme="dark"] .dcl-information-page .dcl-card,
[data-bs-theme="dark"] .dcl-information-summary { box-shadow: none; }

@media (max-width: 900px) {
  .dcl-information-page .dcl-information-grid { grid-template-columns: 1fr; }
  .dcl-information-page .dcl-field-span-2 { grid-column: auto; }
  .dcl-information-integration { grid-template-columns: 1fr; gap: 10px; }
  .dcl-information-integration-badges { justify-content: flex-start; }
  .dcl-information-checks { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 560px) {
  .dcl-page-header--information { margin-bottom: 18px; }
  .dcl-page-header--information .dcl-page-header__title { font-size: 25px; }
  .dcl-information-page { padding-bottom: 24px; }
  .dcl-information-summary > strong { width: 100%; margin-right: 0; }
  .dcl-information-page .dcl-card { padding: 15px; }
  .dcl-information-page .dcl-card-head,
  .dcl-information-diagnostic-head { flex-direction: column; }
  .dcl-information-page .dcl-information-row { grid-template-columns: 1fr; gap: 3px; min-height: 0; padding: 10px 0; }
  .dcl-information-integration-metrics { grid-template-columns: 1fr 1fr; }
  .dcl-information-checks { grid-template-columns: 1fr; }
  .dcl-information-actions { display: grid; grid-template-columns: 1fr; }
  .dcl-information-actions .btn { width: 100%; }
}
'''
(root / 'component/media/css/information.css').write_text(css, encoding='utf-8')

js = r'''(() => {
  'use strict';

  const onReady = (callback) => {
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', callback, { once: true });
    else callback();
  };

  onReady(() => {
    const data = document.getElementById('dcl-information-diagnostic-data');
    const copyButton = document.querySelector('[data-dcl-info-copy]');
    const downloadButton = document.querySelector('[data-dcl-info-download]');
    const feedback = document.querySelector('[data-dcl-info-feedback]');
    if (!data || !feedback) return;

    const text = () => data.textContent || '';
    const message = (key, fallback) => Joomla.Text?._(key) || fallback;
    const setFeedback = (value) => { feedback.textContent = value; };

    const fallbackCopy = (value) => {
      const area = document.createElement('textarea');
      area.value = value;
      area.setAttribute('readonly', '');
      area.style.position = 'fixed';
      area.style.opacity = '0';
      document.body.appendChild(area);
      area.select();
      const ok = document.execCommand('copy');
      area.remove();
      return ok;
    };

    copyButton?.addEventListener('click', async () => {
      try {
        if (navigator.clipboard?.writeText) await navigator.clipboard.writeText(text());
        else if (!fallbackCopy(text())) throw new Error('copy failed');
        setFeedback(message('COM_DECARODCL_INFO_COPIED', 'Diagnostica copiata.'));
      } catch (error) {
        setFeedback(message('COM_DECARODCL_INFO_COPY_FAILED', 'Impossibile copiare la diagnostica.'));
      }
    });

    downloadButton?.addEventListener('click', () => {
      const version = downloadButton.dataset.version || 'unknown';
      const blob = new Blob([text() + '\n'], { type: 'text/plain;charset=utf-8' });
      const url = URL.createObjectURL(blob);
      const anchor = document.createElement('a');
      anchor.href = url;
      anchor.download = `competitions-diagnostics-${version}.txt`;
      document.body.appendChild(anchor);
      anchor.click();
      anchor.remove();
      URL.revokeObjectURL(url);
      setFeedback(message('COM_DECARODCL_INFO_DOWNLOADED', 'Diagnostica scaricata.'));
    });
  });
})();
'''
(root / 'component/media/js/information.js').write_text(js, encoding='utf-8')

# Information view: load dedicated Web Assets and JS language strings.
view_path = root / 'component/admin/src/View/Information/HtmlView.php'
view = view_path.read_text(encoding='utf-8')
needle = "        UiHelper::loadAssets();\n        $this->info = $this->get('Info');"
replacement = """        UiHelper::loadAssets();\n\n        $document = Factory::getApplication()->getDocument();\n        $wa = $document->getWebAssetManager();\n        $wa->getRegistry()->addExtensionRegistryFile('com_decarodcl');\n        $wa->useStyle('com_decarodcl.information');\n        $wa->useScript('com_decarodcl.information');\n\n        foreach ([\n            'COM_DECARODCL_INFO_COPIED',\n            'COM_DECARODCL_INFO_COPY_FAILED',\n            'COM_DECARODCL_INFO_DOWNLOADED',\n        ] as $key) {\n            Text::script($key);\n        }\n\n        $this->info = $this->get('Info');"""
if needle not in view:
    raise SystemExit('Information HtmlView anchor missing')
view_path.write_text(view.replace(needle, replacement, 1), encoding='utf-8')

# Shared page header supports a safe optional modifier; only Information uses it.
helper_path = root / 'component/admin/src/Helper/PageHeaderHelper.php'
helper = helper_path.read_text(encoding='utf-8')
helper = helper.replace("'information' => ['COM_DECARODCL_INFORMATION', 'COM_DECARODCL_INFORMATION', 'COM_DECARODCL_PAGE_INFORMATION_DESC'],", "'information' => ['COM_DECARODCL', 'COM_DECARODCL_INFORMATION', 'COM_DECARODCL_PAGE_INFORMATION_DESC'],", 1)
anchor = """        } else {\n            return '';\n        }\n\n        return LayoutHelper::render('page.header', $data, JPATH_COMPONENT_ADMINISTRATOR . '/layouts');"""
repl = """        } else {\n            return '';\n        }\n\n        if ($view === 'information') {\n            $data['class'] = 'dcl-page-header--information';\n        }\n\n        return LayoutHelper::render('page.header', $data, JPATH_COMPONENT_ADMINISTRATOR . '/layouts');"""
if anchor not in helper:
    raise SystemExit('PageHeaderHelper anchor missing')
helper_path.write_text(helper.replace(anchor, repl, 1), encoding='utf-8')

layout_path = root / 'component/admin/layouts/page/header.php'
layout = layout_path.read_text(encoding='utf-8')
layout = layout.replace("$description = htmlspecialchars((string) ($displayData['description'] ?? ''), ENT_QUOTES, 'UTF-8');", "$description = htmlspecialchars((string) ($displayData['description'] ?? ''), ENT_QUOTES, 'UTF-8');\n$modifier = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($displayData['class'] ?? ''));", 1)
layout = layout.replace('<header class="dcl-page-header">', '<header class="dcl-page-header<?= $modifier !== \'\' ? \' \' . $modifier : \'\'; ?>">', 1)
layout_path.write_text(layout, encoding='utf-8')

# Language additions in the two languages currently shipped by Competitions.
translations = {
'it-IT': {
'COM_DECARODCL_INFO_QUICK_STATUS':'Stato rapido','COM_DECARODCL_INFO_SYSTEM_OK':'Sistema OK','COM_DECARODCL_INFO_SYSTEM_CHECK':'Da verificare','COM_DECARODCL_INFO_MINIMUM_JOOMLA':'Joomla minimo','COM_DECARODCL_INFO_MINIMUM_PHP':'PHP minimo','COM_DECARODCL_INFO_TABLES':'Tabelle','COM_DECARODCL_INFO_PRESENT':'presenti','COM_DECARODCL_INFO_ALIGNED':'Allineato','COM_DECARODCL_INFO_CHECK_REQUIRED':'Da verificare','COM_DECARODCL_INFO_COMPATIBLE':'Compatibile','COM_DECARODCL_INFO_INCOMPATIBLE':'Non compatibile','COM_DECARODCL_INFO_EXTENSIONS_SECTION':'ESTENSIONI','COM_DECARODCL_INFO_INCLUDED_EXTENSIONS':'Estensioni incluse','COM_DECARODCL_INFO_SYSTEM_PLUGIN':'Plugin di sistema','COM_DECARODCL_INFO_TIMELINE_MODULE':'Modulo Timeline','COM_DECARODCL_INFO_COUNTRIES_MODULE':'Modulo Paesi/Federazioni','COM_DECARODCL_INFO_CHANNEL':'Canale','COM_DECARODCL_INFO_STABLE':'Stabile','COM_DECARODCL_INFO_INSTALLED_VERSION':'Versione installata','COM_DECARODCL_INFO_NO_UPDATE_DETECTED':'Nessun aggiornamento rilevato','COM_DECARODCL_INFO_STATUS':'Stato','COM_DECARODCL_INFO_CONFIGURED':'Configurato','COM_DECARODCL_INFO_NOT_CONFIGURED':'Non configurato','COM_DECARODCL_INFO_CONNECTED_COMPONENTS':'Componenti collegati','COM_DECARODCL_INFO_CONNECTED_COMPONENTS_DESC':'Componenti xdecaro rilevati e disponibili nello stesso sito.','COM_DECARODCL_INFO_OPTIONAL_INTEGRATION':'Integrazione opzionale','COM_DECARODCL_INFO_AVAILABLE_MODULES':'Moduli disponibili','COM_DECARODCL_INFO_OPTIONAL':'Opzionale','COM_DECARODCL_INFO_DIAGNOSTICS_PRIVACY':'Il riepilogo contiene solo informazioni tecniche utili alla diagnostica. Password, token, cookie e credenziali non vengono inclusi.','COM_DECARODCL_INFO_CHECK_VERSIONS':'Versioni coerenti','COM_DECARODCL_INFO_CHECK_DATABASE':'Database allineato','COM_DECARODCL_INFO_CHECK_TABLES':'Tabelle presenti','COM_DECARODCL_INFO_CHECK_UPDATE_SERVER':'Update server attivo','COM_DECARODCL_INFO_CHECK_ENVIRONMENT':'Ambiente compatibile','COM_DECARODCL_INFO_CHECK_PACKAGE':'Pacchetto rilevato','COM_DECARODCL_INFO_TECHNICAL_DETAILS':'Dettagli tecnici','COM_DECARODCL_INFO_COPY_DIAGNOSTICS':'Copia diagnostica','COM_DECARODCL_INFO_DOWNLOAD_DIAGNOSTICS':'Scarica .txt','COM_DECARODCL_INFO_COPIED':'Diagnostica copiata.','COM_DECARODCL_INFO_COPY_FAILED':'Impossibile copiare la diagnostica.','COM_DECARODCL_INFO_DOWNLOADED':'Diagnostica scaricata.','COM_DECARODCL_INFO_COMPONENT_ID':'Componente','COM_DECARODCL_INFO_PACKAGE_ID':'Pacchetto','COM_DECARODCL_INFO_UPDATE_AVAILABLE':'Aggiornamento disponibile','COM_DECARODCL_INFO_UP_TO_DATE':'Aggiornato'},
'en-GB': {
'COM_DECARODCL_INFO_QUICK_STATUS':'Quick status','COM_DECARODCL_INFO_SYSTEM_OK':'System OK','COM_DECARODCL_INFO_SYSTEM_CHECK':'Check required','COM_DECARODCL_INFO_MINIMUM_JOOMLA':'Minimum Joomla','COM_DECARODCL_INFO_MINIMUM_PHP':'Minimum PHP','COM_DECARODCL_INFO_TABLES':'Tables','COM_DECARODCL_INFO_PRESENT':'present','COM_DECARODCL_INFO_ALIGNED':'Aligned','COM_DECARODCL_INFO_CHECK_REQUIRED':'Check required','COM_DECARODCL_INFO_COMPATIBLE':'Compatible','COM_DECARODCL_INFO_INCOMPATIBLE':'Not compatible','COM_DECARODCL_INFO_EXTENSIONS_SECTION':'EXTENSIONS','COM_DECARODCL_INFO_INCLUDED_EXTENSIONS':'Included extensions','COM_DECARODCL_INFO_SYSTEM_PLUGIN':'System plugin','COM_DECARODCL_INFO_TIMELINE_MODULE':'Timeline module','COM_DECARODCL_INFO_COUNTRIES_MODULE':'Countries/Federations module','COM_DECARODCL_INFO_CHANNEL':'Channel','COM_DECARODCL_INFO_STABLE':'Stable','COM_DECARODCL_INFO_INSTALLED_VERSION':'Installed version','COM_DECARODCL_INFO_NO_UPDATE_DETECTED':'No update detected','COM_DECARODCL_INFO_STATUS':'Status','COM_DECARODCL_INFO_CONFIGURED':'Configured','COM_DECARODCL_INFO_NOT_CONFIGURED':'Not configured','COM_DECARODCL_INFO_CONNECTED_COMPONENTS':'Connected components','COM_DECARODCL_INFO_CONNECTED_COMPONENTS_DESC':'xdecaro components detected and available on the same site.','COM_DECARODCL_INFO_OPTIONAL_INTEGRATION':'Optional integration','COM_DECARODCL_INFO_AVAILABLE_MODULES':'Available modules','COM_DECARODCL_INFO_OPTIONAL':'Optional','COM_DECARODCL_INFO_DIAGNOSTICS_PRIVACY':'The summary contains only technical information useful for diagnostics. Passwords, tokens, cookies and credentials are not included.','COM_DECARODCL_INFO_CHECK_VERSIONS':'Versions coherent','COM_DECARODCL_INFO_CHECK_DATABASE':'Database aligned','COM_DECARODCL_INFO_CHECK_TABLES':'Tables present','COM_DECARODCL_INFO_CHECK_UPDATE_SERVER':'Update server active','COM_DECARODCL_INFO_CHECK_ENVIRONMENT':'Environment compatible','COM_DECARODCL_INFO_CHECK_PACKAGE':'Package detected','COM_DECARODCL_INFO_TECHNICAL_DETAILS':'Technical details','COM_DECARODCL_INFO_COPY_DIAGNOSTICS':'Copy diagnostics','COM_DECARODCL_INFO_DOWNLOAD_DIAGNOSTICS':'Download .txt','COM_DECARODCL_INFO_COPIED':'Diagnostics copied.','COM_DECARODCL_INFO_COPY_FAILED':'Unable to copy diagnostics.','COM_DECARODCL_INFO_DOWNLOADED':'Diagnostics downloaded.','COM_DECARODCL_INFO_COMPONENT_ID':'Component','COM_DECARODCL_INFO_PACKAGE_ID':'Package','COM_DECARODCL_INFO_UPDATE_AVAILABLE':'Update available','COM_DECARODCL_INFO_UP_TO_DATE':'Up to date'},
}
for tag, items in translations.items():
    p = root / f'component/admin/language/{tag}/com_decarodcl.ini'
    s = p.read_text(encoding='utf-8')
    for key, value in items.items():
        line = f'{key}="{value}"'
        pattern = re.compile(rf'(?m)^{re.escape(key)}=.*$')
        if pattern.search(s):
            s = pattern.sub(line, s)
        else:
            if not s.endswith('\n'): s += '\n'
            s += line + '\n'
    p.write_text(s, encoding='utf-8')

# Web Asset registry: bump all existing assets and add dedicated Information assets.
asset_path = root / 'component/media/joomla.asset.json'
data = json.loads(asset_path.read_text(encoding='utf-8'))
data['version'] = new
for item in data.get('assets', []): item['version'] = new
names = {item.get('name') for item in data.get('assets', [])}
if 'com_decarodcl.information' not in names:
    data['assets'].append({'name':'com_decarodcl.information','type':'style','uri':'com_decarodcl/information.css','version':new,'dependencies':['com_decarodcl.admin']})
    data['assets'].append({'name':'com_decarodcl.information','type':'script','uri':'com_decarodcl/information.js','version':new,'attributes':{'defer':True}})
asset_path.write_text(json.dumps(data, indent=2) + '\n', encoding='utf-8')

# Runtime cache version.
ui_path = root / 'component/admin/src/Helper/UiHelper.php'
ui = ui_path.read_text(encoding='utf-8')
ui = ui.replace("private const VERSION = '0.10.5';", "private const VERSION = '0.10.6';", 1)
ui_path.write_text(ui, encoding='utf-8')

# Version every bundled extension manifest and nested package filenames.
manifests = [
    root/'component/decarodcl.xml', root/'package/pkg_decarodcl.xml',
    root/'plugins/system/decarodcl/decarodcl.xml',
    root/'modules/mod_dcl_matchtimeline/mod_dcl_matchtimeline.xml',
    root/'modules/mod_dcl_countriesfederations/mod_dcl_countriesfederations.xml',
]
for p in manifests:
    s = p.read_text(encoding='utf-8')
    if f'<version>{old}</version>' not in s:
        raise SystemExit(f'Unexpected version in {p}')
    s = s.replace(f'<version>{old}</version>', f'<version>{new}</version>', 1)
    if p.name == 'pkg_decarodcl.xml': s = s.replace(old, new)
    p.write_text(s, encoding='utf-8')

# Update feed version/url now; release workflow will compute the final package SHA-256.
feed_path = root / 'updates/pkg_decarodcl.xml'
feed = feed_path.read_text(encoding='utf-8').replace(f'<version>{old}</version>', f'<version>{new}</version>', 1).replace(f'/v{old}/pkg_decarodcl_{old}.zip', f'/v{new}/pkg_decarodcl_{new}.zip', 1)
feed_path.write_text(feed, encoding='utf-8')

# Validator knows the new assets and verifies Information structure/behaviour.
val_path = root / 'tools/validate_release.py'
val = val_path.read_text(encoding='utf-8')
val = val.replace('"media/joomla.asset.json",\n}', '"media/joomla.asset.json",\n    "media/css/information.css",\n    "media/js/information.js",\n}', 1)
val = val.replace('"com_decarodcl.scope": "script",', '"com_decarodcl.scope": "script",\n        "com_decarodcl.information": "script",', 1)
# The registry has a style and a script with the same Joomla asset name; validate both directly instead of dictionary collapsing them.
old_block = '''    assets = {str(item.get("name", "")): item for item in asset.get("assets", [])}\n    required_assets = {\n        "com_decarodcl.admin": "style",\n        "com_decarodcl.live-sync-style": "style",\n        "com_decarodcl.live-sync": "script",\n        "com_decarodcl.scope": "script",\n        "com_decarodcl.information": "script",\n    }\n    for name, asset_type in required_assets.items():\n        item = assets.get(name)\n        if item is None:\n            fail(f"Web Asset registry is missing {name}")\n        if item.get("type") != asset_type:\n            fail(f"Web Asset {name} has the wrong type")\n        if str(item.get("version", "")).strip() != VERSION:\n            fail(f"Web Asset {name} version does not match VERSION")\n'''
new_block = '''    assets = asset.get("assets", [])\n    required_assets = [\n        ("com_decarodcl.admin", "style"),\n        ("com_decarodcl.live-sync-style", "style"),\n        ("com_decarodcl.live-sync", "script"),\n        ("com_decarodcl.scope", "script"),\n        ("com_decarodcl.information", "style"),\n        ("com_decarodcl.information", "script"),\n    ]\n    for name, asset_type in required_assets:\n        item = next((row for row in assets if row.get("name") == name and row.get("type") == asset_type), None)\n        if item is None:\n            fail(f"Web Asset registry is missing {name} ({asset_type})")\n        if str(item.get("version", "")).strip() != VERSION:\n            fail(f"Web Asset {name} ({asset_type}) version does not match VERSION")\n'''
if old_block not in val:
    raise SystemExit('validate_release asset block anchor missing')
val = val.replace(old_block, new_block, 1)
val = val.replace('for required in ("dcl-information-grid", "dcl-card", "dcl-information-row", "dcl-badge"):', 'for required in ("dcl-information-grid", "dcl-card", "dcl-information-row", "dcl-badge", "dcl-information-integration", "dcl-information-checks", "dcl-information-details", "data-dcl-info-copy"):', 1)
val_path.write_text(val, encoding='utf-8')

# CI/release syntax checks include the new Information client script.
for rel in (root/'.github/workflows/ci.yml', root/'.github/workflows/release.yml'):
    s = rel.read_text(encoding='utf-8')
    s = s.replace('run: node --check component/media/js/filterbar.js', 'run: |\n          node --check component/media/js/filterbar.js\n          node --check component/media/js/information.js', 1)
    rel.write_text(s, encoding='utf-8')

# README and changelog metadata.
readme_path = root/'README.md'
readme = readme_path.read_text(encoding='utf-8').replace('**0.10.5**', '**0.10.6**', 1)
readme_path.write_text(readme, encoding='utf-8')
changelog_path = root/'CHANGELOG.md'
changelog = changelog_path.read_text(encoding='utf-8')
entry = '''## 0.10.6 - 2026-09-08\n\n- Rebuilt the administrator **Informazioni** page to the approved xdecaro standard used by Courses and Forms.\n- Changed the page eyebrow to **COMPETITIONS** while keeping **Informazioni** as the main title.\n- Added the compact product status summary and standardized Product, Environment, Included extensions and Updates cards.\n- Reclassified Timeline and Countries/Federations as bundled extensions instead of presenting them as external integrations.\n- Added the full-width **Componenti collegati** section with safe detection of Forms and Courses, installed versions and available item counts.\n- Added six diagnostic checks, balanced Technical details, Copy diagnostics, Download .txt and GitHub release actions. Diagnostic export excludes passwords, tokens, cookies and credentials.\n- Added dedicated Information CSS/JS loaded only by the Information view, including responsive, keyboard-focus and dark-mode states.\n- Preserved historical `com_decarodcl`, `pkg_decarodcl`, `#__dcl_*` and `xdecaro/dcl` technical identifiers for upgrade compatibility.\n- Preserved competition data, Tournament scope, Live Sync, ACL/CSRF protections and all existing sports functionality. No database migration is introduced.\n- Bumped component, package, plugin, modules, Web Asset registry and Joomla update feed to 0.10.6.\n\n'''
if '## 0.10.6 ' not in changelog:
    changelog = changelog.replace('# Changelog\n\n', '# Changelog\n\n' + entry, 1)
changelog_path.write_text(changelog, encoding='utf-8')

# Bump VERSION last inside the coherent release commit.
(root/'VERSION').write_text(new + '\n', encoding='utf-8')
PY

php -l component/admin/src/Model/InformationModel.php >/dev/null
php -l component/admin/src/View/Information/HtmlView.php >/dev/null
php -l component/admin/tmpl/information/default.php >/dev/null
php -l component/admin/src/Helper/PageHeaderHelper.php >/dev/null
php -l component/admin/layouts/page/header.php >/dev/null
node --check component/media/js/information.js
python3 tools/validate_release.py
python3 tools/build.py
python3 tools/validate_release.py --dist

echo "Prepared and validated Competitions 0.10.6"

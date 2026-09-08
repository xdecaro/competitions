<?php
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
$core = (array) ($info['core'] ?? []);
$coreInstalled = !empty($core['installed']);
$coreApiAvailable = !empty($core['api_available']);
$coreVersion = (string) ($core['version'] ?? '');
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

$coreCheckClass = !$coreInstalled ? 'is-muted' : ($coreApiAvailable ? 'is-ok' : 'is-warning');
$coreCheckIcon = !$coreInstalled ? '•' : ($coreApiAvailable ? '✓' : '!');
$coreCheckLabelKey = !$coreInstalled
    ? 'COM_DECARODCL_INFO_CORE_OPTIONAL_NOT_INSTALLED'
    : ($coreApiAvailable ? 'COM_DECARODCL_INFO_CORE_API_AVAILABLE' : 'COM_DECARODCL_INFO_CORE_API_CHECK');

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
    'Core by xdecaro: ' . ($coreInstalled ? ($coreVersion !== '' ? $coreVersion : 'installed') : 'not installed'),
    'Core public API: ' . ($coreApiAvailable ? 'available' : 'not available'),
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
                <div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_REPOSITORY'); ?></dt><dd><a href="https://github.com/xdecaro/competitions" target="_blank" rel="noopener noreferrer">xdecaro/competitions <span aria-hidden="true">↗</span></a></dd></div>
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
                <?php foreach ($integrations as $integration) :
                    $installed = !empty($integration['installed']);
                    $metricType = (string) ($integration['metric_type'] ?? 'count');
                    $metricLabel = $metricType === 'api'
                        ? Text::_('COM_DECARODCL_INFO_PUBLIC_API')
                        : Text::_('COM_DECARODCL_INFO_AVAILABLE_MODULES');
                    $metricValue = $metricType === 'api'
                        ? Text::_(!empty($integration['api_available']) ? 'COM_DECARODCL_INFO_AVAILABLE' : 'COM_DECARODCL_INFO_NOT_AVAILABLE')
                        : (string) (int) ($integration['available_count'] ?? 0);
                ?>
                    <article class="dcl-information-integration">
                        <div class="dcl-information-integration-main"><strong><?= $this->escape((string) ($integration['name'] ?? '')); ?></strong><span><?= Text::_('COM_DECARODCL_INFO_OPTIONAL_INTEGRATION'); ?> · <code><?= $this->escape((string) ($integration['element'] ?? '')); ?></code></span></div>
                        <div class="dcl-information-integration-metrics"><div><small><?= Text::_('COM_DECARODCL_INFO_INSTALLED_VERSION'); ?></small><strong><?= $installed && (string) ($integration['version'] ?? '') !== '' ? $this->escape((string) $integration['version']) : '—'; ?></strong></div><div><small><?= $this->escape($metricLabel); ?></small><strong><?= $this->escape($metricValue); ?></strong></div></div>
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
                <div class="dcl-information-check <?= $coreCheckClass; ?>"><span aria-hidden="true"><?= $coreCheckIcon; ?></span><?= Text::_($coreCheckLabelKey); ?></div>
            </div>
            <details class="dcl-information-details"><summary><?= Text::_('COM_DECARODCL_INFO_TECHNICAL_DETAILS'); ?></summary><dl class="dcl-information-list"><div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_COMPONENT_ID'); ?></dt><dd><code>com_decarodcl</code></dd></div><div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_PACKAGE_ID'); ?></dt><dd><code>pkg_decarodcl</code></dd></div><div class="dcl-information-row"><dt>Core by xdecaro</dt><dd><?= $coreInstalled ? $this->escape($coreVersion !== '' ? $coreVersion : Text::_('COM_DECARODCL_INFO_INSTALLED')) : Text::_('COM_DECARODCL_INFO_NOT_INSTALLED'); ?> · <?= Text::_('COM_DECARODCL_INFO_PUBLIC_API'); ?>: <?= Text::_($coreApiAvailable ? 'COM_DECARODCL_INFO_AVAILABLE' : 'COM_DECARODCL_INFO_NOT_AVAILABLE'); ?></dd></div><div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_TABLES'); ?></dt><dd><?= (int) ($tableHealth['present_count'] ?? 0); ?>/<?= (int) ($tableHealth['expected_count'] ?? 0); ?></dd></div><div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_DATABASE'); ?></dt><dd><?= $this->escape(trim((string) ($info['database_type'] ?? '') . ' ' . (string) ($info['database_version'] ?? ''))); ?></dd></div><div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_UPDATE_SERVER'); ?></dt><dd><code><?= $this->escape((string) ($info['update_site_url'] ?? '')); ?></code></dd></div></dl></details>
            <pre id="dcl-information-diagnostic-data" hidden><?= $this->escape($diagnosticText); ?></pre>
            <div class="dcl-information-actions dcl-information-diagnostic-actions"><button type="button" class="btn btn-outline-secondary" data-dcl-info-copy><?= Text::_('COM_DECARODCL_INFO_COPY_DIAGNOSTICS'); ?></button><button type="button" class="btn btn-outline-secondary" data-dcl-info-download data-version="<?= $this->escape($installedVersion); ?>"><?= Text::_('COM_DECARODCL_INFO_DOWNLOAD_DIAGNOSTICS'); ?></button><a class="btn btn-outline-secondary" href="https://github.com/xdecaro/competitions/releases" target="_blank" rel="noopener noreferrer"><?= Text::_('COM_DECARODCL_INFO_GITHUB_RELEASES'); ?></a></div>
            <div class="dcl-information-feedback" data-dcl-info-feedback aria-live="polite"></div>
        </section>
    </div>
</div>

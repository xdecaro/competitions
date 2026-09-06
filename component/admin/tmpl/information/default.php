<?php
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$info = $this->info;
$installedVersion = (string) ($info['installed_version'] ?? '0.0.0');
$latestVersion = (string) ($info['latest_version'] ?? '');
$updateAvailable = !empty($info['update_available']);
$updateSiteEnabled = !empty($info['update_site_enabled']);
$installationConsistent = !empty($info['installation_consistent']);
$versions = (array) ($info['extension_versions'] ?? []);
$lastCheck = (int) ($info['last_check_timestamp'] ?? 0);
$healthOk = $installationConsistent && $updateSiteEnabled;

$componentVersion = (string) ($versions['component'] ?? '');
$packageVersion = (string) ($versions['package'] ?? '');
$pluginVersion = (string) ($versions['plugin'] ?? '');
$timelineVersion = (string) ($versions['timeline_module'] ?? '');
$countriesVersion = (string) ($versions['countries_module'] ?? '');

$versionBadge = static function (string $version) use ($installedVersion): string {
    $aligned = $version !== '' && $version === $installedVersion;
    $label = $version !== '' ? htmlspecialchars($version, ENT_QUOTES, 'UTF-8') : '—';
    $class = $aligned ? 'is-success' : 'is-warning';

    return '<span class="dcl-badge ' . $class . '">' . $label . '</span>';
};
?>
<div class="dcl-admin dcl-information-page">
    <?php if (!$installationConsistent) : ?>
        <div class="alert alert-warning dcl-information-note" role="alert">
            <strong><?= Text::_('COM_DECARODCL_INFO_INSTALLATION_INCONSISTENT'); ?>.</strong>
            <?= Text::_('COM_DECARODCL_INFO_INSTALLATION_INCONSISTENT_DESC'); ?>
        </div>
    <?php elseif (!$updateSiteEnabled) : ?>
        <div class="alert alert-warning dcl-information-note" role="alert"><?= Text::_('COM_DECARODCL_INFO_UPDATE_SITE_WARNING'); ?></div>
    <?php elseif ($updateAvailable) : ?>
        <div class="alert alert-info dcl-information-note" role="status"><?= Text::sprintf('COM_DECARODCL_INFO_UPDATE_AVAILABLE_DESC', $this->escape($latestVersion)); ?></div>
    <?php endif; ?>

    <div class="dcl-information-grid">
        <section class="dcl-card dcl-information-card">
            <div class="dcl-card-head">
                <div>
                    <span class="dcl-eyebrow"><?= Text::_('COM_DECARODCL_INFO_PRODUCT_SECTION'); ?></span>
                    <h2><?= Text::_('COM_DECARODCL_INFO_VERSIONS_TITLE'); ?></h2>
                </div>
                <span class="dcl-badge <?= $installationConsistent ? 'is-success' : 'is-warning'; ?>">
                    <?= Text::_($installationConsistent ? 'COM_DECARODCL_INFO_INSTALLATION_CONSISTENT' : 'COM_DECARODCL_INFO_INSTALLATION_INCONSISTENT'); ?>
                </span>
            </div>
            <dl class="dcl-information-list">
                <div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_COMPONENT_VERSION'); ?></dt><dd><?= $versionBadge($componentVersion); ?></dd></div>
                <div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_PACKAGE_VERSION'); ?></dt><dd><?= $versionBadge($packageVersion); ?></dd></div>
                <div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_PLUGIN_VERSION'); ?></dt><dd><?= $versionBadge($pluginVersion); ?></dd></div>
                <div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_DEVELOPER'); ?></dt><dd>Luca De Caro</dd></div>
                <div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_REPOSITORY'); ?></dt><dd><a href="https://github.com/xdecaro/dcl" target="_blank" rel="noopener noreferrer">xdecaro/dcl</a></dd></div>
                <div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_LICENSE'); ?></dt><dd>GNU GPL v2 or later</dd></div>
            </dl>
        </section>

        <section class="dcl-card dcl-information-card">
            <div class="dcl-card-head">
                <div>
                    <span class="dcl-eyebrow"><?= Text::_('COM_DECARODCL_INFO_ENVIRONMENT_SECTION'); ?></span>
                    <h2><?= Text::_('COM_DECARODCL_INFO_SYSTEM_TITLE'); ?></h2>
                </div>
            </div>
            <dl class="dcl-information-list">
                <div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_JOOMLA'); ?></dt><dd><?= $this->escape((string) ($info['joomla_version'] ?? '—')); ?></dd></div>
                <div class="dcl-information-row"><dt>PHP</dt><dd><?= $this->escape((string) ($info['php_version'] ?? '—')); ?></dd></div>
                <div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_DATABASE'); ?></dt><dd><?= $this->escape(trim((string) ($info['database_type'] ?? '') . ' ' . (string) ($info['database_version'] ?? ''))); ?></dd></div>
                <div class="dcl-information-row">
                    <dt><?= Text::_('COM_DECARODCL_INFO_INSTALLATION_STATUS'); ?></dt>
                    <dd><span class="dcl-badge <?= $installationConsistent ? 'is-success' : 'is-warning'; ?>"><?= Text::_($installationConsistent ? 'COM_DECARODCL_INFO_INSTALLATION_CONSISTENT' : 'COM_DECARODCL_INFO_INSTALLATION_INCONSISTENT'); ?></span></dd>
                </div>
            </dl>
        </section>

        <section class="dcl-card dcl-information-card">
            <div class="dcl-card-head">
                <div>
                    <span class="dcl-eyebrow"><?= Text::_('COM_DECARODCL_INFO_INTEGRATIONS_SECTION'); ?></span>
                    <h2><?= Text::_('COM_DECARODCL_INFO_MODULES_TITLE'); ?></h2>
                </div>
            </div>
            <dl class="dcl-information-list">
                <div class="dcl-information-row">
                    <dt><?= Text::_('COM_DECARODCL_INFO_TIMELINE_MODULE_VERSION'); ?></dt>
                    <dd><span class="dcl-badge <?= $timelineVersion !== '' ? 'is-success' : 'is-muted'; ?>"><?= Text::_($timelineVersion !== '' ? 'COM_DECARODCL_INFO_INSTALLED' : 'COM_DECARODCL_INFO_NOT_INSTALLED'); ?></span></dd>
                </div>
                <div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_VERSION'); ?></dt><dd><?= $timelineVersion !== '' ? $this->escape($timelineVersion) : '—'; ?></dd></div>
                <div class="dcl-information-row">
                    <dt><?= Text::_('COM_DECARODCL_INFO_COUNTRIES_MODULE_VERSION'); ?></dt>
                    <dd><span class="dcl-badge <?= $countriesVersion !== '' ? 'is-success' : 'is-muted'; ?>"><?= Text::_($countriesVersion !== '' ? 'COM_DECARODCL_INFO_INSTALLED' : 'COM_DECARODCL_INFO_NOT_INSTALLED'); ?></span></dd>
                </div>
                <div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_VERSION'); ?></dt><dd><?= $countriesVersion !== '' ? $this->escape($countriesVersion) : '—'; ?></dd></div>
            </dl>
        </section>

        <section class="dcl-card dcl-information-card">
            <div class="dcl-card-head">
                <div>
                    <span class="dcl-eyebrow"><?= Text::_('COM_DECARODCL_INFO_UPDATES_SECTION'); ?></span>
                    <h2><?= Text::_('COM_DECARODCL_INFO_UPDATE_CHANNEL_TITLE'); ?></h2>
                </div>
                <span class="dcl-badge <?= $updateSiteEnabled ? 'is-success' : 'is-warning'; ?>"><?= Text::_($updateSiteEnabled ? 'COM_DECARODCL_INFO_ACTIVE' : 'COM_DECARODCL_INFO_INACTIVE'); ?></span>
            </div>
            <dl class="dcl-information-list">
                <div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_UPDATE_SERVER_STATUS'); ?></dt><dd><span class="dcl-badge <?= $updateSiteEnabled ? 'is-success' : 'is-warning'; ?>"><?= Text::_($updateSiteEnabled ? 'COM_DECARODCL_INFO_ACTIVE' : 'COM_DECARODCL_INFO_INACTIVE'); ?></span></dd></div>
                <div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_LATEST_DETECTED'); ?></dt><dd><?= $latestVersion !== '' ? $this->escape($latestVersion) : Text::_('COM_DECARODCL_INFO_NEVER'); ?></dd></div>
                <div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_LAST_CHECK'); ?></dt><dd><?= $lastCheck > 0 ? $this->escape(date('d/m/Y H:i', $lastCheck)) : Text::_('COM_DECARODCL_INFO_NEVER'); ?></dd></div>
                <div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_INSTALL_MODE'); ?></dt><dd><?= Text::_('COM_DECARODCL_INFO_INSTALL_MODE_VALUE'); ?></dd></div>
                <div class="dcl-information-row"><dt><?= Text::_('COM_DECARODCL_INFO_UPDATE_SERVER'); ?></dt><dd><code><?= $this->escape((string) ($info['update_site_url'] ?? '')); ?></code></dd></div>
            </dl>
            <div class="dcl-information-actions">
                <?php if ($this->canManageInstaller) : ?>
                    <a class="btn btn-primary" href="<?= Route::_('index.php?option=com_installer&view=update'); ?>"><?= Text::_('COM_DECARODCL_INFO_OPEN_UPDATES'); ?></a>
                    <a class="btn btn-secondary" href="<?= Route::_('index.php?option=com_installer&view=updatesites'); ?>"><?= Text::_('COM_DECARODCL_INFO_OPEN_UPDATE_SITES'); ?></a>
                <?php endif; ?>
            </div>
        </section>

        <section class="dcl-card dcl-information-card dcl-field-span-2">
            <div class="dcl-card-head">
                <div>
                    <span class="dcl-eyebrow"><?= Text::_('COM_DECARODCL_INFO_DIAGNOSTICS_SECTION'); ?></span>
                    <h2><?= Text::_('COM_DECARODCL_INFO_HEALTH_TITLE'); ?></h2>
                </div>
                <span class="dcl-badge <?= $healthOk ? 'is-success' : 'is-warning'; ?>"><?= Text::_($healthOk ? 'COM_DECARODCL_INFO_NO_CRITICAL_ISSUES' : 'COM_DECARODCL_INFO_ATTENTION_REQUIRED'); ?></span>
            </div>
            <p class="dcl-information-health">
                <?= $healthOk ? Text::_('COM_DECARODCL_INFO_INSTALLATION_CONSISTENT') . ' · ' . Text::_('COM_DECARODCL_INFO_UP_TO_DATE') : Text::_('COM_DECARODCL_INFO_INSTALLATION_INCONSISTENT_DESC'); ?>
            </p>
            <div class="dcl-information-meta">
                <span><strong><?= Text::_('COM_DECARODCL_INFO_VERSION'); ?></strong> <?= $this->escape($installedVersion); ?></span>
                <span><strong><?= Text::_('COM_DECARODCL_INFO_COMPONENT_VERSION'); ?></strong> <code><?= $this->escape((string) ($info['component_id'] ?? '')); ?></code></span>
                <span><strong><?= Text::_('COM_DECARODCL_INFO_PACKAGE_VERSION'); ?></strong> <code><?= $this->escape((string) ($info['package_id'] ?? '')); ?></code></span>
            </div>
            <div class="dcl-information-actions">
                <a class="btn btn-outline-secondary" href="https://github.com/xdecaro/dcl/releases" target="_blank" rel="noopener noreferrer"><?= Text::_('COM_DECARODCL_INFO_GITHUB_RELEASES'); ?></a>
            </div>
        </section>
    </div>
</div>

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

$versionRows = [
    'package' => Text::_('COM_DECARODCL_INFO_PACKAGE_VERSION'),
    'component' => Text::_('COM_DECARODCL_INFO_COMPONENT_VERSION'),
    'plugin' => Text::_('COM_DECARODCL_INFO_PLUGIN_VERSION'),
    'timeline_module' => Text::_('COM_DECARODCL_INFO_TIMELINE_MODULE_VERSION'),
    'countries_module' => Text::_('COM_DECARODCL_INFO_COUNTRIES_MODULE_VERSION'),
];
?>
<div class="dcl-admin dcl-information">
    <div class="dcl-info-head">
        <div>
            <h2><?= Text::_('COM_DECARODCL_INFORMATION_TITLE'); ?></h2>
            <p><?= Text::_('COM_DECARODCL_INFORMATION_DESC'); ?></p>
        </div>
        <a class="btn btn-sm btn-outline-secondary" href="https://github.com/xdecaro/dcl/releases" target="_blank" rel="noopener noreferrer">
            <?= Text::_('COM_DECARODCL_INFO_OPEN_RELEASES'); ?>
        </a>
    </div>

    <div class="dcl-info-summary" aria-label="<?= $this->escape(Text::_('COM_DECARODCL_INFORMATION_TITLE')); ?>">
        <section class="dcl-info-stat">
            <span class="dcl-info-stat__label"><?= Text::_('COM_DECARODCL_INFO_VERSION'); ?></span>
            <strong class="dcl-info-stat__value"><?= $this->escape($installedVersion); ?></strong>
        </section>

        <section class="dcl-info-stat">
            <span class="dcl-info-stat__label"><?= Text::_('COM_DECARODCL_INFO_INSTALLATION_STATUS'); ?></span>
            <span class="badge <?= $installationConsistent ? 'bg-success' : 'bg-warning text-dark'; ?>">
                <?= Text::_($installationConsistent ? 'COM_DECARODCL_INFO_INSTALLATION_CONSISTENT' : 'COM_DECARODCL_INFO_INSTALLATION_INCONSISTENT'); ?>
            </span>
        </section>

        <section class="dcl-info-stat">
            <span class="dcl-info-stat__label"><?= Text::_('COM_DECARODCL_INFO_UPDATE_STATUS'); ?></span>
            <?php if (!$updateSiteEnabled) : ?>
                <span class="badge bg-warning text-dark"><?= Text::_('COM_DECARODCL_INFO_INACTIVE'); ?></span>
            <?php elseif ($updateAvailable) : ?>
                <span class="badge bg-info text-dark"><?= Text::_('COM_DECARODCL_INFO_UPDATE_AVAILABLE'); ?></span>
            <?php else : ?>
                <span class="badge bg-success"><?= Text::_('COM_DECARODCL_INFO_UP_TO_DATE'); ?></span>
            <?php endif; ?>
        </section>

        <section class="dcl-info-stat">
            <span class="dcl-info-stat__label"><?= Text::_('COM_DECARODCL_INFO_JOOMLA'); ?></span>
            <strong class="dcl-info-stat__value dcl-info-stat__value--small"><?= $this->escape((string) ($info['joomla_version'] ?? '')); ?></strong>
            <small>PHP <?= $this->escape((string) ($info['php_version'] ?? '')); ?></small>
        </section>
    </div>

    <?php if (!$installationConsistent) : ?>
        <div class="alert alert-warning dcl-info-alert" role="alert">
            <strong><?= Text::_('COM_DECARODCL_INFO_INSTALLATION_INCONSISTENT'); ?>.</strong>
            <?= Text::_('COM_DECARODCL_INFO_INSTALLATION_INCONSISTENT_DESC'); ?>
        </div>
    <?php elseif (!$updateSiteEnabled) : ?>
        <div class="alert alert-warning dcl-info-alert" role="alert"><?= Text::_('COM_DECARODCL_INFO_UPDATE_SITE_WARNING'); ?></div>
    <?php elseif ($updateAvailable) : ?>
        <div class="alert alert-info dcl-info-alert" role="status"><?= Text::sprintf('COM_DECARODCL_INFO_UPDATE_AVAILABLE_DESC', $this->escape($latestVersion)); ?></div>
    <?php endif; ?>

    <div class="dcl-info-panels">
        <section class="card dcl-info-panel">
            <div class="card-header dcl-info-panel__header">
                <strong><?= Text::_('COM_DECARODCL_INFO_UPDATE_SECTION'); ?></strong>
                <span class="badge <?= $updateSiteEnabled ? 'bg-success' : 'bg-warning text-dark'; ?>">
                    <?= Text::_($updateSiteEnabled ? 'COM_DECARODCL_INFO_ACTIVE' : 'COM_DECARODCL_INFO_INACTIVE'); ?>
                </span>
            </div>
            <div class="card-body dcl-info-panel__body">
                <dl class="dcl-info-rows">
                    <div>
                        <dt><?= Text::_('COM_DECARODCL_INFO_UPDATE_SERVER'); ?></dt>
                        <dd><code class="dcl-info-url" title="<?= $this->escape((string) ($info['update_site_url'] ?? '')); ?>"><?= $this->escape((string) ($info['update_site_url'] ?? '')); ?></code></dd>
                    </div>
                    <div>
                        <dt><?= Text::_('COM_DECARODCL_INFO_LAST_CHECK'); ?></dt>
                        <dd><?= $lastCheck > 0 ? $this->escape(date('d/m/Y H:i', $lastCheck)) : Text::_('COM_DECARODCL_INFO_NEVER'); ?></dd>
                    </div>
                    <div>
                        <dt><?= Text::_('COM_DECARODCL_INFO_INSTALL_MODE'); ?></dt>
                        <dd><?= Text::_('COM_DECARODCL_INFO_INSTALL_MODE_VALUE'); ?></dd>
                    </div>
                    <?php if ($latestVersion !== '') : ?>
                        <div>
                            <dt><?= Text::_('COM_DECARODCL_INFO_LATEST_DETECTED'); ?></dt>
                            <dd><?= $this->escape($latestVersion); ?></dd>
                        </div>
                    <?php endif; ?>
                </dl>

                <div class="dcl-info-actions">
                    <?php if ($this->canManageInstaller) : ?>
                        <a class="btn btn-sm btn-primary" href="<?= Route::_('index.php?option=com_installer&view=update'); ?>"><?= Text::_('COM_DECARODCL_INFO_OPEN_UPDATES'); ?></a>
                        <a class="btn btn-sm btn-secondary" href="<?= Route::_('index.php?option=com_installer&view=updatesites'); ?>"><?= Text::_('COM_DECARODCL_INFO_OPEN_UPDATE_SITES'); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="card dcl-info-panel">
            <div class="card-header dcl-info-panel__header">
                <strong><?= Text::_('COM_DECARODCL_INFO_EXTENSION_VERSIONS'); ?></strong>
                <span class="badge <?= $installationConsistent ? 'bg-success' : 'bg-warning text-dark'; ?>">
                    <?= Text::_($installationConsistent ? 'COM_DECARODCL_INFO_INSTALLATION_CONSISTENT' : 'COM_DECARODCL_INFO_INSTALLATION_INCONSISTENT'); ?>
                </span>
            </div>
            <div class="card-body dcl-info-panel__body">
                <div class="dcl-version-list">
                    <?php foreach ($versionRows as $key => $label) : ?>
                        <?php $version = (string) ($versions[$key] ?? ''); ?>
                        <?php $matches = $version !== '' && $version === $installedVersion; ?>
                        <div class="dcl-version-row">
                            <span><?= $this->escape($label); ?></span>
                            <span class="dcl-version-row__value">
                                <code><?= $this->escape($version !== '' ? $version : Text::_('COM_DECARODCL_INFO_MISSING')); ?></code>
                                <span class="badge <?= $matches ? 'bg-success' : 'bg-warning text-dark'; ?>" aria-label="<?= $this->escape(Text::_($matches ? 'COM_DECARODCL_INFO_MATCHES' : 'COM_DECARODCL_INFO_MISMATCH')); ?>">
                                    <?= $matches ? '✓' : '!'; ?>
                                </span>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="dcl-info-systemline">
                    <span><strong><?= Text::_('COM_DECARODCL_INFO_DATABASE'); ?></strong> <?= $this->escape(trim((string) ($info['database_type'] ?? '') . ' ' . (string) ($info['database_version'] ?? ''))); ?></span>
                    <span><strong><?= Text::_('COM_DECARODCL_INFO_REPOSITORY'); ?></strong> <code><?= $this->escape((string) ($info['repository'] ?? '')); ?></code></span>
                </div>
            </div>
        </section>
    </div>
</div>

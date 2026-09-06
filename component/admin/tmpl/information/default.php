<?php
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$info = $this->info;
$installedVersion = (string) ($info['installed_version'] ?? '0.0.0');
$latestVersion = (string) ($info['latest_version'] ?? '');
$updateAvailable = !empty($info['update_available']);
$updateSiteEnabled = !empty($info['update_site_enabled']);
$lastCheck = (int) ($info['last_check_timestamp'] ?? 0);
?>
<div class="dcl-admin dcl-information">
    <div class="dcl-admin__intro">
        <h2><?= Text::_('COM_DECARODCL_INFORMATION_TITLE'); ?></h2>
        <p><?= Text::_('COM_DECARODCL_INFORMATION_DESC'); ?></p>
    </div>

    <div class="dcl-info-grid">
        <section class="dcl-info-card">
            <span class="dcl-info-card__eyebrow"><?= Text::_('COM_DECARODCL_INFO_VERSION'); ?></span>
            <strong class="dcl-info-card__value"><?= $this->escape($installedVersion); ?></strong>
            <span class="dcl-info-card__meta"><?= Text::_('COM_DECARODCL_INFO_VERSION_DESC'); ?></span>
        </section>

        <section class="dcl-info-card">
            <span class="dcl-info-card__eyebrow"><?= Text::_('COM_DECARODCL_INFO_AUTO_UPDATE'); ?></span>
            <strong class="dcl-info-card__value dcl-info-card__value--small"><?= Text::_($updateSiteEnabled ? 'COM_DECARODCL_INFO_ACTIVE' : 'COM_DECARODCL_INFO_INACTIVE'); ?></strong>
            <span class="dcl-info-card__meta"><?= Text::_('COM_DECARODCL_INFO_AUTO_UPDATE_DESC'); ?></span>
        </section>

        <section class="dcl-info-card">
            <span class="dcl-info-card__eyebrow"><?= Text::_('COM_DECARODCL_INFO_UPDATE_STATUS'); ?></span>
            <strong class="dcl-info-card__value dcl-info-card__value--small"><?= Text::_($updateAvailable ? 'COM_DECARODCL_INFO_UPDATE_AVAILABLE' : 'COM_DECARODCL_INFO_UP_TO_DATE'); ?></strong>
            <span class="dcl-info-card__meta"><?php if ($latestVersion !== '') : ?><?= Text::sprintf('COM_DECARODCL_INFO_LATEST_VERSION', $this->escape($latestVersion)); ?><?php else : ?><?= Text::_('COM_DECARODCL_INFO_NO_UPDATE_CACHED'); ?><?php endif; ?></span>
        </section>

        <section class="dcl-info-card">
            <span class="dcl-info-card__eyebrow"><?= Text::_('COM_DECARODCL_INFO_JOOMLA'); ?></span>
            <strong class="dcl-info-card__value dcl-info-card__value--small"><?= $this->escape((string) ($info['joomla_version'] ?? '')); ?></strong>
            <span class="dcl-info-card__meta">PHP <?= $this->escape((string) ($info['php_version'] ?? '')); ?></span>
        </section>
    </div>

    <?php if (!$updateSiteEnabled) : ?>
        <div class="alert alert-warning mt-4" role="alert"><?= Text::_('COM_DECARODCL_INFO_UPDATE_SITE_WARNING'); ?></div>
    <?php elseif ($updateAvailable) : ?>
        <div class="alert alert-info mt-4" role="status"><?= Text::sprintf('COM_DECARODCL_INFO_UPDATE_AVAILABLE_DESC', $this->escape($latestVersion)); ?></div>
    <?php endif; ?>

    <div class="dcl-info-layout mt-4">
        <section class="card dcl-info-section">
            <div class="card-header"><strong><?= Text::_('COM_DECARODCL_INFO_UPDATE_SECTION'); ?></strong></div>
            <div class="card-body">
                <dl class="dcl-info-list">
                    <div><dt><?= Text::_('COM_DECARODCL_INFO_UPDATE_SERVER'); ?></dt><dd><code><?= $this->escape((string) ($info['update_site_url'] ?? '')); ?></code></dd></div>
                    <div><dt><?= Text::_('COM_DECARODCL_INFO_LAST_CHECK'); ?></dt><dd><?= $lastCheck > 0 ? $this->escape(date('d/m/Y H:i', $lastCheck)) : Text::_('COM_DECARODCL_INFO_NEVER'); ?></dd></div>
                    <div><dt><?= Text::_('COM_DECARODCL_INFO_INSTALL_MODE'); ?></dt><dd><?= Text::_('COM_DECARODCL_INFO_INSTALL_MODE_VALUE'); ?></dd></div>
                </dl>
                <p class="text-muted mb-3"><?= Text::_('COM_DECARODCL_INFO_AUTO_UPDATE_EXPLAIN'); ?></p>
                <div class="dcl-info-actions">
                    <?php if ($this->canManageInstaller) : ?>
                        <a class="btn btn-primary" href="<?= Route::_('index.php?option=com_installer&view=update'); ?>"><?= Text::_('COM_DECARODCL_INFO_OPEN_UPDATES'); ?></a>
                        <a class="btn btn-secondary" href="<?= Route::_('index.php?option=com_installer&view=updatesites'); ?>"><?= Text::_('COM_DECARODCL_INFO_OPEN_UPDATE_SITES'); ?></a>
                    <?php endif; ?>
                    <a class="btn btn-outline-secondary" href="https://github.com/xdecaro/dcl/releases" target="_blank" rel="noopener noreferrer"><?= Text::_('COM_DECARODCL_INFO_OPEN_RELEASES'); ?></a>
                </div>
            </div>
        </section>

        <section class="card dcl-info-section">
            <div class="card-header"><strong><?= Text::_('COM_DECARODCL_INFO_SYSTEM_SECTION'); ?></strong></div>
            <div class="card-body">
                <dl class="dcl-info-list">
                    <div><dt><?= Text::_('COM_DECARODCL_INFO_COMPONENT'); ?></dt><dd><code><?= $this->escape((string) ($info['component_id'] ?? '')); ?></code></dd></div>
                    <div><dt><?= Text::_('COM_DECARODCL_INFO_PACKAGE'); ?></dt><dd><code><?= $this->escape((string) ($info['package_id'] ?? '')); ?></code></dd></div>
                    <div><dt><?= Text::_('COM_DECARODCL_INFO_REPOSITORY'); ?></dt><dd><code><?= $this->escape((string) ($info['repository'] ?? '')); ?></code></dd></div>
                    <div><dt><?= Text::_('COM_DECARODCL_INFO_DATABASE'); ?></dt><dd><?= $this->escape(trim((string) ($info['database_type'] ?? '') . ' ' . (string) ($info['database_version'] ?? ''))); ?></dd></div>
                </dl>
            </div>
        </section>
    </div>
</div>

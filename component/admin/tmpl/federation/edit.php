<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->useStyle('com_xdecarocompetitions.admin');

HTMLHelper::_('behavior.formvalidator');

Factory::getApplication()->getDocument()->getWebAssetManager()
    ->useScript('com_xdecarocompetitions.federation-edit');

$isNew = empty($this->item->id);
$isLinked = !empty($this->item->organization_uuid);
$showLinkPicker = $this->organizationsAvailable && !$isLinked;
$showLocalIdentity = !$isLinked && (!$this->organizationsAvailable || !$isNew);
$organizationName = trim((string) ($this->organizationData['name'] ?? $this->item->name ?? ''));
$organizationCode = trim((string) ($this->organizationData['code'] ?? $this->item->short_name ?? ''));
?>
<form action="index.php?option=com_xdecarocompetitions&layout=edit&id=<?= (int) $this->item->id; ?>" method="post" name="adminForm" id="federation-form" class="form-validate competitions-admin">
    <div class="card">
        <div class="card-body">
            <?php if ($showLinkPicker) : ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                            <div class="flex-grow-1">
                                <?= $this->form->renderField('organization_uuid'); ?>
                            </div>
                            <?php if ($isNew) : ?>
                                <div class="pt-4">
                                    <a
                                        class="btn btn-outline-primary"
                                        href="<?= Route::_('index.php?option=com_xdecaroorganizations&task=organization.add'); ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        <?= Text::_('COM_XDECAROCOMPETITIONS_CREATE_FEDERATION_IN_ORGANIZATIONS'); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="small text-muted">
                            <?= Text::_($isNew
                                ? 'COM_XDECAROCOMPETITIONS_FEDERATION_NEW_LINK_NOTE'
                                : 'COM_XDECAROCOMPETITIONS_FEDERATION_LEGACY_LINK_NOTE'); ?>
                        </div>
                    </div>
                </div>
            <?php elseif ($isLinked) : ?>
                <input type="hidden" name="jform[organization_uuid]" value="<?= $this->escape((string) $this->item->organization_uuid); ?>">

                <?php if ($this->organizationsAvailable) : ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex flex-wrap justify-content-between gap-2 align-items-start">
                                <div>
                                    <div class="small text-muted mb-1">
                                        <?= Text::_('COM_XDECAROCOMPETITIONS_FEDERATION_LINKED_TO_ORGANIZATIONS'); ?>
                                    </div>
                                    <div class="fw-semibold"><?= $this->escape($organizationName ?: '—'); ?></div>
                                    <?php if ($organizationCode !== '') : ?>
                                        <div class="text-muted"><?= $this->escape($organizationCode); ?></div>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($this->organizationData['id'])) : ?>
                                    <a
                                        class="btn btn-sm btn-outline-primary"
                                        href="<?= Route::_('index.php?option=com_xdecaroorganizations&task=organization.edit&id=' . (int) $this->organizationData['id']); ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        <?= Text::_('COM_XDECAROCOMPETITIONS_OPEN_IN_ORGANIZATIONS'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="small text-muted mt-2">
                                <?= Text::_('COM_XDECAROCOMPETITIONS_FEDERATION_LINK_LOCKED_NOTE'); ?>
                            </div>
                        </div>
                    </div>
                <?php else : ?>
                    <div class="alert alert-warning" role="status">
                        <?= Text::_('COM_XDECAROCOMPETITIONS_FEDERATION_ORGANIZATIONS_OFFLINE_SNAPSHOT'); ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div data-federation-country>
                <?= $this->form->renderField('country_id'); ?>
                <input type="hidden" name="jform[country_id]" value="" data-federation-country-shadow disabled>
                <div class="form-text text-success d-none" data-federation-country-derived>
                    <?= Text::_('COM_XDECAROCOMPETITIONS_FEDERATION_COUNTRY_DERIVED_DESC'); ?>
                </div>
            </div>

            <?php if ($showLocalIdentity) : ?>
                <?= $this->form->renderField('name'); ?>
                <?= $this->form->renderField('short_name'); ?>
                <?= $this->form->renderField('logo'); ?>
                <?= $this->form->renderField('website'); ?>
                <?= $this->form->renderField('email'); ?>
            <?php endif; ?>

            <?= $this->form->renderField('state'); ?>
            <?= $this->form->renderField('ordering'); ?>
        </div>
    </div>
    <?= $this->form->getInput('id'); ?>
    <?= $this->form->getInput('modified'); ?>
    <input type="hidden" name="task" value="">
    <?= HTMLHelper::_('form.token'); ?>
</form>

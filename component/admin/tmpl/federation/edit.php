<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->useStyle('com_xdecarocompetitions.admin');

HTMLHelper::_('behavior.formvalidator');

$isNew = empty($this->item->id);
$isLinked = !empty($this->item->organization_uuid);
$showLocalIdentity = !$this->organizationsAvailable || (!$isNew && !$isLinked);
?>
<form action="index.php?option=com_xdecarocompetitions&layout=edit&id=<?= (int) $this->item->id; ?>" method="post" name="adminForm" id="federation-form" class="form-validate competitions-admin">
    <div class="card">
        <div class="card-body">
            <?php if ($this->organizationsAvailable) : ?>
                <?= $this->form->renderField('organization_uuid'); ?>
            <?php elseif ($isLinked) : ?>
                <input type="hidden" name="jform[organization_uuid]" value="<?= $this->escape((string) $this->item->organization_uuid); ?>">
                <div class="alert alert-warning" role="status">
                    <?= Text::_('COM_XDECAROCOMPETITIONS_FEDERATION_ORGANIZATIONS_OFFLINE_SNAPSHOT'); ?>
                </div>
            <?php endif; ?>

            <?= $this->form->renderField('country_id'); ?>

            <?php if ($this->organizationsAvailable && $isLinked && $this->organizationData) : ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between gap-2 align-items-start">
                            <div>
                                <div class="fw-semibold"><?= $this->escape((string) ($this->organizationData['name'] ?? $this->item->name)); ?></div>
                                <?php if (!empty($this->organizationData['code'])) : ?>
                                    <div class="text-muted"><?= $this->escape((string) $this->organizationData['code']); ?></div>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($this->organizationData['id'])) : ?>
                                <a class="btn btn-sm btn-outline-primary" href="<?= Route::_('index.php?option=com_xdecaroorganizations&task=organization.edit&id=' . (int) $this->organizationData['id']); ?>">
                                    <?= Text::_('COM_XDECAROCOMPETITIONS_OPEN_IN_ORGANIZATIONS'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="small text-muted mt-2">
                            <?= Text::_('COM_XDECAROCOMPETITIONS_FEDERATION_CANONICAL_NOTE'); ?>
                        </div>
                    </div>
                </div>
            <?php elseif ($this->organizationsAvailable && $isNew) : ?>
                <div class="alert alert-info" role="status">
                    <?= Text::_('COM_XDECAROCOMPETITIONS_FEDERATION_SELECT_CANONICAL_NOTE'); ?>
                </div>
            <?php endif; ?>

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

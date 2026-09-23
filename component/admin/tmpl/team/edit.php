<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.formvalidator');

Factory::getApplication()->getDocument()->getWebAssetManager()
    ->useScript('com_xdecarocompetitions.team-edit');

$isNew = empty($this->item->id);
$isLinked = !empty($this->item->organization_uuid);
$teamType = strtolower(trim((string) ($this->item->team_type ?? 'club'))) ?: 'club';
$organizationName = trim((string) ($this->organizationData['name'] ?? $this->item->name ?? ''));
$organizationCode = trim((string) ($this->organizationData['code'] ?? $this->item->short_name ?? ''));
?>
<form
    action="index.php?option=com_xdecarocompetitions&layout=edit&id=<?= (int) $this->item->id; ?>"
    method="post"
    name="adminForm"
    id="team-form"
    class="form-validate competitions-admin"
>
    <div class="card">
        <div class="card-body">
            <?php if ($isLinked) : ?>
                <input type="hidden" name="jform[organization_uuid]" value="<?= $this->escape((string) $this->item->organization_uuid); ?>">
                <input type="hidden" name="jform[team_type]" value="club">

                <?php if ($this->organizationsAvailable) : ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex flex-wrap justify-content-between gap-2 align-items-start">
                                <div>
                                    <div class="small text-muted mb-1">
                                        <?= Text::_('COM_XDECAROCOMPETITIONS_TEAM_LINKED_TO_ORGANIZATIONS'); ?>
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
                                <?= Text::_('COM_XDECAROCOMPETITIONS_TEAM_LINK_LOCKED_NOTE'); ?>
                            </div>
                        </div>
                    </div>
                <?php else : ?>
                    <div class="alert alert-warning" role="status">
                        <?= Text::_('COM_XDECAROCOMPETITIONS_TEAM_ORGANIZATIONS_OFFLINE_SNAPSHOT'); ?>
                    </div>
                <?php endif; ?>
            <?php else : ?>
                <?= $this->form->renderField('team_type'); ?>

                <?php if ($this->organizationsAvailable) : ?>
                    <div class="card mb-3" data-team-organization-link>
                        <div class="card-body">
                            <?= $this->form->renderField('organization_uuid'); ?>
                            <div class="small text-muted">
                                <?= Text::_($isNew
                                    ? 'COM_XDECAROCOMPETITIONS_TEAM_NEW_LINK_NOTE'
                                    : 'COM_XDECAROCOMPETITIONS_TEAM_LEGACY_LINK_NOTE'); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?= $this->form->renderField('federation_id'); ?>
            <?= $this->form->renderField('owner_user_id'); ?>

            <?php if (!$isLinked) : ?>
                <div
                    data-team-local-identity
                    data-team-existing-legacy="<?= !$isNew ? '1' : '0'; ?>"
                    data-team-link-available="<?= $this->organizationsAvailable ? '1' : '0'; ?>"
                    <?= $isNew && $this->organizationsAvailable && $teamType === 'club' ? 'hidden' : ''; ?>
                >
                    <?= $this->form->renderField('name'); ?>
                    <?= $this->form->renderField('short_name'); ?>
                    <?= $this->form->renderField('logo'); ?>
                    <?= $this->form->renderField('city'); ?>
                    <?= $this->form->renderField('email'); ?>
                    <?= $this->form->renderField('phone'); ?>
                    <?= $this->form->renderField('website'); ?>
                </div>
            <?php endif; ?>

            <?= $this->form->renderField('alias'); ?>
            <?= $this->form->renderField('approval_status'); ?>
            <?= $this->form->renderField('rejection_reason'); ?>
            <?= $this->form->renderField('state'); ?>
            <?= $this->form->renderField('ordering'); ?>
        </div>
    </div>

    <?= $this->form->getInput('id'); ?>
    <?= $this->form->getInput('modified'); ?>
    <input type="hidden" name="task" value="">
    <?= HTMLHelper::_('form.token'); ?>
</form>

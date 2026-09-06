<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$canChange = \Joomla\CMS\Factory::getApplication()->getIdentity()->authorise('core.edit.state', 'com_decarodcl');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));

$approvalLabels = [
    'pending' => ['COM_DECARODCL_APPROVAL_PENDING', 'bg-warning text-dark'],
    'approved' => ['COM_DECARODCL_APPROVAL_APPROVED', 'bg-success'],
    'rejected' => ['COM_DECARODCL_APPROVAL_REJECTED', 'bg-danger'],
];
?>
<form action="<?= Route::_('index.php?option=com_decarodcl&view=teams'); ?>" method="post" name="adminForm" id="adminForm" class="dcl-admin">
    <div class="dcl-filterbar dcl-filterbar--wide">
        <div class="dcl-filterbar__search">
            <label class="visually-hidden" for="filter_search"><?= Text::_('JSEARCH_FILTER'); ?></label>
            <input
                type="search"
                name="filter_search"
                id="filter_search"
                value="<?= $this->escape($this->state->get('filter.search')); ?>"
                placeholder="<?= Text::_('COM_DECARODCL_SEARCH_TEAMS'); ?>"
            >
        </div>

        <select name="filter_country_id" onchange="this.form.submit()">
            <option value="0"><?= Text::_('COM_DECARODCL_FILTER_ALL_COUNTRIES'); ?></option>
            <?php foreach ($this->countryOptions as $country) : ?>
                <option value="<?= (int) $country->id; ?>" <?= (int) $this->state->get('filter.country_id') === (int) $country->id ? 'selected' : ''; ?>>
                    <?= $this->escape($country->name . ' (' . $country->code . ')'); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="filter_federation_id" onchange="this.form.submit()">
            <option value="0"><?= Text::_('COM_DECARODCL_FILTER_ALL_FEDERATIONS'); ?></option>
            <?php foreach ($this->federationOptions as $federation) : ?>
                <option value="<?= (int) $federation->id; ?>" <?= (int) $this->state->get('filter.federation_id') === (int) $federation->id ? 'selected' : ''; ?>>
                    <?= $this->escape(($federation->country_name ?: '—') . ' — ' . $federation->name); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="filter_approval_status" onchange="this.form.submit()">
            <option value=""><?= Text::_('COM_DECARODCL_FILTER_ALL_APPROVALS'); ?></option>
            <?php foreach ($approvalLabels as $status => $meta) : ?>
                <option value="<?= $this->escape($status); ?>" <?= (string) $this->state->get('filter.approval_status') === $status ? 'selected' : ''; ?>>
                    <?= Text::_($meta[0]); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="filter_state" onchange="this.form.submit()">
            <option value=""><?= Text::_('JOPTION_SELECT_PUBLISHED'); ?></option>
            <option value="1" <?= (string) $this->state->get('filter.state') === '1' ? 'selected' : ''; ?>><?= Text::_('JPUBLISHED'); ?></option>
            <option value="0" <?= (string) $this->state->get('filter.state') === '0' ? 'selected' : ''; ?>><?= Text::_('JUNPUBLISHED'); ?></option>
            <option value="-2" <?= (string) $this->state->get('filter.state') === '-2' ? 'selected' : ''; ?>><?= Text::_('JTRASHED'); ?></option>
        </select>

        <button class="btn btn-primary" type="submit"><?= Text::_('JSEARCH_FILTER_SUBMIT'); ?></button>
        <a class="btn btn-secondary" href="<?= Route::_('index.php?option=com_decarodcl&view=teams'); ?>"><?= Text::_('JSEARCH_FILTER_CLEAR'); ?></a>
    </div>

    <div class="table-responsive">
        <table class="table table-striped align-middle dcl-responsive-table">
            <thead>
                <tr>
                    <th class="w-1 text-center"><?= HTMLHelper::_('grid.checkall'); ?></th>
                    <th><?= HTMLHelper::_('searchtools.sort', 'COM_DECARODCL_FIELD_NAME', 'a.name', $listDirn, $listOrder); ?></th>
                    <th><?= HTMLHelper::_('searchtools.sort', 'COM_DECARODCL_FIELD_FEDERATION', 'f.name', $listDirn, $listOrder); ?></th>
                    <th class="d-none d-xl-table-cell"><?= HTMLHelper::_('searchtools.sort', 'COM_DECARODCL_FIELD_TEAM_MANAGER', 'u.name', $listDirn, $listOrder); ?></th>
                    <th><?= HTMLHelper::_('searchtools.sort', 'COM_DECARODCL_FIELD_APPROVAL_STATUS', 'a.approval_status', $listDirn, $listOrder); ?></th>
                    <th class="text-center d-none d-lg-table-cell"><?= HTMLHelper::_('searchtools.sort', 'COM_DECARODCL_PARTICIPATIONS', 'participations_count', $listDirn, $listOrder); ?></th>
                    <th class="text-center"><?= Text::_('JSTATUS'); ?></th>
                    <th class="text-center d-none d-lg-table-cell"><?= Text::_('JGRID_HEADING_ID'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($this->items as $i => $item) : ?>
                <?php $approval = $approvalLabels[$item->approval_status] ?? ['COM_DECARODCL_APPROVAL_PENDING', 'bg-secondary']; ?>
                <tr>
                    <td class="text-center dcl-responsive-table__check"><?= HTMLHelper::_('grid.id', $i, $item->id); ?></td>
                    <td data-label="<?= Text::_('COM_DECARODCL_FIELD_NAME'); ?>">
                        <a class="fw-semibold" href="<?= Route::_('index.php?option=com_decarodcl&task=team.edit&id=' . (int) $item->id); ?>">
                            <?= $this->escape($item->name); ?>
                        </a>
                        <?php if ($item->short_name || $item->city) : ?>
                            <div class="small text-muted">
                                <?= $this->escape(trim(($item->short_name ?: '') . ($item->short_name && $item->city ? ' · ' : '') . ($item->city ?: ''))); ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td data-label="<?= Text::_('COM_DECARODCL_FIELD_FEDERATION'); ?>">
                        <div><?= $this->escape($item->federation_name ?: '—'); ?></div>
                        <div class="small text-muted">
                            <?= $this->escape(($item->country_name ?: '—') . ($item->resolved_country_code ? ' (' . $item->resolved_country_code . ')' : '')); ?>
                        </div>
                    </td>
                    <td class="d-none d-xl-table-cell" data-label="<?= Text::_('COM_DECARODCL_FIELD_TEAM_MANAGER'); ?>">
                        <?php if ($item->manager_name) : ?>
                            <div><?= $this->escape($item->manager_name); ?></div>
                            <div class="small text-muted"><?= $this->escape($item->manager_email ?: ''); ?></div>
                        <?php else : ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td data-label="<?= Text::_('COM_DECARODCL_FIELD_APPROVAL_STATUS'); ?>"><span class="badge <?= $approval[1]; ?>"><?= Text::_($approval[0]); ?></span></td>
                    <td class="text-center d-none d-lg-table-cell" data-label="<?= Text::_('COM_DECARODCL_PARTICIPATIONS'); ?>">
                        <a href="<?= Route::_('index.php?option=com_decarodcl&view=participations&filter_search=' . rawurlencode((string) $item->name)); ?>">
                            <?= (int) $item->participations_count; ?>
                        </a>
                    </td>
                    <td class="text-center" data-label="<?= Text::_('JSTATUS'); ?>"><?= HTMLHelper::_('jgrid.published', $item->state, $i, 'teams.', $canChange, 'cb'); ?></td>
                    <td class="text-center d-none d-lg-table-cell" data-label="<?= Text::_('JGRID_HEADING_ID'); ?>"><?= (int) $item->id; ?></td>
                </tr>
            <?php endforeach; ?>

            <?php if (!$this->items) : ?>
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted"><?= Text::_('COM_DECARODCL_NO_TEAMS'); ?></td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?= $this->pagination->getListFooter(); ?>
    <input type="hidden" name="task" value="">
    <input type="hidden" name="boxchecked" value="0">
    <input type="hidden" name="filter_order" value="<?= $listOrder; ?>">
    <input type="hidden" name="filter_order_Dir" value="<?= $listDirn; ?>">
    <?= HTMLHelper::_('form.token'); ?>
</form>

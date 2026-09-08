<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$user = Factory::getApplication()->getIdentity();
$canChange = $user->authorise('core.edit.state', 'com_xdecarocompetitions');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));
$approvalLabels = [
    'pending' => 'COM_XDECAROCOMPETITIONS_PLAYER_PENDING',
    'approved' => 'COM_XDECAROCOMPETITIONS_PLAYER_APPROVED',
    'rejected' => 'COM_XDECAROCOMPETITIONS_PLAYER_REJECTED',
];
$approvalClasses = ['pending' => 'bg-warning text-dark', 'approved' => 'bg-success', 'rejected' => 'bg-danger'];
?>
<form action="<?= Route::_('index.php?option=com_xdecarocompetitions&view=players'); ?>" method="post" name="adminForm" id="adminForm" class="competitions-admin">
    <div class="competitions-filterbar">
        <div class="competitions-filterbar__search">
            <label class="visually-hidden" for="filter_search"><?= Text::_('JSEARCH_FILTER'); ?></label>
            <input type="search" name="filter_search" id="filter_search" value="<?= $this->escape($this->state->get('filter.search')); ?>" placeholder="<?= Text::_('COM_XDECAROCOMPETITIONS_SEARCH_PLAYERS'); ?>">
        </div>
        <select name="filter_nationality_code" onchange="this.form.submit()">
            <option value=""><?= Text::_('COM_XDECAROCOMPETITIONS_FILTER_ALL_NATIONALITIES'); ?></option>
            <?php foreach ($this->countryOptions as $country) : ?>
                <option value="<?= $this->escape($country->code); ?>" <?= (string) $this->state->get('filter.nationality_code') === (string) $country->code ? 'selected' : ''; ?>><?= $this->escape($country->name . ' (' . $country->code . ')'); ?></option>
            <?php endforeach; ?>
        </select>
        <select name="filter_approval_status" onchange="this.form.submit()">
            <option value=""><?= Text::_('COM_XDECAROCOMPETITIONS_FILTER_ALL_APPROVALS'); ?></option>
            <?php foreach ($approvalLabels as $value => $label) : ?>
                <option value="<?= $value; ?>" <?= (string) $this->state->get('filter.approval_status') === $value ? 'selected' : ''; ?>><?= Text::_($label); ?></option>
            <?php endforeach; ?>
        </select>
        <select name="filter_state" onchange="this.form.submit()">
            <option value=""><?= Text::_('JOPTION_SELECT_PUBLISHED'); ?></option>
            <option value="1" <?= (string) $this->state->get('filter.state') === '1' ? 'selected' : ''; ?>><?= Text::_('JPUBLISHED'); ?></option>
            <option value="0" <?= (string) $this->state->get('filter.state') === '0' ? 'selected' : ''; ?>><?= Text::_('JUNPUBLISHED'); ?></option>
            <option value="-2" <?= (string) $this->state->get('filter.state') === '-2' ? 'selected' : ''; ?>><?= Text::_('JTRASHED'); ?></option>
        </select>
        <button class="btn btn-primary" type="submit"><?= Text::_('JSEARCH_FILTER_SUBMIT'); ?></button>
        <a class="btn btn-secondary" href="<?= Route::_('index.php?option=com_xdecarocompetitions&view=players'); ?>"><?= Text::_('JSEARCH_FILTER_CLEAR'); ?></a>
    </div>

    <div class="table-responsive">
        <table class="table table-striped align-middle competitions-responsive-table">
            <thead><tr>
                <th class="w-1 text-center"><?= HTMLHelper::_('grid.checkall'); ?></th>
                <th><?= HTMLHelper::_('searchtools.sort', 'COM_XDECAROCOMPETITIONS_FIELD_PLAYER', 'a.last_name', $listDirn, $listOrder); ?></th>
                <th><?= HTMLHelper::_('searchtools.sort', 'COM_XDECAROCOMPETITIONS_FIELD_NATIONALITY', 'c.name', $listDirn, $listOrder); ?></th>
                <th><?= HTMLHelper::_('searchtools.sort', 'COM_XDECAROCOMPETITIONS_FIELD_BIRTH_DATE', 'a.birth_date', $listDirn, $listOrder); ?></th>
                <th><?= HTMLHelper::_('searchtools.sort', 'COM_XDECAROCOMPETITIONS_FIELD_APPROVAL_STATUS', 'a.approval_status', $listDirn, $listOrder); ?></th>
                <th class="text-center"><?= Text::_('COM_XDECAROCOMPETITIONS_ROSTERS'); ?></th>
                <th class="text-center"><?= Text::_('JSTATUS'); ?></th>
                <th class="text-center"><?= Text::_('JGRID_HEADING_ID'); ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($this->items as $i => $item) : ?>
                <tr>
                    <td class="text-center competitions-responsive-table__check"><?= HTMLHelper::_('grid.id', $i, $item->id); ?></td>
                    <td data-label="<?= Text::_('COM_XDECAROCOMPETITIONS_FIELD_PLAYER'); ?>">
                        <a class="fw-semibold" href="<?= Route::_('index.php?option=com_xdecarocompetitions&task=player.edit&id=' . (int) $item->id); ?>"><?= $this->escape($item->last_name . ' ' . $item->first_name); ?></a>
                        <?php if ($item->external_ref) : ?><div class="small text-muted"><?= $this->escape($item->external_ref); ?></div><?php endif; ?>
                    </td>
                    <td data-label="<?= Text::_('COM_XDECAROCOMPETITIONS_FIELD_NATIONALITY'); ?>"><?= $this->escape($item->nationality_name ?: ($item->nationality_code ?: '—')); ?></td>
                    <td data-label="<?= Text::_('COM_XDECAROCOMPETITIONS_FIELD_BIRTH_DATE'); ?>"><?= $this->escape($item->birth_date ?: '—'); ?></td>
                    <td data-label="<?= Text::_('COM_XDECAROCOMPETITIONS_FIELD_APPROVAL_STATUS'); ?>"><span class="badge <?= $approvalClasses[$item->approval_status] ?? 'bg-secondary'; ?>"><?= Text::_($approvalLabels[$item->approval_status] ?? 'COM_XDECAROCOMPETITIONS_PLAYER_PENDING'); ?></span></td>
                    <td class="text-center" data-label="<?= Text::_('COM_XDECAROCOMPETITIONS_ROSTERS'); ?>"><?= (int) $item->rosters_count; ?></td>
                    <td class="text-center" data-label="<?= Text::_('JSTATUS'); ?>"><?= HTMLHelper::_('jgrid.published', $item->state, $i, 'players.', $canChange, 'cb'); ?></td>
                    <td class="text-center" data-label="<?= Text::_('JGRID_HEADING_ID'); ?>"><?= (int) $item->id; ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$this->items) : ?><tr><td colspan="8" class="text-center py-5 text-muted"><?= Text::_('COM_XDECAROCOMPETITIONS_NO_PLAYERS'); ?></td></tr><?php endif; ?>
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

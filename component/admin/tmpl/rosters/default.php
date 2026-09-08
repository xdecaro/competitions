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
$statusLabels = [
    'pending' => 'COM_XDECAROCOMPETITIONS_ROSTER_PENDING',
    'approved' => 'COM_XDECAROCOMPETITIONS_ROSTER_APPROVED',
    'rejected' => 'COM_XDECAROCOMPETITIONS_ROSTER_REJECTED',
];
$statusClasses = ['pending' => 'bg-warning text-dark', 'approved' => 'bg-success', 'rejected' => 'bg-danger'];
?>
<form action="<?= Route::_('index.php?option=com_xdecarocompetitions&view=rosters'); ?>" method="post" name="adminForm" id="adminForm" class="competitions-admin">
    <div class="competitions-filterbar">
        <div class="competitions-filterbar__search">
            <label class="visually-hidden" for="filter_search"><?= Text::_('JSEARCH_FILTER'); ?></label>
            <input type="search" name="filter_search" id="filter_search" value="<?= $this->escape($this->state->get('filter.search')); ?>" placeholder="<?= Text::_('COM_XDECAROCOMPETITIONS_SEARCH_ROSTERS'); ?>">
        </div>
        <select name="filter_team_id" onchange="this.form.submit()">
            <option value="0"><?= Text::_('COM_XDECAROCOMPETITIONS_FILTER_ALL_TEAMS'); ?></option>
            <?php foreach ($this->teamOptions as $team) : ?>
                <option value="<?= (int) $team->id; ?>" <?= (int) $this->state->get('filter.team_id') === (int) $team->id ? 'selected' : ''; ?>><?= $this->escape($team->name); ?></option>
            <?php endforeach; ?>
        </select>
        <select name="filter_season_id" onchange="this.form.submit()">
            <option value="0"><?= Text::_('COM_XDECAROCOMPETITIONS_FILTER_ALL_SEASONS'); ?></option>
            <?php foreach ($this->seasonOptions as $season) : ?>
                <option value="<?= (int) $season->id; ?>" <?= (int) $this->state->get('filter.season_id') === (int) $season->id ? 'selected' : ''; ?>><?= $this->escape($season->tournament_name . ' — ' . $season->name . ($season->season_year ? ' (' . $season->season_year . ')' : '')); ?></option>
            <?php endforeach; ?>
        </select>
        <select name="filter_status" onchange="this.form.submit()">
            <option value=""><?= Text::_('COM_XDECAROCOMPETITIONS_FILTER_ALL_ROSTER_STATUSES'); ?></option>
            <?php foreach ($statusLabels as $value => $label) : ?>
                <option value="<?= $value; ?>" <?= (string) $this->state->get('filter.status') === $value ? 'selected' : ''; ?>><?= Text::_($label); ?></option>
            <?php endforeach; ?>
        </select>
        <select name="filter_state" onchange="this.form.submit()">
            <option value=""><?= Text::_('JOPTION_SELECT_PUBLISHED'); ?></option>
            <option value="1" <?= (string) $this->state->get('filter.state') === '1' ? 'selected' : ''; ?>><?= Text::_('JPUBLISHED'); ?></option>
            <option value="0" <?= (string) $this->state->get('filter.state') === '0' ? 'selected' : ''; ?>><?= Text::_('JUNPUBLISHED'); ?></option>
            <option value="-2" <?= (string) $this->state->get('filter.state') === '-2' ? 'selected' : ''; ?>><?= Text::_('JTRASHED'); ?></option>
        </select>
        <button class="btn btn-primary" type="submit"><?= Text::_('JSEARCH_FILTER_SUBMIT'); ?></button>
        <a class="btn btn-secondary" href="<?= Route::_('index.php?option=com_xdecarocompetitions&view=rosters'); ?>"><?= Text::_('JSEARCH_FILTER_CLEAR'); ?></a>
    </div>

    <div class="table-responsive">
        <table class="table table-striped align-middle competitions-responsive-table">
            <thead><tr>
                <th class="w-1 text-center"><?= HTMLHelper::_('grid.checkall'); ?></th>
                <th><?= HTMLHelper::_('searchtools.sort', 'COM_XDECAROCOMPETITIONS_FIELD_PLAYER', 'p.last_name', $listDirn, $listOrder); ?></th>
                <th><?= HTMLHelper::_('searchtools.sort', 'COM_XDECAROCOMPETITIONS_FIELD_TEAM', 'tm.name', $listDirn, $listOrder); ?></th>
                <th><?= Text::_('COM_XDECAROCOMPETITIONS_FIELD_COMPETITION'); ?></th>
                <th class="text-center"><?= HTMLHelper::_('searchtools.sort', 'COM_XDECAROCOMPETITIONS_FIELD_SHIRT_NUMBER', 'a.shirt_number', $listDirn, $listOrder); ?></th>
                <th><?= HTMLHelper::_('searchtools.sort', 'COM_XDECAROCOMPETITIONS_FIELD_ROLE', 'a.role', $listDirn, $listOrder); ?></th>
                <th><?= HTMLHelper::_('searchtools.sort', 'COM_XDECAROCOMPETITIONS_FIELD_ROSTER_STATUS', 'a.status', $listDirn, $listOrder); ?></th>
                <th class="text-center"><?= Text::_('JSTATUS'); ?></th>
                <th class="text-center"><?= Text::_('JGRID_HEADING_ID'); ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($this->items as $i => $item) : ?>
                <tr>
                    <td class="text-center competitions-responsive-table__check"><?= HTMLHelper::_('grid.id', $i, $item->id); ?></td>
                    <td data-label="<?= Text::_('COM_XDECAROCOMPETITIONS_FIELD_PLAYER'); ?>">
                        <a class="fw-semibold" href="<?= Route::_('index.php?option=com_xdecarocompetitions&task=roster.edit&id=' . (int) $item->id); ?>"><?= $this->escape($item->player_last_name . ' ' . $item->player_first_name); ?></a>
                        <?php if ($item->player_external_ref) : ?><div class="small text-muted"><?= $this->escape($item->player_external_ref); ?></div><?php endif; ?>
                    </td>
                    <td data-label="<?= Text::_('COM_XDECAROCOMPETITIONS_FIELD_TEAM'); ?>"><?= $this->escape($item->team_name); ?></td>
                    <td data-label="<?= Text::_('COM_XDECAROCOMPETITIONS_FIELD_COMPETITION'); ?>"><strong><?= $this->escape($item->tournament_name); ?></strong><div class="small text-muted"><?= $this->escape($item->season_name . ($item->season_year ? ' (' . $item->season_year . ')' : '')); ?></div></td>
                    <td class="text-center" data-label="<?= Text::_('COM_XDECAROCOMPETITIONS_FIELD_SHIRT_NUMBER'); ?>"><?= $item->shirt_number === null ? '—' : (int) $item->shirt_number; ?></td>
                    <td data-label="<?= Text::_('COM_XDECAROCOMPETITIONS_FIELD_ROLE'); ?>"><?= $this->escape($item->role ?: '—'); ?></td>
                    <td data-label="<?= Text::_('COM_XDECAROCOMPETITIONS_FIELD_ROSTER_STATUS'); ?>"><span class="badge <?= $statusClasses[$item->status] ?? 'bg-secondary'; ?>"><?= Text::_($statusLabels[$item->status] ?? 'COM_XDECAROCOMPETITIONS_ROSTER_PENDING'); ?></span></td>
                    <td class="text-center" data-label="<?= Text::_('JSTATUS'); ?>"><?= HTMLHelper::_('jgrid.published', $item->state, $i, 'rosters.', $canChange, 'cb'); ?></td>
                    <td class="text-center" data-label="<?= Text::_('JGRID_HEADING_ID'); ?>"><?= (int) $item->id; ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$this->items) : ?><tr><td colspan="9" class="text-center py-5 text-muted"><?= Text::_('COM_XDECAROCOMPETITIONS_NO_ROSTERS'); ?></td></tr><?php endif; ?>
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

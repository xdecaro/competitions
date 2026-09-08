<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$canChange = Factory::getApplication()->getIdentity()->authorise('core.edit.state', 'com_xdecarocompetitions');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));

$statusLabels = [
    'draft' => ['COM_XDECAROCOMPETITIONS_PARTICIPATION_DRAFT', 'bg-secondary'],
    'submitted' => ['COM_XDECAROCOMPETITIONS_PARTICIPATION_SUBMITTED', 'bg-info text-dark'],
    'approved' => ['COM_XDECAROCOMPETITIONS_PARTICIPATION_APPROVED', 'bg-success'],
    'rejected' => ['COM_XDECAROCOMPETITIONS_PARTICIPATION_REJECTED', 'bg-danger'],
];
?>
<form action="<?= Route::_('index.php?option=com_xdecarocompetitions&view=participations'); ?>" method="post" name="adminForm" id="adminForm" class="competitions-admin">
    <div class="competitions-filterbar competitions-filterbar--wide">
        <div class="competitions-filterbar__search">
            <label class="visually-hidden" for="filter_search"><?= Text::_('JSEARCH_FILTER'); ?></label>
            <input
                type="search"
                name="filter_search"
                id="filter_search"
                value="<?= $this->escape($this->state->get('filter.search')); ?>"
                placeholder="<?= Text::_('COM_XDECAROCOMPETITIONS_SEARCH_PARTICIPATIONS'); ?>"
            >
        </div>

        <select name="filter_tournament_id" onchange="this.form.submit()">
            <option value="0"><?= Text::_('COM_XDECAROCOMPETITIONS_FILTER_ALL_TOURNAMENTS'); ?></option>
            <?php foreach ($this->tournamentOptions as $tournament) : ?>
                <option value="<?= (int) $tournament->id; ?>" <?= (int) $this->state->get('filter.tournament_id') === (int) $tournament->id ? 'selected' : ''; ?>>
                    <?= $this->escape($tournament->name . ($tournament->code ? ' (' . $tournament->code . ')' : '')); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="filter_season_id" onchange="this.form.submit()">
            <option value="0"><?= Text::_('COM_XDECAROCOMPETITIONS_FILTER_ALL_SEASONS'); ?></option>
            <?php foreach ($this->seasonOptions as $season) : ?>
                <option value="<?= (int) $season->id; ?>" <?= (int) $this->state->get('filter.season_id') === (int) $season->id ? 'selected' : ''; ?>>
                    <?= $this->escape(($season->tournament_name ?: '—') . ' — ' . $season->name . ($season->season_year ? ' (' . $season->season_year . ')' : '')); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="filter_status" onchange="this.form.submit()">
            <option value=""><?= Text::_('COM_XDECAROCOMPETITIONS_FILTER_ALL_PARTICIPATION_STATUSES'); ?></option>
            <?php foreach ($statusLabels as $status => $meta) : ?>
                <option value="<?= $this->escape($status); ?>" <?= (string) $this->state->get('filter.status') === $status ? 'selected' : ''; ?>>
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
        <a class="btn btn-secondary" href="<?= Route::_('index.php?option=com_xdecarocompetitions&view=participations'); ?>"><?= Text::_('JSEARCH_FILTER_CLEAR'); ?></a>
    </div>

    <div class="table-responsive">
        <table class="table table-striped align-middle competitions-responsive-table">
            <thead>
                <tr>
                    <th class="w-1 text-center"><?= HTMLHelper::_('grid.checkall'); ?></th>
                    <th><?= HTMLHelper::_('searchtools.sort', 'COM_XDECAROCOMPETITIONS_FIELD_TEAM', 'tm.name', $listDirn, $listOrder); ?></th>
                    <th><?= HTMLHelper::_('searchtools.sort', 'COM_XDECAROCOMPETITIONS_FIELD_SEASON', 's.name', $listDirn, $listOrder); ?></th>
                    <th><?= HTMLHelper::_('searchtools.sort', 'COM_XDECAROCOMPETITIONS_FIELD_PARTICIPATION_STATUS', 'a.status', $listDirn, $listOrder); ?></th>
                    <th class="d-none d-xl-table-cell"><?= HTMLHelper::_('searchtools.sort', 'COM_XDECAROCOMPETITIONS_FIELD_SUBMITTED_AT', 'a.submitted_at', $listDirn, $listOrder); ?></th>
                    <th class="d-none d-xl-table-cell"><?= HTMLHelper::_('searchtools.sort', 'COM_XDECAROCOMPETITIONS_FIELD_REVIEWED_AT', 'a.reviewed_at', $listDirn, $listOrder); ?></th>
                    <th class="text-center"><?= Text::_('JSTATUS'); ?></th>
                    <th class="text-center d-none d-lg-table-cell"><?= Text::_('JGRID_HEADING_ID'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($this->items as $i => $item) : ?>
                <?php $status = $statusLabels[$item->status] ?? ['COM_XDECAROCOMPETITIONS_PARTICIPATION_DRAFT', 'bg-secondary']; ?>
                <tr>
                    <td class="text-center competitions-responsive-table__check"><?= HTMLHelper::_('grid.id', $i, $item->id); ?></td>
                    <td data-label="<?= Text::_('COM_XDECAROCOMPETITIONS_FIELD_TEAM'); ?>">
                        <a class="fw-semibold" href="<?= Route::_('index.php?option=com_xdecarocompetitions&task=participation.edit&id=' . (int) $item->id); ?>">
                            <?= $this->escape($item->team_name ?: '—'); ?>
                        </a>
                        <?php if ($item->team_short_name) : ?>
                            <div class="small text-muted"><?= $this->escape($item->team_short_name); ?></div>
                        <?php endif; ?>
                    </td>
                    <td data-label="<?= Text::_('COM_XDECAROCOMPETITIONS_FIELD_SEASON'); ?>">
                        <div><?= $this->escape($item->tournament_name ?: '—'); ?></div>
                        <div class="small text-muted">
                            <?= $this->escape(($item->season_name ?: '—') . ($item->season_year ? ' · ' . $item->season_year : '')); ?>
                        </div>
                    </td>
                    <td data-label="<?= Text::_('COM_XDECAROCOMPETITIONS_FIELD_PARTICIPATION_STATUS'); ?>"><span class="badge <?= $status[1]; ?>"><?= Text::_($status[0]); ?></span></td>
                    <td class="d-none d-xl-table-cell" data-label="<?= Text::_('COM_XDECAROCOMPETITIONS_FIELD_SUBMITTED_AT'); ?>">
                        <?= $item->submitted_at ? HTMLHelper::_('date', $item->submitted_at, Text::_('DATE_FORMAT_LC4')) : '—'; ?>
                    </td>
                    <td class="d-none d-xl-table-cell" data-label="<?= Text::_('COM_XDECAROCOMPETITIONS_FIELD_REVIEWED_AT'); ?>">
                        <?php if ($item->reviewed_at) : ?>
                            <div><?= HTMLHelper::_('date', $item->reviewed_at, Text::_('DATE_FORMAT_LC4')); ?></div>
                            <?php if ($item->reviewer_name) : ?>
                                <div class="small text-muted"><?= $this->escape($item->reviewer_name); ?></div>
                            <?php endif; ?>
                        <?php else : ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td class="text-center" data-label="<?= Text::_('JSTATUS'); ?>"><?= HTMLHelper::_('jgrid.published', $item->state, $i, 'participations.', $canChange, 'cb'); ?></td>
                    <td class="text-center d-none d-lg-table-cell" data-label="<?= Text::_('JGRID_HEADING_ID'); ?>"><?= (int) $item->id; ?></td>
                </tr>
            <?php endforeach; ?>

            <?php if (!$this->items) : ?>
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted"><?= Text::_('COM_XDECAROCOMPETITIONS_NO_PARTICIPATIONS'); ?></td>
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

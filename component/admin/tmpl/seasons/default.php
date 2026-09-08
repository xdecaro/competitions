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
$formatDate = static function ($value): string {
    if (!$value || $value === '0000-00-00') { return '—'; }
    $timestamp = strtotime((string) $value);
    return $timestamp ? date('d/m/Y', $timestamp) : (string) $value;
};
?>
<form action="<?= Route::_('index.php?option=com_xdecarocompetitions&view=seasons'); ?>" method="post" name="adminForm" id="adminForm" class="competitions-admin">
    <div class="competitions-filterbar">
        <div class="competitions-filterbar__search">
            <label class="visually-hidden" for="filter_search"><?= Text::_('JSEARCH_FILTER'); ?></label>
            <input type="search" name="filter_search" id="filter_search" value="<?= $this->escape($this->state->get('filter.search')); ?>" placeholder="<?= Text::_('COM_XDECAROCOMPETITIONS_SEARCH_SEASONS'); ?>">
        </div>
        <select name="filter_tournament_id" onchange="this.form.submit()">
            <option value="0"><?= Text::_('COM_XDECAROCOMPETITIONS_FILTER_ALL_TOURNAMENTS'); ?></option>
            <?php foreach ($this->tournamentOptions as $tournament) : ?>
                <option value="<?= (int) $tournament->id; ?>" <?= (int) $this->state->get('filter.tournament_id') === (int) $tournament->id ? 'selected' : ''; ?>><?= $this->escape($tournament->name); ?></option>
            <?php endforeach; ?>
        </select>
        <select name="filter_season_year" onchange="this.form.submit()">
            <option value="0"><?= Text::_('COM_XDECAROCOMPETITIONS_FILTER_ALL_YEARS'); ?></option>
            <?php foreach ($this->yearOptions as $year) : ?>
                <option value="<?= (int) $year; ?>" <?= (int) $this->state->get('filter.season_year') === (int) $year ? 'selected' : ''; ?>><?= (int) $year; ?></option>
            <?php endforeach; ?>
        </select>
        <select name="filter_state" onchange="this.form.submit()">
            <option value=""><?= Text::_('JOPTION_SELECT_PUBLISHED'); ?></option>
            <option value="1" <?= (string) $this->state->get('filter.state') === '1' ? 'selected' : ''; ?>><?= Text::_('JPUBLISHED'); ?></option>
            <option value="0" <?= (string) $this->state->get('filter.state') === '0' ? 'selected' : ''; ?>><?= Text::_('JUNPUBLISHED'); ?></option>
            <option value="-2" <?= (string) $this->state->get('filter.state') === '-2' ? 'selected' : ''; ?>><?= Text::_('JTRASHED'); ?></option>
        </select>
        <button class="btn btn-primary" type="submit"><?= Text::_('JSEARCH_FILTER_SUBMIT'); ?></button>
        <a class="btn btn-secondary" href="<?= Route::_('index.php?option=com_xdecarocompetitions&view=seasons'); ?>"><?= Text::_('JSEARCH_FILTER_CLEAR'); ?></a>
    </div>

    <div class="table-responsive">
        <table class="table table-striped align-middle competitions-responsive-table">
            <thead><tr>
                <th class="w-1 text-center"><?= HTMLHelper::_('grid.checkall'); ?></th>
                <th><?= HTMLHelper::_('searchtools.sort', 'COM_XDECAROCOMPETITIONS_FIELD_NAME', 'a.name', $listDirn, $listOrder); ?></th>
                <th><?= HTMLHelper::_('searchtools.sort', 'COM_XDECAROCOMPETITIONS_FIELD_TOURNAMENT', 't.name', $listDirn, $listOrder); ?></th>
                <th><?= HTMLHelper::_('searchtools.sort', 'COM_XDECAROCOMPETITIONS_FIELD_SEASON_YEAR', 'a.season_year', $listDirn, $listOrder); ?></th>
                <th><?= Text::_('COM_XDECAROCOMPETITIONS_FIELD_HOST'); ?></th>
                <th><?= HTMLHelper::_('searchtools.sort', 'COM_XDECAROCOMPETITIONS_FIELD_DATES', 'a.start_date', $listDirn, $listOrder); ?></th>
                <th class="text-center"><?= Text::_('JSTATUS'); ?></th>
                <th class="text-center"><?= Text::_('JGRID_HEADING_ID'); ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($this->items as $i => $item) : ?>
                <?php
                $hostParts = array_filter([trim((string) $item->host_city), trim((string) $item->host_country_name)]);
                $host = $hostParts ? implode(', ', $hostParts) : '—';
                $dateRange = $formatDate($item->start_date);
                if ($item->end_date && $item->end_date !== $item->start_date) { $dateRange .= ' – ' . $formatDate($item->end_date); }
                ?>
                <tr>
                    <td class="text-center competitions-responsive-table__check"><?= HTMLHelper::_('grid.id', $i, $item->id); ?></td>
                    <td data-label="<?= Text::_('COM_XDECAROCOMPETITIONS_FIELD_NAME'); ?>"><a class="fw-semibold" href="<?= Route::_('index.php?option=com_xdecarocompetitions&task=season.edit&id=' . (int) $item->id); ?>"><?= $this->escape($item->name); ?></a></td>
                    <td data-label="<?= Text::_('COM_XDECAROCOMPETITIONS_FIELD_TOURNAMENT'); ?>"><?= $this->escape($item->tournament_name ?: '—'); ?><?php if ($item->tournament_code) : ?><div class="small text-muted"><?= $this->escape($item->tournament_code); ?></div><?php endif; ?></td>
                    <td data-label="<?= Text::_('COM_XDECAROCOMPETITIONS_FIELD_SEASON_YEAR'); ?>"><?= $item->season_year ? (int) $item->season_year : '—'; ?></td>
                    <td data-label="<?= Text::_('COM_XDECAROCOMPETITIONS_FIELD_HOST'); ?>"><?= $this->escape($host); ?></td>
                    <td data-label="<?= Text::_('COM_XDECAROCOMPETITIONS_FIELD_DATES'); ?>"><?= $this->escape($dateRange); ?></td>
                    <td class="text-center" data-label="<?= Text::_('JSTATUS'); ?>"><?= HTMLHelper::_('jgrid.published', $item->state, $i, 'seasons.', $canChange, 'cb'); ?></td>
                    <td class="text-center" data-label="<?= Text::_('JGRID_HEADING_ID'); ?>"><?= (int) $item->id; ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$this->items) : ?><tr><td colspan="8" class="text-center py-5 text-muted"><?= Text::_('COM_XDECAROCOMPETITIONS_NO_SEASONS'); ?></td></tr><?php endif; ?>
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

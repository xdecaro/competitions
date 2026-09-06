<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$user = Factory::getApplication()->getIdentity();
$canChange = $user->authorise('core.edit.state', 'com_decarodcl');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));

$statusLabels = [
    'scheduled' => 'COM_DECARODCL_MATCH_SCHEDULED',
    'live' => 'COM_DECARODCL_MATCH_LIVE',
    'finished' => 'COM_DECARODCL_MATCH_FINISHED',
    'postponed' => 'COM_DECARODCL_MATCH_POSTPONED',
    'cancelled' => 'COM_DECARODCL_MATCH_CANCELLED',
];
?>
<form action="<?= Route::_('index.php?option=com_decarodcl&view=matches'); ?>" method="post" name="adminForm" id="adminForm" class="dcl-admin">
    <div class="dcl-filterbar">
        <div class="dcl-filterbar__search">
            <label class="visually-hidden" for="filter_search"><?= Text::_('JSEARCH_FILTER'); ?></label>
            <input type="search" name="filter_search" id="filter_search" value="<?= $this->escape($this->state->get('filter.search')); ?>" placeholder="<?= Text::_('COM_DECARODCL_SEARCH_MATCHES'); ?>">
        </div>
        <select name="filter_tournament_id" onchange="this.form.submit()"><option value="0"><?= Text::_('COM_DECARODCL_FILTER_ALL_TOURNAMENTS'); ?></option><?php foreach ($this->tournamentOptions as $tournament) : ?><option value="<?= (int) $tournament->id; ?>" <?= (int) $this->state->get('filter.tournament_id') === (int) $tournament->id ? 'selected' : ''; ?>><?= $this->escape($tournament->name); ?></option><?php endforeach; ?></select>
        <select name="filter_season_id" onchange="this.form.submit()"><option value="0"><?= Text::_('COM_DECARODCL_FILTER_ALL_SEASONS'); ?></option><?php foreach ($this->seasonOptions as $season) : ?><?php $label = $season->tournament_name . ' — ' . $season->name . ($season->season_year ? ' (' . $season->season_year . ')' : ''); ?><option value="<?= (int) $season->id; ?>" <?= (int) $this->state->get('filter.season_id') === (int) $season->id ? 'selected' : ''; ?>><?= $this->escape($label); ?></option><?php endforeach; ?></select>
        <select name="filter_team_id" onchange="this.form.submit()"><option value="0"><?= Text::_('COM_DECARODCL_FILTER_ALL_TEAMS'); ?></option><?php foreach ($this->teamOptions as $team) : ?><option value="<?= (int) $team->id; ?>" <?= (int) $this->state->get('filter.team_id') === (int) $team->id ? 'selected' : ''; ?>><?= $this->escape($team->name); ?></option><?php endforeach; ?></select>
        <select name="filter_status" onchange="this.form.submit()"><option value=""><?= Text::_('COM_DECARODCL_FILTER_ALL_MATCH_STATUSES'); ?></option><?php foreach ($statusLabels as $value => $label) : ?><option value="<?= $value; ?>" <?= (string) $this->state->get('filter.status') === $value ? 'selected' : ''; ?>><?= Text::_($label); ?></option><?php endforeach; ?></select>
        <select name="filter_state" onchange="this.form.submit()"><option value=""><?= Text::_('JOPTION_SELECT_PUBLISHED'); ?></option><option value="1" <?= (string) $this->state->get('filter.state') === '1' ? 'selected' : ''; ?>><?= Text::_('JPUBLISHED'); ?></option><option value="0" <?= (string) $this->state->get('filter.state') === '0' ? 'selected' : ''; ?>><?= Text::_('JUNPUBLISHED'); ?></option><option value="-2" <?= (string) $this->state->get('filter.state') === '-2' ? 'selected' : ''; ?>><?= Text::_('JTRASHED'); ?></option></select>
        <button class="btn btn-primary" type="submit"><?= Text::_('JSEARCH_FILTER_SUBMIT'); ?></button><a class="btn btn-secondary" href="<?= Route::_('index.php?option=com_decarodcl&view=matches'); ?>"><?= Text::_('JSEARCH_FILTER_CLEAR'); ?></a>
    </div>
    <div class="table-responsive"><table class="table table-striped align-middle dcl-responsive-table"><thead><tr><th class="w-1 text-center"><?= HTMLHelper::_('grid.checkall'); ?></th><th><?= HTMLHelper::_('searchtools.sort', 'COM_DECARODCL_FIELD_MATCH_DATE', 'a.match_date', $listDirn, $listOrder); ?></th><th><?= Text::_('COM_DECARODCL_FIELD_MATCH_STATUS'); ?></th><th><?= Text::_('COM_DECARODCL_FIELD_HOME_TEAM'); ?></th><th class="text-center"><?= Text::_('COM_DECARODCL_FIELD_SCORE'); ?></th><th><?= Text::_('COM_DECARODCL_FIELD_AWAY_TEAM'); ?></th><th><?= HTMLHelper::_('searchtools.sort', 'COM_DECARODCL_FIELD_COMPETITION', 't.name', $listDirn, $listOrder); ?></th><th><?= Text::_('COM_DECARODCL_FIELD_STAGE'); ?></th><th class="text-center"><?= Text::_('JSTATUS'); ?></th><th class="text-center"><?= Text::_('JGRID_HEADING_ID'); ?></th></tr></thead><tbody>
        <?php foreach ($this->items as $i => $item) : ?><?php $score='—'; if ($item->home_score !== null && $item->away_score !== null) { $score=(int)$item->home_score.' – '.(int)$item->away_score; if ($item->home_penalties !== null && $item->away_penalties !== null) $score.=' ('.(int)$item->home_penalties.'–'.(int)$item->away_penalties.')'; } ?><tr><td class="text-center dcl-responsive-table__check"><?= HTMLHelper::_('grid.id',$i,$item->id); ?></td><td data-label="<?= Text::_('COM_DECARODCL_FIELD_MATCH_DATE'); ?>"><a class="fw-semibold" href="<?= Route::_('index.php?option=com_decarodcl&task=match.edit&id='.(int)$item->id); ?>"><?= $this->escape($item->match_date ?: '—'); ?></a><?php if(!empty($item->kickoff_time)):?><div class="small text-muted"><?= $this->escape(substr((string)$item->kickoff_time,0,5)); ?></div><?php endif;?></td><td data-label="<?= Text::_('COM_DECARODCL_FIELD_MATCH_STATUS'); ?>"><?= Text::_($statusLabels[$item->status] ?? 'COM_DECARODCL_MATCH_SCHEDULED'); ?></td><td data-label="<?= Text::_('COM_DECARODCL_FIELD_HOME_TEAM'); ?>"><?= $this->escape($item->home_team_name); ?></td><td class="text-center fw-semibold" data-label="<?= Text::_('COM_DECARODCL_FIELD_SCORE'); ?>"><?= $this->escape($score); ?></td><td data-label="<?= Text::_('COM_DECARODCL_FIELD_AWAY_TEAM'); ?>"><?= $this->escape($item->away_team_name); ?></td><td data-label="<?= Text::_('COM_DECARODCL_FIELD_COMPETITION'); ?>"><?= $this->escape($item->tournament_name); ?><div class="small text-muted"><?= $this->escape($item->season_name); ?></div></td><td data-label="<?= Text::_('COM_DECARODCL_FIELD_STAGE'); ?>"><?= $this->escape($item->stage ?: '—'); ?><?php if(!empty($item->group_name)):?><div class="small text-muted"><?= $this->escape($item->group_name); ?></div><?php endif;?></td><td class="text-center" data-label="<?= Text::_('JSTATUS'); ?>"><?= HTMLHelper::_('jgrid.published',$item->state,$i,'matches.',$canChange,'cb'); ?></td><td class="text-center" data-label="<?= Text::_('JGRID_HEADING_ID'); ?>"><?= (int)$item->id; ?></td></tr><?php endforeach; ?>
        <?php if(!$this->items):?><tr><td colspan="10" class="text-center py-5 text-muted"><?= Text::_('COM_DECARODCL_NO_MATCHES'); ?></td></tr><?php endif;?></tbody></table></div>
    <?= $this->pagination->getListFooter(); ?><input type="hidden" name="task" value=""><input type="hidden" name="boxchecked" value="0"><input type="hidden" name="filter_order" value="<?= $listOrder; ?>"><input type="hidden" name="filter_order_Dir" value="<?= $listDirn; ?>"><?= HTMLHelper::_('form.token'); ?>
</form>

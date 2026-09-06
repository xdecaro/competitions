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

$genderLabels = [
    'men' => 'COM_DECARODCL_GENDER_MEN',
    'women' => 'COM_DECARODCL_GENDER_WOMEN',
    'mixed' => 'COM_DECARODCL_GENDER_MIXED',
    'open' => 'COM_DECARODCL_GENDER_OPEN',
];

$scopeLabels = [
    'international' => ['COM_DECARODCL_SCOPE_INTERNATIONAL', 'is-info'],
    'zone' => ['COM_DECARODCL_SCOPE_ZONE', 'is-info'],
    'national' => ['COM_DECARODCL_SCOPE_NATIONAL', 'is-success'],
    'local' => ['COM_DECARODCL_SCOPE_LOCAL', 'is-warning'],
];

$participantLabels = [
    'club' => ['COM_DECARODCL_PARTICIPANT_CLUB', 'is-muted'],
    'national' => ['COM_DECARODCL_PARTICIPANT_NATIONAL', 'is-success'],
];
?>
<form action="<?= Route::_('index.php?option=com_decarodcl&view=tournaments'); ?>" method="post" name="adminForm" id="adminForm" class="dcl-admin">
    <div class="dcl-filterbar dcl-filterbar--wide">
        <div class="dcl-filterbar__search">
            <label class="visually-hidden" for="filter_search"><?= Text::_('JSEARCH_FILTER'); ?></label>
            <input type="search" name="filter_search" id="filter_search" value="<?= $this->escape($this->state->get('filter.search')); ?>" placeholder="<?= Text::_('COM_DECARODCL_SEARCH_TOURNAMENTS'); ?>">
        </div>
        <select name="filter_scope_type" onchange="this.form.submit()">
            <option value=""><?= Text::_('COM_DECARODCL_FILTER_ALL_SCOPES'); ?></option>
            <?php foreach ($scopeLabels as $value => $meta) : ?>
                <option value="<?= $value; ?>" <?= (string) $this->state->get('filter.scope_type') === $value ? 'selected' : ''; ?>><?= Text::_($meta[0]); ?></option>
            <?php endforeach; ?>
        </select>
        <select name="filter_participant_type" onchange="this.form.submit()">
            <option value=""><?= Text::_('COM_DECARODCL_FILTER_ALL_PARTICIPANT_TYPES'); ?></option>
            <?php foreach ($participantLabels as $value => $meta) : ?>
                <option value="<?= $value; ?>" <?= (string) $this->state->get('filter.participant_type') === $value ? 'selected' : ''; ?>><?= Text::_($meta[0]); ?></option>
            <?php endforeach; ?>
        </select>
        <select name="filter_discipline" onchange="this.form.submit()">
            <option value=""><?= Text::_('COM_DECARODCL_FILTER_ALL_DISCIPLINES'); ?></option>
            <?php foreach ($this->disciplineOptions as $discipline) : ?>
                <option value="<?= $this->escape($discipline); ?>" <?= (string) $this->state->get('filter.discipline') === (string) $discipline ? 'selected' : ''; ?>><?= $this->escape($discipline); ?></option>
            <?php endforeach; ?>
        </select>
        <select name="filter_gender" onchange="this.form.submit()">
            <option value=""><?= Text::_('COM_DECARODCL_FILTER_ALL_GENDERS'); ?></option>
            <?php foreach ($genderLabels as $value => $label) : ?>
                <option value="<?= $value; ?>" <?= (string) $this->state->get('filter.gender') === $value ? 'selected' : ''; ?>><?= Text::_($label); ?></option>
            <?php endforeach; ?>
        </select>
        <select name="filter_state" onchange="this.form.submit()">
            <option value=""><?= Text::_('JOPTION_SELECT_PUBLISHED'); ?></option>
            <option value="1" <?= (string) $this->state->get('filter.state') === '1' ? 'selected' : ''; ?>><?= Text::_('JPUBLISHED'); ?></option>
            <option value="0" <?= (string) $this->state->get('filter.state') === '0' ? 'selected' : ''; ?>><?= Text::_('JUNPUBLISHED'); ?></option>
            <option value="-2" <?= (string) $this->state->get('filter.state') === '-2' ? 'selected' : ''; ?>><?= Text::_('JTRASHED'); ?></option>
        </select>
        <button class="btn btn-primary" type="submit"><?= Text::_('JSEARCH_FILTER_SUBMIT'); ?></button>
        <a class="btn btn-secondary" href="<?= Route::_('index.php?option=com_decarodcl&view=tournaments'); ?>"><?= Text::_('JSEARCH_FILTER_CLEAR'); ?></a>
    </div>

    <div class="table-responsive">
        <table class="table table-striped align-middle dcl-responsive-table">
            <thead><tr>
                <th class="w-1 text-center"><?= HTMLHelper::_('grid.checkall'); ?></th>
                <th><?= HTMLHelper::_('searchtools.sort', 'COM_DECARODCL_FIELD_NAME', 'a.name', $listDirn, $listOrder); ?></th>
                <th><?= HTMLHelper::_('searchtools.sort', 'COM_DECARODCL_FIELD_DISCIPLINE', 'a.discipline', $listDirn, $listOrder); ?></th>
                <th><?= HTMLHelper::_('searchtools.sort', 'COM_DECARODCL_FIELD_SCOPE_TYPE', 'a.scope_type', $listDirn, $listOrder); ?></th>
                <th class="text-center"><?= HTMLHelper::_('searchtools.sort', 'COM_DECARODCL_SEASONS', 'seasons_count', $listDirn, $listOrder); ?></th>
                <th class="text-center"><?= Text::_('JSTATUS'); ?></th>
                <th class="text-center d-none d-lg-table-cell"><?= Text::_('JGRID_HEADING_ID'); ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($this->items as $i => $item) : ?>
                <?php
                $scope = $scopeLabels[$item->scope_type] ?? ['COM_DECARODCL_SCOPE_INTERNATIONAL', 'is-muted'];
                $participant = $participantLabels[$item->participant_type] ?? ['COM_DECARODCL_PARTICIPANT_CLUB', 'is-muted'];
                $scopeDetail = '';

                if ($item->scope_type === 'zone') {
                    $scopeDetail = (string) ($item->scope_zones ?? '');
                } elseif (in_array($item->scope_type, ['national', 'local'], true)) {
                    $scopeDetail = (string) ($item->scope_countries ?? '');
                    if ($item->scope_type === 'local' && !empty($item->local_area)) {
                        $scopeDetail .= ($scopeDetail !== '' ? ' · ' : '') . (string) $item->local_area;
                    }
                }
                ?>
                <tr>
                    <td class="text-center dcl-responsive-table__check"><?= HTMLHelper::_('grid.id', $i, $item->id); ?></td>
                    <td data-label="<?= Text::_('COM_DECARODCL_FIELD_NAME'); ?>">
                        <a class="fw-semibold" href="<?= Route::_('index.php?option=com_decarodcl&task=tournament.edit&id=' . (int) $item->id); ?>"><?= $this->escape($item->name); ?></a>
                        <div class="small text-muted"><span class="badge bg-secondary"><?= $this->escape($item->code); ?></span></div>
                    </td>
                    <td data-label="<?= Text::_('COM_DECARODCL_FIELD_DISCIPLINE'); ?>">
                        <div><?= $this->escape($item->discipline ?: '—'); ?></div>
                        <div class="small text-muted"><?= $item->gender && isset($genderLabels[$item->gender]) ? Text::_($genderLabels[$item->gender]) : '—'; ?></div>
                    </td>
                    <td data-label="<?= Text::_('COM_DECARODCL_FIELD_SCOPE_TYPE'); ?>">
                        <div class="d-flex flex-wrap gap-1">
                            <span class="dcl-badge <?= $scope[1]; ?>"><?= Text::_($scope[0]); ?></span>
                            <span class="dcl-badge <?= $participant[1]; ?>"><?= Text::_($participant[0]); ?></span>
                        </div>
                        <?php if ($scopeDetail !== '') : ?>
                            <div class="small text-muted mt-1"><?= $this->escape($scopeDetail); ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="text-center" data-label="<?= Text::_('COM_DECARODCL_SEASONS'); ?>"><?= (int) $item->seasons_count; ?></td>
                    <td class="text-center" data-label="<?= Text::_('JSTATUS'); ?>"><?= HTMLHelper::_('jgrid.published', $item->state, $i, 'tournaments.', $canChange, 'cb'); ?></td>
                    <td class="text-center d-none d-lg-table-cell" data-label="<?= Text::_('JGRID_HEADING_ID'); ?>"><?= (int) $item->id; ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$this->items) : ?><tr><td colspan="7" class="text-center py-5 text-muted"><?= Text::_('COM_DECARODCL_NO_TOURNAMENTS'); ?></td></tr><?php endif; ?>
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

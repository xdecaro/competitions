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
?>
<form action="<?= Route::_('index.php?option=com_xdecarocompetitions&view=zones'); ?>" method="post" name="adminForm" id="adminForm" class="competitions-admin">
    <div class="competitions-filterbar">
        <div class="competitions-filterbar__search">
            <label class="visually-hidden" for="filter_search"><?= Text::_('JSEARCH_FILTER'); ?></label>
            <input type="search" name="filter_search" id="filter_search" value="<?= $this->escape($this->state->get('filter.search')); ?>" placeholder="<?= Text::_('COM_XDECAROCOMPETITIONS_SEARCH_ZONES'); ?>">
        </div>

        <select name="filter_organization_id" onchange="this.form.submit()">
            <option value="0"><?= Text::_('COM_XDECAROCOMPETITIONS_FILTER_ALL_ORGANIZATIONS'); ?></option>
            <?php foreach ($this->organizationOptions as $organization) : ?>
                <?php $label = $organization->name . ($organization->short_name ? ' (' . $organization->short_name . ')' : ''); ?>
                <option value="<?= (int) $organization->id; ?>" <?= (int) $this->state->get('filter.organization_id') === (int) $organization->id ? 'selected' : ''; ?>>
                    <?= $this->escape($label); ?>
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
        <a class="btn btn-secondary" href="<?= Route::_('index.php?option=com_xdecarocompetitions&view=zones'); ?>"><?= Text::_('JSEARCH_FILTER_CLEAR'); ?></a>
    </div>

    <div class="table-responsive">
        <table class="table table-striped align-middle competitions-responsive-table">
            <thead>
                <tr>
                    <th class="w-1 text-center"><?= HTMLHelper::_('grid.checkall'); ?></th>
                    <th><?= HTMLHelper::_('searchtools.sort', 'COM_XDECAROCOMPETITIONS_FIELD_NAME', 'a.name', $listDirn, $listOrder); ?></th>
                    <th><?= HTMLHelper::_('searchtools.sort', 'COM_XDECAROCOMPETITIONS_FIELD_CODE', 'a.code', $listDirn, $listOrder); ?></th>
                    <th><?= HTMLHelper::_('searchtools.sort', 'COM_XDECAROCOMPETITIONS_FIELD_ORGANIZATION', 'o.name', $listDirn, $listOrder); ?></th>
                    <th class="text-center"><?= HTMLHelper::_('searchtools.sort', 'COM_XDECAROCOMPETITIONS_COUNTRIES', 'countries_count', $listDirn, $listOrder); ?></th>
                    <th><?= Text::_('COM_XDECAROCOMPETITIONS_ZONE_COUNTRIES'); ?></th>
                    <th class="text-center"><?= Text::_('JSTATUS'); ?></th>
                    <th class="text-center"><?= Text::_('JGRID_HEADING_ID'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($this->items as $i => $item) : ?>
                <tr>
                    <td class="text-center competitions-responsive-table__check"><?= HTMLHelper::_('grid.id', $i, $item->id); ?></td>
                    <td data-label="<?= Text::_('COM_XDECAROCOMPETITIONS_FIELD_NAME'); ?>">
                        <a class="fw-semibold" href="<?= Route::_('index.php?option=com_xdecarocompetitions&task=zone.edit&id=' . (int) $item->id); ?>">
                            <?= $this->escape($item->name); ?>
                        </a>
                    </td>
                    <td data-label="<?= Text::_('COM_XDECAROCOMPETITIONS_FIELD_CODE'); ?>"><span class="badge bg-secondary"><?= $this->escape($item->code); ?></span></td>
                    <td data-label="<?= Text::_('COM_XDECAROCOMPETITIONS_FIELD_ORGANIZATION'); ?>">
                        <?= $item->organization_name ? $this->escape($item->organization_name) : Text::_('COM_XDECAROCOMPETITIONS_ZONE_GLOBAL'); ?>
                    </td>
                    <td class="text-center" data-label="<?= Text::_('COM_XDECAROCOMPETITIONS_COUNTRIES'); ?>"><?= (int) $item->countries_count; ?></td>
                    <td data-label="<?= Text::_('COM_XDECAROCOMPETITIONS_ZONE_COUNTRIES'); ?>">
                        <span class="small"><?= $item->country_names ? $this->escape($item->country_names) : '—'; ?></span>
                    </td>
                    <td class="text-center" data-label="<?= Text::_('JSTATUS'); ?>"><?= HTMLHelper::_('jgrid.published', $item->state, $i, 'zones.', $canChange, 'cb'); ?></td>
                    <td class="text-center" data-label="<?= Text::_('JGRID_HEADING_ID'); ?>"><?= (int) $item->id; ?></td>
                </tr>
            <?php endforeach; ?>

            <?php if (!$this->items) : ?>
                <tr><td colspan="8" class="text-center py-5 text-muted"><?= Text::_('COM_XDECAROCOMPETITIONS_NO_ZONES'); ?></td></tr>
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

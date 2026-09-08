<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->useStyle('com_xdecarocompetitions.admin');

$user = Factory::getApplication()->getIdentity();
$canChange = $user->authorise('core.edit.state', 'com_xdecarocompetitions');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));
?>
<form action="<?= Route::_('index.php?option=com_xdecarocompetitions&view=countries'); ?>" method="post" name="adminForm" id="adminForm" class="competitions-admin">
    <div class="competitions-filterbar">
        <div class="competitions-filterbar__search">
            <label class="visually-hidden" for="filter_search"><?= Text::_('JSEARCH_FILTER'); ?></label>
            <input type="search" name="filter_search" id="filter_search" value="<?= $this->escape($this->state->get('filter.search')); ?>" placeholder="<?= Text::_('COM_XDECAROCOMPETITIONS_SEARCH_COUNTRIES'); ?>">
        </div>
        <select name="filter_entity_type" onchange="this.form.submit()">
            <option value=""><?= Text::_('COM_XDECAROCOMPETITIONS_FILTER_ALL_TYPES'); ?></option>
            <option value="country" <?= $this->state->get('filter.entity_type') === 'country' ? 'selected' : ''; ?>><?= Text::_('COM_XDECAROCOMPETITIONS_ENTITY_COUNTRY'); ?></option>
            <option value="sport_territory" <?= $this->state->get('filter.entity_type') === 'sport_territory' ? 'selected' : ''; ?>><?= Text::_('COM_XDECAROCOMPETITIONS_ENTITY_SPORT_TERRITORY'); ?></option>
        </select>
        <select name="filter_zone_id" onchange="this.form.submit()">
            <option value="0"><?= Text::_('COM_XDECAROCOMPETITIONS_FILTER_ALL_ZONES'); ?></option>
            <?php foreach ($this->zoneOptions as $zone) : ?>
                <?php $label = $zone->name . ($zone->organization_name ? ' — ' . $zone->organization_name : ''); ?>
                <option value="<?= (int) $zone->id; ?>" <?= (int) $this->state->get('filter.zone_id') === (int) $zone->id ? 'selected' : ''; ?>><?= $this->escape($label); ?></option>
            <?php endforeach; ?>
        </select>
        <select name="filter_state" onchange="this.form.submit()">
            <option value=""><?= Text::_('JOPTION_SELECT_PUBLISHED'); ?></option>
            <option value="1" <?= (string) $this->state->get('filter.state') === '1' ? 'selected' : ''; ?>><?= Text::_('JPUBLISHED'); ?></option>
            <option value="0" <?= (string) $this->state->get('filter.state') === '0' ? 'selected' : ''; ?>><?= Text::_('JUNPUBLISHED'); ?></option>
            <option value="-2" <?= (string) $this->state->get('filter.state') === '-2' ? 'selected' : ''; ?>><?= Text::_('JTRASHED'); ?></option>
        </select>
        <button class="btn btn-primary" type="submit"><?= Text::_('JSEARCH_FILTER_SUBMIT'); ?></button>
        <a class="btn btn-secondary" href="<?= Route::_('index.php?option=com_xdecarocompetitions&view=countries'); ?>"><?= Text::_('JSEARCH_FILTER_CLEAR'); ?></a>
    </div>

    <div class="table-responsive">
        <table class="table table-striped align-middle competitions-responsive-table">
            <thead>
                <tr>
                    <th class="w-1 text-center"><?= HTMLHelper::_('grid.checkall'); ?></th>
                    <th><?= HTMLHelper::_('searchtools.sort', 'COM_XDECAROCOMPETITIONS_FIELD_NAME', 'a.name', $listDirn, $listOrder); ?></th>
                    <th><?= HTMLHelper::_('searchtools.sort', 'COM_XDECAROCOMPETITIONS_FIELD_CODE', 'a.code', $listDirn, $listOrder); ?></th>
                    <th><?= HTMLHelper::_('searchtools.sort', 'COM_XDECAROCOMPETITIONS_FIELD_ENTITY_TYPE', 'a.entity_type', $listDirn, $listOrder); ?></th>
                    <th><?= HTMLHelper::_('searchtools.sort', 'COM_XDECAROCOMPETITIONS_ZONES', 'zones_count', $listDirn, $listOrder); ?></th>
                    <th class="text-center"><?= HTMLHelper::_('searchtools.sort', 'COM_XDECAROCOMPETITIONS_FEDERATIONS', 'federations_count', $listDirn, $listOrder); ?></th>
                    <th class="text-center"><?= Text::_('JSTATUS'); ?></th>
                    <th class="text-center"><?= Text::_('JGRID_HEADING_ID'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($this->items as $i => $item) : ?>
                <tr>
                    <td class="text-center competitions-responsive-table__check"><?= HTMLHelper::_('grid.id', $i, $item->id); ?></td>
                    <td data-label="<?= Text::_('COM_XDECAROCOMPETITIONS_FIELD_NAME'); ?>">
                        <a class="fw-semibold" href="<?= Route::_('index.php?option=com_xdecarocompetitions&task=country.edit&id=' . (int) $item->id); ?>">
                            <?= $this->escape($item->name); ?>
                        </a>
                        <?php if ($item->iso2 || $item->iso3) : ?>
                            <div class="small text-muted"><?= $this->escape(trim(($item->iso2 ?: '') . ' ' . ($item->iso3 ?: ''))); ?></div>
                        <?php endif; ?>
                    </td>
                    <td data-label="<?= Text::_('COM_XDECAROCOMPETITIONS_FIELD_CODE'); ?>"><span class="badge bg-secondary"><?= $this->escape($item->code); ?></span></td>
                    <td data-label="<?= Text::_('COM_XDECAROCOMPETITIONS_FIELD_ENTITY_TYPE'); ?>"><?= Text::_($item->entity_type === 'sport_territory' ? 'COM_XDECAROCOMPETITIONS_ENTITY_SPORT_TERRITORY' : 'COM_XDECAROCOMPETITIONS_ENTITY_COUNTRY'); ?></td>
                    <td data-label="<?= Text::_('COM_XDECAROCOMPETITIONS_ZONES'); ?>">
                        <?php if ((int) $item->zones_count > 0) : ?>
                            <span class="badge bg-info text-dark me-1"><?= (int) $item->zones_count; ?></span>
                            <span class="small"><?= $this->escape((string) $item->zone_names); ?></span>
                        <?php else : ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center" data-label="<?= Text::_('COM_XDECAROCOMPETITIONS_FEDERATIONS'); ?>"><?= (int) $item->federations_count; ?></td>
                    <td class="text-center" data-label="<?= Text::_('JSTATUS'); ?>"><?= HTMLHelper::_('jgrid.published', $item->state, $i, 'countries.', $canChange, 'cb'); ?></td>
                    <td class="text-center" data-label="<?= Text::_('JGRID_HEADING_ID'); ?>"><?= (int) $item->id; ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$this->items) : ?>
                <tr><td colspan="8" class="text-center py-5 text-muted"><?= Text::_('COM_XDECAROCOMPETITIONS_NO_COUNTRIES'); ?></td></tr>
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

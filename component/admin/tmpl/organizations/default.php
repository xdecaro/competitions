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
?>
<form action="<?= Route::_('index.php?option=com_decarodcl&view=organizations'); ?>" method="post" name="adminForm" id="adminForm" class="dcl-admin">
    <div class="dcl-filterbar">
        <div class="dcl-filterbar__search"><label class="visually-hidden" for="filter_search"><?= Text::_('JSEARCH_FILTER'); ?></label><input type="search" name="filter_search" id="filter_search" value="<?= $this->escape($this->state->get('filter.search')); ?>" placeholder="<?= Text::_('COM_DECARODCL_SEARCH_ORGANIZATIONS'); ?>"></div>
        <select name="filter_country_id" onchange="this.form.submit()"><option value="0"><?= Text::_('COM_DECARODCL_FILTER_ALL_COUNTRIES'); ?></option><?php foreach ($this->countryOptions as $country) : ?><option value="<?= (int) $country->id; ?>" <?= (int) $this->state->get('filter.country_id') === (int) $country->id ? 'selected' : ''; ?>><?= $this->escape($country->name . ' (' . $country->code . ')'); ?></option><?php endforeach; ?></select>
        <select name="filter_state" onchange="this.form.submit()"><option value=""><?= Text::_('JOPTION_SELECT_PUBLISHED'); ?></option><option value="1" <?= (string) $this->state->get('filter.state') === '1' ? 'selected' : ''; ?>><?= Text::_('JPUBLISHED'); ?></option><option value="0" <?= (string) $this->state->get('filter.state') === '0' ? 'selected' : ''; ?>><?= Text::_('JUNPUBLISHED'); ?></option><option value="-2" <?= (string) $this->state->get('filter.state') === '-2' ? 'selected' : ''; ?>><?= Text::_('JTRASHED'); ?></option></select>
        <button class="btn btn-primary" type="submit"><?= Text::_('JSEARCH_FILTER_SUBMIT'); ?></button>
        <a class="btn btn-secondary" href="<?= Route::_('index.php?option=com_decarodcl&view=organizations'); ?>"><?= Text::_('JSEARCH_FILTER_CLEAR'); ?></a>
    </div>
    <div class="table-responsive"><table class="table table-striped align-middle dcl-responsive-table">
        <thead><tr><th class="w-1 text-center"><?= HTMLHelper::_('grid.checkall'); ?></th><th><?= HTMLHelper::_('searchtools.sort', 'COM_DECARODCL_FIELD_NAME', 'a.name', $listDirn, $listOrder); ?></th><th><?= HTMLHelper::_('searchtools.sort', 'COM_DECARODCL_FIELD_SHORT_NAME', 'a.short_name', $listDirn, $listOrder); ?></th><th><?= HTMLHelper::_('searchtools.sort', 'COM_DECARODCL_FIELD_COUNTRY', 'c.name', $listDirn, $listOrder); ?></th><th class="text-center"><?= Text::_('JSTATUS'); ?></th><th class="text-center"><?= Text::_('JGRID_HEADING_ID'); ?></th></tr></thead>
        <tbody>
        <?php foreach ($this->items as $i => $item) : ?><tr>
            <td class="text-center dcl-responsive-table__check"><?= HTMLHelper::_('grid.id', $i, $item->id); ?></td>
            <td data-label="<?= Text::_('COM_DECARODCL_FIELD_NAME'); ?>"><a class="fw-semibold" href="<?= Route::_('index.php?option=com_decarodcl&task=organization.edit&id=' . (int) $item->id); ?>"><?= $this->escape($item->name); ?></a></td>
            <td data-label="<?= Text::_('COM_DECARODCL_FIELD_SHORT_NAME'); ?>"><?= $this->escape($item->short_name ?: '—'); ?></td>
            <td data-label="<?= Text::_('COM_DECARODCL_FIELD_COUNTRY'); ?>"><?= $this->escape($item->country_name ?: '—'); ?> <?php if (!empty($item->country_code)) : ?><span class="badge bg-secondary"><?= $this->escape($item->country_code); ?></span><?php endif; ?></td>
            <td class="text-center" data-label="<?= Text::_('JSTATUS'); ?>"><?= HTMLHelper::_('jgrid.published', $item->state, $i, 'organizations.', $canChange, 'cb'); ?></td>
            <td class="text-center" data-label="<?= Text::_('JGRID_HEADING_ID'); ?>"><?= (int) $item->id; ?></td>
        </tr><?php endforeach; ?>
        <?php if (!$this->items) : ?><tr><td colspan="6" class="text-center py-5 text-muted"><?= Text::_('COM_DECARODCL_NO_ORGANIZATIONS'); ?></td></tr><?php endif; ?>
        </tbody>
    </table></div>
    <?= $this->pagination->getListFooter(); ?>
    <input type="hidden" name="task" value=""><input type="hidden" name="boxchecked" value="0"><input type="hidden" name="filter_order" value="<?= $listOrder; ?>"><input type="hidden" name="filter_order_Dir" value="<?= $listDirn; ?>"><?= HTMLHelper::_('form.token'); ?>
</form>

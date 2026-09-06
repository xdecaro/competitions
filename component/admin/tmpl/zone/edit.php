<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

HTMLHelper::_('behavior.formvalidator');
?>
<form action="index.php?option=com_decarodcl&layout=edit&id=<?= (int) $this->item->id; ?>" method="post" name="adminForm" id="zone-form" class="form-validate dcl-admin">
    <div class="card mb-3">
        <div class="card-body"><?= $this->form->renderFieldset('details'); ?></div>
    </div>

    <div class="card">
        <div class="card-header"><strong><?= Text::_('COM_DECARODCL_ZONE_COUNTRIES'); ?></strong></div>
        <div class="card-body">
            <p class="text-muted"><?= Text::_('COM_DECARODCL_ZONE_COUNTRIES_DESC'); ?></p>
            <?= $this->form->renderFieldset('countries'); ?>
        </div>
    </div>

    <input type="hidden" name="task" value="">
    <?= HTMLHelper::_('form.token'); ?>
</form>

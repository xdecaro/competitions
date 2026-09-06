<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('behavior.formvalidator');
?>
<form action="index.php?option=com_decarodcl&layout=edit&id=<?= (int) $this->item->id; ?>" method="post" name="adminForm" id="roster-form" class="form-validate dcl-admin">
    <div class="card">
        <div class="card-body">
            <?= $this->form->renderFieldset('details'); ?>
        </div>
    </div>
    <input type="hidden" name="task" value="">
    <?= HTMLHelper::_('form.token'); ?>
</form>

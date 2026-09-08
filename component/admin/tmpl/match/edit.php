<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('behavior.formvalidator');
?>
<form action="index.php?option=com_xdecarocompetitions&layout=edit&id=<?= (int) $this->item->id; ?>" method="post" name="adminForm" id="match-form" class="form-validate competitions-admin">
    <div class="card mb-3">
        <div class="card-body"><?= $this->form->renderFieldset('details'); ?></div>
    </div>

    <div class="card mb-3">
        <div class="card-body"><?= $this->form->renderFieldset('score'); ?></div>
    </div>

    <div class="card">
        <div class="card-body"><?= $this->form->renderFieldset('additional'); ?></div>
    </div>

    <input type="hidden" name="task" value="">
    <?= HTMLHelper::_('form.token'); ?>
</form>

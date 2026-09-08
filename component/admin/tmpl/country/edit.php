<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;

$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->useStyle('com_xdecarocompetitions.admin');

HTMLHelper::_('behavior.formvalidator');
?>
<form action="index.php?option=com_xdecarocompetitions&layout=edit&id=<?= (int) $this->item->id; ?>" method="post" name="adminForm" id="country-form" class="form-validate competitions-admin">
    <div class="card">
        <div class="card-body">
            <?= $this->form->renderFieldset('details'); ?>
        </div>
    </div>
    <input type="hidden" name="task" value="">
    <?= HTMLHelper::_('form.token'); ?>
</form>

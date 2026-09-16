<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.formvalidator');

$esc = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$personUuid = strtolower(trim((string) ($this->item->person_uuid ?? '')));
?>
<form action="index.php?option=com_xdecarocompetitions&layout=edit&id=<?= (int) $this->item->id; ?>" method="post" name="adminForm" id="player-form" class="form-validate competitions-admin">
    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h5 mb-3"><?= Text::_('COM_XDECAROCOMPETITIONS_PEOPLE_IDENTITY'); ?></h2>

            <?php if ($personUuid !== '' && $this->person): ?>
                <div class="alert alert-light mb-3" data-competitions-person-summary>
                    <strong><?= $esc($this->person['display_name'] ?? trim(($this->person['first_name'] ?? '') . ' ' . ($this->person['last_name'] ?? ''))); ?></strong>
                    <?php $meta = array_filter([(string) ($this->person['email'] ?? ''), (string) ($this->person['phone'] ?? '')]); ?>
                    <?php if ($meta): ?>
                        <div class="small text-body-secondary mt-1"><?= $esc(implode(' · ', $meta)); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($this->person['id'])): ?>
                        <div class="mt-2">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= Route::_('index.php?option=com_xdecaropeople&task=person.edit&id=' . (int) $this->person['id']); ?>">
                                <?= Text::_('COM_XDECAROCOMPETITIONS_PEOPLE_OPEN'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                <input type="hidden" name="jform[person_uuid]" value="<?= $esc($personUuid); ?>" data-competitions-person-target>
            <?php elseif ($personUuid !== ''): ?>
                <div class="alert alert-warning mb-3"><?= Text::_('COM_XDECAROCOMPETITIONS_PEOPLE_UNAVAILABLE'); ?></div>
                <input type="hidden" name="jform[person_uuid]" value="<?= $esc($personUuid); ?>" data-competitions-person-target>
            <?php else: ?>
                <div data-competitions-people-picker>
                    <label class="form-label" for="competitions_people_search"><?= Text::_('COM_XDECAROCOMPETITIONS_PEOPLE_SEARCH'); ?> *</label>
                    <input
                        type="search"
                        id="competitions_people_search"
                        class="form-control"
                        data-competitions-people-search
                        data-empty-label="<?= $esc(Text::_('COM_XDECAROCOMPETITIONS_PEOPLE_SEARCH_EMPTY')); ?>"
                        autocomplete="off"
                        placeholder="<?= $esc(Text::_('COM_XDECAROCOMPETITIONS_PEOPLE_SEARCH_PLACEHOLDER')); ?>"
                    >
                    <input type="hidden" name="jform[person_uuid]" value="" data-competitions-person-target>
                    <div data-competitions-people-results></div>
                    <div class="alert alert-light mt-2 mb-0" data-competitions-person-summary hidden></div>
                    <?php if (!empty($this->item->id)): ?>
                        <p class="small text-body-secondary mt-2 mb-0"><?= Text::_('COM_XDECAROCOMPETITIONS_PEOPLE_LEGACY_NOTICE'); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <?= $this->form->renderFieldset('details'); ?>
        </div>
    </div>
    <input type="hidden" name="task" value="">
    <?= HTMLHelper::_('form.token'); ?>
</form>

<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$season = $this->context['season'] ?? [];
$participations = $this->context['participations'] ?? [];
$availability = $this->context['availability'] ?? ['available' => false, 'version' => '', 'reason' => 'missing'];
$latest = $this->context['latest_link'] ?? null;
$approvedCount = (int) ($this->context['approved_count'] ?? 0);
$available = !empty($availability['available']);
$canSubmit = $available && $this->canCreate && $this->canCreateDraw && $approvedCount >= 2;
?>
<div class="competitions-admin">
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                <div>
                    <div class="text-muted small mb-1"><?= Text::_('COM_XDECAROCOMPETITIONS_DRAW_SEASON'); ?></div>
                    <h2 class="h4 mb-1"><?= $this->escape((string) ($season['tournament_name'] ?? '')); ?></h2>
                    <div class="text-muted">
                        <?= $this->escape((string) ($season['name'] ?? '')); ?>
                        <?php if (!empty($season['season_year'])) : ?>
                            · <?= (int) $season['season_year']; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <span class="badge bg-success fs-6">
                    <?= Text::sprintf('COM_XDECAROCOMPETITIONS_DRAW_APPROVED_COUNT', $approvedCount); ?>
                </span>
            </div>
        </div>
    </div>

    <?php if (!$available) : ?>
        <div class="alert alert-warning" role="alert">
            <strong><?= Text::_('COM_XDECAROCOMPETITIONS_DRAW_UNAVAILABLE_TITLE'); ?></strong>
            <div class="mt-1">
                <?php if (($availability['reason'] ?? '') === 'incompatible') : ?>
                    <?= Text::sprintf('COM_XDECAROCOMPETITIONS_DRAW_INCOMPATIBLE', (string) ($availability['version'] ?? '')); ?>
                <?php else : ?>
                    <?= Text::_('COM_XDECAROCOMPETITIONS_DRAW_MISSING'); ?>
                <?php endif; ?>
            </div>
        </div>
    <?php elseif (!$this->canCreateDraw) : ?>
        <div class="alert alert-warning" role="alert"><?= Text::_('COM_XDECAROCOMPETITIONS_DRAW_ERR_DRAW_ACL'); ?></div>
    <?php endif; ?>

    <?php if ($latest) : ?>
        <div class="alert alert-info d-flex flex-wrap align-items-center justify-content-between gap-2" role="status">
            <div>
                <strong><?= Text::_('COM_XDECAROCOMPETITIONS_DRAW_LINKED_TITLE'); ?></strong>
                <?= Text::sprintf(
                    'COM_XDECAROCOMPETITIONS_DRAW_LINKED_INFO',
                    (int) $latest['draw_id'],
                    (int) $latest['group_count'],
                    $this->escape((string) ($latest['status'] ?? 'ready'))
                ); ?>
            </div>
            <a class="btn btn-outline-primary" href="<?= Route::_('index.php?option=com_xdecarodraw&view=draw&id=' . (int) $latest['draw_id']); ?>">
                <?= Text::_('COM_XDECAROCOMPETITIONS_DRAW_OPEN'); ?>
            </a>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-12 col-xl-5">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="h5 mb-3"><?= Text::_('COM_XDECAROCOMPETITIONS_DRAW_CONFIGURATION'); ?></h3>
                    <form action="<?= Route::_('index.php?option=com_xdecarocompetitions&task=participations.createDraw'); ?>" method="post">
                        <div class="mb-3">
                            <label class="form-label" for="group_count"><?= Text::_('COM_XDECAROCOMPETITIONS_DRAW_GROUP_COUNT'); ?></label>
                            <input
                                class="form-control"
                                type="number"
                                name="group_count"
                                id="group_count"
                                min="2"
                                max="<?= max(2, $approvedCount); ?>"
                                inputmode="numeric"
                                required
                                <?= $canSubmit ? '' : 'disabled'; ?>
                            >
                            <div class="form-text"><?= Text::_('COM_XDECAROCOMPETITIONS_DRAW_GROUP_COUNT_HELP'); ?></div>
                        </div>

                        <input type="hidden" name="season_id" value="<?= (int) $this->seasonId; ?>">
                        <?= HTMLHelper::_('form.token'); ?>

                        <button class="btn btn-primary" type="submit" <?= $canSubmit ? '' : 'disabled'; ?>>
                            <span class="icon-shuffle" aria-hidden="true"></span>
                            <?= Text::_('COM_XDECAROCOMPETITIONS_DRAW_CREATE'); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-7">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="h5 mb-3"><?= Text::_('COM_XDECAROCOMPETITIONS_DRAW_APPROVED_TEAMS'); ?></h3>
                    <?php if ($participations) : ?>
                        <div class="table-responsive">
                            <table class="table table-striped align-middle mb-0 competitions-responsive-table">
                                <thead>
                                    <tr>
                                        <th><?= Text::_('COM_XDECAROCOMPETITIONS_FIELD_TEAM'); ?></th>
                                        <th><?= Text::_('COM_XDECAROCOMPETITIONS_FIELD_COUNTRY'); ?></th>
                                        <th class="text-end"><?= Text::_('JGRID_HEADING_ID'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($participations as $participation) : ?>
                                    <tr>
                                        <td data-label="<?= Text::_('COM_XDECAROCOMPETITIONS_FIELD_TEAM'); ?>">
                                            <strong><?= $this->escape((string) ($participation['team_name'] ?? '')); ?></strong>
                                            <?php if (!empty($participation['team_short_name'])) : ?>
                                                <div class="small text-muted"><?= $this->escape((string) $participation['team_short_name']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="<?= Text::_('COM_XDECAROCOMPETITIONS_FIELD_COUNTRY'); ?>"><?= $this->escape((string) ($participation['country_code'] ?? '—')); ?></td>
                                        <td class="text-end" data-label="<?= Text::_('JGRID_HEADING_ID'); ?>"><?= (int) $participation['participation_id']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else : ?>
                        <div class="text-muted py-3"><?= Text::_('COM_XDECAROCOMPETITIONS_DRAW_NO_APPROVED'); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
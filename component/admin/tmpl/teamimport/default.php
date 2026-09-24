<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$statusMap = [
    'ready' => ['COM_XDECAROCOMPETITIONS_TEAMIMPORT_STATUS_READY', 'text-bg-success'],
    'no_affiliation' => ['COM_XDECAROCOMPETITIONS_TEAMIMPORT_STATUS_NO_AFFILIATION', 'text-bg-warning'],
    'unmapped' => ['COM_XDECAROCOMPETITIONS_TEAMIMPORT_STATUS_FEDERATION_UNMAPPED', 'text-bg-warning'],
    'ambiguous' => ['COM_XDECAROCOMPETITIONS_TEAMIMPORT_STATUS_AMBIGUOUS', 'text-bg-danger'],
    'unavailable' => ['COM_XDECAROCOMPETITIONS_TEAMIMPORT_STATUS_UNAVAILABLE', 'text-bg-secondary'],
    'imported' => ['COM_XDECAROCOMPETITIONS_TEAMIMPORT_STATUS_IMPORTED', 'text-bg-secondary'],
    'new' => ['COM_XDECAROCOMPETITIONS_TEAMIMPORT_STATUS_NEW', 'text-bg-info'],
];
?>
<div class="competitions-admin" data-teamimport-live>
    <div class="mb-4">
        <p class="text-muted mb-0"><?= Text::_('COM_XDECAROCOMPETITIONS_TEAMIMPORT_INTRO'); ?></p>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label" for="teamimport_search">
                        <?= Text::_('JSEARCH_FILTER'); ?>
                    </label>
                    <div class="input-group">
                        <input
                            class="form-control"
                            type="search"
                            id="teamimport_search"
                            value=""
                            autocomplete="off"
                            data-teamimport-search
                            placeholder="<?= Text::_('COM_XDECAROCOMPETITIONS_TEAMIMPORT_SEARCH_PLACEHOLDER'); ?>"
                        >
                        <button class="btn btn-outline-secondary" type="button" data-teamimport-clear>
                            <?= Text::_('JSEARCH_FILTER_CLEAR'); ?>
                        </button>
                    </div>
                    <div class="form-text">
                        <?= Text::_('COM_XDECAROCOMPETITIONS_TEAMIMPORT_LIVE_SEARCH_DESC'); ?>
                    </div>
                </div>

                <div class="col-12 d-flex flex-wrap align-items-center gap-3">
                    <div class="form-check mb-0">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            id="teamimport_selected_only"
                            data-teamimport-selected-only
                        >
                        <label class="form-check-label" for="teamimport_selected_only">
                            <?= Text::_('COM_XDECAROCOMPETITIONS_TEAMIMPORT_SHOW_SELECTED_ONLY'); ?>
                        </label>
                    </div>

                    <div class="small text-muted">
                        <?= Text::_('COM_XDECAROCOMPETITIONS_TEAMIMPORT_VISIBLE'); ?>:
                        <strong data-teamimport-visible-count><?= count($this->items); ?></strong>
                        ·
                        <?= Text::_('COM_XDECAROCOMPETITIONS_TEAMIMPORT_SELECTED'); ?>:
                        <strong data-teamimport-selected-count>0</strong>
                    </div>
                </div>
            </div>

            <div class="visually-hidden" aria-live="polite" data-teamimport-live-status></div>

        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="small text-muted"><?= Text::_('COM_XDECAROCOMPETITIONS_TEAMIMPORT_SUMMARY_FOUND'); ?></div>
                    <div class="fs-3 fw-semibold"><?= (int) ($this->summary['total'] ?? 0); ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="small text-muted"><?= Text::_('COM_XDECAROCOMPETITIONS_TEAMIMPORT_SUMMARY_SELECTABLE'); ?></div>
                    <div class="fs-3 fw-semibold"><?= (int) ($this->summary['selectable'] ?? 0); ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="small text-muted"><?= Text::_('COM_XDECAROCOMPETITIONS_TEAMIMPORT_SUMMARY_READY'); ?></div>
                    <div class="fs-3 fw-semibold"><?= (int) ($this->summary['ready'] ?? 0); ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="small text-muted"><?= Text::_('COM_XDECAROCOMPETITIONS_TEAMIMPORT_SUMMARY_IMPORTED'); ?></div>
                    <div class="fs-3 fw-semibold"><?= (int) ($this->summary['imported'] ?? 0); ?></div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($this->providerLimitReached) : ?>
        <div class="alert alert-info">
            <?= Text::_('COM_XDECAROCOMPETITIONS_TEAMIMPORT_LIMIT_NOTICE'); ?>
        </div>
    <?php endif; ?>

    <div class="alert alert-light border">
        <?= Text::_('COM_XDECAROCOMPETITIONS_TEAMIMPORT_PENDING_NOTICE'); ?>
    </div>

    <form
        action="<?= Route::_('index.php?option=com_xdecarocompetitions&view=teamimport'); ?>"
        method="post"
        name="adminForm"
        id="adminForm"
    >
        <div class="table-responsive">
            <table class="table table-striped align-middle competitions-responsive-table">
                <thead>
                    <tr>
                        <th class="w-1 text-center">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                data-teamimport-checkall
                                aria-label="<?= Text::_('COM_XDECAROCOMPETITIONS_TEAMIMPORT_SELECT_VISIBLE'); ?>"
                                title="<?= Text::_('COM_XDECAROCOMPETITIONS_TEAMIMPORT_SELECT_VISIBLE'); ?>"
                            >
                        </th>
                        <th><?= Text::_('COM_XDECAROCOMPETITIONS_FIELD_NAME'); ?></th>
                        <th><?= Text::_('COM_XDECAROCOMPETITIONS_FIELD_COUNTRY'); ?></th>
                        <th><?= Text::_('COM_XDECAROCOMPETITIONS_FIELD_FEDERATION'); ?></th>
                        <th><?= Text::_('JSTATUS'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($this->items as $i => $item) : ?>
                    <?php
                    $status = (string) ($item['status'] ?? 'new');
                    $statusMeta = $statusMap[$status] ?? $statusMap['new'];
                    $selectable = !empty($item['selectable']);
                    $country = trim(
                        (string) ($item['country_name'] ?? '')
                        . (!empty($item['country_code']) ? ' (' . (string) $item['country_code'] . ')' : '')
                    );
                    $federation = trim(
                        (string) ($item['federation_name'] ?? '')
                        . (!empty($item['federation_code']) ? ' (' . (string) $item['federation_code'] . ')' : '')
                    );
                    ?>
                    <tr
                        data-teamimport-row
                        data-teamimport-search="<?= $this->escape(trim(implode(' ', [
                            (string) ($item['name'] ?? ''),
                            (string) ($item['code'] ?? ''),
                            $country,
                            $federation,
                            Text::_($statusMeta[0]),
                        ]))); ?>"
                    >
                        <td class="text-center competitions-responsive-table__check">
                            <?php if ($selectable) : ?>
                                <input
                                    type="checkbox"
                                    id="cb<?= (int) $i; ?>"
                                    name="cid[]"
                                    value="<?= $this->escape((string) $item['uuid']); ?>"
                                >
                            <?php else : ?>
                                <input type="checkbox" disabled aria-label="<?= Text::_('JDISABLED'); ?>">
                            <?php endif; ?>
                        </td>
                        <td data-label="<?= Text::_('COM_XDECAROCOMPETITIONS_FIELD_NAME'); ?>">
                            <div class="fw-semibold"><?= $this->escape((string) ($item['name'] ?: '—')); ?></div>
                            <?php if (!empty($item['code'])) : ?>
                                <div class="small text-muted"><?= $this->escape((string) $item['code']); ?></div>
                            <?php endif; ?>

                            <?php if (!empty($item['team_id'])) : ?>
                                <a
                                    class="small"
                                    href="<?= Route::_('index.php?option=com_xdecarocompetitions&task=team.edit&id=' . (int) $item['team_id']); ?>"
                                >
                                    <?= Text::_('COM_XDECAROCOMPETITIONS_TEAMIMPORT_OPEN_TEAM'); ?>
                                </a>
                            <?php endif; ?>
                        </td>
                        <td data-label="<?= Text::_('COM_XDECAROCOMPETITIONS_FIELD_COUNTRY'); ?>">
                            <?= $this->escape($country !== '' ? $country : '—'); ?>
                        </td>
                        <td data-label="<?= Text::_('COM_XDECAROCOMPETITIONS_FIELD_FEDERATION'); ?>">
                            <?php if ($federation !== '') : ?>
                                <?= $this->escape($federation); ?>
                            <?php elseif ($status === 'no_affiliation') : ?>
                                <span class="text-muted"><?= Text::_('COM_XDECAROCOMPETITIONS_TEAM_FEDERATION_UNDETERMINED'); ?></span>
                            <?php else : ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="<?= Text::_('JSTATUS'); ?>">
                            <span class="badge <?= $this->escape($statusMeta[1]); ?>">
                                <?= Text::_($statusMeta[0]); ?>
                            </span>

                            <?php if ($status === 'unmapped') : ?>
                                <div class="small text-muted mt-1">
                                    <?= Text::_('COM_XDECAROCOMPETITIONS_TEAMIMPORT_STATUS_FEDERATION_UNMAPPED_DESC'); ?>
                                </div>
                            <?php elseif ($status === 'no_affiliation') : ?>
                                <div class="small text-muted mt-1">
                                    <?= Text::_('COM_XDECAROCOMPETITIONS_TEAMIMPORT_STATUS_NO_AFFILIATION_DESC'); ?>
                                </div>
                            <?php elseif ($status === 'ambiguous') : ?>
                                <div class="small text-muted mt-1">
                                    <?= Text::_('COM_XDECAROCOMPETITIONS_TEAMIMPORT_STATUS_AMBIGUOUS_DESC'); ?>
                                </div>
                            <?php elseif ($status === 'unavailable') : ?>
                                <div class="small text-muted mt-1">
                                    <?= Text::_('COM_XDECAROCOMPETITIONS_TEAMIMPORT_STATUS_UNAVAILABLE_DESC'); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$this->items) : ?>
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <?= Text::_('COM_XDECAROCOMPETITIONS_TEAMIMPORT_NO_RESULTS'); ?>
                        </td>
                    </tr>
                <?php else : ?>
                    <tr data-teamimport-empty hidden>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <?= Text::_('COM_XDECAROCOMPETITIONS_TEAMIMPORT_NO_LIVE_RESULTS'); ?>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <input type="hidden" name="task" value="">
        <input type="hidden" name="boxchecked" value="0">
        <?= HTMLHelper::_('form.token'); ?>
    </form>
</div>

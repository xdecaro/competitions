<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.formvalidator');

$seasonId = (int) $this->seasonId;
?>
<form action="<?= Route::_('index.php?option=com_xdecarocompetitions&view=participationbulk'); ?>" method="post" name="adminForm" id="adminForm" class="competitions-admin">
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-8">
                    <label class="form-label fw-semibold" for="season_id">Stagione</label>
                    <select class="form-select" name="season_id" id="season_id" required>
                        <option value="">- Seleziona stagione -</option>
                        <?php foreach ($this->seasonOptions as $season) : ?>
                            <?php
                            $id = (int) $season->id;
                            $label = trim((string) $season->tournament_name)
                                . ' — ' . trim((string) $season->name)
                                . ((int) $season->season_year > 0 ? ' (' . (int) $season->season_year . ')' : '');
                            ?>
                            <option value="<?= $id; ?>"<?= $id === $seasonId ? ' selected' : ''; ?>><?= $this->escape($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Scegli la stagione a cui aggiungere più squadre in una sola operazione.</div>
                </div>
                <div class="col-12 col-lg-auto">
                    <button type="button" class="btn btn-outline-primary w-100" id="load-season">Carica squadre</button>
                </div>
            </div>
        </div>
    </div>

    <?php if ($seasonId > 0) : ?>
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                    <div>
                        <h2 class="h5 mb-1"><?= $this->escape($this->seasonLabel ?: 'Stagione selezionata'); ?></h2>
                        <div class="text-muted"><?= count($this->items); ?> squadre disponibili</div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="select-all">Seleziona tutte</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="select-none">Deseleziona tutte</button>
                    </div>
                </div>

                <div class="mb-3">
                    <input type="search" class="form-control" id="team-search" placeholder="Cerca squadra, Paese o federazione">
                </div>

                <?php if (!$this->items) : ?>
                    <div class="alert alert-info mb-0">Nessuna squadra disponibile: tutte le squadre presenti risultano già associate a questa stagione.</div>
                <?php else : ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="bulk-team-table">
                            <thead>
                                <tr>
                                    <th style="width:48px"><input type="checkbox" id="master-check" aria-label="Seleziona tutte"></th>
                                    <th>Squadra</th>
                                    <th>Paese</th>
                                    <th>Federazione</th>
                                    <th>Approvazione</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($this->items as $item) : ?>
                                    <?php
                                    $search = strtolower(trim(
                                        (string) $item->name . ' '
                                        . (string) $item->short_name . ' '
                                        . (string) $item->country_code . ' '
                                        . (string) $item->federation_name . ' '
                                        . (string) $item->federation_code
                                    ));
                                    ?>
                                    <tr data-search="<?= $this->escape($search); ?>">
                                        <td><input class="form-check-input team-check" type="checkbox" name="cid[]" value="<?= (int) $item->id; ?>"></td>
                                        <td>
                                            <div class="fw-semibold"><?= $this->escape((string) $item->name); ?></div>
                                            <?php if (trim((string) $item->short_name) !== '') : ?>
                                                <div class="small text-muted"><?= $this->escape((string) $item->short_name); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $this->escape((string) ($item->country_code ?: '—')); ?></td>
                                        <td>
                                            <?= $this->escape((string) ($item->federation_name ?: '—')); ?>
                                            <?php if (trim((string) $item->federation_code) !== '') : ?>
                                                <span class="text-muted">(<?= $this->escape((string) $item->federation_code); ?>)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $this->escape((string) ($item->approval_status ?: '—')); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <input type="hidden" name="task" value="">
    <?= HTMLHelper::_('form.token'); ?>
</form>

<script>
(() => {
    const season = document.getElementById('season_id');
    const load = document.getElementById('load-season');
    const search = document.getElementById('team-search');
    const checks = () => [...document.querySelectorAll('.team-check')];
    const visibleChecks = () => checks().filter((box) => box.closest('tr')?.style.display !== 'none');
    const master = document.getElementById('master-check');

    load?.addEventListener('click', () => {
        const id = Number(season?.value || 0);
        if (id > 0) {
            window.location.href = 'index.php?option=com_xdecarocompetitions&view=participationbulk&season_id=' + encodeURIComponent(id);
        }
    });

    search?.addEventListener('input', () => {
        const q = String(search.value || '').trim().toLowerCase();
        document.querySelectorAll('#bulk-team-table tbody tr').forEach((row) => {
            row.style.display = !q || String(row.dataset.search || '').includes(q) ? '' : 'none';
        });
    });

    document.getElementById('select-all')?.addEventListener('click', () => visibleChecks().forEach((box) => { box.checked = true; }));
    document.getElementById('select-none')?.addEventListener('click', () => checks().forEach((box) => { box.checked = false; }));
    master?.addEventListener('change', () => visibleChecks().forEach((box) => { box.checked = master.checked; }));

    const originalSubmit = window.Joomla?.submitbutton;
    if (window.Joomla) {
        window.Joomla.submitbutton = (task) => {
            if (task === 'participationbulk.addSelected') {
                const selected = checks().filter((box) => box.checked).length;
                if (!Number(season?.value || 0) || selected === 0) {
                    alert('Seleziona una stagione e almeno una squadra.');
                    return;
                }
                const label = season.options[season.selectedIndex]?.text || 'stagione selezionata';
                if (!confirm(`Aggiungere ${selected} squadre a ${label}?`)) {
                    return;
                }
            }
            if (typeof originalSubmit === 'function') {
                originalSubmit(task);
            }
        };
    }
})();
</script>

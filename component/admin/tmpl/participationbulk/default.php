<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.formvalidator');

$seasonId = (int) $this->seasonId;
$hasSource = !empty($this->sourceTeamNames);
?>
<style>
.participation-bulk-shell{max-width:1480px;margin:0 auto;padding:0 12px}
.participation-bulk-shell .bulk-top{display:grid;grid-template-columns:minmax(260px,1fr) minmax(260px,.9fr);gap:14px;align-items:end}
.participation-bulk-shell .bulk-source-box{border:1px solid var(--template-bg-dark-10,#d9d9d9);border-radius:.5rem;padding:12px;background:var(--body-bg,#fff)}
.participation-bulk-shell .bulk-source-summary{display:flex;flex-wrap:wrap;gap:8px;margin-top:8px}
.participation-bulk-shell .bulk-source-summary span{font-size:.875rem;padding:.2rem .5rem;border-radius:999px;background:rgba(127,127,127,.12)}
.participation-bulk-shell .bulk-unmatched{margin-top:10px;padding:10px 12px;border-radius:.45rem;background:rgba(220,53,69,.08);border:1px solid rgba(220,53,69,.2)}
.participation-bulk-shell .bulk-unmatched ul{margin:.35rem 0 0;padding-left:1.15rem}
.participation-bulk-shell .bulk-list-head{display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap}
.participation-bulk-shell .bulk-actions{display:flex;gap:8px;flex-wrap:wrap}
.participation-bulk-shell .bulk-search{max-width:520px}
.participation-bulk-shell #bulk-team-table{table-layout:fixed;width:100%;margin-bottom:0}
.participation-bulk-shell #bulk-team-table th,.participation-bulk-shell #bulk-team-table td{padding:.55rem .65rem;vertical-align:middle}
.participation-bulk-shell #bulk-team-table th:nth-child(1){width:44px}
.participation-bulk-shell #bulk-team-table th:nth-child(2){width:38%}
.participation-bulk-shell #bulk-team-table th:nth-child(3){width:11%}
.participation-bulk-shell #bulk-team-table th:nth-child(4){width:34%}
.participation-bulk-shell #bulk-team-table th:nth-child(5){width:17%}
.participation-bulk-shell #bulk-team-table td{overflow-wrap:anywhere}
@media (max-width:900px){
  .participation-bulk-shell .bulk-top{grid-template-columns:1fr}
  .participation-bulk-shell #bulk-team-table thead{display:none}
  .participation-bulk-shell #bulk-team-table,.participation-bulk-shell #bulk-team-table tbody,.participation-bulk-shell #bulk-team-table tr,.participation-bulk-shell #bulk-team-table td{display:block;width:100%}
  .participation-bulk-shell #bulk-team-table tr{position:relative;border:1px solid var(--template-bg-dark-10,#ddd);border-radius:.55rem;margin-bottom:.65rem;padding:.65rem .75rem .65rem 42px}
  .participation-bulk-shell #bulk-team-table td{border:0;padding:.18rem 0}
  .participation-bulk-shell #bulk-team-table td:first-child{position:absolute;left:12px;top:12px;width:auto}
  .participation-bulk-shell #bulk-team-table td:nth-child(3)::before{content:'Paese: ';font-weight:600}
  .participation-bulk-shell #bulk-team-table td:nth-child(4)::before{content:'Federazione: ';font-weight:600}
  .participation-bulk-shell #bulk-team-table td:nth-child(5)::before{content:'Approvazione: ';font-weight:600}
}
</style>

<form action="<?= Route::_('index.php?option=com_xdecarocompetitions&view=participationbulk'); ?>" method="post" enctype="multipart/form-data" name="adminForm" id="adminForm" class="competitions-admin participation-bulk-shell">
    <div class="card mb-3">
        <div class="card-body">
            <div class="bulk-top">
                <div>
                    <label class="form-label fw-semibold" for="season_id">Stagione</label>
                    <div class="input-group">
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
                        <button type="button" class="btn btn-outline-primary" id="load-season">Carica</button>
                    </div>
                    <div class="form-text">Scegli la stagione da preparare.</div>
                </div>

                <div class="bulk-source-box">
                    <label class="form-label fw-semibold mb-1" for="source_csv">Filtro squadre da CSV</label>
                    <div class="input-group">
                        <input type="file" class="form-control" name="source_csv" id="source_csv" accept=".csv,.txt,text/csv,text/plain">
                        <button type="button" class="btn btn-outline-primary" id="preview-csv">Carica CSV</button>
                    </div>
                    <div class="form-text">Legge solo i nomi squadra dal file. Non crea e non modifica squadre.</div>
                    <?php if ($hasSource) : ?>
                        <div class="small fw-semibold mt-2"><?= $this->escape($this->sourceFileName ?: 'CSV caricato'); ?></div>
                        <div class="bulk-source-summary" id="bulk-source-summary">
                            <span><?= (int) $this->sourceSummary['source']; ?> nel CSV</span>
                            <span><?= (int) $this->sourceSummary['matched']; ?> trovate in Competitions</span>
                            <span><?= (int) $this->sourceSummary['unmatched']; ?> non abbinate</span>
                        </div>
                        <?php if (!empty($this->unmatchedSourceNames)) : ?>
                            <div class="bulk-unmatched">
                                <div class="fw-semibold">Non abbinate</div>
                                <ul>
                                    <?php foreach ($this->unmatchedSourceNames as $unmatchedName) : ?>
                                        <li><?= $this->escape((string) $unmatchedName); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        <button type="button" class="btn btn-sm btn-link px-0 mt-1" id="clear-csv">Rimuovi filtro CSV</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($seasonId > 0) : ?>
        <div class="card">
            <div class="card-body p-3">
                <div class="bulk-list-head mb-3">
                    <div>
                        <h2 class="h5 mb-1"><?= $this->escape($this->seasonLabel ?: 'Stagione selezionata'); ?></h2>
                        <div class="text-muted">
                            <?= count($this->items); ?> squadre disponibili
                            <?php if ($hasSource) : ?>· filtrate dal CSV<?php endif; ?>
                        </div>
                    </div>
                    <div class="bulk-actions">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="select-all">Seleziona tutte</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="select-none">Deseleziona tutte</button>
                    </div>
                </div>

                <div class="mb-3 bulk-search">
                    <input type="search" class="form-control" id="team-search" placeholder="Cerca squadra, Paese o federazione">
                </div>

                <?php if (!$this->items) : ?>
                    <div class="alert alert-info mb-0">
                        <?= $hasSource
                            ? 'Nessuna squadra del CSV è disponibile per questa stagione. Controlla i nomi o le partecipazioni già create.'
                            : 'Nessuna squadra disponibile: tutte le squadre presenti risultano già associate a questa stagione.'; ?>
                    </div>
                <?php else : ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="bulk-team-table">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="master-check" aria-label="Seleziona tutte"></th>
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
    <input type="hidden" name="boxchecked" value="0">
    <?= HTMLHelper::_('form.token'); ?>
</form>

<script>
(() => {
    const form = document.getElementById('adminForm');
    const season = document.getElementById('season_id');
    const load = document.getElementById('load-season');
    const csv = document.getElementById('source_csv');
    const preview = document.getElementById('preview-csv');
    const clearCsv = document.getElementById('clear-csv');
    const search = document.getElementById('team-search');
    const checks = () => [...document.querySelectorAll('.team-check')];
    const visibleChecks = () => checks().filter((box) => box.closest('tr')?.style.display !== 'none');
    const master = document.getElementById('master-check');
    const boxchecked = form?.querySelector('input[name="boxchecked"]');

    const syncSelectionState = () => {
        const selected = checks().filter((box) => box.checked).length;

        if (boxchecked) {
            boxchecked.value = '0';
        }

        if (window.Joomla?.isChecked) {
            for (let i = 0; i < selected; i += 1) {
                Joomla.isChecked(true);
            }
        } else if (boxchecked) {
            boxchecked.value = String(selected);
        }

        if (master) {
            const visible = visibleChecks();
            master.checked = visible.length > 0 && visible.every((box) => box.checked);
            master.indeterminate = visible.some((box) => box.checked) && !master.checked;
        }
    };

    load?.addEventListener('click', () => {
        const id = Number(season?.value || 0);
        if (id > 0) {
            window.location.href = 'index.php?option=com_xdecarocompetitions&view=participationbulk&season_id=' + encodeURIComponent(id);
        }
    });

    preview?.addEventListener('click', () => {
        if (!Number(season?.value || 0)) {
            alert('Seleziona prima una stagione.');
            return;
        }
        if (!csv?.files?.length) {
            alert('Seleziona un file CSV.');
            return;
        }
        if (window.Joomla?.submitbutton) {
            window.Joomla.submitbutton('participationbulk.previewCsv');
        }
    });

    clearCsv?.addEventListener('click', () => {
        if (window.Joomla?.submitbutton) {
            window.Joomla.submitbutton('participationbulk.clearCsv');
        }
    });

    search?.addEventListener('input', () => {
        const q = String(search.value || '').trim().toLowerCase();
        document.querySelectorAll('#bulk-team-table tbody tr').forEach((row) => {
            row.style.display = !q || String(row.dataset.search || '').includes(q) ? '' : 'none';
        });
        syncSelectionState();
    });

    checks().forEach((box) => box.addEventListener('change', syncSelectionState));

    document.getElementById('select-all')?.addEventListener('click', () => {
        visibleChecks().forEach((box) => { box.checked = true; });
        syncSelectionState();
    });

    document.getElementById('select-none')?.addEventListener('click', () => {
        checks().forEach((box) => { box.checked = false; });
        syncSelectionState();
    });

    master?.addEventListener('change', () => {
        visibleChecks().forEach((box) => { box.checked = master.checked; });
        syncSelectionState();
    });

    syncSelectionState();

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
            } else if (form) {
                const taskInput = form.querySelector('input[name="task"]');
                if (taskInput) taskInput.value = task;
                form.submit();
            }
        };
    }
})();
</script>

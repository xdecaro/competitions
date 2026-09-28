<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.formvalidator');
$hasSource = !empty($this->sourcePersonNames);
?>
<style>
.player-bulk-shell{max-width:1480px;margin:0 auto;padding:0 12px}
.player-bulk-shell .bulk-top{display:grid;grid-template-columns:minmax(300px,1fr) minmax(320px,.95fr);gap:14px;align-items:end}
.player-bulk-shell .bulk-search{min-width:0}
.player-bulk-shell .bulk-source-box{border:1px solid var(--template-bg-dark-10,#d9d9d9);border-radius:.5rem;padding:12px;background:var(--body-bg,#fff)}
.player-bulk-shell .bulk-source-summary{display:flex;flex-wrap:wrap;gap:8px;margin-top:8px}
.player-bulk-shell .bulk-source-summary span{font-size:.875rem;padding:.2rem .5rem;border-radius:999px;background:rgba(127,127,127,.12)}
.player-bulk-shell .bulk-unmatched{margin-top:10px;padding:10px 12px;border-radius:.45rem;background:rgba(220,53,69,.08);border:1px solid rgba(220,53,69,.2)}
.player-bulk-shell .bulk-unmatched ul{margin:.35rem 0 0;padding-left:1.15rem;max-height:180px;overflow:auto}
.player-bulk-shell .bulk-actions{display:flex;gap:8px;flex-wrap:wrap}
.player-bulk-shell #bulk-player-table{table-layout:fixed;width:100%;margin-bottom:0}
.player-bulk-shell #bulk-player-table th,.player-bulk-shell #bulk-player-table td{padding:.55rem .65rem;vertical-align:middle}
.player-bulk-shell #bulk-player-table th:nth-child(1){width:44px}
.player-bulk-shell #bulk-player-table th:nth-child(2){width:40%}
.player-bulk-shell #bulk-player-table th:nth-child(3){width:35%}
.player-bulk-shell #bulk-player-table th:nth-child(4){width:25%}
.player-bulk-shell #bulk-player-table td{overflow-wrap:anywhere}
@media (max-width:900px){
  .player-bulk-shell .bulk-top{grid-template-columns:1fr}
  .player-bulk-shell #bulk-player-table thead{display:none}
  .player-bulk-shell #bulk-player-table,.player-bulk-shell #bulk-player-table tbody,.player-bulk-shell #bulk-player-table tr,.player-bulk-shell #bulk-player-table td{display:block;width:100%}
  .player-bulk-shell #bulk-player-table tr{position:relative;border:1px solid var(--template-bg-dark-10,#ddd);border-radius:.55rem;margin-bottom:.65rem;padding:.65rem .75rem .65rem 42px}
  .player-bulk-shell #bulk-player-table td{border:0;padding:.18rem 0}
  .player-bulk-shell #bulk-player-table td:first-child{position:absolute;left:12px;top:12px;width:auto}
  .player-bulk-shell #bulk-player-table td:nth-child(3)::before{content:'Email: ';font-weight:600}
  .player-bulk-shell #bulk-player-table td:nth-child(4)::before{content:'Stato People: ';font-weight:600}
}
</style>

<form action="<?= Route::_('index.php?option=com_xdecarocompetitions&view=playerbulk'); ?>" method="post" enctype="multipart/form-data" name="adminForm" id="adminForm" class="competitions-admin player-bulk-shell">
    <div class="card mb-3">
        <div class="card-body p-3">
            <div class="bulk-top">
                <div class="bulk-search">
                    <label class="form-label fw-semibold" for="people-search"><?= $hasSource ? 'Filtra risultati CSV' : 'Cerca in People'; ?></label>
                    <div class="input-group">
                        <input type="search" class="form-control" id="people-search" value="<?= $this->escape($this->search); ?>" placeholder="Nome, cognome o email">
                        <?php if (!$hasSource) : ?>
                            <button type="button" class="btn btn-primary" id="run-search">Cerca</button>
                            <?php if ($this->search !== '') : ?>
                                <button type="button" class="btn btn-outline-secondary" id="clear-search">Pulisci</button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <div class="form-text">
                        <?= $hasSource
                            ? 'Filtra le persone trovate dal CSV senza modificare People.'
                            : 'Mostra fino a 200 persone attive di People non ancora collegate a Competitions. Usa la ricerca per restringere l’elenco.'; ?>
                    </div>
                </div>

                <div class="bulk-source-box">
                    <label class="form-label fw-semibold mb-1" for="source_csv">Importa elenco da CSV</label>
                    <div class="input-group">
                        <input type="file" class="form-control" name="source_csv" id="source_csv" accept=".csv,.txt,text/csv,text/plain">
                        <button type="button" class="btn btn-outline-primary" id="preview-csv">Carica CSV</button>
                    </div>
                    <div class="form-text">Legge solo i nominativi e li cerca in People. Non crea persone in People e non modifica le anagrafiche.</div>

                    <?php if ($hasSource) : ?>
                        <div class="small fw-semibold mt-2"><?= $this->escape($this->sourceFileName ?: 'CSV caricato'); ?></div>
                        <div class="bulk-source-summary">
                            <span><?= (int) $this->sourceSummary['source']; ?> nel CSV</span>
                            <span><?= (int) $this->sourceSummary['matched']; ?> trovate in People</span>
                            <span><?= (int) $this->alreadyLinked; ?> già in Competitions</span>
                            <span><?= count($this->items); ?> da aggiungere</span>
                            <span><?= (int) $this->sourceSummary['unmatched']; ?> non abbinate</span>
                        </div>
                        <?php if (!empty($this->unmatchedSourceNames)) : ?>
                            <div class="bulk-unmatched">
                                <div class="fw-semibold">Non abbinate</div>
                                <ul>
                                    <?php foreach ($this->unmatchedSourceNames as $name) : ?>
                                        <li><?= $this->escape((string) $name); ?></li>
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

    <div class="card">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h2 class="h5 mb-1"><?= $hasSource ? 'Persone del CSV disponibili' : 'Persone disponibili'; ?></h2>
                    <div class="text-muted"><?= count($this->items); ?> risultati disponibili<?= $hasSource ? ' · filtrati dal CSV' : ''; ?></div>
                </div>
                <div class="bulk-actions">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="select-all">Seleziona tutte</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="select-none">Deseleziona tutte</button>
                </div>
            </div>

            <?php if (!$this->items) : ?>
                <div class="alert alert-info mb-0">
                    <?= $hasSource
                        ? 'Nessuna nuova persona del CSV da aggiungere. Controlla le non abbinate oppure le persone già presenti in Competitions.'
                        : 'Nessuna persona disponibile con questi criteri oppure tutte le persone trovate sono già collegate a Competitions.'; ?>
                </div>
            <?php else : ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="bulk-player-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="master-check" aria-label="Seleziona tutte"></th>
                                <th>Persona</th>
                                <th>Email</th>
                                <th>Stato People</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->items as $person) : ?>
                                <?php
                                $uuid = strtolower(trim((string) ($person['uuid'] ?? '')));
                                $displayName = trim((string) ($person['display_name'] ?? ''));
                                $firstLast = trim((string) ($person['first_name'] ?? '') . ' ' . (string) ($person['last_name'] ?? ''));
                                if ($displayName === '') {
                                    $displayName = $firstLast;
                                }
                                $email = trim((string) ($person['email'] ?? ''));
                                $searchText = mb_strtolower(trim($displayName . ' ' . $firstLast . ' ' . $email), 'UTF-8');
                                ?>
                                <tr data-search="<?= $this->escape($searchText); ?>">
                                    <td><input class="form-check-input person-check" type="checkbox" name="person_uuid[]" value="<?= $this->escape($uuid); ?>"></td>
                                    <td>
                                        <div class="fw-semibold"><?= $this->escape($displayName ?: '—'); ?></div>
                                        <?php if ($firstLast !== '' && $firstLast !== $displayName) : ?>
                                            <div class="small text-muted"><?= $this->escape($firstLast); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $this->escape($email !== '' ? $email : '—'); ?></td>
                                    <td><?= $this->escape((string) ($person['person_status'] ?? 'active')); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <input type="hidden" name="search" value="<?= $this->escape($this->search); ?>">
    <input type="hidden" name="task" value="">
    <input type="hidden" name="boxchecked" value="0">
    <?= HTMLHelper::_('form.token'); ?>
</form>

<script>
(() => {
    const form = document.getElementById('adminForm');
    const search = document.getElementById('people-search');
    const csv = document.getElementById('source_csv');
    const checks = () => [...document.querySelectorAll('.person-check')];
    const visibleChecks = () => checks().filter((box) => box.closest('tr')?.style.display !== 'none');
    const master = document.getElementById('master-check');
    const hasSource = <?= $hasSource ? 'true' : 'false'; ?>;

    const syncSelection = () => {
        const selected = checks().filter((box) => box.checked).length;
        const boxchecked = form?.querySelector('input[name="boxchecked"]');
        if (boxchecked) boxchecked.value = String(selected);
        if (window.Joomla?.isChecked) Joomla.isChecked(selected > 0);
        if (master) master.checked = checks().length > 0 && selected === checks().length;
    };

    document.getElementById('run-search')?.addEventListener('click', () => {
        const value = String(search?.value || '').trim();
        window.location.href = 'index.php?option=com_xdecarocompetitions&view=playerbulk' + (value ? '&search=' + encodeURIComponent(value) : '');
    });
    search?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !hasSource) {
            event.preventDefault();
            document.getElementById('run-search')?.click();
        }
    });
    search?.addEventListener('input', () => {
        if (!hasSource) return;
        const q = String(search.value || '').trim().toLowerCase();
        document.querySelectorAll('#bulk-player-table tbody tr').forEach((row) => {
            row.style.display = !q || String(row.dataset.search || '').includes(q) ? '' : 'none';
        });
    });
    document.getElementById('clear-search')?.addEventListener('click', () => {
        window.location.href = 'index.php?option=com_xdecarocompetitions&view=playerbulk';
    });

    document.getElementById('preview-csv')?.addEventListener('click', () => {
        if (!csv?.files?.length) {
            alert('Seleziona un file CSV.');
            return;
        }
        window.Joomla?.submitbutton?.('playerbulk.previewCsv');
    });
    document.getElementById('clear-csv')?.addEventListener('click', () => {
        window.Joomla?.submitbutton?.('playerbulk.clearCsv');
    });

    document.getElementById('select-all')?.addEventListener('click', () => {
        visibleChecks().forEach((box) => { box.checked = true; });
        syncSelection();
    });
    document.getElementById('select-none')?.addEventListener('click', () => {
        checks().forEach((box) => { box.checked = false; });
        syncSelection();
    });
    master?.addEventListener('change', () => {
        visibleChecks().forEach((box) => { box.checked = master.checked; });
        syncSelection();
    });
    checks().forEach((box) => box.addEventListener('change', syncSelection));
    syncSelection();

    const originalSubmit = window.Joomla?.submitbutton;
    if (window.Joomla) {
        window.Joomla.submitbutton = (task) => {
            if (task === 'playerbulk.addSelected') {
                const selected = checks().filter((box) => box.checked).length;
                if (selected === 0) {
                    alert('Seleziona almeno una persona.');
                    return;
                }
                if (!confirm(`Aggiungere ${selected} giocatori a Competitions?`)) {
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

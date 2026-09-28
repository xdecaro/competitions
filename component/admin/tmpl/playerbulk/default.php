<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.formvalidator');
?>
<style>
.player-bulk-shell{max-width:1480px;margin:0 auto;padding:0 12px}
.player-bulk-shell .bulk-head{display:flex;gap:12px;align-items:end;justify-content:space-between;flex-wrap:wrap}
.player-bulk-shell .bulk-search{flex:1 1 520px;max-width:760px}
.player-bulk-shell .bulk-actions{display:flex;gap:8px;flex-wrap:wrap}
.player-bulk-shell #bulk-player-table{table-layout:fixed;width:100%;margin-bottom:0}
.player-bulk-shell #bulk-player-table th,.player-bulk-shell #bulk-player-table td{padding:.55rem .65rem;vertical-align:middle}
.player-bulk-shell #bulk-player-table th:nth-child(1){width:44px}
.player-bulk-shell #bulk-player-table th:nth-child(2){width:40%}
.player-bulk-shell #bulk-player-table th:nth-child(3){width:35%}
.player-bulk-shell #bulk-player-table th:nth-child(4){width:25%}
.player-bulk-shell #bulk-player-table td{overflow-wrap:anywhere}
@media (max-width:900px){
  .player-bulk-shell #bulk-player-table thead{display:none}
  .player-bulk-shell #bulk-player-table,.player-bulk-shell #bulk-player-table tbody,.player-bulk-shell #bulk-player-table tr,.player-bulk-shell #bulk-player-table td{display:block;width:100%}
  .player-bulk-shell #bulk-player-table tr{position:relative;border:1px solid var(--template-bg-dark-10,#ddd);border-radius:.55rem;margin-bottom:.65rem;padding:.65rem .75rem .65rem 42px}
  .player-bulk-shell #bulk-player-table td{border:0;padding:.18rem 0}
  .player-bulk-shell #bulk-player-table td:first-child{position:absolute;left:12px;top:12px;width:auto}
  .player-bulk-shell #bulk-player-table td:nth-child(3)::before{content:'Email: ';font-weight:600}
  .player-bulk-shell #bulk-player-table td:nth-child(4)::before{content:'Stato People: ';font-weight:600}
}
</style>

<form action="<?= Route::_('index.php?option=com_xdecarocompetitions&view=playerbulk'); ?>" method="post" name="adminForm" id="adminForm" class="competitions-admin player-bulk-shell">
    <div class="card mb-3">
        <div class="card-body p-3">
            <div class="bulk-head">
                <div class="bulk-search">
                    <label class="form-label fw-semibold" for="people-search">Cerca in People</label>
                    <div class="input-group">
                        <input type="search" class="form-control" id="people-search" value="<?= $this->escape($this->search); ?>" placeholder="Nome, cognome o email">
                        <button type="button" class="btn btn-primary" id="run-search">Cerca</button>
                        <?php if ($this->search !== '') : ?>
                            <button type="button" class="btn btn-outline-secondary" id="clear-search">Pulisci</button>
                        <?php endif; ?>
                    </div>
                    <div class="form-text">Mostra fino a 200 persone attive di People non ancora collegate a Competitions. Usa la ricerca per restringere l'elenco.</div>
                </div>
                <div class="bulk-actions">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="select-all">Seleziona tutte</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="select-none">Deseleziona tutte</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h2 class="h5 mb-1">Persone disponibili</h2>
                    <div class="text-muted"><?= count($this->items); ?> risultati disponibili</div>
                </div>
            </div>

            <?php if (!$this->items) : ?>
                <div class="alert alert-info mb-0">Nessuna persona disponibile con questi criteri oppure tutte le persone trovate sono già collegate a Competitions.</div>
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
                                if ($displayName === '') {
                                    $displayName = trim((string) ($person['first_name'] ?? '') . ' ' . (string) ($person['last_name'] ?? ''));
                                }
                                ?>
                                <tr>
                                    <td><input class="form-check-input person-check" type="checkbox" name="person_uuid[]" value="<?= $this->escape($uuid); ?>"></td>
                                    <td>
                                        <div class="fw-semibold"><?= $this->escape($displayName ?: '—'); ?></div>
                                        <div class="small text-muted"><?= $this->escape(trim((string) ($person['first_name'] ?? '') . ' ' . (string) ($person['last_name'] ?? ''))); ?></div>
                                    </td>
                                    <td><?= $this->escape((string) ($person['email'] ?? '—')); ?></td>
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
    const checks = () => [...document.querySelectorAll('.person-check')];
    const master = document.getElementById('master-check');

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
        if (event.key === 'Enter') {
            event.preventDefault();
            document.getElementById('run-search')?.click();
        }
    });
    document.getElementById('clear-search')?.addEventListener('click', () => {
        window.location.href = 'index.php?option=com_xdecarocompetitions&view=playerbulk';
    });

    document.getElementById('select-all')?.addEventListener('click', () => {
        checks().forEach((box) => { box.checked = true; });
        syncSelection();
    });
    document.getElementById('select-none')?.addEventListener('click', () => {
        checks().forEach((box) => { box.checked = false; });
        syncSelection();
    });
    master?.addEventListener('change', () => {
        checks().forEach((box) => { box.checked = master.checked; });
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

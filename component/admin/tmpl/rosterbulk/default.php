<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.formvalidator');
$hasCsv = $this->sourceFileName !== '';
$isAllTeams = $this->rosterTarget === 'all';
?>
<style>
.roster-bulk-shell{max-width:1480px;margin:0 auto;padding:0 12px}
.roster-bulk-shell .bulk-top{display:grid;grid-template-columns:minmax(440px,1fr) minmax(340px,.9fr);gap:14px;align-items:end}
.roster-bulk-shell .bulk-selectors{display:grid;grid-template-columns:minmax(250px,1fr) minmax(320px,1.25fr) auto;gap:10px;align-items:end}
.roster-bulk-shell .bulk-box{border:1px solid var(--template-bg-dark-10,#ddd);border-radius:.6rem;padding:12px;background:var(--body-bg,#fff);color:var(--body-color,inherit)}
.roster-bulk-shell .bulk-summary{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}
.roster-bulk-shell .bulk-summary span{font-size:.875rem;padding:.25rem .55rem;border-radius:999px;background:rgba(127,127,127,.12)}
.roster-bulk-shell .bulk-unmatched{margin-top:10px;padding:10px 12px;border-radius:.5rem;background:var(--danger-bg-subtle,rgba(220,53,69,.08));border:1px solid var(--danger-border-subtle,rgba(220,53,69,.25))}
.roster-bulk-shell .bulk-actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:0 0 12px}
.roster-bulk-shell .roster-team-accordion{border:1px solid var(--template-bg-dark-10,#ddd);border-radius:.65rem;background:var(--body-bg,#fff);overflow:hidden;margin-bottom:10px}
.roster-bulk-shell .roster-team-accordion>summary{list-style:none;cursor:pointer;padding:12px 14px;display:flex;align-items:center;gap:12px;justify-content:space-between;background:rgba(127,127,127,.055)}
.roster-bulk-shell .roster-team-accordion>summary::-webkit-details-marker{display:none}
.roster-bulk-shell .roster-team-accordion>summary::after{content:'⌄';font-size:1.15rem;line-height:1;transition:transform .15s ease}
.roster-bulk-shell .roster-team-accordion[open]>summary::after{transform:rotate(180deg)}
.roster-bulk-shell .roster-team-title{display:flex;align-items:center;gap:10px;min-width:0;flex:1}
.roster-bulk-shell .roster-team-name{font-weight:700;overflow-wrap:anywhere}
.roster-bulk-shell .roster-team-counts{display:flex;gap:6px;flex-wrap:wrap;font-size:.85rem;color:var(--secondary-color,#6c757d)}
.roster-bulk-shell .roster-team-body{padding:12px 14px 14px}
.roster-bulk-shell .roster-bulk-table{table-layout:fixed;width:100%;margin-bottom:0}
.roster-bulk-shell .roster-bulk-table th,.roster-bulk-shell .roster-bulk-table td{padding:.55rem .65rem;vertical-align:middle}
.roster-bulk-shell .roster-bulk-table th:nth-child(1){width:44px}.roster-bulk-shell .roster-bulk-table th:nth-child(2){width:38%}.roster-bulk-shell .roster-bulk-table th:nth-child(3){width:14%}.roster-bulk-shell .roster-bulk-table th:nth-child(4){width:22%}.roster-bulk-shell .roster-bulk-table th:nth-child(5){width:18%}
@media(max-width:1100px){.roster-bulk-shell .bulk-selectors{grid-template-columns:1fr 1fr}.roster-bulk-shell .bulk-selectors .bulk-load{grid-column:1/-1;justify-self:start}}
@media(max-width:900px){.roster-bulk-shell .bulk-top,.roster-bulk-shell .bulk-selectors{grid-template-columns:1fr}.roster-bulk-shell .bulk-selectors .bulk-load{grid-column:auto}.roster-bulk-shell .roster-team-accordion>summary{align-items:flex-start}.roster-bulk-shell .roster-team-title{align-items:flex-start}.roster-bulk-shell .roster-bulk-table thead{display:none}.roster-bulk-shell .roster-bulk-table,.roster-bulk-shell .roster-bulk-table tbody,.roster-bulk-shell .roster-bulk-table tr,.roster-bulk-shell .roster-bulk-table td{display:block;width:100%}.roster-bulk-shell .roster-bulk-table tr{position:relative;border:1px solid var(--template-bg-dark-10,#ddd);border-radius:.55rem;margin-bottom:.65rem;padding:.65rem .75rem .65rem 42px}.roster-bulk-shell .roster-bulk-table td{border:0;padding:.18rem 0}.roster-bulk-shell .roster-bulk-table td:first-child{position:absolute;left:12px;top:12px;width:auto}.roster-bulk-shell .roster-bulk-table td:nth-child(3)::before{content:'Numero maglia: ';font-weight:600}.roster-bulk-shell .roster-bulk-table td:nth-child(4)::before{content:'Ruolo: ';font-weight:600}.roster-bulk-shell .roster-bulk-table td:nth-child(5)::before{content:'Giocatore: ';font-weight:600}}
</style>

<form action="<?= Route::_('index.php?option=com_xdecarocompetitions&view=rosterbulk'); ?>" method="post" enctype="multipart/form-data" name="adminForm" id="adminForm" class="competitions-admin roster-bulk-shell">
  <div class="card mb-3"><div class="card-body"><div class="bulk-top">
    <div>
      <div class="bulk-selectors">
        <div>
          <label class="form-label fw-semibold" for="season_id">Stagione</label>
          <select class="form-select" name="season_id" id="season_id">
            <option value="">- Seleziona stagione -</option>
            <?php foreach ($this->seasonOptions as $season) : ?>
              <?php $label = trim((string) $season->tournament_name) . ' — ' . trim((string) $season->name) . ((int) $season->season_year ? ' (' . (int) $season->season_year . ')' : ''); ?>
              <option value="<?= (int) $season->id; ?>"<?= (int) $season->id === $this->seasonId ? ' selected' : ''; ?>><?= $this->escape($label); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="form-label fw-semibold" for="roster_target">Squadra / Partecipazione</label>
          <select class="form-select" name="roster_target" id="roster_target"<?= $this->seasonId > 0 ? '' : ' disabled'; ?>>
            <option value="">- Seleziona squadra -</option>
            <option value="all"<?= $isAllTeams ? ' selected' : ''; ?>>Tutte le squadre della stagione</option>
            <?php foreach ($this->participationOptions as $option) : ?>
              <option value="<?= (int) $option->id; ?>" data-season-id="<?= (int) $option->season_id; ?>"<?= (string) $option->id === $this->rosterTarget ? ' selected' : ''; ?>><?= $this->escape((string) $option->team_name); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="bulk-load"><button type="button" class="btn btn-outline-primary" id="load-participation">Carica</button></div>
      </div>
      <div class="form-text mt-2">Scegli la stagione, poi tutte le squadre oppure una sola partecipazione.</div>
    </div>
    <div class="bulk-box">
      <label class="form-label fw-semibold mb-1" for="source_csv">Importa elenco da CSV</label>
      <div class="input-group">
        <input type="file" class="form-control" name="source_csv" id="source_csv" accept=".csv,.txt,text/csv,text/plain">
        <button type="button" class="btn btn-outline-primary" id="preview-csv">Carica CSV</button>
      </div>
      <div class="form-text"><?= $isAllTeams ? 'Il file deve contenere la colonna Squadra/Team. Le squadre non esistenti non vengono create.' : 'Collega solo giocatori già presenti in Competitions e non duplica quelli già presenti nella rosa.'; ?></div>
      <?php if ($hasCsv) : ?>
        <div class="small fw-semibold mt-2"><?= $this->escape($this->sourceFileName); ?></div>
        <div class="bulk-summary">
          <span><?= (int) $this->sourceSummary['teams_in_file']; ?> squadre nel file</span>
          <span><?= (int) $this->sourceSummary['matched_teams']; ?> abbinate</span>
          <span><?= (int) $this->sourceSummary['players']; ?> giocatori</span>
          <span><?= (int) $this->sourceSummary['existing']; ?> già in rosa</span>
          <span><?= (int) $this->sourceSummary['available']; ?> da aggiungere</span>
          <span><?= (int) $this->sourceSummary['unmatched']; ?> non abbinati</span>
        </div>
        <?php if ($this->unmatchedTeams) : ?>
          <div class="bulk-unmatched">
            <div class="fw-semibold">Squadre non riconosciute</div>
            <ul class="mb-0 mt-1">
              <?php foreach ($this->unmatchedTeams as $team) : ?>
                <li>
                  <?= $this->escape((string) $team['name']); ?> — <?= (int) $team['players']; ?> giocatori
                  <?php if (($team['reason'] ?? '') === 'ambiguous') : ?><span class="text-muted">(nome ambiguo nella stagione)</span><?php endif; ?>
                  <?php if (($team['reason'] ?? '') === 'missing') : ?><span class="text-muted">(squadra non presente nella stagione)</span><?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>
        <button type="button" class="btn btn-sm btn-link px-0 mt-1" id="clear-csv">Rimuovi CSV</button>
      <?php endif; ?>
    </div>
  </div></div></div>

  <?php if ($this->seasonId > 0 && $this->rosterTarget !== '') : ?>
    <div class="bulk-actions">
      <button type="button" class="btn btn-sm btn-outline-secondary" id="select-all-new">Seleziona tutti i nuovi</button>
      <button type="button" class="btn btn-sm btn-outline-secondary" id="select-none">Deseleziona tutti</button>
      <span class="small text-muted" id="selected-count" aria-live="polite"></span>
    </div>

    <?php if (!$hasCsv) : ?>
      <div class="alert alert-info">Carica un CSV per controllare le rose prima dell’aggiunta.</div>
    <?php elseif (!$this->groups && !$this->unmatchedTeams) : ?>
      <div class="alert alert-info">Nessun dato utilizzabile trovato nel file.</div>
    <?php endif; ?>

    <?php foreach ($this->groups as $groupIndex => $group) : ?>
      <?php
        $pid = (int) $group['participation_id'];
        $available = (int) $group['available'];
        $groupUnmatched = (array) $group['unmatched'];
      ?>
      <details class="roster-team-accordion"<?= $available > 0 || $groupUnmatched ? ' open' : ''; ?> data-participation-id="<?= $pid; ?>">
        <summary>
          <div class="roster-team-title">
            <input type="checkbox" class="form-check-input team-check" data-participation-id="<?= $pid; ?>" aria-label="Seleziona i nuovi giocatori di <?= $this->escape((string) $group['team_name']); ?>"<?= $available === 0 ? ' disabled' : ''; ?>>
            <div>
              <div class="roster-team-name"><?= $this->escape((string) $group['team_name']); ?> — <?= (int) $group['source']; ?> giocatori</div>
              <div class="roster-team-counts">
                <span><?= (int) $group['existing']; ?> già presenti</span>
                <span>· <?= $available; ?> da aggiungere</span>
                <?php if ($groupUnmatched) : ?><span>· <?= count($groupUnmatched); ?> non riconosciuti</span><?php endif; ?>
              </div>
            </div>
          </div>
        </summary>
        <div class="roster-team-body">
          <?php if ($groupUnmatched) : ?>
            <div class="bulk-unmatched mb-3">
              <div class="fw-semibold">Persone non riconosciute</div>
              <ul class="mb-0 mt-1"><?php foreach ($groupUnmatched as $name) : ?><li><?= $this->escape((string) $name); ?></li><?php endforeach; ?></ul>
            </div>
          <?php endif; ?>

          <?php if ($available === 0) : ?>
            <div class="text-muted">Nessun nuovo giocatore da aggiungere per questa squadra.</div>
          <?php else : ?>
            <div class="table-responsive"><table class="table table-hover align-middle roster-bulk-table">
              <thead><tr><th></th><th>Giocatore</th><th>Numero maglia</th><th>Ruolo</th><th>Stato giocatore</th></tr></thead>
              <tbody>
              <?php foreach ((array) $group['items'] as $item) : ?>
                <tr>
                  <td><input class="form-check-input roster-check" type="checkbox" name="selection[]" value="<?= $pid; ?>:<?= (int) $item['player_id']; ?>" data-participation-id="<?= $pid; ?>"></td>
                  <td><div class="fw-semibold"><?= $this->escape((string) $item['name']); ?></div><div class="small text-muted"><?= $this->escape((string) $item['person_uuid']); ?></div></td>
                  <td><?= $item['shirt_number'] !== null ? (int) $item['shirt_number'] : '—'; ?></td>
                  <td><?= $this->escape((string) ($item['role'] ?: '—')); ?></td>
                  <td><?= $this->escape((string) $item['approval_status']); ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table></div>
          <?php endif; ?>
        </div>
      </details>
    <?php endforeach; ?>
  <?php endif; ?>

  <input type="hidden" name="participation_id" value="<?= (int) $this->participationId; ?>">
  <input type="hidden" name="task" value="">
  <input type="hidden" name="boxchecked" value="0">
  <?= HTMLHelper::_('form.token'); ?>
</form>
<script>
(() => {
  const form = document.getElementById('adminForm');
  const season = document.getElementById('season_id');
  const target = document.getElementById('roster_target');
  const csv = document.getElementById('source_csv');
  const allParticipationOptions = target ? [...target.querySelectorAll('option[data-season-id]')] : [];
  const allOption = target?.querySelector('option[value="all"]');
  const checks = () => [...document.querySelectorAll('.roster-check')];
  const teamChecks = () => [...document.querySelectorAll('.team-check')];

  const filterParticipations = () => {
    if (!target) return;
    const seasonId = Number(season?.value || 0);
    let selectedStillVisible = target.value === '' || (target.value === 'all' && seasonId > 0);
    allParticipationOptions.forEach(option => {
      const visible = seasonId > 0 && Number(option.dataset.seasonId || 0) === seasonId;
      option.hidden = !visible;
      option.disabled = !visible;
      if (visible && option.selected) selectedStillVisible = true;
    });
    if (allOption) {
      allOption.hidden = seasonId <= 0;
      allOption.disabled = seasonId <= 0;
    }
    if (!selectedStillVisible) target.value = '';
    target.disabled = seasonId <= 0;
  };

  const sync = () => {
    const all = checks();
    const selected = all.filter(input => input.checked).length;
    const box = form?.querySelector('input[name="boxchecked"]');
    if (box) box.value = String(selected);
    if (window.Joomla?.isChecked) Joomla.isChecked(selected > 0);
    const counter = document.getElementById('selected-count');
    if (counter) counter.textContent = selected ? `${selected} selezionati` : '';

    teamChecks().forEach(teamCheck => {
      const pid = teamCheck.dataset.participationId;
      const groupChecks = all.filter(input => input.dataset.participationId === pid);
      const groupSelected = groupChecks.filter(input => input.checked).length;
      teamCheck.checked = groupChecks.length > 0 && groupSelected === groupChecks.length;
      teamCheck.indeterminate = groupSelected > 0 && groupSelected < groupChecks.length;
    });
  };

  season?.addEventListener('change', () => {
    if (target) target.value = '';
    filterParticipations();
  });
  filterParticipations();

  document.getElementById('load-participation')?.addEventListener('click', () => {
    const seasonId = Number(season?.value || 0);
    const rosterTarget = String(target?.value || '');
    if (!seasonId) { alert('Seleziona prima una stagione.'); return; }
    if (!rosterTarget) { alert('Seleziona tutte le squadre oppure una squadra.'); return; }
    location.href = 'index.php?option=com_xdecarocompetitions&view=rosterbulk&season_id=' + encodeURIComponent(seasonId) + '&roster_target=' + encodeURIComponent(rosterTarget);
  });

  document.getElementById('preview-csv')?.addEventListener('click', () => {
    if (!Number(season?.value || 0)) { alert('Seleziona prima una stagione.'); return; }
    if (!String(target?.value || '')) { alert('Seleziona tutte le squadre oppure una squadra.'); return; }
    if (!csv?.files?.length) { alert('Seleziona un file CSV.'); return; }
    window.Joomla?.submitbutton?.('rosterbulk.previewCsv');
  });

  document.getElementById('clear-csv')?.addEventListener('click', () => window.Joomla?.submitbutton?.('rosterbulk.clearCsv'));
  document.getElementById('select-all-new')?.addEventListener('click', () => { checks().forEach(input => input.checked = true); sync(); });
  document.getElementById('select-none')?.addEventListener('click', () => { checks().forEach(input => input.checked = false); sync(); });

  teamChecks().forEach(teamCheck => {
    teamCheck.addEventListener('click', event => event.stopPropagation());
    teamCheck.addEventListener('change', () => {
      const pid = teamCheck.dataset.participationId;
      checks().filter(input => input.dataset.participationId === pid).forEach(input => input.checked = teamCheck.checked);
      sync();
    });
  });
  checks().forEach(input => input.addEventListener('change', sync));
  sync();

  const original = window.Joomla?.submitbutton;
  if (window.Joomla) {
    window.Joomla.submitbutton = task => {
      if (task === 'rosterbulk.addSelected') {
        const selected = checks().filter(input => input.checked).length;
        if (!selected) { alert('Seleziona almeno un nuovo giocatore.'); return; }
        if (!confirm(`Aggiungere ${selected} giocatori alle rose selezionate?`)) return;
      }
      if (typeof original === 'function') original(task);
      else {
        const taskInput = form.querySelector('input[name="task"]');
        if (taskInput) taskInput.value = task;
        form.submit();
      }
    };
  }
})();
</script>

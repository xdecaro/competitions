<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.formvalidator');
$hasCsv = $this->sourceFileName !== '';
?>
<style>
.roster-bulk-shell{max-width:1480px;margin:0 auto;padding:0 12px}
.roster-bulk-shell .bulk-top{display:grid;grid-template-columns:minmax(320px,1fr) minmax(340px,.9fr);gap:14px;align-items:end}
.roster-bulk-shell .bulk-box{border:1px solid var(--template-bg-dark-10,#ddd);border-radius:.5rem;padding:12px;background:var(--body-bg,#fff)}
.roster-bulk-shell .bulk-summary{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}
.roster-bulk-shell .bulk-summary span{font-size:.875rem;padding:.2rem .5rem;border-radius:999px;background:rgba(127,127,127,.12)}
.roster-bulk-shell .bulk-unmatched{margin-top:10px;padding:10px 12px;border-radius:.45rem;background:rgba(220,53,69,.08);border:1px solid rgba(220,53,69,.2)}
.roster-bulk-shell #roster-bulk-table{table-layout:fixed;width:100%;margin-bottom:0}
.roster-bulk-shell #roster-bulk-table th,.roster-bulk-shell #roster-bulk-table td{padding:.55rem .65rem;vertical-align:middle}
.roster-bulk-shell #roster-bulk-table th:nth-child(1){width:44px}.roster-bulk-shell #roster-bulk-table th:nth-child(2){width:38%}.roster-bulk-shell #roster-bulk-table th:nth-child(3){width:14%}.roster-bulk-shell #roster-bulk-table th:nth-child(4){width:22%}.roster-bulk-shell #roster-bulk-table th:nth-child(5){width:18%}
@media(max-width:900px){.roster-bulk-shell .bulk-top{grid-template-columns:1fr}.roster-bulk-shell #roster-bulk-table thead{display:none}.roster-bulk-shell #roster-bulk-table,.roster-bulk-shell #roster-bulk-table tbody,.roster-bulk-shell #roster-bulk-table tr,.roster-bulk-shell #roster-bulk-table td{display:block;width:100%}.roster-bulk-shell #roster-bulk-table tr{position:relative;border:1px solid var(--template-bg-dark-10,#ddd);border-radius:.55rem;margin-bottom:.65rem;padding:.65rem .75rem .65rem 42px}.roster-bulk-shell #roster-bulk-table td{border:0;padding:.18rem 0}.roster-bulk-shell #roster-bulk-table td:first-child{position:absolute;left:12px;top:12px;width:auto}.roster-bulk-shell #roster-bulk-table td:nth-child(3)::before{content:'Numero maglia: ';font-weight:600}.roster-bulk-shell #roster-bulk-table td:nth-child(4)::before{content:'Ruolo: ';font-weight:600}.roster-bulk-shell #roster-bulk-table td:nth-child(5)::before{content:'Giocatore: ';font-weight:600}}
</style>

<form action="<?= Route::_('index.php?option=com_xdecarocompetitions&view=rosterbulk'); ?>" method="post" enctype="multipart/form-data" name="adminForm" id="adminForm" class="competitions-admin roster-bulk-shell">
  <div class="card mb-3"><div class="card-body"><div class="bulk-top">
    <div>
      <label class="form-label fw-semibold" for="participation_id">Partecipazione</label>
      <div class="input-group">
        <select class="form-select" name="participation_id" id="participation_id" required>
          <option value="">- Seleziona partecipazione -</option>
          <?php foreach ($this->participationOptions as $option) : ?>
            <?php $label = trim((string)$option->team_name).' — '.trim((string)$option->tournament_name).' / '.trim((string)$option->season_name).((int)$option->season_year ? ' ('.(int)$option->season_year.')' : ''); ?>
            <option value="<?= (int)$option->id; ?>"<?= (int)$option->id === $this->participationId ? ' selected' : ''; ?>><?= $this->escape($label); ?></option>
          <?php endforeach; ?>
        </select>
        <button type="button" class="btn btn-outline-primary" id="load-participation">Carica</button>
      </div>
      <div class="form-text">Scegli squadra e stagione della rosa da preparare.</div>
    </div>
    <div class="bulk-box">
      <label class="form-label fw-semibold mb-1" for="source_csv">Importa elenco da CSV</label>
      <div class="input-group">
        <input type="file" class="form-control" name="source_csv" id="source_csv" accept=".csv,.txt,text/csv,text/plain">
        <button type="button" class="btn btn-outline-primary" id="preview-csv">Carica CSV</button>
      </div>
      <div class="form-text">Filtra il CSV sulla squadra della partecipazione e collega solo giocatori già presenti in Competitions.</div>
      <?php if ($hasCsv) : ?>
        <div class="small fw-semibold mt-2"><?= $this->escape($this->sourceFileName); ?></div>
        <div class="bulk-summary">
          <span><?= (int)$this->sourceSummary['source']; ?> righe CSV</span>
          <span><?= (int)$this->sourceSummary['team_rows']; ?> della squadra</span>
          <span><?= (int)$this->sourceSummary['matched']; ?> trovati</span>
          <span><?= (int)$this->sourceSummary['existing']; ?> già in rosa</span>
          <span><?= (int)$this->sourceSummary['available']; ?> da aggiungere</span>
          <span><?= (int)$this->sourceSummary['unmatched']; ?> non abbinate</span>
        </div>
        <?php if ($this->unmatched) : ?><div class="bulk-unmatched"><div class="fw-semibold">Non abbinate</div><ul class="mb-0 mt-1"><?php foreach ($this->unmatched as $name) : ?><li><?= $this->escape($name); ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <button type="button" class="btn btn-sm btn-link px-0 mt-1" id="clear-csv">Rimuovi filtro CSV</button>
      <?php endif; ?>
    </div>
  </div></div></div>

  <?php if ($this->participation) : ?>
  <div class="card"><div class="card-body p-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
      <div><h2 class="h5 mb-1"><?= $this->escape((string)$this->participation->team_name); ?></h2><div class="text-muted"><?= $this->escape((string)$this->participation->tournament_name); ?> · <?= $this->escape((string)$this->participation->season_name); ?><?= (int)$this->participation->season_year ? ' · '.(int)$this->participation->season_year : ''; ?></div></div>
      <div class="d-flex gap-2"><button type="button" class="btn btn-sm btn-outline-secondary" id="select-all">Seleziona tutte</button><button type="button" class="btn btn-sm btn-outline-secondary" id="select-none">Deseleziona tutte</button></div>
    </div>
    <?php if (!$hasCsv) : ?><div class="alert alert-info mb-0">Carica un CSV per vedere i giocatori della squadra.</div>
    <?php elseif (!$this->items) : ?><div class="alert alert-info mb-0">Nessun nuovo giocatore disponibile per questa rosa.</div>
    <?php else : ?>
      <div class="table-responsive"><table class="table table-hover align-middle" id="roster-bulk-table"><thead><tr><th><input type="checkbox" id="master-check" aria-label="Seleziona tutte"></th><th>Giocatore</th><th>Numero maglia</th><th>Ruolo</th><th>Stato giocatore</th></tr></thead><tbody>
      <?php foreach ($this->items as $item) : ?><tr><td><input class="form-check-input roster-check" type="checkbox" name="player_id[]" value="<?= (int)$item['player_id']; ?>"></td><td><div class="fw-semibold"><?= $this->escape($item['name']); ?></div><div class="small text-muted"><?= $this->escape($item['person_uuid']); ?></div></td><td><?= $item['shirt_number'] !== null ? (int)$item['shirt_number'] : '—'; ?></td><td><?= $this->escape($item['role'] ?: '—'); ?></td><td><?= $this->escape($item['approval_status']); ?></td></tr><?php endforeach; ?>
      </tbody></table></div>
    <?php endif; ?>
  </div></div>
  <?php endif; ?>

  <input type="hidden" name="task" value=""><input type="hidden" name="boxchecked" value="0"><?= HTMLHelper::_('form.token'); ?>
</form>
<script>
(() => {
 const form=document.getElementById('adminForm'), participation=document.getElementById('participation_id'), csv=document.getElementById('source_csv'), checks=()=>[...document.querySelectorAll('.roster-check')], master=document.getElementById('master-check');
 const sync=()=>{const n=checks().filter(x=>x.checked).length,b=form?.querySelector('input[name="boxchecked"]');if(b)b.value=String(n);if(window.Joomla?.isChecked)Joomla.isChecked(n>0);if(master)master.checked=checks().length>0&&n===checks().length;};
 document.getElementById('load-participation')?.addEventListener('click',()=>{const id=Number(participation?.value||0);if(id)location.href='index.php?option=com_xdecarocompetitions&view=rosterbulk&participation_id='+encodeURIComponent(id);});
 document.getElementById('preview-csv')?.addEventListener('click',()=>{if(!Number(participation?.value||0)){alert('Seleziona prima una partecipazione.');return;}if(!csv?.files?.length){alert('Seleziona un file CSV.');return;}window.Joomla?.submitbutton?.('rosterbulk.previewCsv');});
 document.getElementById('clear-csv')?.addEventListener('click',()=>window.Joomla?.submitbutton?.('rosterbulk.clearCsv'));
 document.getElementById('select-all')?.addEventListener('click',()=>{checks().forEach(x=>x.checked=true);sync();});document.getElementById('select-none')?.addEventListener('click',()=>{checks().forEach(x=>x.checked=false);sync();});master?.addEventListener('change',()=>{checks().forEach(x=>x.checked=master.checked);sync();});checks().forEach(x=>x.addEventListener('change',sync));sync();
 const original=window.Joomla?.submitbutton;if(window.Joomla)window.Joomla.submitbutton=(task)=>{if(task==='rosterbulk.addSelected'){const n=checks().filter(x=>x.checked).length;if(!n){alert('Seleziona almeno un giocatore.');return;}if(!confirm(`Aggiungere ${n} giocatori alla rosa?`))return;}if(typeof original==='function')original(task);else{const t=form.querySelector('input[name="task"]');if(t)t.value=task;form.submit();}};
})();
</script>

(() => {
  'use strict';

  const options = window.Joomla?.getOptions?.('com_decarodcl.liveSync') || {};

  if (!options.endpoint || !options.token) {
    return;
  }

  const entityMap = {
    organizations: 'organization', organization: 'organization',
    zones: 'zone', zone: 'zone',
    countries: 'country', country: 'country',
    federations: 'federation', federation: 'federation',
    tournaments: 'tournament', tournament: 'tournament',
    seasons: 'season', season: 'season',
    teams: 'team', team: 'team',
    participations: 'participation', participation: 'participation',
    players: 'player', player: 'player',
    rosters: 'roster', roster: 'roster',
    matches: 'match', match: 'match'
  };

  const strings = options.strings || {};
  const interval = Math.max(3000, Number(options.interval) || 5000);
  const params = new URLSearchParams(window.location.search);
  const bodyViewClass = Array.from(document.body.classList).find((name) => name.startsWith('view-'));
  const rawView = bodyViewClass ? bodyViewClass.slice(5) : (params.get('view') || (params.get('task') || '').split('.')[0]);
  const entity = entityMap[rawView] || '';
  const isDashboard = rawView === 'dashboard';
  const root = document.querySelector('.dcl-admin');
  const form = document.querySelector('form.form-validate.dcl-admin');
  const idField = document.querySelector('[name="jform[id]"]');
  const entityId = Math.max(0, Number(params.get('id') || idField?.value || 0));
  const isEditing = Boolean(form && entity && entityId > 0);
  const clientId = getClientId();
  let cursor = 0;
  let bootstrapped = false;
  let dirty = false;
  let conflict = false;
  let reloadTimer = null;
  let pollTimer = null;
  const processed = new Set();
  const channel = 'BroadcastChannel' in window ? new BroadcastChannel('com_decarodcl.liveSync') : null;

  injectHidden(document.querySelector('form.dcl-admin'), 'dcl_client_id', clientId);

  if (form) {
    form.addEventListener('input', markDirty, { passive: true });
    form.addEventListener('change', markDirty, { passive: true });
    form.addEventListener('submit', (event) => {
      if (!conflict) {
        return;
      }

      event.preventDefault();
      showConflict();
    });
  }

  channel?.addEventListener('message', (event) => {
    const change = event.data;

    if (change && change.id) {
      handleChange(change);
    }
  });

  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) {
      poll();
    }
  });

  window.addEventListener('focus', () => poll(), { passive: true });
  window.addEventListener('beforeunload', () => channel?.close(), { once: true });

  poll();

  function getClientId() {
    const key = 'com_decarodcl.liveSync.clientId';
    let value = window.sessionStorage.getItem(key);

    if (!value) {
      value = window.crypto?.randomUUID?.() || `dcl-${Date.now()}-${Math.random().toString(36).slice(2)}`;
      window.sessionStorage.setItem(key, value);
    }

    return value;
  }

  function injectHidden(targetForm, name, value) {
    if (!targetForm) {
      return null;
    }

    let input = targetForm.querySelector(`input[name="${name}"]`);

    if (!input) {
      input = document.createElement('input');
      input.type = 'hidden';
      input.name = name;
      targetForm.append(input);
    }

    input.value = value ?? '';

    return input;
  }

  function markDirty(event) {
    if (event.target instanceof HTMLInputElement && event.target.type === 'hidden') {
      return;
    }

    dirty = true;
  }

  async function poll() {
    window.clearTimeout(pollTimer);

    if (document.hidden) {
      pollTimer = window.setTimeout(poll, interval);
      return;
    }

    const body = new URLSearchParams();
    body.set(options.token, '1');
    body.set('since', String(cursor));
    body.set('entity', entity);
    body.set('entity_id', String(entityId));
    body.set('client_id', clientId);
    body.set('editing', isEditing ? '1' : '0');

    try {
      const response = await fetch(options.endpoint, {
        method: 'POST',
        body,
        credentials: 'same-origin',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        }
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      const json = await response.json();
      const payload = json?.data || json;

      if (!payload || typeof payload !== 'object') {
        throw new Error('Invalid live-sync response');
      }

      if (!bootstrapped) {
        cursor = Number(payload.latest_id || 0);
        bootstrapped = true;

        if (isEditing && payload.current_modified) {
          injectHidden(form, 'dcl_expected_modified', String(payload.current_modified));
        }
      } else {
        const changes = Array.isArray(payload.changes) ? payload.changes : [];

        for (const change of changes) {
          if (!change?.id) {
            continue;
          }

          handleChange(change);
          channel?.postMessage(change);
        }

        cursor = Math.max(cursor, Number(payload.latest_id || cursor));
      }

      updatePresence(Array.isArray(payload.presence) ? payload.presence : []);
    } catch (error) {
      if (options.debug) {
        console.warn('Competitions Live Sync:', error);
      }
    } finally {
      pollTimer = window.setTimeout(poll, interval);
    }
  }

  function handleChange(change) {
    const id = Number(change.id || 0);

    if (id > 0) {
      if (processed.has(id)) {
        return;
      }

      processed.add(id);

      if (processed.size > 250) {
        processed.delete(processed.values().next().value);
      }
    }

    const changedEntity = entityMap[String(change.entity_type || '')] || String(change.entity_type || '');
    const changedId = Number(change.entity_id || 0);

    if (isEditing && changedEntity === entity && changedId === entityId) {
      if (dirty) {
        conflict = true;
        showConflict();
      } else {
        scheduleReload();
      }

      return;
    }

    if (!isEditing && ((entity && changedEntity === entity) || isDashboard)) {
      scheduleReload();
    }
  }

  function scheduleReload() {
    if (reloadTimer) {
      return;
    }

    reloadTimer = window.setTimeout(() => window.location.reload(), 650);
  }

  function updatePresence(presence) {
    if (conflict || !isEditing) {
      return;
    }

    const names = [...new Set(
      presence
        .map((item) => String(item.user_name || '').trim())
        .filter(Boolean)
    )];

    if (!names.length) {
      removeNotice('presence');
      return;
    }

    const template = strings.presence || 'Also being edited by %s';
    showNotice('presence', template.replace('%s', names.join(', ')), 'info', false);
  }

  function showConflict() {
    const message = strings.conflict || 'This record was changed in another session. Reload before saving to avoid overwriting newer data.';
    showNotice('conflict', message, 'warning', true);
  }

  function showNotice(key, message, tone, withReload) {
    if (!root) {
      return;
    }

    let notice = root.querySelector(`[data-dcl-live-sync="${key}"]`);

    if (!notice) {
      notice = document.createElement('div');
      notice.dataset.dclLiveSync = key;
      notice.className = `dcl-live-sync dcl-live-sync--${tone}`;
      notice.setAttribute('role', tone === 'warning' ? 'alert' : 'status');
      root.prepend(notice);
    }

    notice.replaceChildren();

    const text = document.createElement('div');
    text.className = 'dcl-live-sync__text';
    text.textContent = message;
    notice.append(text);

    if (withReload) {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'btn btn-sm btn-primary';
      button.textContent = strings.reload || 'Reload data';
      button.addEventListener('click', () => window.location.reload());
      notice.append(button);
    }
  }

  function removeNotice(key) {
    root?.querySelector(`[data-dcl-live-sync="${key}"]`)?.remove();
  }
})();

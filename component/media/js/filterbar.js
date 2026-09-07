(() => {
  'use strict';

  const options = window.Joomla?.getOptions?.('com_decarodcl.filterbar') || {};
  const strings = {
    filters: 'Filters',
    clear: 'Clear',
    show: 'Show filters',
    hide: 'Hide filters',
    remove: 'Remove filter %s',
    ...options.strings,
  };
  const debounceMs = Math.max(250, Number(options.debounce) || 450);

  document.querySelectorAll('.dcl-filterbar').forEach((bar, index) => enhance(bar, index));

  function enhance(bar, index) {
    if (bar.dataset.dclFilterbarEnhanced === '1') {
      return;
    }

    const form = bar.closest('form');
    const searchWrap = bar.querySelector('.dcl-filterbar__search');
    const search = searchWrap?.querySelector('input[type="search"]') || null;
    const children = Array.from(bar.children);
    const selects = children.filter((node) => node instanceof HTMLSelectElement);
    const submitButton = children.find((node) => node.matches?.('button[type="submit"], input[type="submit"]')) || null;
    const clearLink = children.find((node) => node instanceof HTMLAnchorElement) || null;

    if (!form || (!search && selects.length === 0)) {
      return;
    }

    const panelId = `dcl-filterbar-panel-${index + 1}`;
    const storageKey = `com_decarodcl.filterbar.${window.location.pathname}.${new URLSearchParams(window.location.search).get('view') || 'list'}`;
    let debounceTimer = null;
    let lastSearch = search ? search.value : '';

    bar.dataset.dclFilterbarEnhanced = '1';
    bar.classList.add('dcl-filterbar--enhanced');

    if (searchWrap && search) {
      search.setAttribute('autocomplete', 'off');
      search.setAttribute('enterkeyhint', 'search');
      searchWrap.prepend(icon('search', 'dcl-filterbar__search-icon'));
    }

    const top = document.createElement('div');
    top.className = 'dcl-filterbar__top';

    if (searchWrap) {
      top.append(searchWrap);
    }

    const actions = document.createElement('div');
    actions.className = 'dcl-filterbar__actions';

    let toggle = null;
    let count = null;
    let panel = null;

    if (selects.length > 0) {
      toggle = document.createElement('button');
      toggle.type = 'button';
      toggle.className = 'dcl-filterbar__toggle';
      toggle.setAttribute('aria-controls', panelId);
      toggle.append(icon('filter', 'dcl-filterbar__button-icon'));

      const toggleText = document.createElement('span');
      toggleText.textContent = strings.filters;
      toggle.append(toggleText);

      count = document.createElement('span');
      count.className = 'dcl-filterbar__count';
      count.setAttribute('aria-hidden', 'true');
      toggle.append(count);
      actions.append(toggle);

      panel = document.createElement('div');
      panel.id = panelId;
      panel.className = 'dcl-filterbar__panel';

      selects.forEach((select, selectIndex) => {
        select.removeAttribute('onchange');
        select.onchange = null;

        if (!select.id) {
          select.id = `${panelId}-field-${selectIndex + 1}`;
        }

        const field = document.createElement('div');
        field.className = 'dcl-filterbar__field';

        const label = document.createElement('label');
        label.className = 'dcl-filterbar__label';
        label.htmlFor = select.id;
        label.textContent = fieldLabel(select);

        if (!select.getAttribute('aria-label')) {
          select.setAttribute('aria-label', label.textContent);
        }

        field.append(label, select);
        panel.append(field);

        select.addEventListener('change', () => {
          storeOpen(storageKey, true);
          submit(form);
        });
      });

      const open = readOpen(storageKey);
      setPanelOpen(toggle, panel, open);
      toggle.addEventListener('click', () => {
        const nextOpen = toggle.getAttribute('aria-expanded') !== 'true';
        setPanelOpen(toggle, panel, nextOpen);
        storeOpen(storageKey, nextOpen);
      });
    }

    if (clearLink) {
      clearLink.className = 'dcl-filterbar__clear';
      clearLink.replaceChildren(
        icon('clear', 'dcl-filterbar__button-icon'),
        document.createTextNode(strings.clear)
      );
      actions.append(clearLink);
    }

    if (submitButton) {
      submitButton.classList.add('dcl-filterbar__legacy-submit');
      submitButton.hidden = true;
    }

    top.append(actions);

    const chips = document.createElement('div');
    chips.className = 'dcl-filterbar__chips';
    chips.setAttribute('role', 'list');

    bar.replaceChildren(top);

    if (panel) {
      bar.append(panel);
    }

    bar.append(chips);
    updateState();

    if (search) {
      search.addEventListener('input', scheduleSearch);
      search.addEventListener('search', scheduleSearch);
      search.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
          event.preventDefault();
          window.clearTimeout(debounceTimer);
          submitSearch();
          return;
        }

        if (event.key === 'Escape' && search.value !== '') {
          event.preventDefault();
          search.value = '';
          window.clearTimeout(debounceTimer);
          submitSearch();
        }
      });
    }

    function scheduleSearch() {
      updateState();
      window.clearTimeout(debounceTimer);
      debounceTimer = window.setTimeout(submitSearch, debounceMs);
    }

    function submitSearch() {
      const current = search ? search.value : '';

      if (current === lastSearch) {
        return;
      }

      lastSearch = current;
      submit(form);
    }

    function updateState() {
      const active = selects.filter((select) => select.selectedIndex > 0);
      const hasSearch = Boolean(search?.value.trim());

      if (count) {
        count.textContent = active.length > 0 ? String(active.length) : '';
      }

      if (clearLink) {
        clearLink.hidden = active.length === 0 && !hasSearch;
      }

      chips.replaceChildren();

      for (const select of active) {
        const selected = select.options[select.selectedIndex];
        const label = selected?.textContent?.trim() || '';

        if (!label) {
          continue;
        }

        const chip = document.createElement('button');
        chip.type = 'button';
        chip.className = 'dcl-filterbar__chip';
        chip.setAttribute('role', 'listitem');
        chip.setAttribute('aria-label', strings.remove.replace('%s', label));

        const text = document.createElement('span');
        text.textContent = label;
        const remove = document.createElement('span');
        remove.className = 'dcl-filterbar__chip-remove';
        remove.setAttribute('aria-hidden', 'true');
        remove.textContent = '×';
        chip.append(text, remove);

        chip.addEventListener('click', () => {
          select.selectedIndex = 0;
          storeOpen(storageKey, true);
          submit(form);
        });

        chips.append(chip);
      }

      chips.hidden = chips.childElementCount === 0;
    }
  }

  function fieldLabel(select) {
    const first = select.options[0]?.textContent?.trim() || strings.filters;
    const cleaned = first.replace(/^[\s\-–—]+|[\s\-–—]+$/g, '').trim();

    return cleaned || strings.filters;
  }

  function setPanelOpen(toggle, panel, open) {
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    toggle.setAttribute('aria-label', open ? strings.hide : strings.show);
    panel.hidden = !open;
  }

  function readOpen(key) {
    try {
      return window.sessionStorage.getItem(key) === '1';
    } catch (_) {
      return false;
    }
  }

  function storeOpen(key, open) {
    try {
      window.sessionStorage.setItem(key, open ? '1' : '0');
    } catch (_) {
    }
  }

  function submit(form) {
    if (typeof form.requestSubmit === 'function') {
      const task = form.querySelector('input[name="task"]');

      if (task) {
        task.value = '';
      }

      form.requestSubmit();
      return;
    }

    form.submit();
  }

  function icon(type, className) {
    const span = document.createElement('span');
    span.className = className;
    span.setAttribute('aria-hidden', 'true');

    if (type === 'search') {
      span.innerHTML = '<svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.5"></circle><path d="m16 16 4.5 4.5"></path></svg>';
    } else if (type === 'filter') {
      span.innerHTML = '<svg viewBox="0 0 24 24"><path d="M4 6h16M7 12h10M10 18h4"></path></svg>';
    } else {
      span.innerHTML = '<svg viewBox="0 0 24 24"><path d="m7 7 10 10M17 7 7 17"></path></svg>';
    }

    return span;
  }
})();

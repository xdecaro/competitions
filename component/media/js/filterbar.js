(() => {
  'use strict';

  const options = window.Joomla?.getOptions?.('com_decarodcl.filterbar') || {};
  const strings = {
    filters: 'Filters',
    clear: 'Clear',
    show: 'Show filters',
    hide: 'Close filters',
    remove: 'Remove filter %s',
    ...options.strings,
  };

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

    const filterModeId = `dcl-filterbar-filters-${index + 1}`;
    const storageKey = `com_decarodcl.filterbar.${window.location.pathname}.${new URLSearchParams(window.location.search).get('view') || 'list'}`;

    bar.dataset.dclFilterbarEnhanced = '1';
    bar.classList.add('dcl-filterbar--enhanced');

    if (searchWrap && search) {
      search.setAttribute('autocomplete', 'off');
      search.setAttribute('enterkeyhint', 'search');
      searchWrap.prepend(icon('search', 'dcl-filterbar__search-icon'));
    }

    const searchMode = document.createElement('div');
    searchMode.className = 'dcl-filterbar__mode dcl-filterbar__mode--search';

    if (searchWrap) {
      searchMode.append(searchWrap);
    }

    const actions = document.createElement('div');
    actions.className = 'dcl-filterbar__actions';

    if (submitButton) {
      submitButton.hidden = false;
      submitButton.className = 'dcl-filterbar__submit';
      actions.append(submitButton);
    }

    if (clearLink) {
      clearLink.hidden = false;
      clearLink.className = 'dcl-filterbar__clear';
      clearLink.replaceChildren(
        icon('clear', 'dcl-filterbar__button-icon'),
        document.createTextNode(strings.clear)
      );
      actions.append(clearLink);
    }

    let toggle = null;
    let count = null;
    let filterMode = null;
    let closeFilters = null;

    if (selects.length > 0) {
      toggle = document.createElement('button');
      toggle.type = 'button';
      toggle.className = 'dcl-filterbar__toggle';
      toggle.setAttribute('aria-controls', filterModeId);
      toggle.append(icon('filter', 'dcl-filterbar__button-icon'));

      const toggleText = document.createElement('span');
      toggleText.textContent = strings.filters;
      toggle.append(toggleText);

      count = document.createElement('span');
      count.className = 'dcl-filterbar__count';
      count.setAttribute('aria-hidden', 'true');
      toggle.append(count);
      actions.append(toggle);

      filterMode = document.createElement('div');
      filterMode.id = filterModeId;
      filterMode.className = 'dcl-filterbar__mode dcl-filterbar__mode--filters';
      filterMode.setAttribute('role', 'group');
      filterMode.setAttribute('aria-label', strings.filters);
      filterMode.style.setProperty('--dcl-filter-count', String(selects.length));

      selects.forEach((select, selectIndex) => {
        select.removeAttribute('onchange');
        select.onchange = null;

        if (!select.id) {
          select.id = `${filterModeId}-field-${selectIndex + 1}`;
        }

        const field = document.createElement('div');
        field.className = 'dcl-filterbar__field';

        const label = document.createElement('label');
        label.className = 'visually-hidden';
        label.htmlFor = select.id;
        label.textContent = fieldLabel(select);

        if (!select.getAttribute('aria-label')) {
          select.setAttribute('aria-label', label.textContent);
        }

        field.append(label, select);
        filterMode.append(field);

        select.addEventListener('change', () => {
          storeOpen(storageKey, true);
          submit(form);
        });
      });

      closeFilters = document.createElement('button');
      closeFilters.type = 'button';
      closeFilters.className = 'dcl-filterbar__close';
      closeFilters.setAttribute('aria-controls', filterModeId);
      closeFilters.replaceChildren(
        icon('clear', 'dcl-filterbar__button-icon'),
        document.createTextNode(strings.hide)
      );
      filterMode.append(closeFilters);

      toggle.addEventListener('click', () => {
        setMode(true, true);
      });

      closeFilters.addEventListener('click', () => {
        setMode(false, true);
      });
    }

    searchMode.append(actions);
    bar.replaceChildren(searchMode);

    if (filterMode) {
      bar.append(filterMode);
    }

    updateState();
    setMode(Boolean(filterMode && readOpen(storageKey)), false);

    if (search) {
      search.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
          event.preventDefault();
          submit(form);
        }
      });
    }

    function setMode(showFilters, moveFocus) {
      const filtersOpen = Boolean(filterMode && showFilters);

      searchMode.hidden = filtersOpen;

      if (filterMode) {
        filterMode.hidden = !filtersOpen;
      }

      if (toggle) {
        toggle.setAttribute('aria-expanded', filtersOpen ? 'true' : 'false');
        toggle.setAttribute('aria-label', filtersOpen ? strings.hide : strings.show);
      }

      bar.dataset.dclFilterMode = filtersOpen ? 'filters' : 'search';
      storeOpen(storageKey, filtersOpen);

      if (!moveFocus) {
        return;
      }

      if (filtersOpen) {
        selects[0]?.focus();
      } else if (search) {
        search.focus();
      } else {
        toggle?.focus();
      }
    }

    function updateState() {
      const active = selects.filter((select) => select.selectedIndex > 0);

      if (count) {
        count.textContent = active.length > 0 ? String(active.length) : '';
      }
    }
  }

  function fieldLabel(select) {
    const first = select.options[0]?.textContent?.trim() || strings.filters;
    let cleaned = first.replace(/^[\s\-–—]+|[\s\-–—]+$/g, '').trim();

    cleaned = cleaned.replace(
      /^(?:all|tutti|tutte|tutto|tutta)(?:\s+(?:the|gli|i|le|la|il|lo))?\s+/i,
      ''
    ).trim();

    return cleaned || strings.filters;
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
    const task = form.querySelector('input[name="task"]');

    if (task) {
      task.value = '';
    }

    if (typeof form.requestSubmit === 'function') {
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

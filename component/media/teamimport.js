(() => {
  'use strict';

  const normalise = (value) => String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim();

  const init = () => {
    const root = document.querySelector('[data-teamimport-live]');

    if (!root) {
      return;
    }

    const search = root.querySelector('[data-teamimport-search]');
    const clear = root.querySelector('[data-teamimport-clear]');
    const selectedOnly = root.querySelector('[data-teamimport-selected-only]');
    const selectedCount = root.querySelector('[data-teamimport-selected-count]');
    const visibleCount = root.querySelector('[data-teamimport-visible-count]');
    const checkAll = root.querySelector('[data-teamimport-checkall]');
    const emptyState = root.querySelector('[data-teamimport-empty]');
    const rows = Array.from(root.querySelectorAll('[data-teamimport-row]'));
    const selectable = rows.filter((row) => row.querySelector('input[name="cid[]"]:not(:disabled)'));

    const checkedBoxes = () => selectable
      .map((row) => row.querySelector('input[name="cid[]"]'))
      .filter((box) => box && box.checked);

    const isRowVisibleBySearch = (row) => {
      const query = normalise(search?.value);
      const haystack = normalise(row.dataset.teamimportSearch || '');

      if (query !== '' && !haystack.includes(query)) {
        return false;
      }

      if (selectedOnly?.checked) {
        const box = row.querySelector('input[name="cid[]"]');

        if (!box?.checked) {
          return false;
        }
      }

      return true;
    };

    const updateToolbarSelection = () => {
      const count = checkedBoxes().length;
      const boxchecked = document.querySelector('#adminForm input[name="boxchecked"]');

      if (boxchecked) {
        boxchecked.value = String(count);
      }

      if (selectedCount) {
        selectedCount.textContent = String(count);
      }

      document.querySelectorAll('joomla-toolbar-button button[disabled]').forEach(() => {
        // Joomla updates toolbar enablement through isChecked when available.
      });
    };

    const updateCheckAll = () => {
      if (!checkAll) {
        return;
      }

      const visibleSelectable = selectable.filter((row) => !row.hidden);
      const checkedVisible = visibleSelectable.filter((row) => {
        const box = row.querySelector('input[name="cid[]"]');
        return !!box?.checked;
      });

      checkAll.checked = visibleSelectable.length > 0 && checkedVisible.length === visibleSelectable.length;
      checkAll.indeterminate = checkedVisible.length > 0 && checkedVisible.length < visibleSelectable.length;
      checkAll.disabled = visibleSelectable.length === 0;
    };

    const applyFilter = () => {
      let visible = 0;

      rows.forEach((row) => {
        const show = isRowVisibleBySearch(row);
        row.hidden = !show;

        if (show) {
          visible += 1;
        }
      });

      if (visibleCount) {
        visibleCount.textContent = String(visible);
      }

      if (emptyState) {
        emptyState.hidden = visible > 0;
      }

      updateCheckAll();
      updateToolbarSelection();
    };

    search?.addEventListener('input', applyFilter);

    clear?.addEventListener('click', () => {
      if (search) {
        search.value = '';
        search.focus();
      }

      if (selectedOnly) {
        selectedOnly.checked = false;
      }

      applyFilter();
    });

    selectedOnly?.addEventListener('change', applyFilter);

    selectable.forEach((row) => {
      const box = row.querySelector('input[name="cid[]"]');

      box?.addEventListener('change', () => {
        if (typeof Joomla !== 'undefined' && typeof Joomla.isChecked === 'function') {
          Joomla.isChecked(box.checked);
        }

        updateToolbarSelection();
        updateCheckAll();

        if (selectedOnly?.checked) {
          applyFilter();
        }
      });
    });

    checkAll?.addEventListener('change', () => {
      const visibleSelectable = selectable.filter((row) => !row.hidden);

      visibleSelectable.forEach((row) => {
        const box = row.querySelector('input[name="cid[]"]');

        if (box && box.checked !== checkAll.checked) {
          box.checked = checkAll.checked;

          if (typeof Joomla !== 'undefined' && typeof Joomla.isChecked === 'function') {
            Joomla.isChecked(box.checked);
          }
        }
      });

      updateToolbarSelection();
      updateCheckAll();
    });

    applyFilter();
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();

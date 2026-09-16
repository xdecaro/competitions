(() => {
  'use strict';

  const endpoint = (task) => `index.php?option=com_xdecarocompetitions&task=${task}&format=json`;

  const tokenName = () => document.querySelector('form#player-form input[type="hidden"][name][value="1"]')?.name || '';

  const showError = (message) => {
    const text = String(message || 'Request failed.');
    if (window.Joomla && typeof window.Joomla.renderMessages === 'function') {
      window.Joomla.renderMessages({ error: [text] });
      return;
    }
    window.alert(text);
  };

  const label = (person) => {
    const meta = [person.email, person.phone].filter(Boolean).join(' · ');
    return meta ? `${person.display_name || person.uuid} — ${meta}` : (person.display_name || person.uuid);
  };

  const init = (picker) => {
    const input = picker.querySelector('[data-competitions-people-search]');
    const results = picker.querySelector('[data-competitions-people-results]');
    const target = picker.querySelector('[data-competitions-person-target]');
    const summary = picker.querySelector('[data-competitions-person-summary]');
    if (!input || !results || !target) return;

    let timer = 0;
    let request = null;

    const clear = () => results.replaceChildren();

    const selectPerson = (person) => {
      target.value = String(person.uuid || '').toLowerCase();
      target.dispatchEvent(new Event('change', { bubbles: true }));

      const firstName = document.getElementById('jform_first_name');
      const lastName = document.getElementById('jform_last_name');
      if (firstName) firstName.value = person.first_name || '';
      if (lastName) lastName.value = person.last_name || '';

      input.value = person.display_name || `${person.first_name || ''} ${person.last_name || ''}`.trim();
      if (summary) {
        summary.textContent = label(person);
        summary.hidden = false;
      }
      clear();
    };

    const render = (rows) => {
      clear();
      if (!Array.isArray(rows) || rows.length === 0) {
        const empty = document.createElement('div');
        empty.className = 'alert alert-light mt-2 mb-0';
        empty.textContent = input.dataset.emptyLabel || 'No People records found.';
        results.appendChild(empty);
        return;
      }

      rows.forEach((person) => {
        if (!person?.uuid) return;
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-light text-start w-100 mt-2';
        button.textContent = label(person);
        button.addEventListener('click', () => selectPerson(person));
        results.appendChild(button);
      });
    };

    const search = async () => {
      const q = input.value.trim();
      if (q.length < 2) {
        clear();
        return;
      }

      if (request) request.abort();
      request = new AbortController();
      const url = new URL(endpoint('people.search'), window.location.href);
      url.searchParams.set('q', q);
      const token = tokenName();
      if (token) url.searchParams.set(token, '1');

      try {
        const response = await fetch(url.toString(), {
          headers: { Accept: 'application/json' },
          signal: request.signal,
        });
        const payload = await response.json();
        if (!response.ok || payload?.success === false) {
          throw new Error(payload?.message || `HTTP ${response.status}`);
        }
        render(payload?.data || []);
      } catch (error) {
        if (error?.name !== 'AbortError') showError(error?.message);
      }
    };

    input.addEventListener('input', () => {
      window.clearTimeout(timer);
      timer = window.setTimeout(search, 250);
    });
  };

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-competitions-people-picker]').forEach(init);
  });
})();

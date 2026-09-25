(() => {
  'use strict';

  const init = () => {
    const form = document.getElementById('federation-form');

    if (!form) {
      return;
    }

    const organization = form.querySelector('[name="jform[organization_uuid]"]');
    const country = form.querySelector('select[name="jform[country_id]"]');
    const shadow = form.querySelector('[data-federation-country-shadow]');
    const note = form.querySelector('[data-federation-country-derived]');
    const options = window.Joomla?.getOptions?.('com_xdecarocompetitions.federationEdit') || {};
    const countryMap = options.countryMap && typeof options.countryMap === 'object'
      ? options.countryMap
      : {};

    if (!organization || !country || !shadow) {
      return;
    }

    let wasDerived = false;

    const sync = () => {
      const uuid = String(organization.value || '').trim().toLowerCase();
      const countryId = Number(countryMap[uuid] || 0);
      const derived = countryId > 0;

      if (derived) {
        country.value = String(countryId);
        country.required = false;
        country.disabled = true;
        country.setAttribute('aria-readonly', 'true');
        shadow.value = String(countryId);
        shadow.disabled = false;
        country.dispatchEvent(new Event('change', { bubbles: true }));
      } else {
        if (wasDerived) {
          country.value = '';
          country.dispatchEvent(new Event('change', { bubbles: true }));
        }

        country.disabled = false;
        country.required = true;
        country.removeAttribute('aria-readonly');
        shadow.value = '';
        shadow.disabled = true;
      }

      wasDerived = derived;

      if (note) {
        note.classList.toggle('d-none', !derived);
      }
    };

    organization.addEventListener('change', sync);
    sync();
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();

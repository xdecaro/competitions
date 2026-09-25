(() => {
  'use strict';

  const init = () => {
    const form = document.getElementById('federation-form');

    if (!form) {
      return;
    }

    const organization = form.querySelector('[name="jform[organization_uuid]"]');
    const country = form.querySelector('[name="jform[country_id]"]');
    const note = form.querySelector('[data-federation-country-derived]');
    const options = window.Joomla?.getOptions?.('com_xdecarocompetitions.federationEdit') || {};
    const countryMap = options.countryMap && typeof options.countryMap === 'object'
      ? options.countryMap
      : {};

    if (!organization || !country) {
      return;
    }

    const sync = () => {
      const uuid = String(organization.value || '').trim().toLowerCase();
      const countryId = Number(countryMap[uuid] || 0);
      const derived = countryId > 0;

      if (derived) {
        country.value = String(countryId);
        country.required = false;
        country.disabled = true;
        country.setAttribute('aria-readonly', 'true');
        country.dispatchEvent(new Event('change', { bubbles: true }));
      } else {
        country.disabled = false;
        country.required = true;
        country.removeAttribute('aria-readonly');
      }

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

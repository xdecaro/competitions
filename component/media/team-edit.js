(() => {
  'use strict';

  const init = () => {
    const form = document.getElementById('team-form');

    if (!form) {
      return;
    }

    const typeField = form.querySelector('[name="jform[team_type]"]');
    const linkCard = form.querySelector('[data-team-organization-link]');
    const localIdentity = form.querySelector('[data-team-local-identity]');
    const manualFederation = form.querySelector('[data-team-manual-federation]');

    if (!typeField || !localIdentity) {
      return;
    }

    const existingLegacy = localIdentity.dataset.teamExistingLegacy === '1';
    const linkAvailable = localIdentity.dataset.teamLinkAvailable === '1';

    const sync = () => {
      const isClub = String(typeField.value || 'club') === 'club';

      if (linkCard) {
        linkCard.hidden = !isClub;
      }

      if (!linkAvailable || existingLegacy) {
        localIdentity.hidden = false;

        if (manualFederation) {
          manualFederation.hidden = false;
        }

        return;
      }

      localIdentity.hidden = isClub;

      if (manualFederation) {
        manualFederation.hidden = isClub;
      }
    };

    typeField.addEventListener('change', sync);
    sync();
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();

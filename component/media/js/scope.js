(() => {
  'use strict';

  const options = window.Joomla?.getOptions?.('com_decarodcl.scope') || {};
  const season = document.getElementById('jform_season_id');
  const team = document.getElementById('jform_team_id');

  if (!options.endpoint || !options.token || !season || !team) {
    return;
  }

  const strings = options.strings || {};
  let requestController = null;
  let selectedTeamId = String(team.value || '');

  season.addEventListener('change', () => {
    selectedTeamId = '';
    loadTeams();
  });

  loadTeams();

  async function loadTeams() {
    requestController?.abort();
    requestController = new AbortController();

    const seasonId = Number(season.value || 0);

    if (seasonId <= 0) {
      replaceOptions([], strings.selectSeason || 'Select the season first', '');
      team.disabled = true;
      return;
    }

    const previous = selectedTeamId || String(team.value || '');
    replaceOptions([], strings.loading || 'Loading eligible teams…', '');
    team.disabled = true;

    const body = new URLSearchParams();
    body.set(options.token, '1');
    body.set('season_id', String(seasonId));

    try {
      const response = await fetch(options.endpoint, {
        method: 'POST',
        body,
        credentials: 'same-origin',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        },
        signal: requestController.signal
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      const json = await response.json();
      const payload = json?.data || json;
      const teams = Array.isArray(payload?.teams) ? payload.teams : [];
      const placeholder = teams.length
        ? (strings.selectTeam || 'Select')
        : (strings.noTeams || 'No eligible teams for this season');

      replaceOptions(teams, placeholder, previous);
      team.disabled = teams.length === 0;
      selectedTeamId = String(team.value || '');
    } catch (error) {
      if (error.name === 'AbortError') {
        return;
      }

      team.disabled = false;
    }
  }

  function replaceOptions(teams, placeholder, selected) {
    const fragment = document.createDocumentFragment();
    const first = document.createElement('option');
    first.value = '';
    first.textContent = placeholder;
    fragment.append(first);

    for (const item of teams) {
      const option = document.createElement('option');
      option.value = String(item.id);
      const country = item.country_code ? `${item.country} (${item.country_code})` : item.country;
      option.textContent = `${item.name} — ${country} / ${item.federation}`;

      if (String(item.id) === String(selected)) {
        option.selected = true;
      }

      fragment.append(option);
    }

    team.replaceChildren(fragment);
    team.dispatchEvent(new Event('dcl:options-updated', { bubbles: true }));
  }
})();

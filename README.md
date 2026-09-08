# Competitions

**Competitions** is a neutral Joomla 6 management package for tournaments, seasons, organizations, zones, federations, teams, players, participations, rosters, matches, match events, standings, rankings and coefficients.

The repository keeps the historical/internal `dcl` technical identifiers for upgrade compatibility. They are implementation details and are no longer used as the visible product name.

## Naming

- Visible product: `Competitions`
- Administrator area: `Competitions`
- Joomla component: `com_decarodcl`
- Joomla package: `pkg_decarodcl`
- Core plugin: `plg_system_decarodcl`
- GitHub repository: `xdecaro/competitions`
- Database tables: `#__dcl_*`
- PHP component namespace: `Xdecaro\Component\Decarodcl`

## Current version

**0.12.1**

## Architecture

Competitions separates authentication, sport data and presentation:

- Joomla users are used for team-manager authentication and permissions only.
- Organizations, zones, teams, players, participations, rosters, matches, events, rankings and coefficients use dedicated `#__dcl_*` tables.
- `#__dcl_organizations` stores reusable organizations such as competition organizers, governing bodies, local organizers and partners.
- `#__dcl_zones` stores reusable sporting zones and may optionally be scoped to an Organization.
- `#__dcl_zone_countries` links Countries or sporting territories to Zones without forcing one global Zone per Country.
- Tournament and Season organization roles are stored in dedicated relation tables instead of duplicated text fields.
- `#__dcl_matches` is the authoritative source for match date, teams, status and scores.
- Joomla articles are optional editorial links only; `article_id` is not the sports-data source of truth.
- Match timeline events use `match_id` as the primary relation. The legacy `article_id` relation remains available for backward compatibility.
- `com_decarodcl` is the central administrator component.
- Frontend modules can be placed directly in YOOtheme layouts without moving authoritative sports data into Joomla articles.

## Xdecaro Core integration

Version 0.11.0 adds an optional Joomla DI service for the public Xdecaro Core cross-product reference contract. Competitions can create `EntityReference` and `RelationReference` values when Core is installed while continuing to work normally without Core.

The public component identifier in those references is deliberately `com_decarodcl`, not `com_decarocompetitions`, because the historical Joomla element remains the compatibility contract for installed sites and updates. Competitions keeps ownership of all sports-domain data and never exposes `#__dcl_*` tables as the cross-product API.

## Competition scope and participant types

Version 0.10.0 separates the geographic scope of a Tournament from the kind of Teams that are allowed to participate.

Tournament scope supports:

- **International** — no geographic restriction;
- **Continental / Zone** — one or more configured sporting Zones;
- **National** — exactly one Country or sporting territory;
- **Regional / Local** — exactly one Country plus an optional local-area description.

Tournament participant type supports **Clubs / Teams** or **National teams**. Teams also have an explicit `team_type` (`club` or `national`) so a World Championship for national teams and an international club competition such as DCL can use the same Team, Participation, Roster and Match architecture without treating a national selection as a normal club.

Tournament → Country and Tournament → Zone relations are stored in `#__dcl_tournament_countries` and `#__dcl_tournament_zones`. Participation eligibility is enforced server-side through Season → Tournament → scope, participant type and Federation → Country. The Participation form also filters eligible Teams dynamically, but JavaScript is only a UX aid: server validation remains authoritative.

Existing data is protected when scope-related master data changes. A Tournament scope change, Team type/Federation change, Federation Country change or Zone membership change is rejected when it would make an existing Participation incompatible. Existing Season host Countries are checked as well, so scope changes cannot silently leave a Season outside its Tournament geography.

## Live synchronization

Version 0.10.0 adds shared administrator synchronization for Organizations, Zones, Countries, Federations, Tournaments, Seasons, Teams, Participations, Players, Rosters and Matches.

The implementation intentionally does not require WebSockets or a special daemon. It combines:

- `BroadcastChannel` for fast communication between open browser tabs;
- lightweight incremental polling for other browsers and other computers;
- `#__dcl_changes` as a short-lived change cursor/log;
- `#__dcl_edit_sessions` for advisory edit presence;
- optimistic locking based on the record `modified` value rendered with the edit form.

Lists and Dashboard reload when relevant remote changes are detected while keeping the current URL, filters, sorting and pagination. A clean edit form can reload automatically when its record changes. If the local form already contains unsaved edits, Competitions shows a conflict warning instead of replacing the form. The same `modified` baseline is checked again server-side on save, preventing a stale browser from overwriting a newer record even if it saves before the next poll. State/publish actions explicitly advance the version timestamp for the same reason.

All synchronization endpoints require Joomla CSRF validation and `core.manage`. Edit presence is advisory only; a temporary synchronization failure never blocks normal CRUD operations.

## Administrator design system

Version 0.9.0 introduced a shared administrator page header and visual language for every Competitions view. Dashboard, lists, edit forms and Information use the same blue uppercase eyebrow, large page title, muted description, typography, spacing, rounded surfaces and responsive/dark-mode tokens inspired by the established xdecaro Courses interface.

Version 0.10.1 extended the shared design system to list search and filters with one centralized filterbar enhancement, a collapsible secondary filter area, active-filter count and removable filter chips.

Version 0.10.2 refines that interaction after administrator feedback: search is explicit rather than automatic. Each list now prioritizes one wide search field followed by **Search** and **Clear**, while **Filters** stays on its own row below. Pressing Enter is equivalent to Search; typing no longer reloads the list in the background. Secondary filters still apply immediately, retain the active-filter counter/chips, and remain responsive in light and dark mode.

Version 0.10.3 fixes the Joomla Web Asset paths used by the shared filterbar, Live Sync and scope scripts. Joomla resolves style/script URIs against the component `media/css` and `media/js` folders, so asset URIs must not repeat `/css/` or `/js/`. The runtime registrations and `joomla.asset.json` now follow the same convention already used by Courses. The standard folder copies are authoritative; root copies remain declared only as legacy compatibility fallbacks for upgrades from earlier Competitions builds.

Version 0.10.4 replaces the stacked search-plus-filter panel with two mutually exclusive toolbar modes. The default mode is one wide search field followed by **Search**, **Clear** and **Filters**. Opening Filters replaces that same row with all available select filters plus **Close filters**, so desktop never shows a second filter panel underneath. Active filter selections are preserved while switching modes; select changes continue to submit immediately, and the Filters button shows the active-filter count. Tablet and smartphone layouts adapt to two columns or one column without forcing unusably narrow selects.

Version 0.10.5 keeps the search row permanently visible and opens the filters underneath as a lightweight animated drawer inside the same toolbar card. The drawer uses only a subtle divider rather than a nested card, adds extra horizontal inset so the first filter is not flush against the left edge, preserves active selections after reload, and keeps the active-filter count on the Filters button. The opening/closing transition respects `prefers-reduced-motion`, while tablet and smartphone layouts remain responsive.

Version 0.10.7 centers filter select values and uses consistent inset chevrons while preserving the existing responsive and dark-mode behavior.

The header is rendered centrally through `PageHeaderHelper` and a reusable Joomla layout rather than duplicated in each template. This keeps future visual changes synchronized across the whole component and preserves existing toolbar, form, filter, table and CRUD behaviour.

Version 0.9.1 hardened that shared header integration by normalizing the optional record ID before rendering. Joomla input returns `null` when an `id` query parameter is absent unless a default is supplied, which affected list, Dashboard and Information views. The controller supplies `0` explicitly and the helper also accepts/normalizes a nullable ID defensively.

The Information page follows the same product-style card system while retaining the package/component/plugin/module version-integrity diagnostics and native Joomla update controls introduced in 0.8.x.

## Zones

Version 0.8.0 added a dedicated **Zones** administrator area between Organizations and Countries.

A Zone can be global or assigned to a specific Organization. Countries are linked through a many-to-many relation, so a Country can belong to different sporting Zones when different Organizations use different classifications.

The project-provided initial sporting classification is installed as global Zones:

- African zone: Cameroon, Nigeria, South Africa
- Asian zone: Australia, China PR, Japan, Korea Republic, Thailand
- European zone: England, France, Germany, Italy, Netherlands, Norway, Scotland, Spain, Sweden
- North, Central American and Caribbean zone: Canada, Jamaica, USA
- Oceania zone: New Zealand
- South American zone: Argentina, Brazil, Chile

Existing Country records are preserved. Missing Countries from the supplied classification are inserted with stable sporting codes and then linked to the appropriate Zone.

## Administrator information and updates

Version 0.7.1 added an **Information** administrator view with the installed package version, Joomla/PHP/database information, native Joomla update status, configured update-server URL and shortcuts to Joomla Updates, Update Sites and GitHub Releases.

Version 0.8.1 fixed Joomla database parameter binding in the Information view by binding local variables instead of object properties/constants, as required by Joomla's by-reference query API.

Version 0.8.2 added an installation-integrity check comparing the package, component, system plugin, Match Timeline module and Countries/Federations module versions.

Version 0.8.3 fixed the package postflight update-site repair itself. Joomla's database `bind()` API requires variables passed by reference; the previous installer script passed constants/literals for update-site values, so the repair could fail silently and Competitions would not appear in Joomla's extension update list. The installer now binds local variables and recreates/enables the package update-site association on installation or update.

The package registers `https://raw.githubusercontent.com/xdecaro/dcl/main/updates/pkg_decarodcl.xml` as its Joomla update server. The historical URL is intentionally retained for installed-site compatibility and GitHub redirect support. The update feed identifies the distributable as the `pkg_decarodcl` site-client package and uses a Joomla 6 version regular expression compatible with Joomla's update finder. The package installer also repairs the update-site association on install/update if it is missing or disabled. Installing an available release remains managed through Joomla's native extension updater.

## 0.7.0

Organizations and native Match management:

- added administrator CRUD for Organizations using `#__dcl_organizations`;
- Organizations can optionally reference an existing Country and store short name, logo, website and email;
- added Tournament organization roles: organizer, governing body, co-organizer and partner;
- added Season organization roles, including local organizer;
- added native `#__dcl_matches` storage and administrator CRUD for Matches;
- Match records link Season, Home Team, Away Team and optional Venue;
- both Teams must have an approved Participation in the selected Season;
- Match status supports scheduled, live, finished, postponed and cancelled;
- scores, extra-time scores and penalties are validated in pairs and the winner is derived server-side for finished Matches;
- optional Joomla `article_id` remains available for editorial compatibility and cannot be reused by multiple Matches;
- added `match_id` to `#__dcl_match_events` while preserving legacy `article_id` data;
- the Match Timeline module now prefers `match_id` and falls back to the legacy article relation;
- added backward-compatible SQL update and fresh-install SQL without deleting existing sports data;
- enabled Organizations and Matches in the administrator menu and Dashboard.

## Requirements

- Joomla 6.0+
- PHP 8.3+
- MySQL / MariaDB with InnoDB and utf8mb4

## Data preservation

Updates and uninstall routines do not delete `#__dcl_*` data tables automatically. The 0.7.0 migration adds Organizations and native Matches while preserving the previous `article_id` event relation. Version 0.8.0 adds Zones and Country mappings using additive tables and `INSERT IGNORE`, so existing Country records and sports data are not overwritten or deleted. Version 0.10.0 adds Tournament scope fields, Team type, scope relation tables and synchronization support tables through additive migrations; existing Teams and Tournaments default to the backward-compatible `club` / `international` configuration. Versions 0.10.1 through 0.10.7 and 0.11.0 change administrator UI/assets/integration code and release metadata only; they introduce no database migration and delete no sports data. Destructive data removal must be an explicit administrator action.

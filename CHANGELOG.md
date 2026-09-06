# Changelog

## 0.6.0 - 2026-09-06

- Added complete administrator CRUD for Players using the existing `#__dcl_players` table.
- Added Player fields for first name, last name, optional external reference, birth date, nationality, photo, approval and publication state.
- Added server-side validation for required names, valid/non-future birth dates, nationality references and unique external references.
- Added Player approval workflow with `core.edit.state` protection against direct-form tampering.
- Added complete administrator CRUD for Rosters using the existing `#__dcl_rosters` table.
- Added Participation → Player roster assignment with shirt number, free-text role, review note, ordering and approval state.
- Roster `team_id` is derived server-side from the selected Participation and never trusted from posted input.
- Prevented approval of a Roster entry until its Team, Participation and Player are approved.
- Prevented duplicate Player + Participation roster entries.
- Added Player and Roster search, filters, sorting, pagination, publish/unpublish, trash and responsive mobile layouts.
- Added Players and Rosters to the Competitions submenu and Dashboard, including a Roster counter.
- Preserved the Joomla 6 administrator asset-loading workaround verified in 0.3.5.
- No destructive database migration; all existing `#__dcl_*` data is preserved.
- Bumped component, package, plugin, modules, Web Asset registry and update feed to 0.6.0.

## 0.5.0 - 2026-09-06

- Added complete administrator CRUD for Teams using the existing `#__dcl_teams` table.
- Added Federation and Country context to Team lists without duplicating country data.
- Added Joomla responsible-user assignment while keeping sports data separate from Joomla user profiles; changing that ownership link is protected by `core.edit.state`.
- Added server-side validation for Team name, Federation, manager account, alias, email, URL and approval state.
- Synchronizes the legacy three-character `country_code` field from Federation → Country when a compatible ISO/code value exists, avoiding truncation of longer neutral country codes.
- Added Team approval workflow: pending, approved and rejected.
- Approval changes require `core.edit.state` server-side; approval and publication state fields are also protected against direct-form tampering when that permission is missing.
- Added complete administrator CRUD for Participations using the existing `#__dcl_participations` table.
- Added Team → Season participation workflow: draft, submitted, approved and rejected.
- Added automatic submitted/reviewed timestamps and reviewer tracking.
- Prevented approval of a Participation until its Team is approved.
- Prevented duplicate Team + Season participations.
- Added search, filters, sorting, pagination, publish/unpublish, trash and responsive mobile layouts.
- Added Teams and Participations to the Competitions submenu and Dashboard.
- Preserved the Joomla 6 administrator asset-loading workaround verified in 0.3.5.
- No destructive database migration; all existing `#__dcl_*` data is preserved.
- Bumped component, package, plugin, modules, Web Asset registry and update feed to 0.5.0.

## 0.4.0 - 2026-09-06

- Added complete administrator CRUD for Tournaments using the existing `#__dcl_tournaments` table.
- Added complete administrator CRUD for Seasons using the existing `#__dcl_seasons` table.
- Added Tournament → Seasons relationship views and season counts.
- Added host-country selection from the existing Countries registry without introducing duplicate country records.
- Added search, filters, sorting, pagination, state actions and responsive list layouts.
- Added server-side validation for names, unique tournament codes, disciplines, categories, tournament references, season years, host countries and date ranges.
- Added Tournaments and Seasons to the Competitions submenu and Dashboard.
- Dashboard counters now exclude trashed records.
- Improved responsive administrator CSS while preserving light/dark compatibility and the proven Joomla 6 asset-loading workaround.
- No destructive database migration; all existing `#__dcl_*` data is preserved.
- Bumped component, package, plugin, modules, Web Asset registry and update feed to 0.4.0.

## 0.3.5 - 2026-09-06

- Confirmed with browser-console diagnostics that the stylesheet file existed and returned HTTP 200, while Joomla did not add any `com_decarodcl` stylesheet to the page.
- Added a canonical root media stylesheet at `media/com_decarodcl/admin.css`.
- Changed the Web Asset URI from `com_decarodcl/css/admin.css` to `com_decarodcl/admin.css`, matching the proven asset layout used by the other xdecaro components.
- Added `admin.css` explicitly to the component media manifest and retained the legacy `css/` directory for upgrade compatibility.
- Reworked `UiHelper` to register and use a dedicated runtime style asset instead of relying on automatic extension-registry discovery.
- Preserved all technical identifiers and existing `#__dcl_*` data.

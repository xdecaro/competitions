# Changelog

## 0.13.0 - 2026-09-08

- Rebased Competitions database storage from the legacy DCL table prefix to `#__decarocompetitions_*`.
- Fresh installations create only `#__decarocompetitions_*` tables.
- Existing installations are protected by a component preflight migration that renames legacy DCL-prefixed tables before normal Joomla schema updates.
- Updated runtime queries, Table classes, modules, Live Sync helpers, SQL and release validation to the new table prefix.
- Kept `com_decarodcl`, `pkg_decarodcl`, plugin/module identifiers and PHP namespace unchanged in this release to avoid coupling the database cleanup to a separate extension-identity migration.
- No sports-domain behavior is intentionally changed.

## 0.12.2 - 2026-09-08

    - Renamed the public Competitions distribution archive to `pkg_competitions_<version>.zip`.
    - Kept the historical Joomla package element `pkg_decarodcl` and internal manifest filename unchanged so existing installations continue to receive normal Joomla updates.
    - Updated build, release validation, checksum publication and update-feed download URL to the new public package filename.
    - Preserved `com_decarodcl`, `plg_system_decarodcl`, `mod_dcl_*`, namespaces, `#__dcl_*` tables, sports data and configuration.
    - No database migration is introduced.
    - Bumped component, package, plugin, modules, Web Asset registry and Joomla update feed to 0.12.2.

    ## 0.12.1 - 2026-09-08

    - Renamed the visible system plugin from **Competitions Core** to **Competitions - System Plugin / Competitions - Plugin di sistema** without changing `plg_system_decarodcl`.
    - Added **Core by xdecaro** to Information → Connected components with installed version and public-API status.
    - Added Core status to diagnostics and technical details while keeping Core optional.
    - Moved public repository/update URLs from the renamed `xdecaro/dcl` location to `xdecaro/competitions`; historical Joomla identifiers remain unchanged.
    - Added safe package-metadata repair: current Competitions children are reassociated with the canonical package record, and only orphan legacy `PKG_DCL` / duplicate package metadata is removed.
    - The current lightweight `plg_system_decarodcl` plugin is enabled during package postflight; obsolete `dclcore` remains disabled.
    - No `#__dcl_*` table, sports record, configuration or user data is deleted.
    - Bumped component, package, plugin, modules, Web Asset registry and Joomla update feed to 0.12.1.

    ## 0.12.0 - 2026-09-08

- Added optional Core by xdecaro 1.1+ Web Asset Manager integration to the administrator Information/Diagnostics view.
- Added an isolated `.xdecaro-scope` layout and a small token bridge from `--dcl-*` to the public `--xdecaro-*` design tokens.
- Preserved the complete local Competitions CSS as an automatic fallback when Core is missing, older than 1.1.0 or its asset registry cannot be loaded.
- Kept historical Joomla identifiers `com_decarodcl`, `pkg_decarodcl` and `#__dcl_*` unchanged for upgrade compatibility.
- Preserved tournaments, seasons, teams, participations, rosters, matches, standings, Live Sync, ACL, CSRF and all sports-domain behavior.
- No database migration is introduced.
- Bumped component, package, plugin, modules, Web Asset registry and Joomla update feed to 0.12.0.

## 0.11.0 - 2026-09-08

- Added an optional Xdecaro Core integration adapter registered through the Joomla dependency-injection container.
- Added support for the public Core `EntityReference` and `RelationReference` cross-product contracts when Core is installed.
- Preserved the historical Joomla component element `com_decarodcl` in all public cross-product references for upgrade compatibility.
- Core remains optional; Core-dependent calls fail with a controlled runtime exception instead of a class-not-found fatal error when Core is unavailable.
- Added integration documentation for Forms, Courses, Membership, Documents and Events without exposing private `#__dcl_*` tables as an API.
- Preserved all tournament, season, team, participant, roster, match, result, standing, Live Sync, ACL and CSRF behavior.
- No database migration is introduced.
- Bumped component, package, plugin, modules, Web Asset registry and Joomla update feed to 0.11.0.

## 0.10.7 - 2026-09-08

- Centered administrator filter select values and replaced native select arrows with consistent inset chevrons.
- Preserved dark-mode behavior and the existing search/filter workflow.
- No database migration or sports-data change was introduced.
- Bumped component, package, plugin, modules, Web Asset registry and Joomla update feed to 0.10.7.

## 0.10.6 - 2026-09-08

- Rebuilt the administrator **Informazioni** page to the approved xdecaro standard used by Courses and Forms.
- Changed the page eyebrow to **COMPETITIONS** while keeping **Informazioni** as the main title.
- Added the compact product status summary and standardized Product, Environment, Included extensions and Updates cards.
- Reclassified Timeline and Countries/Federations as bundled extensions instead of presenting them as external integrations.
- Added the full-width **Componenti collegati** section with safe detection of Forms and Courses, installed versions and available item counts.
- Added six diagnostic checks, balanced Technical details, Copy diagnostics, Download .txt and GitHub release actions. Diagnostic export excludes passwords, tokens, cookies and credentials.
- Added dedicated Information CSS/JS loaded only by the Information view, including responsive, keyboard-focus and dark-mode states.
- Preserved historical `com_decarodcl`, `pkg_decarodcl`, `#__dcl_*` and `xdecaro/dcl` technical identifiers for upgrade compatibility.
- Preserved competition data, Tournament scope, Live Sync, ACL/CSRF protections and all existing sports functionality. No database migration is introduced.
- Bumped component, package, plugin, modules, Web Asset registry and Joomla update feed to 0.10.6.

## 0.10.5 - 2026-09-08

- Kept the primary search row permanently visible instead of replacing it when Filters opens.
- Changed Filters into an animated drawer that expands underneath the search row within the same toolbar card.
- Removed the nested-card appearance: the drawer uses a subtle divider and shared surface instead of a second bordered panel.
- Added extra horizontal inset to the filter row so the first select is not flush against the left edge.
- Kept all filters on one row on desktop with **Close filters** at the end; tablet falls back to two columns and smartphone to one column.
- Preserved the active-filter count, immediate select submission, current search value and open drawer state across list reloads.
- Added a short vertical/opacity transition and respected `prefers-reduced-motion` for accessibility.
- Preserved list models, sorting, pagination, Live Sync, Tournament scope logic, ACL/CSRF protections and all existing sports data.
- No database migration is introduced.
- Bumped component, package, plugin, modules, Web Asset registry and Joomla update feed to 0.10.5.

## 0.10.4 - 2026-09-07

- Reworked the shared administrator filterbar into two mutually exclusive modes instead of showing search and a second filter panel at the same time.
- Default mode is one row with the wide search field, **Search**, **Clear** and **Filters**.
- Opening **Filters** replaces that row with all available select filters plus **Close filters**; closing returns to search without losing the current search or filter values.
- Kept immediate server-side submission when a select filter changes and preserved the active-filter count on the Filters button.
- Removed the extra stacked filter panel and removable-chip row to reduce duplicated visual layers and keep list pages compact.
- Added responsive behaviour: one-row filters on desktop, two-column adaptation on tablet and one-column controls on smartphone.
- Updated Italian/English wording to **Chiudi filtri / Close filters** and retained keyboard/focus accessibility plus light/dark mode states.
- Preserved list models, sorting, pagination, Live Sync, Tournament scope logic, ACL/CSRF protections and all existing sports data.
- No database migration is introduced.
- Bumped component, package, plugin, modules, Web Asset registry and Joomla update feed to 0.10.4.

## 0.10.3 - 2026-09-07

- Fixed the Joomla Web Asset path mismatch that kept the 0.10.1/0.10.2 enhanced filterbar from loading even though the CSS/JS files were present in the package.
- Corrected filterbar runtime/Web Asset URIs from `com_decarodcl/css/filterbar.css` and `com_decarodcl/js/filterbar.js` to Joomla's component-media convention: `com_decarodcl/filterbar.css` and `com_decarodcl/filterbar.js`.
- Added authoritative `media/css/live-sync.css`, `media/js/live-sync.js` and `media/js/scope.js` copies so Live Sync and Tournament-scope JavaScript resolve through the same Joomla convention.
- Kept the earlier root Live Sync/scope files declared only as upgrade-compatibility fallbacks; runtime loading now uses the standard CSS/JS folders.
- Preserved the requested 0.10.2 layout: wide search field with explicit **Search** and **Clear**, plus **Filters** on a separate row below.
- No database migration or sports-data change is introduced.
- Bumped component, package, plugin, modules, Web Asset registry and Joomla update feed to 0.10.3.

## 0.10.2 - 2026-09-07

- Refined the shared administrator search/filter toolbar from the 0.10.1 feedback cycle.
- Replaced automatic debounced search with an explicit **Search** action; pressing Enter remains supported, while typing alone no longer reloads the list.
- Kept **Clear** directly beside Search so the primary row is always `Search field → Search → Clear`.
- Moved the **Filters** control to its own row below the primary search row, keeping secondary filters visually separate from search.
- Preserved immediate application of secondary select filters, active-filter count and removable filter chips.
- Improved desktop/tablet/smartphone spacing and kept light/dark/focus states centralized in the shared filterbar assets.
- Preserved all server-side list models, sorting, pagination, Live Sync, Tournament scope logic, ACL/CSRF protections and existing sports data.
- No database migration is introduced.
- Bumped component, package, plugin, modules, Web Asset registry and Joomla update feed to 0.10.2.

## 0.10.1 - 2026-09-07

- Redesigned administrator list search as one compact search-first toolbar across Competitions instead of a row of large native fields and buttons.
- Search now submits automatically after a short debounce; pressing Enter submits immediately and Escape clears the current search.
- Moved secondary filters behind one accessible **Filters** button while preserving every existing server-side filter and the no-JavaScript form fallback.
- Added active-filter count and removable filter chips so administrators can see and remove individual constraints without opening the filter panel.
- **Clear** is shown only when a search or filter is active.
- Added shared responsive/light-dark styling for search, filter panel, chips, focus states and smartphone layout.
- Added one centralized `filterbar.js`/`filterbar.css` enhancement instead of duplicating list-specific JavaScript or CSS.
- Preserved toolbar actions, sorting, pagination, Live Sync, Tournament scope logic, ACL, CSRF protections and all existing sports data.
- No database migration is introduced.
- Bumped component, package, plugin, modules, Web Asset registry and update feed to 0.10.1.

## 0.10.0 - 2026-09-07

- Added Tournament competition scope: International, Continental / Zone, National and Regional / Local.
- Added Tournament participant type: Clubs / Teams or National teams.
- Added explicit Team type (`club` or `national`) while preserving the existing Team, Participation, Roster and Match architecture.
- Added `#__dcl_tournament_countries` and `#__dcl_tournament_zones` relation tables instead of duplicating geographic data in Tournament rows.
- Added server-side Tournament scope validation: National/Local require a Country, Zone requires one or more valid Zones, International remains unrestricted.
- Added server-side Participation eligibility validation through Season → Tournament, Team type and Federation → Country.
- Added AJAX filtering of eligible Teams in the Participation form with CSRF and ACL checks; server validation remains authoritative if JavaScript is unavailable or tampered with.
- Added Season host-country validation against Tournament geography.
- Protected existing data when changing Tournament scope, Team type/Federation, Federation Country or Zone membership: incompatible existing Participations are rejected rather than silently invalidated.
- Protected existing Season host Countries when Tournament scope or Zone membership changes.
- Added global administrator Live Sync for Organizations, Zones, Countries, Federations, Tournaments, Seasons, Teams, Participations, Players, Rosters and Matches.
- Added `#__dcl_changes` for incremental change cursors and `#__dcl_edit_sessions` for advisory edit presence.
- Added BroadcastChannel synchronization for browser tabs plus lightweight polling for other browsers/computers; no WebSocket service is required.
- Added race-safe optimistic locking based on the `modified` timestamp rendered with every edit form, including a sentinel for legacy rows with a NULL timestamp.
- Added server-side conflict rejection so a stale edit cannot overwrite newer data even before the next poll.
- State/publish actions now advance the record version timestamp and emit Live Sync change events.
- Added responsive/light-dark Live Sync notices and a reload action for edit conflicts.
- Added additive 0.10.0 fresh-install/update SQL. Existing Tournaments default to `international`, existing Teams default to `club`, and no existing sports records are deleted.
- Bumped component, package, plugin, modules, Web Asset registry and Joomla update feed to 0.10.0.

## 0.9.1 - 2026-09-07

- Fixed the administrator fatal error `PageHeaderHelper::render(): Argument #3 ($id) must be of type int, null given` introduced by the shared 0.9.0 page header.
- Root cause: Joomla `Input::getInt('id')` returns `null` when the query parameter is absent unless an explicit default is supplied; Dashboard, list and Information views normally have no record ID.
- `DisplayController` now normalizes the optional ID to integer `0` before rendering the shared header.
- `PageHeaderHelper` now defensively accepts a nullable ID and normalizes it, preventing the same regression from future callers.
- Added release validation for the page-header ID default and Joomla update-feed client/target-platform metadata.
- Preserved the 0.9.0 design system, all CRUD behaviour and all existing `#__dcl_*` sports data; no database migration or destructive change is introduced.
- Bumped component, package, plugin, modules, Web Asset registry and update feed to 0.9.1.

## 0.9.0 - 2026-09-07

- Added a shared administrator design system for all Competitions views, aligned with the established xdecaro Courses visual language.
- Added one reusable Joomla page-header layout with blue uppercase eyebrow, large title and concise muted description.
- Added `PageHeaderHelper` and centralized header rendering in `DisplayController`, so Dashboard, all list views and all create/edit forms stay visually synchronized without duplicating header markup.
- Redesigned **Information** into product-style cards for Versions, System, Frontend modules, Update channel and Diagnostics while preserving all existing version-integrity and Joomla update checks.
- Added scoped light/dark design tokens, responsive header/card behaviour and consistent spacing without changing existing CRUD, toolbar, filters, tables or server-side validation.
- Added Italian and English 0.9.0 administrator language strings for every page description.
- Preserved the 0.8.3 update-site repair and all existing `#__dcl_*` sports data; no database migration or destructive change is introduced.
- Bumped component, package, plugin, modules, Web Asset registry and update feed to 0.9.0.

## 0.8.3 - 2026-09-07

- Fixed the Competitions package postflight update-site repair so Joomla can reliably discover future releases.
- Replaced constants and string literals passed directly to `DatabaseQuery::bind()` with local variables because Joomla binds values by reference.
- Fixed the silent failure path that could leave `pkg_decarodcl` without a valid `#__update_sites` / `#__update_sites_extensions` association.
- The installer now recreates or re-enables the Competitions update site and re-associates it with the package on install/update.
- Preserved the compact Information page and installation-integrity diagnostics from 0.8.2.
- Preserved all existing `#__dcl_*` sports data; no database migration or destructive change is introduced.
- Bumped component, package, plugin, modules, Web Asset registry and update feed to 0.8.3.

## 0.8.2 - 2026-09-06

- Redesigned **Information** as a compact responsive administrator overview with smaller summary cards and two balanced detail panels.
- Added installation-integrity diagnostics comparing package, component, system plugin, Match Timeline module and Countries/Federations module versions.
- Added clear consistent/inconsistent status badges so partial Joomla updates are immediately visible.
- Kept Joomla native update discovery and update-site shortcuts while reducing visual clutter and preserving dark/light compatibility.
- Kept the 0.8.1 Joomla `DatabaseQuery::bind()` by-reference fix.
- Preserved Zones, Countries, Organizations, Matches and all existing `#__dcl_*` sports data; no database migration is introduced.
- Bumped component, package, plugin, modules, Web Asset registry and update feed to 0.8.2.

## 0.8.1 - 2026-09-06

- Fixed the administrator **Information** view fatal error `Joomla\Database\DatabaseQuery::bind(): Argument #2 ($value) could not be passed by reference`.
- Joomla query parameters in `InformationModel` now bind local variables for package extension ID and update-server URL, matching Joomla's by-reference database API.
- Preserved Zones, Countries, Organizations, Matches and all existing `#__dcl_*` sports data; no database migration is introduced.
- Bumped component, package, plugin, modules, Web Asset registry and update feed to 0.8.1.

## 0.8.0 - 2026-09-06

- Added native `#__dcl_zones` and `#__dcl_zone_countries` tables.
- Added complete administrator CRUD for Zones with optional Organization scope.
- Added many-to-many Zone → Country assignments so a Country can belong to different sporting Zones without changing the Country record.
- Added Zone search, Organization filter, publication state, ordering, Country counts and responsive Country summaries.
- Added a Zone filter and Zone column to the Countries administrator list.
- Added Zones to the Competitions submenu and Dashboard.
- Added the project-provided initial sporting Zones: African, Asian, European, North/Central American and Caribbean, Oceania and South American.
- Added missing Countries from the supplied Zone classification without replacing existing Country records.
- Seeded the supplied Country → Zone mappings using additive `INSERT IGNORE` migrations.
- Preserved Organization, Country, Federation, Tournament, Season, Team, Player, Match and other existing sports data.
- Bumped component, package, plugin, modules, Web Asset registry and update feed to 0.8.0.

## 0.7.1 - 2026-09-06

- Added a dedicated **Information** administrator menu/view for fast access to the installed Competitions version and system details.
- Added Joomla, PHP and database information plus package/component/repository identifiers.
- Added native Joomla update status, latest cached update version, update-server URL and last-check information.
- Added shortcuts to Joomla Extension Updates, Update Sites and GitHub Releases while respecting `com_installer` ACL.
- Added a defensive package postflight repair for the Competitions update-site record and `#__update_sites_extensions` association.
- Update discovery remains handled by Joomla's native update system; release installation stays controlled by Joomla rather than using an unsafe custom self-updater.
- Added the Information submenu entry and kept Organizations and Matches in the component manifest so a normal component upgrade rebuilds the current administrator submenu.
- Preserved all existing `#__dcl_*` sports data; no destructive database migration is introduced.
- Bumped component, package, plugin, modules, Web Asset registry and update feed to 0.7.1.

## 0.7.0 - 2026-09-06

- Added `#__dcl_organizations` and complete administrator CRUD for reusable competition organizations.
- Added Tournament → Organization role relations for organizer, governing body, co-organizer and partner.
- Added Season organization roles, including local organizer.
- Added native `#__dcl_matches` as the authoritative sports-data source for Matches.
- Added complete Match administrator CRUD with Season, Home/Away Team, optional Venue, date/time, stage, group, round, matchday, status, scores, attendance and notes.
- Match creation validates that both Teams have an approved Participation in the selected Season.
- Added server-side score-pair validation, numeric bounds and winner derivation for finished Matches.
- Kept Joomla `article_id` as an optional editorial compatibility link and reject reuse of the same article by multiple Matches.
- Added `match_id` to `#__dcl_match_events` while preserving legacy `article_id` values and migrating events where an article-linked Match exists.
- Updated Match Timeline to prefer `match_id` while retaining article fallback and accessibility labels.
- Added fresh-install and update SQL for Organizations, organization relations, Matches and event migration; no existing sports data is deleted.
- Added Organizations and Matches to the administrator submenu and Dashboard.
- Preserved the Joomla 6 administrator asset-loading workaround verified in 0.3.5.
- Bumped component, package, plugin, modules, Web Asset registry and update feed to 0.7.0.

## 0.6.0 - 2026-09-06

- Added complete administrator CRUD for Players using the existing `#__dcl_players` table.
- Added Player fields for first name, last name, optional external reference, birth date, nationality, photo, approval and state.
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

- Added complete administrator CRUD for Teams and Participations.
- Teams are linked to Federations and derive Country context from Federation → Country.
- Added responsible Joomla user assignment while keeping authentication separate from sports data.
- Added Team approval and Participation review workflows with server-side ACL protection.
- Prevented approval of a Participation until its Team is approved and prevented duplicate Team + Season participation records.
- Added search, filters, sorting, pagination, state actions and responsive layouts.

## 0.4.0 - 2026-09-06

- Added complete administrator CRUD for Tournaments and Seasons.
- Added Tournament → Seasons relations, host-country selection, search, filters, sorting and responsive layouts.
- Added server-side validation for tournament codes, disciplines, categories, season years, host countries and date ranges.

## 0.3.5 - 2026-09-06

- Confirmed through browser-console diagnostics that the administrator stylesheet existed but Joomla did not inject it.
- Added canonical `media/com_decarodcl/admin.css` and a runtime Web Asset registration fallback.
- Preserved all technical identifiers and existing `#__dcl_*` data.

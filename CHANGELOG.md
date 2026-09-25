# Changelog

## 1.5.18 - 2026-09-25

- Changed the Teams list **Club** badge from muted grey to the distinct info style for clearer visual separation.
- Replaced the Bootstrap Organizations source badge with the shared `competitions-badge is-success` style.
- Club and Organizations badges now use matching height, padding and vertical alignment.
- No database structure or business-logic changes are required.

## 1.5.17 - 2026-09-25

- Team Import live search now matches only the team/club name.
- Country, federation, affiliation code and row status no longer affect search results.
- Removed the legacy duplicate `component/media/teamimport.js`; the canonical script remains only under `component/media/js/teamimport.js`.
- Removed the root-level Team Import JavaScript entry from the component media manifest.
- Strengthened regression and distribution validation so the legacy root copy cannot be shipped again.
- Preserved live filtering, persistent selections, selected-only review, counters and visible-row select-all.
- No database structure changes are required.

## 1.5.16 - 2026-09-25

- Fixed the root cause of Team Import live-search JavaScript not loading: the file was packaged at the media root, while Joomla Web Asset Manager resolves script URIs from the component `media/js` directory.
- Added `component/media/js/teamimport.js`, matching the layout already used by the working `filterbar.js`, `live-sync.js`, `scope.js` and `people-picker.js` assets.
- Kept the existing guarded runtime registration and live-search logic unchanged.
- Extended release validation and regression tests to require the Team Import script in the Joomla `media/js` path.
- No database structure changes are required.

## 1.5.15 - 2026-09-25

- Fixed the final Team Import asset activation issue confirmed in the live site: all shared Competitions administrator scripts were loading, while only `teamimport.js` was absent.
- Removed the fragile conditional `view=teamimport` gate from `UiHelper::loadAssets()`.
- The Team Import script is now always attached with the other administrator assets; its own DOM guard exits immediately on pages that do not contain `[data-teamimport-live]`, so there is no functional side effect elsewhere.
- This keeps a single Joomla Web Asset Manager path and removes dependence on mutable request/view state during MVC dispatch.
- Preserved live filtering, persistent multi-selection, selected/visible counters, selected-only review, visible-row select-all and the corrected Cerca/Pulisci layout.
- Updated regression coverage to prevent reintroducing conditional Team Import asset activation.
- No database structure changes are required.

## 1.5.14 - 2026-09-24

- Fixed the confirmed Team Import asset lifecycle issue after browser diagnostics proved the JavaScript file exists and works when loaded directly, but Joomla never inserted it into the page.
- Registers the Team Import runtime asset inside the shared administrator `UiHelper::loadAssets()` **before any Web Asset is enabled**, avoiding late registration after Web Asset use has already begun.
- Enables the Team Import script only when `view=teamimport`.
- Removed the too-late template-level `registerAndUseScript()` workaround.
- Preserved the working live-search logic, persistent selections, visible/selected counters, selected-only review and visible-row select-all.
- Extended the regression contract to enforce pre-use asset registration order.
- No database structure changes are required.

## 1.5.13 - 2026-09-24

- Fixed the confirmed Organizations team-import failure where the page HTML rendered correctly but `teamimport.js` was not inserted at all: browser diagnostics showed no script tag, no resource request and zero input/search listeners.
- Moved the page-specific script activation into the rendered Team Import template using Joomla Web Asset Manager `registerAndUseScript()`, matching Joomla's recommended placement for template-specific JavaScript.
- Added a `core` dependency and a diagnostic `data-xdecaro-teamimport` script attribute.
- Removed the earlier helper-level loading path that could leave the page asset registered but not attached.
- Kept the corrected search/Pulisci input-group layout and the existing persistent selection, selected-only review and visible-row select-all behavior.
- Extended the import regression contract to require template-time Web Asset activation.
- No database structure changes are required.

## 1.5.12 - 2026-09-24

- Fixed the Organizations team-import live search not executing after installation because the new script depended only on Web Asset registry discovery.
- The import script is now registered explicitly at runtime, matching the existing Competitions administrator asset strategy and avoiding stale/undiscovered registry entries after upgrades.
- Fixed the search toolbar layout: **Pulisci** now sits directly beside the search input instead of dropping lower beside the helper text.
- Kept persistent multi-selection, selected/visible counters, selected-only review and visible-row select-all.
- Added an accessible live status for filtered row counts and handles both input and native search-clear events.
- Extended regression coverage to require runtime script registration and the corrected input-group layout.
- No database structure changes are required.

## 1.5.11 - 2026-09-24

- Reworked the Organizations team-import search into a true live client-side filter.
- Searching no longer reloads the page, so teams already selected remain selected while searching for additional clubs.
- Added a persistent selected counter plus a visible-row counter.
- Added **Show selected only** to review the complete multi-selection before importing.
- Replaced the generic Joomla select-all behavior with **Select all visible rows**, so filtering and bulk selection work predictably together.
- The import preview now loads one stable Organizations provider window (up to 200 Clubs) and filters that in place; no row is recreated while typing.
- Added accessible labels and explicit no-live-results messaging.
- Added regression coverage for persistent selection, live filtering, visible-row select-all and asset packaging.
- No database structure changes are required.

## 1.5.10 - 2026-09-24

- Added **Importa da Organizations** to the Teams Joomla toolbar.
- Added a dedicated preview page listing up to 200 Organizations records of type `club`, with search, select-all and per-row import status.
- The preview detects already imported teams by stable Organizations UUID and never creates a second Competition team for the same club.
- Federation readiness is shown before import: ready, no affiliation, federation not mapped, ambiguous affiliation or Organizations affiliation service unavailable.
- Clubs without a determined federation may still be imported as **Pending**, preserving the workflow for large event/team batches; ambiguous or unverifiable records are disabled until corrected.
- Bulk import reuses `TeamModel::save()` for every selected club, so canonical identity sync, UUID persistence, affiliation-derived federation rules, alias generation, ACL-related table validation and live-sync creation events remain centralized.
- New imported teams default to `Club`, `Approval = Pending`, published state, no manager and automatic alias.
- Added duplicate-safe handling for linked teams already in the trash, plus row-level error reporting without aborting the entire batch.
- Added Italian/English UI strings, distribution validation and a dedicated Organizations team-import contract.
- No database structure changes are required.

## 1.5.9 - 2026-09-24

- Added bulk team approval actions to the main Joomla toolbar: Approve, Pending and Reject.
- Approval actions require `core.edit.state`, validate the Joomla CSRF token and operate only on explicitly selected team rows.
- Approving a team is blocked when its federation is not determined, so a Club with a missing/invalid Organizations sports affiliation cannot be approved accidentally.
- Bulk approval changes update live-sync timestamps/events so concurrently opened team records stay consistent.
- Added Italian/English validation messages and a dedicated regression contract.
- No database structure changes are required.

## 1.5.8 - 2026-09-24

- Club federation assignment is now derived automatically from the club's active `sports_affiliation` in Organizations instead of being selected manually in Competitions.
- Competitions consumes only the public Organizations `getAffiliations()` provider and still does not access Organizations private tables.
- A linked Club with no active sports affiliation is saved as `Federazione non determinata`; Competitions does not guess a federation.
- If more than one active sports federation affiliation exists, saving is blocked until the ambiguity is corrected in Organizations.
- If the affiliated federation exists in Organizations but is not linked in Competitions → Federations, the Club remains without a Competition federation mapping and the editor explains what must be fixed.
- Linked Club editors no longer show the manual Federation selector; they show the derived federation and its source.
- Manual Federation selection remains available for National teams and legacy teams not yet linked to Organizations.
- Teams with an undetermined federation cannot be used for new/approved participations; existing participations prevent silently clearing a previously determined federation.
- The team list now shows an explicit `Federazione non determinata` state instead of an ambiguous dash.
- Added regression coverage for the Organizations affiliation provider, derived federation mapping, ambiguity handling and Club/National UI separation.
- No database structure changes are required.

## 1.5.7 - 2026-09-23

- Linked Competition club teams to canonical Organizations records of type `club` through a nullable unique `organization_uuid`.
- New Club teams use the Organizations picker instead of duplicating club identity data; a shortcut opens Organizations to create a missing club first.
- Existing legacy Club teams remain editable and can be linked once without changing their Competition team ID or historical participations, rosters, matches or coefficients.
- Once linked, a Club team keeps its canonical Organizations UUID and cannot be swapped or converted into a National team from the normal editor.
- Linked club name, short name, logo, email, phone and website are refreshed from Organizations while Competition keeps federation assignment, manager, approval state, alias, ordering and participation lifecycle.
- National representative teams remain Competition-native records and do not require an Organizations club.
- Added the Organizations badge to linked teams in the list and a direct "Open in Organizations" action in the team editor.
- Added a non-destructive 1.5.7 schema migration plus installer repair for historical installations.
- Added JavaScript/UI handling so switching a new team between Club and National shows only the appropriate identity workflow.
- Added source, distribution and Joomla 6.1.3 runtime regression coverage for the team Organizations schema and integration boundary.

## 1.5.6 - 2026-09-23

- Fixed federation-to-Organizations links that appeared selected during editing but reopened empty because historical installations could be missing the `organization_uuid` schema column/index.
- Added a component installer repair that runs on install/update and restores the nullable `organization_uuid` column plus its unique index when missing.
- Federation saves now verify that the schema exists and confirm that the selected Organizations UUID was really persisted before reporting success.
- Added explicit administrator errors instead of silently accepting an unpersisted federation link.
- Live edit presence no longer reports another session belonging to the same Joomla user, removing misleading "In modifica anche da ..." notices for yourself.
- Added CI coverage for schema repair, federation-link persistence contracts and same-user presence filtering.
- Added a non-destructive 1.5.6 schema marker.

## 1.5.5 - 2026-09-23

- Simplified federation linking UX: the Organizations selector is shown only while creating a new federation or while linking an existing legacy federation for the first time.
- After a federation is linked, the selector disappears and the editor shows only the active Organizations link with an "Open in Organizations" action.
- Existing canonical federation links are immutable from the normal Competitions edit form, preventing accidental swaps that could corrupt team/history semantics.
- Added a "Create in Organizations" action for new Competition federations instead of encouraging duplicate local federation identity data.
- Renamed the confusing "Organizzazione federazione" label to the clearer "Collega federazione".
- Linked federation identity fields remain owned by Organizations; Competitions keeps only compatibility snapshots plus its own sport-country/state/ordering data.
- Added regression coverage for one-time linking and link immutability.
- Added a non-destructive 1.5.5 schema marker; no database changes are required.

## 1.5.4 - 2026-09-23

- Fixed the administrator edit-page reload loop caused by live-sync comparing the database `modified` timestamp with a lock field that edit templates were not rendering.
- All Competitions edit templates now submit the hidden `jform[id]` and `jform[modified]` fields required by optimistic locking.
- Added a defensive live-sync fallback so a missing lock field cannot trigger an automatic infinite reload loop.
- Bumped the runtime administrator asset version so browsers receive the corrected live-sync JavaScript immediately.
- Added regression coverage for every Competitions edit template and the live-sync fallback.
- Added a non-destructive 1.5.4 schema marker; no database changes are required.

## 1.5.3 - 2026-09-23

- Fixed the PHP fatal compile error in the federation editor introduced in 1.5.2.
- `FederationModel::save($data)` now declares the required `: bool` return type and is compatible with `BaseAdminModel::save($data): bool`.
- Added a contract check so future federation model changes cannot silently reintroduce the incompatible method signature.
- Added a non-destructive 1.5.3 schema marker; no database changes are required.

## 1.5.2 - 2026-09-23

- Linked Competition federations to canonical Organizations records through nullable unique `organization_uuid` references.
- Added an Organizations-only federation picker using `bootComponent('com_xdecaroorganizations')->getOrganizationProviderService()`; no Organizations private tables are read.
- New linked federations synchronize name, code, logo, website and email from Organizations while preserving the local Competition federation ID used by teams, coefficients and history.
- Existing unlinked federations remain supported for backward compatibility; an existing link is preserved when Organizations is temporarily unavailable.
- Kept the Competition country explicit because tournament scope and team eligibility depend on the local country taxonomy; the editor now requires an explicit country choice instead of silently selecting the first country.
- Added a non-destructive 1.5.2 schema migration adding only the nullable UUID column and unique index.

## 1.5.1 - 2026-09-17

- Fixed player approval confirmation messages so Approve, Pending and Reject show translated text instead of raw Joomla language keys.
- The approval controller now loads the existing Competitions language fragments before translating redirect messages.
- Standardized public branding from `Competitions by xdecaro` to `Competitions` in Joomla update metadata, README and future GitHub release titles.
- Technical xdecaro component/package identifiers, namespaces, artifact names and repository URLs remain unchanged.
- Added a non-destructive 1.5.1 schema marker; no database structure changes are required.

## 1.5.0 - 2026-09-17

- Added the public read-only `PersonHistoryService` keyed by People `person_uuid`.
- Added Core capability `competitions.people_history` v1 so People can discover the history provider without private-table coupling.
- Returns normalized competition, season, team, role, shirt number and roster status history ordered newest first.
- Added contract and Joomla 6.1.3 runtime coverage for multi-season history and public service registration.
- Added a non-destructive 1.5.0 schema marker; no database structure changes are required.

## 1.4.4 - 2026-09-16

- Added bulk player approval actions to the administrator Players toolbar: Approve, Pending and Reject.
- Approval actions require `core.edit.state` and work with multiple selected players.
- Approval changes update `modified` / `modified_by` and participate in the existing live-sync change stream.
- Added dedicated RED/GREEN contract and Joomla 6.1.3 runtime coverage for all three approval states.
- Added a non-destructive 1.4.4 schema marker; no database structure changes are required.

## 1.4.3 - 2026-09-16

- Fixed People player autofill when the selected sensitive profile could not be returned completely.
- Removed the silent fallback to a non-sensitive People profile, so missing birth date/nationality can no longer be hidden behind a successful response.
- Normalizes People birth dates to `Y-m-d` for the Joomla calendar field.
- Supports both `nationality_code` and People `nationality_codes`, using the first nationality when needed.
- Added a non-destructive 1.4.3 schema marker; no database structure changes are required.

## 1.4.2 - 2026-09-16

- Added a targeted People profile lookup after selecting a person in the player editor.
- Automatically prefills player birth date and nationality from People when the current Joomla user is authorised to view sensitive People data.
- Keeps the autocomplete search itself non-sensitive; sensitive fields are requested only for the selected person.
- Added a RED/GREEN contract covering the profile endpoint and the `birth_date` / `nationality_code` field mapping.
- Added a non-destructive 1.4.2 schema marker; no database structure changes are required.

## 1.4.1 - 2026-09-16

- Fixed the Joomla 6 WebAsset URI for `people-picker.js`; the player People autocomplete is loaded from the standard component `js` media folder again.
- Added a focused RED/GREEN asset-path contract so a duplicated `/js/` segment cannot silently disable the picker again.
- Made package/CI version checks derive from `VERSION` instead of hard-coding the 1.4.0 artifact name.
- Added a non-destructive 1.4.1 schema marker so Joomla records the patch version correctly.
- Corrected the release workflow to publish the clean `com_competitions_<version>.zip` component artifact.

## 1.4.0 - 2026-09-16

- Added a stable nullable unique `person_uuid` link from Competitions players to People identities.
- Added an administrator People picker for new player records using `bootComponent('com_xdecaropeople')->getPersonProviderService()` only.
- Added roster-owned edition/team photos and People profile-document fallback resolution without copying People media.
- Added Joomla 6.1.3 runtime coverage for the People provider boundary, player UUID schema and roster photo schema.
- Added a non-destructive 1.4.0 schema migration preserving existing Competition player and roster history.

## 1.3.0 - 2026-09-09

- Added an optional Finance 1.2 public-service bridge through `bootComponent('com_decarofinance')` and consumes only `getFinanceService()`.
- Added idempotent participation-fee obligations using stable Finance `external_key` values and typed `component + entity + id` source/debtor references.
- Added team deposit/caution account creation, idempotent credits, disciplinary debits and balance queries through Finance's public deposit API.
- Kept competition pricing and disciplinary rules inside Competitions: the bridge receives the rule-derived amount and cause and contains no hard-coded card/suspension/fight tariffs.

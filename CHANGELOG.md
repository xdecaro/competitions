# Changelog

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

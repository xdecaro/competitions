# Competitions by xdecaro

Competitions by xdecaro is the competition-management component in the xdecaro Joomla ecosystem.

## Stable technical identity

- Component: `com_xdecarocompetitions`
- Package: `pkg_xdecarocompetitions`
- System plugin: `plg_system_xdecarocompetitions`
- Analytics plugin: `plg_xdecaroanalytics_competitions`
- Scheduled Tasks plugin: `plg_task_xdecarocompetitions`
- Match Timeline module: `mod_xdecarocompetitions_matchtimeline`
- Countries/Federations module: `mod_xdecarocompetitions_countriesfederations`
- PHP component namespace: `xdecaro\Component\Competitions`
- Database tables: `#__xdecarocompetitions_*`
- Repository: `xdecaro/competitions`

## Current version

**1.4.0**

Version 1.4.0 links Competitions players to the canonical People identity registry through People’s public provider and introduces a roster-owned competition photo for a specific team/edition. Existing player photos remain readable only as a legacy fallback.

Photo ownership is explicit:

1. a roster photo belongs to the player’s participation in that team/edition;
2. otherwise Competitions resolves the person’s People profile-document reference when available;
3. otherwise an existing legacy player photo can still be used as a compatibility fallback.

Competitions does not copy People profile images or access People private tables. The People fallback is deliberately represented as a Documents entity reference; a consumer that needs image bytes or a public URL must resolve that reference through a public Documents API rather than reading Documents storage directly.

## Architecture

Competitions owns competition-domain data and rules: countries and sporting territories, federations, organizations used in competition roles, zones, tournaments, seasons, teams, participations, players, rosters, matches, match events, rankings/coefficient data, synchronization state and the business reason/amount for competition charges or disciplinary deductions.

People owns canonical person identity. New Competition player records are linked by stable `person_uuid`; legacy unlinked player records remain supported so upgrades do not destroy historical data. The Competitions player record retains competition-specific lifecycle/approval data and historical compatibility fields while the roster owns edition/team-specific presentation such as its photo.

Other products own their own domains. Competitions must not read or write another xdecaro product's private tables, and other products must not read or write `#__xdecarocompetitions_*` directly.

Core integration remains infrastructure-only: public references, shared administrator design assets, diagnostics, capabilities and reusable technical services. Competition rules and sports-domain behavior remain in this repository.

## Optional integrations

### People

People integration is discovered at runtime with `bootComponent('com_xdecaropeople')` and consumed only through `getPersonProviderService()`.

The administrator player editor provides a People search picker. New players require a People person UUID; existing legacy players without a UUID remain editable for compatibility. Competitions never queries `#__xdecaropeople_*` directly.

### Finance

Finance is optional. Competitions discovers it at runtime with `bootComponent('com_decarofinance')` and consumes only `getFinanceService()`.

The bridge supports:

- participation-fee obligations;
- team deposit/caution accounts;
- idempotent deposit credits;
- idempotent disciplinary deposit debits;
- deposit balance queries.

Competitions owns *why* and *how much* must be charged. Finance owns financial persistence, obligation/payment state, allocations, deposit accounts and append-only movements. No yellow-card, red-card, suspension, fight or other tariff is hard-coded into Core or Finance.

Finance absence is a supported state and returns no Finance result. Once Finance is installed/enabled, provider incompatibility or a financial operation failure is logged and propagated instead of being silently ignored. Idempotency is provided through stable Finance `external_key` values.

Competitions runtime code never accesses `#__decarofinance_*`.

### Notifications, Tasks and Analytics

Notifications and Tasks are optional best-effort integrations through their public Joomla component services. Competitions also exposes an ACL-protected Analytics source through `plg_xdecaroanalytics_competitions` and can schedule upcoming-match reminders through the Joomla Scheduled Tasks plugin.

When Core 1.4+ `CapabilityRegistry` is available, Competitions declares analytics, Notifications, Tasks, Finance and match-reminder capabilities. Core itself contains no competition or finance business logic.

## Joomla baseline

The current Competitions 1.x line targets Joomla 6 and PHP 8.3+. Compatibility with earlier Joomla versions is not claimed until runtime-tested.

CI performs a real Joomla 6.1.3 installation of the built package. The 1.4.0 gate validates the People provider boundary, People UUID schema, roster photo schema and the existing Finance bridge regression coverage.

## Data and update policy

Fresh installations create only `#__xdecarocompetitions_*` tables. Version 1.4.0 adds nullable unique `person_uuid` to players and a nullable edition/team `photo` to rosters. The update migration is additive and preserves existing player identity fields, legacy photos, rosters and historical competition data.

Existing player records are not auto-linked or auto-merged. Linking to People must be explicit or performed by a separately verified migration workflow.

## Build

`python3 tools/build.py` creates deterministic component, plugin, module and package ZIP files in `dist/`. `tools/validate_release.py` validates source/version consistency, cross-product boundaries, schema history and distribution contents before release.

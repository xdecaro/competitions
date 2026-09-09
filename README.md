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

**1.3.0**

Version 1.3.0 adds an optional Finance bridge through Finance's public Joomla component service. It is a backward-compatible 1.x update from 1.2.0 and does not change the Competitions database schema.

The earlier pre-1.0 experimental identities are not part of the supported 1.x migration path.

## Architecture

Competitions owns competition-domain data and rules: countries and sporting territories, federations, organizations used in competition roles, zones, tournaments, seasons, teams, participations, players, rosters, matches, match events, rankings/coefficient data, synchronization state and the business reason/amount for competition charges or disciplinary deductions.

Other products own their own domains. Competitions must not read or write another xdecaro product's private tables, and other products must not read or write `#__xdecarocompetitions_*` directly.

Core integration remains infrastructure-only: public references, shared administrator design assets, diagnostics, capabilities and reusable technical services. Competition rules and sports-domain behavior remain in this repository.

## Optional integrations

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

CI performs a real Joomla 6.1.3 installation of the built package and validates the current schema. The 1.3.0 gate also installs the published Finance 1.2.0 package and exercises the public Finance bridge end to end.

## Data and update policy

Fresh installations create only `#__xdecarocompetitions_*` tables. The canonical `admin/sql/install.mysql.utf8mb4.sql` contains the complete current Competitions schema and does not replay pre-1.0 `ALTER TABLE` migrations.

`1.3.0.sql` is intentionally non-mutating because the Finance integration requires no competition-domain database change. Normal updates must preserve existing data, configuration and plugin enabled/disabled state.

## Build

`python3 tools/build.py` creates deterministic component, plugin, module and package ZIP files in `dist/`. `tools/validate_release.py` validates source/version consistency, cross-product boundaries, schema history and distribution contents before release.

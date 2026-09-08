# Competitions by xdecaro

Competitions by xdecaro is the competition-management component in the xdecaro Joomla ecosystem.

## Stable technical identity

- Component: `com_xdecarocompetitions`
- Package: `pkg_xdecarocompetitions`
- System plugin: `plg_system_xdecarocompetitions`
- Match Timeline module: `mod_xdecarocompetitions_matchtimeline`
- Countries/Federations module: `mod_xdecarocompetitions_countriesfederations`
- PHP component namespace: `xdecaro\Component\Competitions`
- Database tables: `#__xdecarocompetitions_*`
- Repository: `xdecaro/competitions`

## Current version

**1.1.0**

Version 1.1.0 keeps the clean 1.x Joomla/package/database identity established by 1.0.0 and normalizes the PHP vendor namespace to lowercase `xdecaro`. It is an in-place 1.x update from 1.0.0 and does not change competition data or database structure.

The earlier pre-1.0 experimental identities are not part of the supported 1.x migration path.

## Architecture

Competitions owns competition-domain data: countries and sporting territories, federations, organizations used in competition roles, zones, tournaments, seasons, teams, participations, players, rosters, matches, match events, rankings/coefficient data and synchronization state.

Cross-product integration must use Core by xdecaro public contracts. Other xdecaro components must not read or write Competitions private tables directly.

Core integration remains infrastructure-only: public references, shared administrator design assets, diagnostics and reusable technical services. Competition rules and sports-domain behavior remain in this repository.

## Joomla baseline

The 1.x line targets Joomla 6 and PHP 8.3+. Compatibility with earlier Joomla versions is not claimed until runtime-tested.

## Data policy

Fresh installations create only `#__xdecarocompetitions_*` tables. The canonical `admin/sql/install.mysql.utf8mb4.sql` contains the complete current schema, including organizations, matches, zones, tournament scope and Live Sync tables; it does not replay pre-1.0 `ALTER TABLE` migrations.

The `1.1.0.sql` update file is intentionally non-mutating because 1.1.0 changes only PHP namespace casing. Future 1.x updates must preserve data and configuration through additive or otherwise safe migrations.

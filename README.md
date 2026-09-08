# Competitions by xdecaro

Competitions by xdecaro is the competition-management component in the xdecaro Joomla ecosystem.

## Stable technical identity

- Component: `com_xdecarocompetitions`
- Package: `pkg_xdecarocompetitions`
- System plugin: `plg_system_xdecarocompetitions`
- Match Timeline module: `mod_xdecarocompetitions_matchtimeline`
- Countries/Federations module: `mod_xdecarocompetitions_countriesfederations`
- PHP component namespace: `Xdecaro\Component\Competitions`
- Database tables: `#__xdecarocompetitions_*`
- Repository: `xdecaro/competitions`

## Current version

**1.0.0**

Version 1.0.0 is a clean technical baseline. It intentionally does not provide an automatic migration from earlier experimental package/component identities. Install it as a fresh extension.

## Architecture

Competitions owns competition-domain data: countries and sporting territories, federations, organizations used in competition roles, zones, tournaments, seasons, teams, participations, players, rosters, matches, match events, rankings/coefficient data and synchronization state.

Cross-product integration must use Xdecaro Core public contracts. Other xdecaro components must not read or write Competitions private tables directly.

Core integration remains infrastructure-only: public references, shared administrator design assets, diagnostics and reusable technical services. Competition rules and sports-domain behavior remain in this repository.

## Joomla baseline

The 1.0.0 release targets Joomla 6 and PHP 8.3+. Compatibility with earlier Joomla versions is not claimed until runtime-tested.

## Data policy

Fresh installations create only `#__xdecarocompetitions_*` tables. The 1.0.0 source tree contains no legacy table-prefix migration. Normal future updates must preserve 1.x data and configuration using additive or otherwise safe migrations.

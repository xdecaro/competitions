# Competitions

**Competitions** is a neutral Joomla 6 management package for tournaments, seasons, federations, teams, players, participations, match events, standings, rankings and coefficients.

The repository keeps the historical/internal `dcl` technical identifiers for upgrade compatibility. They are implementation details and are no longer used as the visible product name.

## Naming

- Visible product: `Competitions`
- Administrator area: `Competitions`
- Joomla component: `com_decarodcl`
- Joomla package: `pkg_decarodcl`
- Core plugin: `plg_system_decarodcl`
- GitHub repository: `xdecaro/dcl`
- Database tables: `#__dcl_*`
- PHP component namespace: `Xdecaro\Component\Decarodcl`

## Current version

**0.4.0**

## Architecture

Competitions separates authentication, sport data and presentation:

- Joomla users are used for team-manager authentication and permissions.
- Team profiles, players, participations, rosters, events, rankings and coefficients use dedicated `#__dcl_*` tables.
- Match public pages remain Joomla articles so they can use YOOtheme Pro Dynamic Content.
- Match timeline events are stored in `#__dcl_match_events` and linked to the article ID.
- `com_decarodcl` is the central administrator component.
- Frontend modules can be placed directly in YOOtheme layouts.

## 0.4.0

Tournament and season management:

- added complete Joomla administrator CRUD for Tournaments;
- added complete Joomla administrator CRUD for Seasons;
- Seasons are linked to a Tournament and can use the existing Countries registry as host country;
- added server-side validation for tournament codes, disciplines, categories, dates and referenced records;
- added search, filters, sorting, pagination, publish/unpublish and trash actions;
- enabled Tournaments and Seasons in the Competitions administrator submenu and Dashboard;
- Dashboard counts now exclude trashed records;
- improved administrator responsiveness with stackable list cards on small screens;
- preserved all existing `#__dcl_*` tables and data without destructive migrations.

## Requirements

- Joomla 6.0+
- PHP 8.3+
- MySQL / MariaDB with InnoDB and utf8mb4

## Data preservation

Updates and uninstall routines do not delete `#__dcl_*` data tables automatically. Destructive data removal must be an explicit administrator action.

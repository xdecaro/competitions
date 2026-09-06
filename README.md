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

**0.5.0**

## Architecture

Competitions separates authentication, sport data and presentation:

- Joomla users are used for team-manager authentication and permissions.
- Team profiles, players, participations, rosters, events, rankings and coefficients use dedicated `#__dcl_*` tables.
- Match public pages remain Joomla articles so they can use YOOtheme Pro Dynamic Content.
- Match timeline events are stored in `#__dcl_match_events` and linked to the article ID.
- `com_decarodcl` is the central administrator component.
- Frontend modules can be placed directly in YOOtheme layouts.

## 0.5.0

Team and participation management:

- added complete Joomla administrator CRUD for Teams using the existing `#__dcl_teams` table;
- Teams are linked to Federations and derive their country from Federation → Country;
- a Joomla user can be assigned as the responsible team account without mixing authentication data with sport data;
- added team approval states (`pending`, `approved`, `rejected`) with server-side ACL enforcement;
- added complete administrator CRUD for Participations using `#__dcl_participations`;
- Participations link a Team to a Season and support `draft`, `submitted`, `approved` and `rejected`;
- participation approval is blocked until the Team itself is approved;
- submission/review timestamps and reviewer are maintained automatically;
- duplicate Team + Season participations are rejected server-side;
- added search, filters, sorting, pagination, state actions and mobile-friendly list layouts;
- enabled Teams and Participations in the Competitions submenu and Dashboard;
- preserved all existing `#__dcl_*` data and schema without destructive migrations.

## Requirements

- Joomla 6.0+
- PHP 8.3+
- MySQL / MariaDB with InnoDB and utf8mb4

## Data preservation

Updates and uninstall routines do not delete `#__dcl_*` data tables automatically. Destructive data removal must be an explicit administrator action.

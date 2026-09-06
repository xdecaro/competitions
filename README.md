# Competitions

**Competitions** is a neutral Joomla 6 management package for tournaments, seasons, federations, teams, players, participations, rosters, match events, standings, rankings and coefficients.

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

**0.6.0**

## Architecture

Competitions separates authentication, sport data and presentation:

- Joomla users are used for team-manager authentication and permissions.
- Team profiles, players, participations, rosters, events, rankings and coefficients use dedicated `#__dcl_*` tables.
- Match public pages remain Joomla articles so they can use YOOtheme Pro Dynamic Content.
- Match timeline events are stored in `#__dcl_match_events` and linked to the article ID.
- `com_decarodcl` is the central administrator component.
- Frontend modules can be placed directly in YOOtheme layouts.

## 0.6.0

Player and roster management:

- added complete Joomla administrator CRUD for Players using the existing `#__dcl_players` table;
- Players support name, optional external reference, birth date, nationality, photo, publication state and approval workflow;
- nationality is selected from the existing Countries registry and validated against the three-character sports code stored by the existing schema;
- external player references are validated as unique when provided;
- player approval and publication changes are protected server-side by `core.edit.state`;
- added complete Joomla administrator CRUD for Rosters using the existing `#__dcl_rosters` table;
- Roster entries link a Player to a Team Participation and support shirt number, free-text role, review note and approval state;
- `team_id` is always derived server-side from the selected Participation and cannot be forged through form input;
- Roster approval requires an approved Team, approved Participation and approved Player;
- duplicate Player + Participation roster entries are rejected server-side;
- added search, filters, sorting, pagination, publish/unpublish, trash and responsive mobile layouts;
- enabled Players and Rosters in the Competitions submenu and Dashboard;
- preserved all existing `#__dcl_*` data and schema without destructive migrations.

## Requirements

- Joomla 6.0+
- PHP 8.3+
- MySQL / MariaDB with InnoDB and utf8mb4

## Data preservation

Updates and uninstall routines do not delete `#__dcl_*` data tables automatically. Destructive data removal must be an explicit administrator action.

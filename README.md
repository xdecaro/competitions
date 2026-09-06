# DCL

**DCL** is a Joomla 6 management package for the Deaf Champions League project.

## Naming

- Visible product: `DCL`
- Administrator area: `DCL Manager`
- Joomla component: `com_decarodcl`
- Joomla package: `pkg_decarodcl`
- Core plugin: `plg_system_decarodcl`
- GitHub repository: `xdecaro/dcl`
- Database tables: `#__dcl_*`
- PHP component namespace: `Xdecaro\Component\Decarodcl`

## Current version

**0.3.0**

## Architecture

DCL separates authentication, sport data and presentation:

- Joomla users are used for team-manager authentication and permissions.
- Team profiles, players, participations, rosters, events, rankings and coefficients use dedicated `#__dcl_*` tables.
- Match public pages remain Joomla articles so they can use YOOtheme Pro Dynamic Content.
- Match timeline events are stored in `#__dcl_match_events` and linked to the article ID.
- `com_decarodcl` is the central administrator manager.
- Frontend modules can be placed directly in YOOtheme layouts.

## 0.3.0

First administrator component release:

- `DCL Manager` component added;
- Dashboard;
- Countries CRUD with search, status, ordering and entity type;
- Federations CRUD linked to countries;
- existing DCL database data preserved;
- schema ownership moved to the component;
- new `plg_system_decarodcl` bootstrap;
- match timeline module retained;
- countries/federations frontend module retained;
- legacy `plg_system_dclcore` is disabled, not deleted, by the new package installer when found;
- IT / EN administrator language;
- responsive and dark-mode-compatible administrator UI;
- package-level update server configuration;
- CI and release build workflows.

## Requirements

- Joomla 6.0+
- PHP 8.3+
- MySQL / MariaDB with InnoDB and utf8mb4

## Data preservation

Updates and uninstall routines do not delete DCL data tables automatically. Destructive data removal must be an explicit administrator action.

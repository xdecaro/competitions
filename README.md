# Competitions

**Competitions** is a neutral Joomla 6 management package for tournaments, seasons, organizations, zones, federations, teams, players, participations, rosters, matches, match events, standings, rankings and coefficients.

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

**0.8.2**

## Architecture

Competitions separates authentication, sport data and presentation:

- Joomla users are used for team-manager authentication and permissions only.
- Organizations, zones, teams, players, participations, rosters, matches, events, rankings and coefficients use dedicated `#__dcl_*` tables.
- `#__dcl_organizations` stores reusable organizations such as competition organizers, governing bodies, local organizers and partners.
- `#__dcl_zones` stores reusable sporting zones and may optionally be scoped to an Organization.
- `#__dcl_zone_countries` links Countries or sporting territories to Zones without forcing one global Zone per Country.
- Tournament and Season organization roles are stored in dedicated relation tables instead of duplicated text fields.
- `#__dcl_matches` is the authoritative source for match date, teams, status and scores.
- Joomla articles are optional editorial links only; `article_id` is not the sports-data source of truth.
- Match timeline events use `match_id` as the primary relation. The legacy `article_id` relation remains available for backward compatibility.
- `com_decarodcl` is the central administrator component.
- Frontend modules can be placed directly in YOOtheme layouts without moving authoritative sports data into Joomla articles.

## Zones

Version 0.8.0 adds a dedicated **Zones** administrator area between Organizations and Countries.

A Zone can be global or assigned to a specific Organization. Countries are linked through a many-to-many relation, so a Country can belong to different sporting Zones when different Organizations use different classifications.

The project-provided initial sporting classification is installed as global Zones:

- African zone: Cameroon, Nigeria, South Africa
- Asian zone: Australia, China PR, Japan, Korea Republic, Thailand
- European zone: England, France, Germany, Italy, Netherlands, Norway, Scotland, Spain, Sweden
- North, Central American and Caribbean zone: Canada, Jamaica, USA
- Oceania zone: New Zealand
- South American zone: Argentina, Brazil, Chile

Existing Country records are preserved. Missing Countries from the supplied classification are inserted with stable sporting codes and then linked to the appropriate Zone.

## Administrator information and updates

Version 0.7.1 added an **Information** administrator view with the installed package version, Joomla/PHP/database information, native Joomla update status, configured update-server URL and shortcuts to Joomla Updates, Update Sites and GitHub Releases.

Version 0.8.1 fixes Joomla database parameter binding in the Information view by binding local variables instead of object properties/constants, as required by Joomla's by-reference query API.

Version 0.8.2 redesigns Information as a compact responsive overview and adds an installation-integrity check. The page now compares the installed versions of the package, component, system plugin, Match Timeline module and Countries/Federations module, clearly warning when a partial update leaves the installation out of sync.

The package registers `https://raw.githubusercontent.com/xdecaro/dcl/main/updates/pkg_decarodcl.xml` as its Joomla update server. The package installer also repairs the update-site association on install/update if it is missing or disabled. Joomla automatically checks extension update availability when an administrator signs in; installing an available release remains managed through Joomla's native extension updater.

## 0.7.0

Organizations and native Match management:

- added administrator CRUD for Organizations using `#__dcl_organizations`;
- Organizations can optionally reference an existing Country and store short name, logo, website and email;
- added Tournament organization roles: organizer, governing body, co-organizer and partner;
- added Season organization roles, including local organizer;
- added native `#__dcl_matches` storage and administrator CRUD for Matches;
- Match records link Season, Home Team, Away Team and optional Venue;
- both Teams must have an approved Participation in the selected Season;
- Match status supports scheduled, live, finished, postponed and cancelled;
- scores, extra-time scores and penalties are validated in pairs and the winner is derived server-side for finished Matches;
- optional Joomla `article_id` remains available for editorial compatibility and cannot be reused by multiple Matches;
- added `match_id` to `#__dcl_match_events` while preserving legacy `article_id` data;
- the Match Timeline module now prefers `match_id` and falls back to the legacy article relation;
- added backward-compatible SQL update and fresh-install SQL without deleting existing sports data;
- enabled Organizations and Matches in the administrator menu and Dashboard.

## Requirements

- Joomla 6.0+
- PHP 8.3+
- MySQL / MariaDB with InnoDB and utf8mb4

## Data preservation

Updates and uninstall routines do not delete `#__dcl_*` data tables automatically. The 0.7.0 migration adds Organizations and native Matches while preserving the previous `article_id` event relation. Version 0.8.0 adds Zones and Country mappings using additive tables and `INSERT IGNORE`, so existing Country records and sports data are not overwritten or deleted. Versions 0.8.1 and 0.8.2 change administrator diagnostics/UI and release metadata only; they do not modify sports data or database schema. Destructive data removal must be an explicit administrator action.

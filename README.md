# Competitions

**Competitions** is a neutral Joomla 6 management package for tournaments, seasons, organizations, federations, teams, players, participations, rosters, matches, match events, standings, rankings and coefficients.

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

**0.7.0**

## Architecture

Competitions separates authentication, sport data and presentation:

- Joomla users are used for team-manager authentication and permissions only.
- Organizations, teams, players, participations, rosters, matches, events, rankings and coefficients use dedicated `#__dcl_*` tables.
- `#__dcl_organizations` stores reusable organizations such as competition organizers, governing bodies, local organizers and partners.
- Tournament and Season organization roles are stored in dedicated relation tables instead of duplicated text fields.
- `#__dcl_matches` is the authoritative source for match date, teams, status and scores.
- Joomla articles are optional editorial links only; `article_id` is not the sports-data source of truth.
- Match timeline events use `match_id` as the primary relation. The legacy `article_id` relation remains available for backward compatibility.
- `com_decarodcl` is the central administrator component.
- Frontend modules can be placed directly in YOOtheme layouts without moving authoritative sports data into Joomla articles.

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

Updates and uninstall routines do not delete `#__dcl_*` data tables automatically. The 0.7.0 migration adds new structures and preserves the previous `article_id` event relation. Destructive data removal must be an explicit administrator action.

# Changelog

## 0.8.0 - 2026-09-06

- Added native `#__dcl_zones` and `#__dcl_zone_countries` tables.
- Added complete administrator CRUD for Zones with optional Organization scope.
- Added many-to-many Zone → Country assignments so a Country can belong to different sporting Zones without changing the Country record.
- Added Zone search, Organization filter, publication state, ordering, Country counts and responsive Country summaries.
- Added a Zone filter and Zone column to the Countries administrator list.
- Added Zones to the Competitions submenu and Dashboard.
- Added the project-provided initial sporting Zones: African, Asian, European, North/Central American and Caribbean, Oceania and South American.
- Added missing Countries from the supplied Zone classification without replacing existing Country records.
- Seeded the supplied Country → Zone mappings using additive `INSERT IGNORE` migrations.
- Preserved Organization, Country, Federation, Tournament, Season, Team, Player, Match and other existing sports data.
- Bumped component, package, plugin, modules, Web Asset registry and update feed to 0.8.0.

## 0.7.1 - 2026-09-06

- Added a dedicated **Information** administrator menu/view for fast access to the installed Competitions version and system details.
- Added Joomla, PHP and database information plus package/component/repository identifiers.
- Added native Joomla update status, latest cached update version, update-server URL and last-check information.
- Added shortcuts to Joomla Extension Updates, Update Sites and GitHub Releases while respecting `com_installer` ACL.
- Added a defensive package postflight repair for the Competitions update-site record and `#__update_sites_extensions` association.
- Update discovery remains handled by Joomla's native update system; release installation stays controlled by Joomla rather than using an unsafe custom self-updater.
- Added the Information submenu entry and kept Organizations and Matches in the component manifest so a normal component upgrade rebuilds the current administrator submenu.
- Preserved all existing `#__dcl_*` sports data; no destructive database migration is introduced.
- Bumped component, package, plugin, modules, Web Asset registry and update feed to 0.7.1.

## 0.7.0 - 2026-09-06

- Added `#__dcl_organizations` and complete administrator CRUD for reusable competition organizations.
- Added Tournament → Organization role relations for organizer, governing body, co-organizer and partner.
- Added Season → Organization role relations, including local organizer.
- Added native `#__dcl_matches` as the authoritative sports-data source for Matches.
- Added complete Match administrator CRUD with Season, Home/Away Team, optional Venue, date/time, stage, group, round, matchday, status, scores, attendance and notes.
- Match creation validates that both Teams have an approved Participation in the selected Season.
- Added server-side score-pair validation, numeric bounds and winner derivation for finished Matches.
- Kept Joomla `article_id` as an optional editorial compatibility link and reject reuse of the same article by multiple Matches.
- Added `match_id` to `#__dcl_match_events` while preserving legacy `article_id` values and migrating events where an article-linked Match exists.
- Updated Match Timeline to prefer `match_id` while retaining article fallback and accessibility labels.
- Added fresh-install and update SQL for Organizations, organization relations, Matches and event migration; no existing sports data is deleted.
- Added Organizations and Matches to the administrator submenu and Dashboard.
- Preserved the Joomla 6 administrator asset-loading workaround verified in 0.3.5.
- Bumped component, package, plugin, modules, Web Asset registry and update feed to 0.7.0.

## 0.6.0 - 2026-09-06

- Added complete administrator CRUD for Players using the existing `#__dcl_players` table.
- Added Player fields for first name, last name, optional external reference, birth date, nationality, photo, approval and publication state.
- Added server-side validation for required names, valid/non-future birth dates, nationality references and unique external references.
- Added Player approval workflow with `core.edit.state` protection against direct-form tampering.
- Added complete administrator CRUD for Rosters using the existing `#__dcl_rosters` table.
- Added Participation → Player roster assignment with shirt number, free-text role, review note, ordering and approval state.
- Roster `team_id` is derived server-side from the selected Participation and never trusted from posted input.
- Prevented approval of a Roster entry until its Team, Participation and Player are approved.
- Prevented duplicate Player + Participation roster entries.
- Added Player and Roster search, filters, sorting, pagination, publish/unpublish, trash and responsive mobile layouts.
- Added Players and Rosters to the Competitions submenu and Dashboard, including a Roster counter.
- Preserved the Joomla 6 administrator asset-loading workaround verified in 0.3.5.
- No destructive database migration; all existing `#__dcl_*` data is preserved.
- Bumped component, package, plugin, modules, Web Asset registry and update feed to 0.6.0.

## 0.5.0 - 2026-09-06

- Added complete administrator CRUD for Teams and Participations.
- Teams are linked to Federations and derive Country context from Federation → Country.
- Added responsible Joomla user assignment while keeping authentication separate from sports data.
- Added Team approval and Participation review workflows with server-side ACL protection.
- Prevented approval of a Participation until its Team is approved and prevented duplicate Team + Season participation records.
- Added search, filters, sorting, pagination, state actions and responsive layouts.

## 0.4.0 - 2026-09-06

- Added complete administrator CRUD for Tournaments and Seasons.
- Added Tournament → Seasons relations, host-country selection, search, filters, sorting and responsive layouts.
- Added server-side validation for tournament codes, disciplines, categories, season years, host countries and date ranges.

## 0.3.5 - 2026-09-06

- Confirmed through browser-console diagnostics that the administrator stylesheet existed but Joomla did not inject it.
- Added canonical `media/com_decarodcl/admin.css` and a runtime Web Asset registration fallback.
- Preserved all technical identifiers and existing `#__dcl_*` data.

# Changelog

## 1.2.0 - 2026-09-09

- Added optional bridges to Notifications 1.x and Tasks 1.x through their public Joomla component services only.
- Added an ACL-protected Competitions Analytics provider for tournaments, seasons, teams, matches, participations, match events and rankings.
- Added `plg_xdecaroanalytics_competitions` for Analytics provider discovery.
- Added a Joomla Scheduled Tasks plugin for upcoming scheduled-match reminders to an explicitly configured Joomla manager.
- Added Core capability declarations for analytics, notification/task bridges and match reminders when Core 1.4+ CapabilityRegistry is available.
- Kept all optional integrations safe when the related product is absent; no external database tables are read or written.
- Added a non-destructive 1.2.0 schema marker; no competition-domain database changes are required.
- Fixed Joomla SQL manifest declarations to use `charset="utf8"` while retaining utf8mb4 table definitions.
- Package updates preserve existing plugin enabled/disabled state; integration plugins are enabled only on fresh install.

## 1.1.0 - 2026-09-08

- Made `xdecaro\Component\Competitions` the canonical PHP component namespace.
- Kept `com_xdecarocompetitions`, `pkg_xdecarocompetitions` and `#__xdecarocompetitions_*` unchanged.
- Updated plugin/module/component PHP vendor namespaces to lowercase `xdecaro`.
- Added a non-mutating `1.1.0.sql` schema marker; no database structure or data migration is required for this namespace-only release.
- Preserved competition-domain behavior and Core integration.

## 1.0.0 - 2026-09-08

- Established a clean Competitions by xdecaro technical identity.
- Component: `com_xdecarocompetitions`.
- Package: `pkg_xdecarocompetitions`.
- PHP namespace at release: `Xdecaro\Component\Competitions`.
- Database namespace: `#__xdecarocompetitions_*`.
- Renamed the system plugin and site modules to the xdecaro Competitions namespace.
- Consolidated administrator language files into one current file per language.
- Removed pre-1.0 schema/install migration history from the active 1.0 source tree.
- Preserved current competition-domain behavior and Core by xdecaro integration.
- This was a fresh-install baseline and intentionally did not auto-migrate experimental pre-1.0 identities.

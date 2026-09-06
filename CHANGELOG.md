# Changelog

## 0.3.0 - 2026-09-06

- Added `com_decarodcl` / DCL Manager.
- Added Dashboard.
- Added Countries administrator CRUD.
- Added Federations administrator CRUD linked to Countries.
- Preserved existing `#__dcl_*` data tables.
- Moved schema ownership from legacy system plugin to the component.
- Added `plg_system_decarodcl`.
- Kept `mod_dcl_matchtimeline`.
- Kept `mod_dcl_countriesfederations`.
- Added responsive/dark-mode-compatible administrator styling.
- Added package update feed, build tooling, SHA-256 generation and GitHub CI/release workflows.
- Legacy `plg_system_dclcore` is disabled, not removed, on installation of the new package.

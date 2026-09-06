# Changelog

## 0.3.5 - 2026-09-06

- Confirmed with browser-console diagnostics that the stylesheet file existed and returned HTTP 200, while Joomla did not add any `com_decarodcl` stylesheet to the page.
- Added a canonical root media stylesheet at `media/com_decarodcl/admin.css`.
- Changed the Web Asset URI from `com_decarodcl/css/admin.css` to `com_decarodcl/admin.css`, matching the proven asset layout used by the xdecaro Courses component.
- Added `admin.css` explicitly to the component media manifest and retained the legacy `css/` directory for upgrade compatibility.
- Reworked `UiHelper` to register and use a dedicated runtime style asset instead of relying on automatic extension-registry discovery.
- Preserved all technical identifiers and existing `#__dcl_*` data.

## 0.3.4 - 2026-09-06

- Fixed administrator CSS not loading on Joomla 6.
- Added a central `UiHelper` that explicitly loads the `com_decarodcl` Web Asset registry.
- Added a defensive fallback that registers `com_decarodcl/css/admin.css` directly if the registry asset is still unavailable.
- Applied the asset loader to Dashboard, Countries, Country, Federations and Federation administrator views.
- Bumped component, package, plugin, modules, Web Asset registry and update feed to 0.3.4.
- Preserved all technical identifiers and existing `#__dcl_*` data.

## 0.3.3 - 2026-09-06

- Fixed the root cause of `500 Layout default not found`: restored `<folder>tmpl</folder>` inside the administrator file list of `com_decarodcl`.
- Kept explicit template paths for Dashboard, Countries, Country, Federations and Federation views as a defensive fallback.
- Keeps the visible product name `Competitions` while preserving all technical identifiers and existing `#__dcl_*` data.
- Bumped all shipped manifests/assets to 0.3.3; 0.3.2 remains immutable.

## 0.3.2 - 2026-09-06

- Renamed the visible product and administrator area to `Competitions`.
- Kept `com_decarodcl`, `pkg_decarodcl`, `plg_system_decarodcl`, repository `xdecaro/dcl` and `#__dcl_*` tables unchanged for upgrade compatibility.
- Renamed visible core plugin and frontend module labels to the Competitions brand.
- Removed DCL-specific wording from generic administrator labels such as country code.
- Added explicit administrator template paths for Dashboard, Countries and Federations views to fix `500 Layout default not found`.
- Updated package/update descriptions, GitHub workflow display names and release branding.
- Bumped all shipped manifests/assets to 0.3.2; 0.3.1 remains immutable.

## 0.3.1 - 2026-09-06

- Fixed component package manifest by removing the unused empty site template folder reference.
- Rebuilt and republished as 0.3.1; 0.3.0 remains immutable.
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

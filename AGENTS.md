# Competitions — Codex Repository Rules

## xdecaro Core integration

Competitions is part of the xdecaro Joomla ecosystem and should use **xdecaro Core** for infrastructure that is genuinely shared across multiple xdecaro extensions.

Core is infrastructure, not Competitions business logic.

Before implementing or refactoring reusable technical functionality, inspect whether the same responsibility already exists in Core or clearly belongs there.

Good Core candidates include:

- shared design tokens and `.xdecaro-*` UI primitives;
- light/dark mode foundations;
- responsive administrator UI helpers;
- common buttons, badges, cards, tables, modals, alerts and loading states;
- shared Web Asset Manager registration;
- generic JavaScript utilities;
- Joomla-compliant AJAX/CSRF helpers;
- dependency/version checks;
- common diagnostics;
- xdecaro extension registry;
- shared information/update UI.

Keep all Competitions-specific business logic in this repository, including:

- competitions and competition types;
- seasons;
- sports disciplines;
- teams and participants where represented by Competitions domain logic;
- phases;
- groups;
- rounds;
- fixtures and matches;
- results;
- rankings and standings;
- scoring rules;
- tie-break rules;
- qualification rules;
- brackets;
- competition-specific statistics;
- competition-specific imports/exports;
- sport-specific or tournament-specific workflows.

Do not move these domain concepts into Core.

Do not move code into Core merely because it could technically be reused. A Core abstraction must be domain-neutral and useful to more than one xdecaro product.

## Migration and implementation rule

When a task touches functionality that is a good Core candidate:

1. inspect the current Competitions implementation first;
2. inspect the available Core public API before designing a duplicate;
3. use Core when a stable public API already covers the requirement;
4. keep Competitions-specific behavior inside Competitions;
5. avoid circular dependencies: Competitions may depend on Core, Core must never depend on Competitions;
6. preserve existing data, configuration and update paths;
7. migrate incrementally rather than rewriting large working areas;
8. do not remove a local implementation until the Core replacement is verified;
9. verify desktop, tablet, smartphone, light mode and dark mode when UI is affected;
10. test the real competition workflows that use the changed shared functionality.

If Core is not available in the current workspace or the required public API does not yet exist, do not invent a fake Core API. Implement only what is necessary locally and keep the reusable boundary clear for a future safe migration.

## Dependency policy

If Competitions declares Core as a mandatory runtime dependency, the package, manifests, installer/update path and minimum Core version must be updated coherently.

Dependency handling must be predictable:

- clean install must explain a missing dependency clearly;
- updates must preserve existing data and configuration;
- incompatible Core versions must produce a controlled Joomla administrator message rather than an opaque fatal error;
- package manifests and release artifacts must stay coherent.

Do not silently assume a Core API exists without verifying it.

## Public API stability

Treat Core public classes, services, asset identifiers, JavaScript APIs, CSS classes and CSS variables as stable contracts.

Do not copy internal Core implementation details into Competitions and do not access Core internals that are not part of its public API.

Likewise, do not expose unstable Competitions internals as shared Core contracts.

## Joomla and security

Core integration must not weaken Competitions security or Joomla conventions.

Continue to enforce where relevant:

- server-side ACL;
- Joomla CSRF tokens;
- filtered and validated input;
- escaped output;
- bound database queries;
- safe upload validation;
- no authorization decisions made only in JavaScript.

Check role-specific actions, match/result editing, competition administration and any state-changing operation server-side.

## Database

Use `#__` for Joomla tables.

Keep competition-domain tables in Competitions.

Do not move competition entities or persistent competition state into Core.

Database updates must preserve existing data and configuration.

Avoid destructive table recreation during normal updates when a safe migration is possible.

Check indexes, joins, duplicate queries and queries inside loops, especially on standings, results, schedules and large competition datasets.

## UI and assets

Prefer Core for shared visual primitives and asset infrastructure when available, but keep Competitions-specific presentation and behavior local.

Shared UI migration must preserve:

- existing administrator workflows;
- frontend output;
- responsive behavior;
- light mode;
- dark mode;
- accessibility;
- current data presentation.

Do not force a large visual rewrite merely to adopt Core.

Load shared Core assets through Joomla Web Asset Manager and avoid duplicate CSS/JS registration.

## Regression rule

A Core-related change is complete only when the affected Competitions behavior remains verified.

Check as applicable:

- clean installation;
- update installation;
- Joomla 4/5/6 where supported by the implementation;
- administrator/frontend behavior;
- competition creation/editing;
- phases/groups/rounds;
- fixtures and matches;
- result entry;
- standings/rankings;
- modules/plugins affected;
- assets loaded once;
- AJAX;
- ACL and CSRF;
- database migrations and queries;
- PHP errors/warnings;
- JavaScript Console;
- desktop/tablet/smartphone;
- light/dark mode.

Do not combine an opportunistic Core integration with unrelated large refactors.

## Repository rename

This repository is **Competitions**.

Do not introduce new repository-level references to the former name `competitions` unless they refer to a historical compatibility identifier that is still technically required.

Before renaming package names, extension element names, namespaces, database tables, update URLs or public identifiers inherited from the old project, inspect compatibility impact first.

Do not rename existing technical identifiers solely to match the repository name if doing so would break installed Joomla extensions, updates, database data or public APIs.

For incompatible technical renames, prefer a staged migration with compatibility support and a future major release when needed.

## Working rule

When the user says **“procedi”**, execute the requested work directly after inspecting the relevant code and dependencies.

Do not ask for another confirmation when requirements are already clear.

If a proposed technical approach is weaker than a safer or more maintainable alternative, explain the issue and use or recommend the stronger approach.

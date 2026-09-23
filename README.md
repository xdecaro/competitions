# Competitions

Competitions is the competition-management component in the xdecaro Joomla ecosystem.

## Stable technical identity

- Component: `com_xdecarocompetitions`
- Package: `pkg_xdecarocompetitions`
- System plugin: `plg_system_xdecarocompetitions`
- Analytics plugin: `plg_xdecaroanalytics_competitions`
- Scheduled Tasks plugin: `plg_task_xdecarocompetitions`
- Match Timeline module: `mod_xdecarocompetitions_matchtimeline`
- Countries/Federations module: `mod_xdecarocompetitions_countriesfederations`
- PHP component namespace: `xdecaro\Component\Competitions`
- Database tables: `#__xdecarocompetitions_*`
- Repository: `xdecaro/competitions`

## Current version

**1.5.8**

Version 1.5.8 derives a linked Club team's federation from its active sports affiliation in Organizations. Club users no longer choose a federation manually: one active affiliation maps to the linked Competition federation, no affiliation produces an explicit undetermined state, and multiple active federation affiliations are rejected as ambiguous.

Version 1.5.0 added a public read-only person-history provider keyed by the existing People `person_uuid`. Competitions advertises the `competitions.people_history` v1 capability through Core, allowing People to show competition, season, team, role, shirt number and roster status history without reading Competitions private tables or duplicating competition data.

Photo ownership is explicit:

1. a roster photo belongs to the player’s participation in that team/edition;
2. otherwise Competitions resolves the person’s People profile-document reference when available;
3. otherwise an existing legacy player photo can still be used as a compatibility fallback.

Competitions does not copy People profile images or access People private tables. The People fallback is deliberately represented as a Documents entity reference; a consumer that needs image bytes or a public URL must resolve that reference through a public Documents API rather than reading Documents storage directly.

## Architecture

Competitions owns competition-domain data and rules: countries and sporting territories, federations, organizations used in competition roles, zones, tournaments, seasons, teams, participations, players, rosters, matches, match events, rankings/coefficient data, synchronization state and the business reason/amount for competition charges or disciplinary deductions.

People owns canonical person identity. New Competition player records are linked by stable `person_uuid`; legacy unlinked player records remain supported so upgrades do not destroy historical data. The Competitions player record retains competition-specific lifecycle/approval data and historical compatibility fields while the roster owns edition/team-specific presentation such as its photo.

Other products own their own domains. Competitions must not read or write another xdecaro product's private tables, and other products must not read or write `#__xdecarocompetitions_*` directly.

Core integration remains infrastructure-only: public references, shared administrator design assets, diagnostics, capabilities and reusable technical services. Competition rules and sports-domain behavior remain in this repository.

## Optional integrations

### People

People integration is discovered at runtime with `bootComponent('com_xdecaropeople')` and consumed only through `getPersonProviderService()`.

The administrator player editor provides a People search picker. New players require a People person UUID; existing legacy players without a UUID remain editable for compatibility. Search results use the non-sensitive People provider surface. After a person is selected, Competitions performs a targeted sensitive profile lookup so `birth_date` and nationality can be copied when the current user is authorised to view sensitive People data. The selected-profile lookup does not silently degrade to a non-sensitive result. Competitions never queries `#__xdecaropeople_*` directly.

Competitions also exposes `getPersonHistoryService()->getHistoryByPersonUuid()` as a public read-only outward service. It reads only Competitions-owned tables and returns normalized roster history for consumers that first verify `competitions.people_history` v1 through Core's `CapabilityRegistry`.

### Organizations

Organizations is an optional canonical identity provider for federations and club teams. Competitions discovers it at runtime with `bootComponent('com_xdecaroorganizations')` and consumes only `getOrganizationProviderService()`.

Federation mappings use a stable Organizations UUID filtered to organizations of type `federation`. Club-team mappings use a separate stable Organizations UUID filtered to organizations of type `club`. Competition keeps its own local federation/team primary keys because participations, rosters, matches, coefficients and history already reference those IDs.

For linked federations, name, short name, logo, website and email are compatibility snapshots refreshed from Organizations. For linked club teams, name, short name, logo, email, phone and website are compatibility snapshots refreshed from Organizations. A linked Club's federation is derived from exactly one published active `sports_affiliation` returned by Organizations: Competitions maps the affiliation target UUID to its own linked federation row. If there is no active affiliation or the target federation is not yet mapped in Competitions, the Club stays explicitly undetermined; multiple active federation affiliations are treated as ambiguous and are never guessed. Approval state, manager, ordering, alias and participation lifecycle remain Competition-domain data. National representative teams are not treated as legal club organizations and keep manual federation management inside Competitions.

Existing legacy club teams remain valid and can be linked once without changing their Competition ID or historical relations. Once linked, the canonical club cannot be swapped from the normal team editor. If Organizations is temporarily unavailable, existing links and local compatibility snapshots remain usable.

Competitions never queries `#__xdecaroorganizations_*` directly.

### Finance

Finance is optional. Competitions discovers it at runtime with `bootComponent('com_decarofinance')` and consumes only `getFinanceService()`.

The bridge supports:

- participation-fee obligations;
- team deposit/caution accounts;
- idempotent deposit credits;
- idempotent disciplinary deposit debits;
- deposit balance queries.

Competitions owns *why* and *how much* must be charged. Finance owns financial persistence, obligation/payment state, allocations, deposit accounts and append-only movements. No yellow-card, red-card, suspension, fight or other tariff is hard-coded into Core or Finance.

Finance absence is a supported state and returns no Finance result. Once Finance is installed/enabled, provider incompatibility or a financial operation failure is logged and propagated instead of being silently ignored. Idempotency is provided through stable Finance `external_key` values.

Competitions runtime code never accesses `#__decarofinance_*`.

### Notifications, Tasks and Analytics

Notifications and Tasks are optional best-effort integrations through their public Joomla component services. Competitions also exposes an ACL-protected Analytics source through `plg_xdecaroanalytics_competitions` and can schedule upcoming-match reminders through the Joomla Scheduled Tasks plugin.

When Core `CapabilityRegistry` is available, Competitions declares analytics, Notifications, Tasks, Finance, match-reminder, People-history and Organizations-bridge capabilities. Core itself contains no competition or finance business logic.

## Joomla baseline

The current Competitions 1.x line targets Joomla 6 and PHP 8.3+. Compatibility with earlier Joomla versions is not claimed until runtime-tested.

CI performs a real Joomla 6.1.3 installation of the built package. The 1.5.8 gate validates active sports-affiliation federation derivation, ambiguity/no-affiliation behavior, Club/National federation UI separation, club-team Organizations linking, team UUID schema/migration and editor behavior, schema repair and federation-link persistence, one-time federation linking and immutable canonical links, all edit-form live-sync lock fields, the live-sync reload-loop guard, the federation model method signature, the Organizations public-provider boundary, stable federation UUID schema/migration, public branding, approval-message language loading, the People provider boundary, People UUID schema, roster photo schema, People picker WebAsset path, selected sensitive-profile autofill contract, public person-history provider/runtime, bulk player approval actions and the existing Finance bridge regression coverage.

## Data and update policy

Fresh installations create only `#__xdecarocompetitions_*` tables. Version 1.4.0 adds nullable unique `person_uuid` to players and a nullable edition/team `photo` to rosters. Versions 1.5.0 and 1.5.1 do not change the database schema. Version 1.5.2 adds a nullable unique `organization_uuid` to federations; existing federation rows remain valid and unlinked. Versions 1.5.3, 1.5.4 and 1.5.5 change no database structure. Version 1.5.6 repairs the existing 1.5.2 federation UUID schema on historical installations when it is missing; it does not remove or rewrite data. Version 1.5.7 adds a nullable unique `organization_uuid` to teams for canonical Organizations club links; existing teams remain valid and unlinked. Version 1.5.8 changes no database structure.

Existing player records are not auto-linked or auto-merged. Linking to People must be explicit or performed by a separately verified migration workflow.

## Build

`python3 tools/build.py` creates deterministic component, plugin, module and package ZIP files in `dist/`. `tools/validate_release.py` validates source/version consistency, cross-product boundaries, schema history and distribution contents before release.

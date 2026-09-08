# Xdecaro Core integration

Competitions uses the Xdecaro Core cross-product reference contract for relationships with other Xdecaro products.

The visible product/repository name is Competitions, but the installed Joomla component element remains **`com_decarodcl`** for upgrade compatibility. Cross-product `EntityReference` values must therefore use `com_decarodcl`; do not derive the component identifier from the repository name.

Use:

- `Xdecaro\Core\Integration\EntityReference` for `component/entity/id` references;
- `Xdecaro\Core\Integration\RelationReference` for typed links between references.

Competitions remains the owner of tournaments, seasons, organizations, zones, federations, teams, players, participations, rosters, matches, results, standings, rankings and coefficients.

Typical integrations include:

- Membership member -> Competitions player/participation with an explicit participant relation;
- Forms submission -> competition participation workflow with relation type such as `source_submission`;
- Documents document -> participant, team, season, match or other published Competitions entity;
- Events event -> tournament, season, match or competition-related event.

Do not expose `#__dcl_*` tables as the integration API and do not rename the historical Joomla identifiers merely to match the visible product name.

Entity type names become stable public API only when Competitions explicitly publishes them. Preserve compatibility for any entity type once published.

Optional integrations must fail gracefully when the other product is unavailable. Avoid circular mandatory dependencies.

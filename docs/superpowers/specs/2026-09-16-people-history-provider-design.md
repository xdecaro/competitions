# Competitions Person History Provider Design

## Goal

Expose a stable, read-only public Competitions service that returns a person's competition history by People `person_uuid`, so People can render a Competitions tab without reading Competitions private tables or duplicating competition-owned data.

## Domain ownership

Competitions remains the sole owner of player records, roster memberships, teams, participations, seasons, tournaments, sport roles, shirt numbers and competition statuses.

People remains the owner of person master data. This feature does not move competition state into People and does not introduce writes from People into Competitions.

The cross-product key is the existing `person_uuid` stored on `#__xdecarocompetitions_players`. No People numeric ID is introduced.

## Public component surface

`CompetitionsComponent` adds a provider-owned public getter:

```php
public function getPersonHistoryService(): PersonHistoryService;
```

The service is registered through the component service provider in the same style as the existing public integration services.

The service exposes:

```php
final class PersonHistoryService
{
    public function getHistoryByPersonUuid(string $personUuid): array;
}
```

This is a read-only contract. It must not update players, rosters, participations or any other table.

## Query model

The provider resolves `person_uuid` through the Competitions player row and then joins the competition-owned history graph:

```text
players
  -> rosters
  -> participations
  -> teams
  -> seasons
  -> tournaments
```

The query returns one row per roster membership. A person appearing in multiple seasons, teams or competitions therefore receives multiple history rows.

The provider must not collapse separate roster memberships into one row.

Rows are ordered newest first using the best available competition chronology:

1. `seasons.start_date` descending when available;
2. `seasons.season_year` descending;
3. `seasons.id` descending;
4. `rosters.id` descending as a deterministic final tie-breaker.

## Normalized return shape

Each row returned by `getHistoryByPersonUuid()` has exactly these public keys:

```php
[
    'player_id' => 123,
    'roster_id' => 456,
    'participation_id' => 789,
    'team_id' => 10,
    'team_name' => 'Example Team',
    'season_id' => 20,
    'season_name' => '2026',
    'season_year' => 2026,
    'tournament_id' => 30,
    'competition_name' => 'Example Cup',
    'role' => 'player',
    'shirt_number' => 7,
    'status' => 'approved',
    'start_date' => '2026-09-01',
    'end_date' => '2026-09-07',
]
```

Type normalization:

- IDs are integers;
- `season_year` and `shirt_number` are integers when present, otherwise `null`;
- names, role, status and dates are strings when present, otherwise `null`;
- no extra internal columns are exposed through the public response.

`status` represents the roster membership status because the People tab describes the person's participation in that edition. Participation-level review state remains internal for this first version.

An empty or whitespace-only UUID returns `[]` without querying.

The UUID is trimmed and lowercased before lookup.

## State handling

Historical visibility is based on persisted roster history, not only currently active rows. The provider must include roster memberships that remain legitimate historical records even if a season has ended.

Rows whose underlying player or roster record has been explicitly trashed/deleted according to the component's existing state conventions are excluded. The implementation must follow the repository's current state semantics rather than inventing a new archive flag.

No historical snapshot table is introduced in this feature. Display names are resolved from the current Competitions entities at query time.

## Optional consumer behavior

Competitions does not depend on People to serve the history method: it accepts a UUID string and queries its own data.

People may optionally consume this service by booting `com_competitions`. Competitions must not boot People from inside `PersonHistoryService` and must not create a circular dependency.

The existing `PeopleIntegrationService` used by Competitions for player selection/autofill remains unchanged in responsibility. `PersonHistoryService` is a separate provider because it serves Competitions-owned data outward rather than reading People-owned data inward.

## Security

The service returns only competition-domain data required for the history UI. It must not return birth date, nationality copied for player workflow, disability, accessibility, tax, residence, contact data, or any other People-sensitive values.

Database access uses Joomla's database abstraction, bound UUID parameters and `#__` table names.

The provider is read-only and performs no state-changing operation, so no CSRF token is required for direct in-process use.

Normal Joomla administrator authorization remains the responsibility of the consuming UI. No frontend public endpoint is added by this feature.

## Performance

The history is fetched in one bounded join query per person view. Do not query teams, seasons or tournaments inside a PHP loop.

The existing unique/indexed `person_uuid` player lookup should be used. If implementation inspection shows the existing index is absent or insufficient on supported installed schemas, add a non-destructive migration rather than relying on a table scan.

## Compatibility

Existing player and roster data must be preserved.

No destructive schema recreation is allowed.

The service is additive to the public component surface and does not change the current People picker/autofill contract.

Competitions remains usable when People is absent because this outward provider operates only on Competitions-owned stored UUID values.

## Testing

Contract and runtime tests must cover:

- `CompetitionsComponent::getPersonHistoryService()` exists and returns the registered service;
- empty UUID returns `[]`;
- UUID lookup is normalized by trim/lowercase;
- a linked player with one roster returns the expected normalized row;
- multiple roster memberships return multiple rows ordered newest first;
- team, season and tournament names are joined without N+1 queries;
- nullable role/shirt number/date values remain `null`;
- trashed/deleted records are excluded according to existing state semantics;
- the public row does not expose People-sensitive or internal-only columns;
- existing People player picker/autofill tests remain green;
- clean installation and update installation on the supported Joomla runtime preserve existing data.

## Out of scope

This feature does not expose match-by-match events, goals, cards, standings, transfers, aggregate statistics, editing actions, frontend profile pages or automatic synchronization into People.

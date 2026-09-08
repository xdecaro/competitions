# Competitions integration with Draw

## Purpose

Draw is a separate optional Xdecaro component responsible for controlled draws, group composition, pairings and bracket-slot assignments.

Competitions remains the authoritative owner of competitions, participants, phases, groups, brackets, fixtures, matches, standings and sports rules.

The integration direction is intentionally loose:

`Competitions -> Draw public integration contract`

Competitions must continue to install and function when Draw is not installed.

## Responsibilities

### Competitions owns

- competition eligibility;
- authoritative team/participant records;
- phases and competition structure;
- group entities and membership after import/apply;
- bracket entities and match generation;
- sport-specific qualification logic;
- standings/ranking/tie-break rules;
- permission to apply a Draw result to competition data.

### Draw owns

- draw session lifecycle;
- normalized draw input snapshot;
- pots/fasce and seeding;
- supported draw constraints;
- server-side draw execution;
- result assignment sequence;
- draw audit/history;
- public live presentation;
- reveal/move animations;
- published Draw result payload.

Neither product may use the other product's private tables as its integration API.

## Core references

Use Xdecaro Core `EntityReference` for stable cross-product identity where available.

Current Competitions technical element is retained for compatibility as `com_decarodcl`.

Examples:

```json
{
  "component": "com_decarodcl",
  "entity": "competition",
  "id": "42"
}
```

```json
{
  "component": "com_decarodcl",
  "entity": "participant",
  "id": "10"
}
```

These references do not expose table names and do not grant authorization by themselves.

## Preparing a draw

When an authorized Competition administrator opens automatic/live draw functionality:

1. verify that Draw is installed and compatible;
2. verify the current user may manage the competition and requested phase;
3. select only eligible participants;
4. export a normalized participant payload;
5. include only metadata required by the chosen constraints;
6. create/open a Draw session through its documented API/service;
7. retain the returned Draw reference/integration key where useful.

Do not let Draw independently discover participants by querying Competitions tables.

## Applying the result

A Draw result must not be applied blindly.

Before changing Competitions data:

1. validate supported Draw result schema/version;
2. verify that the result belongs to the intended competition/phase;
3. verify that every referenced participant still exists and is eligible;
4. verify that no participant appears more than permitted;
5. verify target keys/slots are valid for the intended operation;
6. verify current ACL again;
7. protect the operation against duplicate application;
8. perform the Competitions changes transactionally where practical;
9. record the external Draw result identifier/reference for audit.

If validation fails, do not partially apply the result.

## Groups

For group mode, Draw may return generic assignments such as:

`participant 10 -> group A, position 1`

Competitions maps that result into its own group and group-membership model.

Draw does not create or modify Competitions group rows directly.

## Brackets and pairings

For bracket or pairing mode, Draw may return generic slot/pairing descriptors.

Competitions remains responsible for:

- translating slots into its bracket model;
- creating/updating fixtures or matches;
- home/away rules;
- legs/round rules;
- qualification progression;
- competition-specific scheduling.

Do not move these rules to Draw simply because the draw selected the initial slot.

## Manual fallback

When Draw is not installed or is incompatible:

- hide/disable automatic/live draw actions with a clear administrator message;
- preserve existing manual group/bracket/pairing workflows;
- do not produce fatal errors;
- do not make Competitions installation dependent on Draw.

## Live public view

The public live experience belongs to Draw.

Competitions may link to or embed a documented Draw public view, but must not duplicate Draw's reveal engine.

The Draw live view may display the competition branding/data passed through the integration contract, while Competitions remains the owner of the underlying competition entity.

## UI entry point

Recommended Competitions UX:

- phase/competition toolbar action: `Sorteggio`;
- if Draw is available: open/create the linked Draw session;
- if a published result exists: show `Risultato sorteggio` and application status;
- if Draw is unavailable: explain that automatic/live draw requires Draw while keeping manual configuration available.

Avoid adding a second full Draw management interface inside Competitions.

## Idempotency

Applying the same Draw result twice must not duplicate groups, participants or bracket slots.

Store enough integration metadata to identify an already-applied result.

If the source participants or competition structure changed after the Draw result was produced, require revalidation before reapplying.

## Re-draw/restart

A re-draw is a Draw-domain operation, but applying it can affect Competitions data.

Competitions should not silently overwrite an already-applied published result.

A replacement result requires an explicit administrator action and should preserve an audit trail linking the previous and new Draw references where possible.

If matches/results have already started, Competitions must enforce its own business rules before allowing group/bracket replacement.

## Security

Competitions must enforce server-side ACL both when exporting participants and when applying a result.

Do not treat a Draw reference/result URL as authorization.

State-changing requests require Joomla CSRF protection and server-side validation.

## Dependency policy

Draw is optional for Competitions.

Xdecaro Core may be used by both products through its public APIs, but no circular dependency is allowed.

Correct:

`Competitions -> Core`

`Draw -> Core`

`Competitions -> optional Draw contract`

Incorrect:

`Core -> Draw`

`Draw -> Competitions private internals`

`Competitions -> Draw private internals`

## Regression checks

When implementing this integration verify at minimum:

- Competitions works with Draw absent;
- Draw availability/version detection;
- ACL and CSRF;
- participant export correctness;
- changed participant detection;
- result schema validation;
- duplicate-apply protection;
- group mapping;
- bracket/pairing mapping when supported;
- no direct cross-product table access;
- no partial application on validation failure;
- administrator UX on desktop/tablet/smartphone;
- light/dark mode;
- PHP errors/warnings;
- JavaScript Console.

This document defines the architectural boundary only. The actual adapter must be implemented incrementally in the Competitions project after inspecting the real current phase/group/bracket code.

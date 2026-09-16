# Competitions Person History Provider Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Expose a read-only Competitions public API that returns roster history for a People `person_uuid`.

**Architecture:** Competitions owns the query and normalized history rows. The component advertises `competitions.people_history` through the existing Core capability registry and exposes `PersonHistoryService`; consumers never read Competitions tables directly.

**Tech Stack:** PHP 8.3, Joomla 6.1.3, Joomla Database API, xdecaro Core CapabilityRegistry, GitHub Actions.

**Spec:** `docs/superpowers/specs/2026-09-16-people-history-provider-design.md`

## Global Constraints

- Competition-domain data remains owned by Competitions.
- Cross-product key is the existing People `person_uuid`.
- Provider is read-only and returns no sensitive People data.
- Query history in one joined database query; no N+1 lookups.
- Existing People picker/autofill behavior must remain unchanged.
- Existing data must be preserved; no destructive migration.

---

### Task 1: Add failing provider contract

**Files:**
- Create: `tests/person-history-contract.php`
- Modify: `.github/workflows/ci.yml`

**Interfaces:**
- Produces requirement for `PersonHistoryService::getHistoryByPersonUuid(string): array`, `CompetitionsComponent::getPersonHistoryService()`, and capability `competitions.people_history` v1.

- [ ] **Step 1: Write the failing test** checking service file/class/method, component getter, provider wiring, capability registration, exact public row keys, and absence of People-sensitive keys.
- [ ] **Step 2: Add the test to CI validate** with `php tests/person-history-contract.php`.
- [ ] **Step 3: Run CI and confirm RED** because the service/getter/capability do not yet exist.

### Task 2: Implement provider and public wiring

**Files:**
- Create: `component/admin/src/Service/PersonHistoryService.php`
- Modify: `component/admin/src/Service/CoreIntegrationService.php`
- Modify: `component/admin/src/Extension/CompetitionsComponent.php`
- Modify: `component/admin/services/provider.php`

**Interfaces:**
- Consumes: existing `DatabaseInterface`, existing `person_uuid` column/index.
- Produces: `getHistoryByPersonUuid(string): array`, component getter, capability `competitions.people_history` v1.

- [ ] **Step 1: Implement one bound join query** across players, rosters, participations, teams, seasons and tournaments.
- [ ] **Step 2: Normalize UUID with `trim` + `strtolower`; empty UUID returns `[]` without querying.**
- [ ] **Step 3: Exclude player/roster rows with `state = -2`; retain ended/unpublished historical records.**
- [ ] **Step 4: Normalize output types and exact public keys.**
- [ ] **Step 5: Register service in DI/component and add Core capability.**
- [ ] **Step 6: Run contract CI and confirm GREEN.**

### Task 3: Add Joomla runtime history probe

**Files:**
- Modify: `.github/workflows/ci.yml`

**Interfaces:**
- Consumes: installed Competitions package and its public component service.
- Produces: runtime evidence for joined history, ordering, null normalization and trash filtering.

- [ ] **Step 1: Extend the Joomla runtime workflow** to create tournament/season/team/player/participation/roster fixtures and call `getPersonHistoryService()`.
- [ ] **Step 2: Assert two memberships return newest-first with expected names/IDs/status and nullable values.**
- [ ] **Step 3: Mark one roster trashed and assert it disappears.**
- [ ] **Step 4: Run CI and confirm runtime GREEN.**

### Task 4: Version and documentation coherence

**Files:**
- Modify version-bearing manifest/update/changelog files required by repository validation.
- Modify: `CHANGELOG.md`
- Modify: `VERSION`
- Add non-destructive SQL update file only if repository schema-version validation requires it.

**Interfaces:**
- Produces: Competitions `1.5.0` installable package metadata.

- [ ] **Step 1: Bump feature release to 1.5.0 consistently.**
- [ ] **Step 2: Document the public People-history capability/service.**
- [ ] **Step 3: Run complete CI and deterministic build.**
- [ ] **Step 4: Review diff against spec and ensure no unrelated refactor.**

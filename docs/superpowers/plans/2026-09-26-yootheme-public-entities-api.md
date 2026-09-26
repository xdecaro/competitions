# YOOtheme Public Entities API Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Extend Competitions with a safe read-only public API for federation, team and approved roster/player presentation in YOOtheme.

**Architecture:** `PublicBuilderDataService` remains the only public presentation boundary owned by Competitions. It may query only Competitions tables and must fail closed on publication/approval; the YOOtheme addon consumes these methods without private SQL.

**Tech Stack:** Joomla 6.1.3+, PHP 8.2+, Joomla Database API, existing Competitions build/test tooling.

**Spec:** `xdecaro/addons-for-yootheme-pro:docs/superpowers/specs/2026-09-26-competitions-entities-yootheme-design.md`

## Global Constraints

- Joomla 6.1.3 and later only.
- Vendor remains lowercase `xdecaro`.
- No direct People/Organizations private-table queries.
- Public roster/player output excludes birth date, external references, person UUID, review notes and medical/ISCD data.
- Only published and approved public entities are returned.
- Current main version is 1.5.23; use 1.5.24 if still unused at execution time, otherwise the next unused SemVer patch.

## Review Focus

- Pending/rejected roster rows must never appear in public output.
- Pending/rejected players must never appear even when the roster row itself is approved.
- A team that is unpublished or not approved must not leak through roster joins.
- Invalid season/team/federation/roster IDs must return empty/null, never broad unfiltered data.
- Public row arrays must omit every sensitive field even if those columns exist in joined tables.

---

### Task 1: Pin the public entity contract

**Files:**
- Create: `tests/public-builder-entities-contract.php`
- Modify: `.github/workflows/ci.yml`

**Interfaces:**
- Consumes: existing `PublicBuilderDataService`.
- Produces: executable contract requiring the new public methods and privacy filters.

- [ ] **Step 1: Write the failing contract test**

Require these exact signatures in `PublicBuilderDataService`:

```php
getFederation(int $federationId): ?array
getTeams(?int $federationId = null, int $limit = 250): array
getFederationTeams(int $federationId, int $limit = 250): array
getRoster(int $seasonId, int $teamId, bool $approvedOnly = true): array
getRosterPlayer(int $rosterId): ?array
```

Assert source-level guards for `state = 1`, team/player approval `approved`, participation/roster approval in public paths, and absence of People/Organizations private table names plus sensitive selected columns (`birth_date`, `external_ref`, `person_uuid`, `review_note`).

- [ ] **Step 2: Run test and confirm RED**

Run: `php tests/public-builder-entities-contract.php`

Expected: FAIL because one or more required methods are missing.

- [ ] **Step 3: Add test to CI**

Update `.github/workflows/ci.yml` so the new contract runs with the existing Competitions contracts.

- [ ] **Step 4: Commit RED test**

```bash
git add tests/public-builder-entities-contract.php .github/workflows/ci.yml
git commit -m "test: define public federation team roster contract"
```

### Task 2: Add federation and approved-team public reads

**Files:**
- Modify: `component/admin/src/Service/PublicBuilderDataService.php`
- Test: `tests/public-builder-entities-contract.php`

**Interfaces:**
- Consumes: Joomla `DatabaseInterface` already injected into the service.
- Produces: `getFederation()`, `getTeams()`, `getFederationTeams()`.

- [ ] **Step 1: Add contract assertions for federation/team output**

Assert federation output can include `id`, `country_id`, `country_name`, `country_code`, `name`, `short_name`, `logo`, `website`; team output uses the existing normalized public team shape and requires `state = 1` plus `approval_status = approved`.

- [ ] **Step 2: Run contract and confirm RED**

Run: `php tests/public-builder-entities-contract.php`

Expected: FAIL on missing federation/team implementations.

- [ ] **Step 3: Implement federation/team methods**

Implement exactly:

```php
public function getFederation(int $federationId): ?array
public function getTeams(?int $federationId = null, int $limit = 250): array
public function getFederationTeams(int $federationId, int $limit = 250): array
```

Use only `#__xdecarocompetitions_federations`, `#__xdecarocompetitions_countries`, and `#__xdecarocompetitions_teams`. Invalid positive-ID requirements must return null/empty before querying broadly.

- [ ] **Step 4: Run contract and PHP lint**

Run:

```bash
php tests/public-builder-entities-contract.php
php -l component/admin/src/Service/PublicBuilderDataService.php
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add component/admin/src/Service/PublicBuilderDataService.php tests/public-builder-entities-contract.php
git commit -m "feat: expose public federations and approved teams"
```

### Task 3: Add safe approved roster/player public reads

**Files:**
- Modify: `component/admin/src/Service/PublicBuilderDataService.php`
- Test: `tests/public-builder-entities-contract.php`

**Interfaces:**
- Consumes: season/team IDs and Competitions-owned roster/player/participation tables.
- Produces: `getRoster()` and `getRosterPlayer()` with the exact safe public row contract.

Public roster row keys:

```text
roster_id
player_id
first_name
last_name
display_name
nationality_code
shirt_number
role
photo
team_id
team_name
season_id
```

- [ ] **Step 1: Strengthen the failing test for roster filters and field allowlist**

Assert implementation joins Competitions roster/participation/player/team data, filters published rows, approved team/player, and approved participation/roster in public output. Assert forbidden fields are not selected or returned.

- [ ] **Step 2: Run test and confirm RED**

Run: `php tests/public-builder-entities-contract.php`

Expected: FAIL on missing roster methods.

- [ ] **Step 3: Implement `getRoster()`**

Signature:

```php
public function getRoster(int $seasonId, int $teamId, bool $approvedOnly = true): array
```

Return `[]` for invalid IDs. When `$approvedOnly` is true, require approved participation and roster rows. Always require published team/player/roster/participation and approved team/player.

- [ ] **Step 4: Implement `getRosterPlayer()`**

Signature:

```php
public function getRosterPlayer(int $rosterId): ?array
```

This is a public single-record lookup and therefore always uses the fully approved/public filters. Return `null` for invalid/unavailable rows.

- [ ] **Step 5: Normalize the safe public roster row**

Create one focused private normalizer used by both methods; construct `display_name` from first + last name and expose only the allowlisted keys above.

- [ ] **Step 6: Run contract and lint**

Run:

```bash
php tests/public-builder-entities-contract.php
php -l component/admin/src/Service/PublicBuilderDataService.php
```

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add component/admin/src/Service/PublicBuilderDataService.php tests/public-builder-entities-contract.php
git commit -m "feat: expose approved public rosters"
```

### Task 4: Version, package and regression verification

**Files:**
- Modify: `VERSION`
- Modify: `component/xdecarocompetitions.xml`
- Modify: `package/pkg_xdecarocompetitions.xml`
- Modify: `updates/pkg_xdecarocompetitions.xml`
- Modify: `CHANGELOG.md`
- Modify: `README.md` only if the public API documentation needs a consumer note.

**Interfaces:**
- Consumes: completed public API tasks.
- Produces: an installable Competitions release exposing the new stable methods.

- [ ] **Step 1: Re-check current released version**

Run: `cat VERSION` and inspect tags/releases/update feed. If `1.5.24` is unused, select `1.5.24`; otherwise choose the next unused patch and use it consistently.

- [ ] **Step 2: Update all version metadata and changelog**

Document only the new public presentation API; do not claim changes to approval workflows.

- [ ] **Step 3: Run the complete Competitions test suite**

Run the repository’s existing CI-equivalent commands plus:

```bash
php tests/public-builder-entities-contract.php
find component package plugins modules tests -type f -name '*.php' -print0 | xargs -0 -n1 php -l
```

Expected: all PASS/no syntax errors.

- [ ] **Step 4: Build and validate the package**

Run the repository’s canonical `tools/build.py` and `tools/validate_release.py` flow for the selected version.

Expected: package integrity and release validation PASS.

- [ ] **Step 5: Commit release candidate changes**

Commit only after all checks pass.

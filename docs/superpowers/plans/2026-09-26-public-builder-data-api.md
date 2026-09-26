# Public Builder Data API Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a stable, read-only public data service to Competitions so external presentation integrations such as Essential Addons/YOOtheme can query public competition, season, team, match, calendar and standings data without depending on internal admin models or raw table details.

**Architecture:** Keep all competition-domain logic in Competitions. Add one focused public service under `component/admin/src/Service/` that accepts Joomla's `DatabaseInterface`, delegates to existing models/services where stable logic already exists, and returns sanitized arrays suitable for external read-only consumers. The service must not reference YOOtheme or Essential Addons.

**Tech Stack:** Joomla 6.1.3 target runtime, PHP 8.3 target runtime, Joomla DatabaseInterface, existing Competitions tables/services, repository contract-test style.

**Spec:** `https://github.com/xdecaro/addons-for-yootheme-pro/blob/main/docs/competitions-integration.md`

## Global Constraints

- Competitions remains the owner of tournament, season, participation, match, result, standing and ranking business logic.
- Do not add any YOOtheme classes or imports to Competitions.
- Read-only public API only; no mutations.
- Never expose private emails/phones, review notes, rejection reasons, user IDs, medical/membership/card data or other private People data.
- Public queries default to active/published records and approved participation where applicable.
- Use `#__` table names and bound Joomla queries.
- Avoid N+1 queries; joins or bulk lookup maps are required for related team/federation data.
- Current GitHub `VERSION` is 1.5.23; do not bump or reuse a release version until the implementation worker reconciles this branch with any newer installed/test package.

## Review Focus

- Missing/NULL dates: competition temporal status must remain deterministic and must not classify an undated edition as current by accident.
- Missing related federation/team rows: public queries must return partial safe data rather than fatal errors.
- Approval/state filters: draft, rejected or disabled participations must not leak into default public output.
- Large season/team sets: no per-row queries when enriching teams/federations or counts.
- Schema drift: external consumers need a stable public key set even when optional columns are NULL.

---

### Task 1: Add public competition/season query service

**Files:**
- Create: `component/admin/src/Service/PublicBuilderDataService.php`
- Create: `tests/public-builder-data-service-contract.php`

**Interfaces:**
- Consumes: `Joomla\Database\DatabaseInterface` in the constructor.
- Produces:
  - `public function getCurrentCompetitions(int $limit = 12): array`
  - `public function getUpcomingCompetitions(int $limit = 12): array`
  - `public function getPreviousCompetitions(int $limit = 12): array`
  - `public function getCompetitionsByYear(int $year, int $limit = 50): array`
  - `public function getCompetitionsByTournament(int $tournamentId, int $limit = 50): array`
  - `public function getUpcomingSeasons(int $fromYear, int $toYear, int $limit = 100): array`

- [ ] **Step 1: Write the failing contract test**

Create `tests/public-builder-data-service-contract.php` and assert that `PublicBuilderDataService.php` exists, declares the exact public methods above, imports `DatabaseInterface`, contains no `YOOtheme` reference, and returns a stable competition field contract containing at least `season_id`, `tournament_id`, `title`, `tournament_name`, `tournament_code`, `discipline`, `gender`, `season_name`, `season_year`, `host_city`, `host_country_code`, `start_date`, `end_date`, `temporal_status`, `team_count`.

- [ ] **Step 2: Run the contract test and verify it fails**

Run: `php tests/public-builder-data-service-contract.php`

Expected: FAIL because `PublicBuilderDataService.php` does not exist.

- [ ] **Step 3: Implement `PublicBuilderDataService::__construct(DatabaseInterface $db)` and the competition/season methods**

Use `#__xdecarocompetitions_tournaments`, `#__xdecarocompetitions_seasons` and a grouped approved/active participation count. `title` is display data derived from the real tournament name plus season year/name without altering stored data. Define temporal status from dates using one shared private method; if both dates are absent, do not label the record current.

- [ ] **Step 4: Add review-focus assertions to the contract test**

Assert that the implementation explicitly filters active tournament/season rows, uses approved/active participation filtering for `team_count`, has a deterministic undated-state branch, and does not select review/private columns.

- [ ] **Step 5: Run PHP syntax + contract**

Run:

```bash
php -l component/admin/src/Service/PublicBuilderDataService.php
php tests/public-builder-data-service-contract.php
```

Expected: both PASS.

- [ ] **Step 6: Commit**

```bash
git add component/admin/src/Service/PublicBuilderDataService.php tests/public-builder-data-service-contract.php
git commit -m "feat: add public competition data service"
```

### Task 2: Add public team/participation queries

**Files:**
- Modify: `component/admin/src/Service/PublicBuilderDataService.php`
- Modify: `tests/public-builder-data-service-contract.php`

**Interfaces:**
- Consumes: Task 1 service and DB dependency.
- Produces:
  - `public function getParticipatingTeams(int $seasonId, bool $approvedOnly = true): array`
  - `public function getTeam(int $teamId): ?array`

- [ ] **Step 1: Extend the failing contract test**

Assert the two methods exist and the public team field contract contains `id`, `name`, `short_name`, `alias`, `logo`, `country_code`, `city`, `federation_id`, `federation_name`, `federation_short_name` but not `email`, `phone`, `owner_user_id`, `review_note`, `rejection_reason`.

- [ ] **Step 2: Run the contract and verify failure**

Run: `php tests/public-builder-data-service-contract.php`

Expected: FAIL because team methods are not implemented.

- [ ] **Step 3: Implement the team queries with joins**

Use one query per call joining teams, participations and federations as needed. Default `approvedOnly=true` must require active participation and approved/public status. Do not issue a federation query inside a team loop.

- [ ] **Step 4: Add review-focus assertions**

Pin missing federation behavior and ensure a team can still be returned with `federation_name`/`federation_short_name` as NULL.

- [ ] **Step 5: Run syntax + contract**

Run the same commands as Task 1.

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add component/admin/src/Service/PublicBuilderDataService.php tests/public-builder-data-service-contract.php
git commit -m "feat: expose public competition teams"
```

### Task 3: Add public match/result queries

**Files:**
- Modify: `component/admin/src/Service/PublicBuilderDataService.php`
- Modify: `tests/public-builder-data-service-contract.php`
- Reference while implementing: the real matches table definition and any existing match/result model/service on the implementation branch.

**Interfaces:**
- Produces:
  - `public function getUpcomingMatches(?int $seasonId = null, ?int $teamId = null, int $limit = 20): array`
  - `public function getLatestResults(?int $seasonId = null, ?int $teamId = null, int $limit = 20): array`
  - `public function getMatchesBySeason(int $seasonId, int $limit = 100): array`
  - `public function getMatch(int $matchId): ?array`

- [ ] **Step 1: Inspect the implementation branch's current match schema and existing match/result logic**

Do not copy field names from this plan. Record the actual public-safe match columns and any existing status/scoring normalization already used by Competitions.

- [ ] **Step 2: Extend the contract test with the four method signatures**

Assert that implementation uses the inspected real schema and that no internal notes/user IDs are part of the output contract.

- [ ] **Step 3: Run the contract and verify failure**

Run: `php tests/public-builder-data-service-contract.php`

Expected: FAIL on missing match methods.

- [ ] **Step 4: Implement match queries**

Use real Competitions status/date/result semantics. Enrich home/away team names/logos in joins, not per-match queries. `getUpcomingMatches()` must exclude completed/past matches according to the existing domain rules; `getLatestResults()` must return only completed/result-bearing matches according to those same rules.

- [ ] **Step 5: Add edge assertions**

Pin NULL venue/team relation behavior, limit clamping, and season/team filters. A broken relation must not expose private data or fatal.

- [ ] **Step 6: Run syntax + contract**

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add component/admin/src/Service/PublicBuilderDataService.php tests/public-builder-data-service-contract.php
git commit -m "feat: expose public competition matches"
```

### Task 4: Add public standings/ranking adapter

**Files:**
- Modify: `component/admin/src/Service/PublicBuilderDataService.php`
- Modify: `tests/public-builder-data-service-contract.php`
- Reference: existing standings/ranking/scoring services or models on the implementation branch.

**Interfaces:**
- Produces:
  - `public function getStandings(int $seasonId): array`
  - `public function getRankings(?int $seasonId = null, int $limit = 100): array`

- [ ] **Step 1: Locate the canonical Competitions standings/ranking implementation**

Document in the code comment which existing service/model is authoritative. Do not implement a second scoring algorithm in this service.

- [ ] **Step 2: Extend the contract test**

Assert both public signatures and assert the service references/delegates to the existing authoritative standings/ranking logic rather than embedding points/tie-break formulas.

- [ ] **Step 3: Run the contract and verify failure**

Expected: FAIL.

- [ ] **Step 4: Implement adapters**

Normalize the canonical result to stable public arrays. Keep public fields limited to ranking position, team identity/display fields and public statistics actually produced by Competitions.

- [ ] **Step 5: Run syntax + contract**

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add component/admin/src/Service/PublicBuilderDataService.php tests/public-builder-data-service-contract.php
git commit -m "feat: expose public standings data"
```

### Task 5: Add public countries/federations queries

**Files:**
- Modify: `component/admin/src/Service/PublicBuilderDataService.php`
- Modify: `tests/public-builder-data-service-contract.php`

**Interfaces:**
- Produces:
  - `public function getCountries(int $limit = 250): array`
  - `public function getFederations(?int $countryId = null, int $limit = 250): array`

- [ ] **Step 1: Extend the contract test**

Assert both methods and active-state filtering. Country public keys: `id`, `name`, `code`, `iso2`, `iso3`, `entity_type`, `flag`. Federation public keys: `id`, `country_id`, `name`, `short_name`, `logo`, `website`.

- [ ] **Step 2: Run and verify failure**

Expected: FAIL.

- [ ] **Step 3: Implement bulk read-only queries**

Do not expose federation `email` by default.

- [ ] **Step 4: Run syntax + contract**

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add component/admin/src/Service/PublicBuilderDataService.php tests/public-builder-data-service-contract.php
git commit -m "feat: expose public competition geography"
```

### Task 6: Verify API stability and repository regressions

**Files:**
- Modify only if required by existing repository validation/release tooling.

**Interfaces:**
- Produces: a verified public service class that external plugins can instantiate with `new PublicBuilderDataService($db)`.

- [ ] **Step 1: Run all repository contract tests**

Run the repository's normal test loop over `tests/*.php` using the same command/pattern already used by the project tooling or CI.

Expected: all PASS.

- [ ] **Step 2: Run PHP syntax over changed files and package validation/build**

Use the repository's existing package/validation commands; do not invent a new release pipeline.

Expected: PASS with no PHP warnings/errors.

- [ ] **Step 3: Smoke-test against Joomla 6.1.3 + PHP 8.3**

Verify service construction and representative calls for current competitions, future 3–5 year seasons, participating teams, next matches, latest results and standings using real test data.

- [ ] **Step 4: Verify privacy manually**

Inspect returned arrays and confirm there are no emails/phones/review notes/rejection reasons/user IDs/medical or membership/card fields.

- [ ] **Step 5: Reconcile SemVer before release**

Compare repository `VERSION` (currently 1.5.23 on main at plan time) with the newest installed/test artifact. Choose the next unused version only after that check; update all package/version metadata coherently.

- [ ] **Step 6: Commit release-preparation changes separately**

Use a release-specific commit only after the functional branch is verified.

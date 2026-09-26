<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$servicePath = $root . '/component/admin/src/Service/PublicBuilderDataService.php';
$source = file_get_contents($servicePath);

if ($source === false) {
    fwrite(STDERR, "PublicBuilderDataService.php could not be read.\n");
    exit(1);
}

$failures = [];

$requiredSignatures = [
    'getFederation' => 'public function getFederation(int $federationId): ?array',
    'getTeams' => 'public function getTeams(?int $federationId = null, int $limit = 250): array',
    'getFederationTeams' => 'public function getFederationTeams(int $federationId, int $limit = 250): array',
    'getRoster' => 'public function getRoster(int $seasonId, int $teamId, bool $approvedOnly = true): array',
    'getRosterPlayer' => 'public function getRosterPlayer(int $rosterId): ?array',
];

foreach ($requiredSignatures as $name => $signature) {
    if (!str_contains($source, $signature)) {
        $failures[] = "Missing public method signature: {$name}";
    }
}

foreach (['#__xdecaropeople_', '#__xdecaroorganizations_'] as $privateTablePrefix) {
    if (str_contains($source, $privateTablePrefix)) {
        $failures[] = "Public builder service must not access private table prefix {$privateTablePrefix}";
    }
}

$requiredGuards = [
    "tm.approval_status",
    "pl.approval_status",
    "p.status",
    "r.status",
    "tm.state",
    "pl.state",
    "p.state",
    "r.state",
    "approved",
];

foreach ($requiredGuards as $guard) {
    if (!str_contains($source, $guard)) {
        $failures[] = "Missing public approval/publication guard: {$guard}";
    }
}

$forbiddenSelections = [
    "quoteName('pl.birth_date'",
    "quoteName('pl.external_ref'",
    "quoteName('pl.person_uuid'",
    "quoteName('r.review_note'",
];

foreach ($forbiddenSelections as $forbidden) {
    if (str_contains($source, $forbidden)) {
        $failures[] = "Sensitive field must not be selected by public builder service: {$forbidden}";
    }
}

$requiredRosterKeys = [
    'roster_id',
    'player_id',
    'first_name',
    'last_name',
    'display_name',
    'nationality_code',
    'shirt_number',
    'role',
    'photo',
    'team_id',
    'team_name',
    'season_id',
];

foreach ($requiredRosterKeys as $key) {
    if (!str_contains($source, "'{$key}'")) {
        $failures[] = "Missing safe public roster key: {$key}";
    }
}

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, "FAIL: {$failure}\n");
    }
    exit(1);
}

echo "Public builder entities contract OK\n";

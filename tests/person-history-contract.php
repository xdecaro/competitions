<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$servicePath = $root . '/component/admin/src/Service/PersonHistoryService.php';
$componentPath = $root . '/component/admin/src/Extension/CompetitionsComponent.php';
$providerPath = $root . '/component/admin/services/provider.php';
$corePath = $root . '/component/admin/src/Service/CoreIntegrationService.php';

$fail = static function (string $message): never {
    fwrite(STDERR, $message . "\n");
    exit(1);
};

if (!is_file($servicePath)) {
    $fail('PersonHistoryService.php is missing.');
}

$service = file_get_contents($servicePath);
$component = file_get_contents($componentPath);
$provider = file_get_contents($providerPath);
$core = file_get_contents($corePath);

foreach ([$service, $component, $provider, $core] as $source) {
    if ($source === false) {
        $fail('Unable to read a required source file.');
    }
}

$requiredServiceFragments = [
    'final class PersonHistoryService',
    'function getHistoryByPersonUuid(string $personUuid): array',
    '#__xdecarocompetitions_players',
    '#__xdecarocompetitions_rosters',
    '#__xdecarocompetitions_participations',
    '#__xdecarocompetitions_teams',
    '#__xdecarocompetitions_seasons',
    '#__xdecarocompetitions_tournaments',
    'strtolower(trim($personUuid))',
    "'player_id'",
    "'roster_id'",
    "'participation_id'",
    "'team_id'",
    "'team_name'",
    "'season_id'",
    "'season_name'",
    "'season_year'",
    "'tournament_id'",
    "'competition_name'",
    "'role'",
    "'shirt_number'",
    "'status'",
    "'start_date'",
    "'end_date'",
];

foreach ($requiredServiceFragments as $fragment) {
    if (!str_contains($service, $fragment)) {
        $fail('Missing PersonHistoryService contract fragment: ' . $fragment);
    }
}

foreach (['birth_date', 'nationality_code', 'disability', 'tax_identifier', 'email', 'phone'] as $forbidden) {
    if (preg_match('/[\'\"]' . preg_quote($forbidden, '/') . '[\'\"]\s*=>/', $service)) {
        $fail('Person history public output exposes forbidden field: ' . $forbidden);
    }
}

if (!str_contains($component, 'getPersonHistoryService(): PersonHistoryService')) {
    $fail('CompetitionsComponent public PersonHistoryService getter is missing.');
}
if (!str_contains($provider, 'PersonHistoryService::class')) {
    $fail('PersonHistoryService is not wired into the component service provider.');
}
if (!str_contains($core, "new Capability(self::COMPONENT, 'competitions.people_history', '1')")) {
    $fail('competitions.people_history v1 capability is missing.');
}

$workflow = file_get_contents($root . '/.github/workflows/person-history-contract.yml');
if ($workflow === false || !str_contains($workflow, 'php tests/person-history-contract.php')) {
    $fail('Person history contract is not executed by its CI workflow.');
}

echo "Competitions person history contract OK\n";

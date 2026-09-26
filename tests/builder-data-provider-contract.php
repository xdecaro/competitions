<?php

$root = dirname(__DIR__);
$service = $root . '/component/admin/src/Service/BuilderDataProviderService.php';
$component = $root . '/component/admin/src/Extension/CompetitionsComponent.php';
$provider = $root . '/component/admin/services/provider.php';

foreach ([$service, $component, $provider] as $file) {
    if (!is_file($file)) {
        fwrite(STDERR, "Missing required file: {$file}\n");
        exit(1);
    }
}

$serviceSource = file_get_contents($service);
$componentSource = file_get_contents($component);
$providerSource = file_get_contents($provider);

foreach ([
    'getCurrentCompetitions',
    'getUpcomingCompetitions',
    'getPreviousCompetitions',
    'getUpcomingMatches',
    'getLatestResults',
    'getParticipatingTeams',
    'getCountries',
    'getFederations',
] as $method) {
    if (!str_contains($serviceSource, "function {$method}(")) {
        fwrite(STDERR, "Missing BuilderDataProviderService::{$method}()\n");
        exit(1);
    }
}

if (!str_contains($componentSource, 'getBuilderDataProviderService')) {
    fwrite(STDERR, "CompetitionsComponent does not expose builder data provider\n");
    exit(1);
}

if (!str_contains($providerSource, 'BuilderDataProviderService::class')) {
    fwrite(STDERR, "DI provider does not register builder data provider\n");
    exit(1);
}

if (!str_contains($serviceSource, "quoteName('state')") || !str_contains($serviceSource, " = 1")) {
    fwrite(STDERR, "Public builder provider must filter unpublished rows\n");
    exit(1);
}

if (preg_match('/#__xdecaropeople_|#__xdecaroorganizations_|#__decaro(?!competitions)/i', $serviceSource)) {
    fwrite(STDERR, "Builder provider must not query other products private tables\n");
    exit(1);
}

echo "builder-data-provider-contract=ok\n";

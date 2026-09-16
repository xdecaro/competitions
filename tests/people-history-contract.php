<?php

$root = dirname(__DIR__);
$servicePath = $root . '/component/admin/src/Service/PersonHistoryService.php';
$component = (string) file_get_contents($root . '/component/admin/src/Extension/CompetitionsComponent.php');
$core = (string) file_get_contents($root . '/component/admin/src/Service/CoreIntegrationService.php');
$provider = (string) file_get_contents($root . '/component/admin/services/provider.php');

if (!is_file($servicePath)) {
    fwrite(STDERR, "PersonHistoryService is missing.\n");
    exit(1);
}

$service = (string) file_get_contents($servicePath);

$checks = [
    [$core, "competitions.people_history", 'Competitions must declare the competitions.people_history capability.'],
    [$component, 'getPersonHistoryService', 'CompetitionsComponent must expose getPersonHistoryService().'],
    [$component, 'setPersonHistoryService', 'CompetitionsComponent must accept PersonHistoryService from DI.'],
    [$provider, 'PersonHistoryService::class', 'The service provider must register PersonHistoryService.'],
    [$provider, 'setPersonHistoryService', 'The service provider must inject PersonHistoryService into the component.'],
    [$service, 'function getHistoryByPersonUuid', 'PersonHistoryService must expose getHistoryByPersonUuid().'],
    [$service, '#__xdecarocompetitions_players', 'History must start from Competitions players.'],
    [$service, '#__xdecarocompetitions_rosters', 'History must join rosters.'],
    [$service, '#__xdecarocompetitions_participations', 'History must join participations.'],
    [$service, '#__xdecarocompetitions_teams', 'History must join teams.'],
    [$service, '#__xdecarocompetitions_seasons', 'History must join seasons.'],
    [$service, '#__xdecarocompetitions_tournaments', 'History must join tournaments.'],
    [$service, 'person_uuid', 'History must resolve the linked People UUID.'],
    [$service, "'competition_name'", 'History response must expose competition_name.'],
    [$service, "'season_name'", 'History response must expose season_name.'],
    [$service, "'team_name'", 'History response must expose team_name.'],
    [$service, "'role'", 'History response must expose role.'],
    [$service, "'shirt_number'", 'History response must expose shirt_number.'],
    [$service, "'status'", 'History response must expose roster status.'],
];

foreach ($checks as [$source, $needle, $message]) {
    if (!str_contains($source, $needle)) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

if (preg_match('/birth_date|disability|tax_identifier|email|phone/i', $service)) {
    fwrite(STDERR, "PersonHistoryService must not expose People-sensitive profile data.\n");
    exit(1);
}

echo "People history provider contract OK\n";

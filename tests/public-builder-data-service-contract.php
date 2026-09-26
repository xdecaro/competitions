<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$servicePath = $root . '/component/admin/src/Service/PublicBuilderDataService.php';
$extensionPath = $root . '/component/admin/src/Extension/CompetitionsComponent.php';
$providerPath = $root . '/component/admin/services/provider.php';

function fail(string $message): never
{
    fwrite(STDERR, "FAIL: {$message}\n");
    exit(1);
}

function expectContains(string $haystack, string $needle, string $message): void
{
    if (!str_contains($haystack, $needle)) {
        fail($message . " (missing: {$needle})");
    }
}

function expectNotContains(string $haystack, string $needle, string $message): void
{
    if (str_contains($haystack, $needle)) {
        fail($message . " (found forbidden: {$needle})");
    }
}

if (!is_file($servicePath)) {
    fail('PublicBuilderDataService.php does not exist');
}

$service = (string) file_get_contents($servicePath);
$extension = is_file($extensionPath) ? (string) file_get_contents($extensionPath) : '';
$provider = is_file($providerPath) ? (string) file_get_contents($providerPath) : '';

expectContains($service, 'use Joomla\\Database\\DatabaseInterface;', 'Service must depend on Joomla DatabaseInterface');
expectNotContains($service, 'YOOtheme', 'Competitions public service must stay presentation-framework agnostic');

$methods = [
    'getCurrentCompetitions',
    'getUpcomingCompetitions',
    'getPreviousCompetitions',
    'getCompetitionsByYear',
    'getCompetitionsByTournament',
    'getUpcomingSeasons',
    'getParticipatingTeams',
    'getTeam',
    'getUpcomingMatches',
    'getLatestResults',
    'getMatchesBySeason',
    'getMatch',
    'getStandings',
    'getRankings',
    'getCountries',
    'getFederations',
];

foreach ($methods as $method) {
    expectContains($service, 'function ' . $method . '(', "Missing public method {$method}");
}

$competitionFields = [
    'season_id', 'tournament_id', 'title', 'tournament_name', 'tournament_code',
    'discipline', 'gender', 'season_name', 'season_year', 'host_city',
    'host_country_code', 'start_date', 'end_date', 'temporal_status', 'team_count',
];
foreach ($competitionFields as $field) {
    expectContains($service, "'{$field}'", "Competition field contract missing {$field}");
}

$teamFields = [
    'id', 'name', 'short_name', 'alias', 'logo', 'country_code', 'city',
    'federation_id', 'federation_name', 'federation_short_name',
];
foreach ($teamFields as $field) {
    expectContains($service, "'{$field}'", "Team field contract missing {$field}");
}

$privateTokens = [
    "'email'", "'phone'", "'owner_user_id'", "'review_note'", "'rejection_reason'",
    "'reviewed_by'", "'created_by'", "'modified_by'",
];
foreach ($privateTokens as $token) {
    expectNotContains($service, $token, 'Public service must not expose private/internal fields');
}

expectContains($service, "p.status = 'approved'", 'Default public participation counts must require approved status');
expectContains($service, "p.state = 1", 'Default public participation counts must require active state');
expectContains($service, 'private function temporalStatus(', 'Temporal status must be centralized');
expectContains($service, "return 'undated';", 'Undated competitions must not be classified as current');
expectContains($service, 'LEFT JOIN', 'Public relation enrichment must tolerate missing related rows');

expectContains($extension, 'getPublicBuilderDataService()', 'Component must expose the public builder service');
expectContains($provider, 'PublicBuilderDataService::class', 'DI provider must register the public builder service');

foreach ([
    "'id'", "'match_date'", "'kickoff_time'", "'status'", "'home_team_name'",
    "'away_team_name'", "'home_score'", "'away_score'", "'venue_name'",
] as $field) {
    expectContains($service, $field, "Match public field contract missing {$field}");
}

foreach (["'position'", "'team_id'", "'team_name'", "'points'"] as $field) {
    expectContains($service, $field, "Standing public field contract missing {$field}");
}

foreach (["'code'", "'iso2'", "'iso3'", "'entity_type'", "'flag'"] as $field) {
    expectContains($service, $field, "Country public field contract missing {$field}");
}

expectContains($service, "'website'", 'Federation public contract should include website');

echo "PASS: public builder data service contract\n";

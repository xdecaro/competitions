<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$installer = (string) file_get_contents($root . '/component/script.php');

$checks = [
    [str_contains($installer, 'syncUndeterminedTeamFederations'), 'Installer must backfill unresolved linked club federations'],
    [str_contains($installer, "bootComponent('com_xdecaroorganizations')"), 'Backfill must consume Organizations through Joomla component boot'],
    [str_contains($installer, 'getOrganizationProviderService'), 'Backfill must use the Organizations public provider'],
    [str_contains($installer, 'getAffiliations($clubUuid, true)'), 'Backfill must read active Organizations affiliations through the provider'],
    [str_contains($installer, "'sports_affiliation'"), 'Backfill must only use sports affiliations'],
    [str_contains($installer, "'federation'"), 'Backfill must require a federation target'],
    [str_contains($installer, "count($targets) !== 1"), 'Backfill must refuse ambiguous or missing federation affiliations'],
    [str_contains($installer, "#__xdecarocompetitions_federations"), 'Backfill must map against Competitions-owned federation records'],
    [str_contains($installer, "#__xdecarocompetitions_teams"), 'Backfill must update Competitions-owned team records'],
    [str_contains($installer, "$db->quoteName('federation_id') . ' = 0'"), 'Backfill must only touch unresolved teams'],
    [!str_contains($installer, '#__xdecaroorganizations_'), 'Backfill must never read Organizations private tables'],
    [str_contains($installer, 'Log::WARNING'), 'Backfill failure must not abort the extension update'],
];

foreach ($checks as [$ok, $message]) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

echo "Team federation backfill contract OK\n";

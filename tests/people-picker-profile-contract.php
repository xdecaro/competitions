<?php

$root = dirname(__DIR__);
$controller = (string) file_get_contents($root . '/component/admin/src/Controller/PeopleController.php');
$service = (string) file_get_contents($root . '/component/admin/src/Service/PeopleIntegrationService.php');
$picker = (string) file_get_contents($root . '/component/media/js/people-picker.js');

$checks = [
    [$controller, 'function profile', 'PeopleController must expose a profile action for the selected person.'],
    [$controller, 'getProfilePerson', 'Profile action must use the public People profile lookup.'],
    [$controller, "'birth_date'", 'Profile response must expose birth_date when authorised.'],
    [$controller, "'nationality_code'", 'Profile response must expose nationality_code when authorised.'],
    [$controller, "'nationality_codes'", 'Profile response must support People multi-nationality data.'],
    [$controller, 'normalizeBirthDate', 'Profile response must normalize People birth dates for the Competitions calendar.'],
    [$service, 'getPerson($uuid, true)', 'Selected People profile must request sensitive fields explicitly.'],
    [$picker, 'people.profile', 'People picker must request the selected People profile.'],
    [$picker, "getElementById('jform_birth_date')", 'People picker must target the birth date field.'],
    [$picker, "getElementById('jform_nationality_code')", 'People picker must target the nationality field.'],
    [$picker, 'birth_date', 'People picker must copy birth_date from the selected profile.'],
    [$picker, 'nationality_code', 'People picker must copy nationality_code from the selected profile.'],
];

foreach ($checks as [$source, $needle, $message]) {
    if (!str_contains($source, $needle)) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

if (str_contains($service, 'catch (Throwable)') && str_contains($service, 'getPerson($uuid, false)')) {
    fwrite(STDERR, "Selected People profile must not silently fall back to a non-sensitive lookup.\n");
    exit(1);
}

if (str_contains($controller, 'searchPeople($q, 20, true)')) {
    fwrite(STDERR, "People search must remain non-sensitive; sensitive fields belong to the selected profile lookup only.\n");
    exit(1);
}

echo "People picker profile autofill contract OK\n";

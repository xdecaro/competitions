<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$version = trim((string) file_get_contents($root . '/VERSION'));
$fail = static function (string $message): never {
    fwrite(STDERR, "ERROR: {$message}\n");
    exit(1);
};

if ($version === '') {
    $fail('VERSION is empty.');
}

$files = [
    'install' => $root . '/component/admin/sql/install.mysql.utf8mb4.sql',
    'installExtension' => $root . '/component/admin/sql/install.1.4.0.mysql.utf8mb4.sql',
    'update' => $root . '/component/admin/sql/updates/mysql/1.4.0.sql',
    'manifest' => $root . '/component/xdecarocompetitions.xml',
    'packageManifest' => $root . '/package/pkg_xdecarocompetitions.xml',
    'build' => $root . '/tools/build.py',
    'playerForm' => $root . '/component/admin/forms/player.xml',
    'rosterForm' => $root . '/component/admin/forms/roster.xml',
    'playerModel' => $root . '/component/admin/src/Model/PlayerModel.php',
    'informationModel' => $root . '/component/admin/src/Model/InformationModel.php',
    'playerTable' => $root . '/component/admin/src/Table/PlayerTable.php',
    'rosterTable' => $root . '/component/admin/src/Table/RosterTable.php',
    'people' => $root . '/component/admin/src/Service/PeopleIntegrationService.php',
    'photo' => $root . '/component/admin/src/Service/CompetitionPhotoService.php',
    'playerTemplate' => $root . '/component/admin/tmpl/player/edit.php',
    'js' => $root . '/component/media/js/people-picker.js',
];

foreach ($files as $name => $path) {
    if (!is_file($path)) {
        $fail("Missing {$name} contract file: {$path}");
    }
}

$freshInstall = (string) file_get_contents($files['install']) . "\n" . (string) file_get_contents($files['installExtension']);
$update = (string) file_get_contents($files['update']);
$manifest = (string) file_get_contents($files['manifest']);
$packageManifest = (string) file_get_contents($files['packageManifest']);
$build = (string) file_get_contents($files['build']);
$playerForm = (string) file_get_contents($files['playerForm']);
$rosterForm = (string) file_get_contents($files['rosterForm']);
$playerModel = (string) file_get_contents($files['playerModel']);
$informationModel = (string) file_get_contents($files['informationModel']);
$playerTable = (string) file_get_contents($files['playerTable']);
$rosterTable = (string) file_get_contents($files['rosterTable']);
$people = (string) file_get_contents($files['people']);
$photo = (string) file_get_contents($files['photo']);
$playerTemplate = (string) file_get_contents($files['playerTemplate']);
$js = (string) file_get_contents($files['js']);

// Clean-install component identity contract. The package remains xdecaro-branded,
// while the Joomla component itself is shipped and installed as com_competitions.
if (!str_contains($packageManifest, 'type="component" id="com_competitions"')) {
    $fail('Package manifest must install component id com_competitions.');
}
if (!str_contains($packageManifest, 'com_competitions_' . $version . '.zip')) {
    $fail('Package manifest must reference the current-version com_competitions ZIP.');
}
foreach (['LEGACY_COMPONENT_OPTION = "com_xdecarocompetitions"', 'COMPONENT_OPTION = "com_competitions"', 'stage_extension', 'com_competitions_{VERSION}.zip'] as $token) {
    if (!str_contains($build, $token)) {
        $fail('Build-time component identity normalization missing: ' . $token);
    }
}

$buildOutput = [];
$buildStatus = 0;
exec('python3 ' . escapeshellarg($root . '/tools/build.py') . ' 2>&1', $buildOutput, $buildStatus);
if ($buildStatus !== 0) {
    $fail("Component identity build failed:\n" . implode("\n", $buildOutput));
}

$componentZip = $root . '/dist/com_competitions_' . $version . '.zip';
$legacyComponentZip = $root . '/dist/com_xdecarocompetitions_' . $version . '.zip';
if (!is_file($componentZip) || is_file($legacyComponentZip)) {
    $fail('Distribution must contain the current com_competitions component ZIP and no legacy component ZIP.');
}

$archive = new ZipArchive();
if ($archive->open($componentZip) !== true) {
    $fail('Unable to open built com_competitions component ZIP.');
}

foreach (['competitions.xml', 'admin/language/en-GB/com_competitions.ini', 'admin/language/en-GB/com_competitions.140.ini', 'admin/language/en-GB/com_competitions.sys.ini', 'admin/language/it-IT/com_competitions.ini', 'admin/language/it-IT/com_competitions.140.ini', 'admin/language/it-IT/com_competitions.sys.ini'] as $entry) {
    if ($archive->locateName($entry) === false) {
        $archive->close();
        $fail('Built component ZIP is missing ' . $entry);
    }
}
if ($archive->locateName('xdecarocompetitions.xml') !== false) {
    $archive->close();
    $fail('Built component ZIP still contains the legacy manifest name.');
}

$builtManifest = (string) $archive->getFromName('competitions.xml');
if (!str_contains($builtManifest, '<element>com_competitions</element>') || !str_contains($builtManifest, 'destination="com_competitions"')) {
    $archive->close();
    $fail('Built component manifest does not declare the clean com_competitions identity.');
}

for ($i = 0; $i < $archive->numFiles; $i++) {
    $name = (string) $archive->getNameIndex($i);
    if (str_ends_with($name, '/')) {
        continue;
    }
    $contents = $archive->getFromIndex($i);
    if (is_string($contents) && str_contains($contents, 'com_xdecarocompetitions')) {
        $archive->close();
        $fail('Legacy component option remains in built component file ' . $name);
    }
}
$archive->close();

foreach ([
    'ADD COLUMN `person_uuid` CHAR(36) NULL',
    'ADD UNIQUE KEY `uq_player_person_uuid` (`person_uuid`)',
    'ADD COLUMN `photo` VARCHAR(512) NULL',
] as $token) {
    if (!str_contains($freshInstall, $token)) {
        $fail('Fresh-install People/photo schema contract missing: ' . $token);
    }
}

if (!str_contains($manifest, 'sql/install.1.4.0.mysql.utf8mb4.sql')) {
    $fail('Component manifest must execute the 1.4.0 fresh-install schema extension.');
}

foreach ([
    'ADD COLUMN `person_uuid` CHAR(36) NULL',
    'ADD UNIQUE KEY `uq_player_person_uuid` (`person_uuid`)',
    'ADD COLUMN `photo` VARCHAR(512) NULL',
] as $token) {
    if (!str_contains($update, $token)) {
        $fail('1.4.0 upgrade contract missing: ' . $token);
    }
}

if (!str_contains($playerForm, 'name="person_uuid"') || !str_contains($playerForm, 'type="hidden"')) {
    $fail('Player form must carry the selected People UUID as a hidden field.');
}
if (str_contains($playerForm, 'name="photo"')) {
    $fail('Player form must not expose a new editable global photo; People owns the primary profile photo.');
}
if (!str_contains($rosterForm, 'name="photo"') || !str_contains($rosterForm, 'type="media"')) {
    $fail('Roster form must own the edition/team photo.');
}

foreach ([
    "bootComponent('com_xdecaropeople')",
    'getPersonProviderService',
    'searchPeople',
    'getPerson',
] as $token) {
    if (!str_contains($people, $token)) {
        $fail('People public-provider integration missing: ' . $token);
    }
}
foreach (['#__xdecaropeople_', 'Component\\People\\Administrator\\Service\\PersonProviderService'] as $forbidden) {
    if (str_contains($people, $forbidden)) {
        $fail('Forbidden direct People coupling found: ' . $forbidden);
    }
}

foreach ([
    'prepareTable',
    'getPeopleIntegrationService',
    'getPerson',
    '$table->first_name',
    '$table->last_name',
] as $token) {
    if (!str_contains($playerModel, $token)) {
        $fail('Server-side People identity hydration missing: ' . $token);
    }
}

foreach (['getPeopleIntegration', "bootComponent('com_xdecaropeople')", 'getPersonProviderService'] as $token) {
    if (!str_contains($informationModel, $token)) {
        $fail('People diagnostics integration missing: ' . $token);
    }
}
if (str_contains($informationModel, '#__xdecaropeople_')) {
    $fail('People diagnostics must not inspect People private tables.');
}

foreach ([
    "'roster'",
    "'people'",
    "'legacy'",
    'profile_document_reference',
    'profile_document_uuid',
] as $token) {
    if (!str_contains($photo, $token)) {
        $fail('Photo fallback contract missing: ' . $token);
    }
}

if (!str_contains($playerTable, 'person_uuid') || !str_contains($playerTable, 'strtolower')) {
    $fail('Player table must normalize the People UUID.');
}
if (!str_contains($rosterTable, '$this->photo')) {
    $fail('Roster table must normalize the edition photo.');
}

foreach (['data-competitions-people-picker', 'jform[person_uuid]'] as $token) {
    if (!str_contains($playerTemplate, $token)) {
        $fail('Player People picker template contract missing: ' . $token);
    }
}
foreach (['people.search', 'data-competitions-people-search', 'data-competitions-person-target'] as $token) {
    if (!str_contains($js, $token)) {
        $fail('Player People picker JavaScript contract missing: ' . $token);
    }
}

fwrite(STDOUT, "Competitions People + roster edition photo contract OK\n");

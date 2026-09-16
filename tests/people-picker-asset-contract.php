<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$ui = (string) file_get_contents($root . '/component/admin/src/Helper/UiHelper.php');
$assets = (string) file_get_contents($root . '/component/media/joomla.asset.json');
$pickerPath = $root . '/component/media/js/people-picker.js';

$fail = static function (string $message): never {
    fwrite(STDERR, "ERROR: {$message}\n");
    exit(1);
};

if (!is_file($pickerPath)) {
    $fail('People picker physical JavaScript file is missing from component/media/js.');
}

// Joomla WebAssetItem resolves script URIs through the media "js" folder itself.
// The URI therefore identifies the extension + filename, not extension + js + filename.
foreach ([
    $ui => "'com_xdecarocompetitions/people-picker.js'",
    $assets => '"uri":"com_xdecarocompetitions/people-picker.js"',
] as $source => $expected) {
    if (!str_contains($source, $expected)) {
        $fail('People picker asset URI must omit the duplicate /js/ segment: ' . $expected);
    }
}

foreach ([$ui, $assets] as $source) {
    if (str_contains($source, 'com_xdecarocompetitions/js/people-picker.js')) {
        $fail('People picker asset URI contains a duplicate /js/ segment.');
    }
}

fwrite(STDOUT, "Competitions People picker asset contract OK\n");

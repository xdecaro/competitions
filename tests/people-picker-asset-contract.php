<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$ui = (string) file_get_contents($root . '/component/admin/src/Helper/UiHelper.php');
$assetsJson = (string) file_get_contents($root . '/component/media/joomla.asset.json');
$pickerPath = $root . '/component/media/js/people-picker.js';

$fail = static function (string $message): never {
    fwrite(STDERR, "ERROR: {$message}\n");
    exit(1);
};

if (!is_file($pickerPath)) {
    $fail('People picker physical JavaScript file is missing from component/media/js.');
}

if (!str_contains($ui, "'com_xdecarocompetitions/people-picker.js'")) {
    $fail('UiHelper people picker URI must omit the duplicate /js/ segment.');
}

$assets = json_decode($assetsJson, true);
if (!is_array($assets)) {
    $fail('joomla.asset.json is not valid JSON.');
}

$pickerUri = null;
foreach (($assets['assets'] ?? []) as $asset) {
    if (($asset['name'] ?? '') === 'com_xdecarocompetitions.people-picker' && ($asset['type'] ?? '') === 'script') {
        $pickerUri = (string) ($asset['uri'] ?? '');
        break;
    }
}

if ($pickerUri !== 'com_xdecarocompetitions/people-picker.js') {
    $fail('People picker asset URI must omit the duplicate /js/ segment.');
}

if (str_contains($ui, 'com_xdecarocompetitions/js/people-picker.js') || str_contains($assetsJson, 'com_xdecarocompetitions/js/people-picker.js')) {
    $fail('People picker asset URI contains a duplicate /js/ segment.');
}

fwrite(STDOUT, "Competitions People picker asset contract OK\n");

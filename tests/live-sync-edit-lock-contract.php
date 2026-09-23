<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$templates = [
    'country',
    'federation',
    'match',
    'organization',
    'participation',
    'player',
    'roster',
    'season',
    'team',
    'tournament',
    'zone',
];

foreach ($templates as $view) {
    $path = $root . '/component/admin/tmpl/' . $view . '/edit.php';
    $content = file_get_contents($path);

    if ($content === false) {
        fwrite(STDERR, "FAIL: missing edit template {$view}\n");
        exit(1);
    }

    if (!str_contains($content, "\$this->form->getInput('id')")) {
        fwrite(STDERR, "FAIL: {$view} edit template does not render jform[id]\n");
        exit(1);
    }

    if (!str_contains($content, "\$this->form->getInput('modified')")) {
        fwrite(STDERR, "FAIL: {$view} edit template does not render jform[modified]\n");
        exit(1);
    }
}

foreach ([
    $root . '/component/media/live-sync.js',
    $root . '/component/media/js/live-sync.js',
] as $path) {
    $content = file_get_contents($path);

    if ($content === false
        || !str_contains($content, "if (!modifiedField)")
        || !str_contains($content, "injectHidden(form, 'jform[modified]', serverModified)")) {
        fwrite(STDERR, "FAIL: live-sync reload-loop guard is missing in {$path}\n");
        exit(1);
    }
}

echo "Live-sync edit lock contract OK\n";

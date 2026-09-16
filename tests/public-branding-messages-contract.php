<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$files = [
    'it' => $root . '/component/admin/language/it-IT/com_xdecarocompetitions.ini',
    'en' => $root . '/component/admin/language/en-GB/com_xdecarocompetitions.ini',
    'feed' => $root . '/updates/pkg_xdecarocompetitions.xml',
    'readme' => $root . '/README.md',
    'release' => $root . '/.github/workflows/release.yml',
];

foreach ($files as $name => $path) {
    if (!is_file($path)) {
        fwrite(STDERR, "Missing {$name} file: {$path}\n");
        exit(1);
    }
}

$content = array_map(static fn (string $path): string => (string) file_get_contents($path), $files);

$messages = [
    'COM_XDECAROCOMPETITIONS_APPROVAL_UPDATED_APPROVED=',
    'COM_XDECAROCOMPETITIONS_APPROVAL_UPDATED_PENDING=',
    'COM_XDECAROCOMPETITIONS_APPROVAL_UPDATED_REJECTED=',
];

foreach (['it', 'en'] as $locale) {
    foreach ($messages as $message) {
        if (strpos($content[$locale], $message) === false) {
            fwrite(STDERR, "Base {$locale} language file must contain {$message}\n");
            exit(1);
        }
    }
}

foreach ($content as $name => $text) {
    if (stripos($text, 'Competitions by xdecaro') !== false) {
        fwrite(STDERR, "Public branding still contains 'Competitions by xdecaro' in {$name}.\n");
        exit(1);
    }
}

if (strpos($content['feed'], '<name>Competitions</name>') === false) {
    fwrite(STDERR, "Joomla update feed must expose the public name Competitions.\n");
    exit(1);
}

if (strpos($content['release'], '--title "Competitions ${VERSION}"') === false) {
    fwrite(STDERR, "GitHub releases must use the public title Competitions <version>.\n");
    exit(1);
}

echo "Public branding and approval messages contract OK\n";

<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$files = [
    'controller' => $root . '/component/admin/src/Controller/PlayersController.php',
    'it144' => $root . '/component/admin/language/it-IT/com_xdecarocompetitions.144.ini',
    'en144' => $root . '/component/admin/language/en-GB/com_xdecarocompetitions.144.ini',
    'componentSysIt' => $root . '/component/admin/language/it-IT/com_xdecarocompetitions.sys.ini',
    'componentSysEn' => $root . '/component/admin/language/en-GB/com_xdecarocompetitions.sys.ini',
    'packageSysIt' => $root . '/package/language/it-IT/pkg_xdecarocompetitions.sys.ini',
    'packageSysEn' => $root . '/package/language/en-GB/pkg_xdecarocompetitions.sys.ini',
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

foreach (['it144', 'en144'] as $locale) {
    foreach ($messages as $message) {
        if (strpos($content[$locale], $message) === false) {
            fwrite(STDERR, "Approval language fragment {$locale} must contain {$message}\n");
            exit(1);
        }
    }
}

if (strpos($content['controller'], 'use xdecaro\\Component\\Competitions\\Administrator\\Helper\\LanguageHelper;') === false
    || strpos($content['controller'], 'LanguageHelper::load();') === false) {
    fwrite(STDERR, "Players approval controller must load Competition language fragments before translating redirect messages.\n");
    exit(1);
}

foreach (['feed', 'readme', 'release', 'componentSysIt', 'componentSysEn', 'packageSysIt', 'packageSysEn'] as $name) {
    if (stripos($content[$name], 'Competitions by xdecaro') !== false) {
        fwrite(STDERR, "Public branding still contains 'Competitions by xdecaro' in {$name}.\n");
        exit(1);
    }
}

if (strpos($content['feed'], '<name>Competitions</name>') === false
    || strpos($content['feed'], '<description>Competitions package for Joomla 6.</description>') === false) {
    fwrite(STDERR, "Joomla update feed must expose the public name Competitions.\n");
    exit(1);
}

if (strpos($content['release'], '--title "Competitions ${VERSION}"') === false) {
    fwrite(STDERR, "GitHub releases must use the public title Competitions <version>.\n");
    exit(1);
}

echo "Public branding and approval messages contract OK\n";

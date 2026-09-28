<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$controllerPath = $root . '/component/admin/src/Controller/ParticipationbulkController.php';
$modelPath = $root . '/component/admin/src/Model/ParticipationbulkModel.php';
$viewPath = $root . '/component/admin/src/View/Participationbulk/HtmlView.php';
$templatePath = $root . '/component/admin/tmpl/participationbulk/default.php';

foreach ([$controllerPath, $modelPath, $viewPath, $templatePath] as $path) {
    if (!is_file($path)) {
        fwrite(STDERR, "Missing participation bulk file: {$path}\n");
        exit(1);
    }
}

$controller = file_get_contents($controllerPath);
$model = file_get_contents($modelPath);
$view = file_get_contents($viewPath);
$template = file_get_contents($templatePath);

$requirements = [
    [$controller, 'public function previewCsv()', 'Bulk controller must expose CSV preview action.'],
    [$controller, '$this->checkToken()', 'CSV preview must validate CSRF token.'],
    [$controller, "authorise('core.create'", 'CSV preview must enforce create ACL.'],
    [$controller, 'parseCsvTeamNames', 'Bulk controller must parse team names from CSV.'],
    [$controller, "'nameteam'", 'CSV parser must support the DCL nameteam column.'],
    [$controller, 'setUserState', 'CSV preview must persist only the parsed filter in Joomla user state.'],
    [$model, 'filterTeamsBySourceNames', 'Bulk model must filter existing teams by uploaded source names.'],
    [$model, 'normalizeTeamName', 'Bulk matching must normalize team names.'],
    [$model, "'Ü' => 'U'", 'Bulk matching must normalize German umlauts safely.'],
    [$model, "preg_replace('/[^A-Z0-9]+/'", 'Bulk matching must ignore punctuation and spacing after transliteration.'],
    [$model, 'getUnmatchedSourceNames', 'Bulk model must expose exact source names that did not match.'],
    [$view, 'sourceTeamNames', 'Bulk view must expose the active source filter.'],
    [$view, 'unmatchedSourceNames', 'Bulk view must expose unmatched CSV names.'],
    [$template, 'enctype="multipart/form-data"', 'Bulk form must support CSV upload.'],
    [$template, 'name="source_csv"', 'Bulk form must expose a source CSV file field.'],
    [$template, 'participationbulk.previewCsv', 'Bulk page must submit CSV preview to the dedicated action.'],
    [$template, 'bulk-source-summary', 'Bulk page must show CSV match summary.'],
    [$template, 'Non abbinate', 'Bulk page must show the exact unmatched source names.'],
    [$template, 'participation-bulk-shell', 'Bulk page must use the compact responsive shell.'],
    [$template, 'table-layout:fixed', 'Desktop table must use a compact fixed layout.'],
];

foreach ($requirements as [$haystack, $needle, $message]) {
    if (strpos($haystack, $needle) === false) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

// Behavioral regression: the historical CSV often uses plain ASCII while
// Organizations/Competitions keeps the canonical German spelling.
if (!defined('_JEXEC')) {
    define('_JEXEC', 1);
}
if (!class_exists('Joomla\\CMS\\MVC\\Model\\BaseDatabaseModel')) {
    eval('namespace Joomla\\CMS\\MVC\\Model; class BaseDatabaseModel {}');
}
require_once $modelPath;

$probe = new \xdecaro\Component\Competitions\Administrator\Model\ParticipationbulkModel();
$canonical = $probe->normalizeTeamName('G.S.V. DÜSSELDORF');
$asciiCsv = $probe->normalizeTeamName('G S V DUSSELDORF');
$punctuatedCsv = $probe->normalizeTeamName('G.S.V. DUSSELDORF');

if ($canonical === '' || $canonical !== $asciiCsv || $canonical !== $punctuatedCsv) {
    fwrite(STDERR, "Düsseldorf CSV normalization regression: {$canonical} / {$asciiCsv} / {$punctuatedCsv}\n");
    exit(1);
}

echo "Participation bulk CSV contract OK\n";

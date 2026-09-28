<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$controllerPath = $root . '/component/admin/src/Controller/PlayerbulkController.php';
$modelPath = $root . '/component/admin/src/Model/PlayerbulkModel.php';
$viewPath = $root . '/component/admin/src/View/Playerbulk/HtmlView.php';
$templatePath = $root . '/component/admin/tmpl/playerbulk/default.php';

foreach ([$controllerPath, $modelPath, $viewPath, $templatePath] as $path) {
    if (!is_file($path)) {
        fwrite(STDERR, "Missing player bulk file: {$path}\n");
        exit(1);
    }
}

$controller = file_get_contents($controllerPath);
$model = file_get_contents($modelPath);
$view = file_get_contents($viewPath);
$template = file_get_contents($templatePath);

$requirements = [
    [$controller, 'public function previewCsv()', 'Player bulk controller must expose CSV preview.'],
    [$controller, 'public function clearCsv()', 'Player bulk controller must expose CSV filter clearing.'],
    [$controller, '$this->checkToken()', 'Player CSV preview must validate CSRF token.'],
    [$controller, "authorise('core.create'", 'Player CSV preview must enforce create ACL.'],
    [$controller, 'parseCsvPersonNames', 'Player bulk controller must parse person names from CSV.'],
    [$controller, "'firstname'", 'CSV parser must support firstname.'],
    [$controller, "'lastname'", 'CSV parser must support lastname.'],
    [$model, 'filterPeopleBySourceNames', 'Player bulk model must filter People results by CSV names.'],
    [$model, 'normalizePersonName', 'Player bulk matching must normalize person names.'],
    [$model, 'getUnmatchedSourceNames', 'Player bulk model must expose unmatched CSV names.'],
    [$view, 'sourcePersonNames', 'Player bulk view must expose source CSV names.'],
    [$view, 'sourceSummary', 'Player bulk view must expose CSV summary.'],
    [$view, 'unmatchedSourceNames', 'Player bulk view must expose unmatched CSV names.'],
    [$template, 'enctype="multipart/form-data"', 'Player bulk form must support CSV upload.'],
    [$template, 'name="source_csv"', 'Player bulk form must expose source CSV input.'],
    [$template, 'playerbulk.previewCsv', 'Player bulk page must submit CSV preview action.'],
    [$template, 'nel CSV', 'Player bulk page must show CSV counts.'],
    [$template, 'Non abbinate', 'Player bulk page must list unmatched CSV people.'],
    [$template, 'Non crea persone in People', 'Player bulk page must explain that CSV does not create People records.'],
];

foreach ($requirements as [$haystack, $needle, $message]) {
    if (strpos($haystack, $needle) === false) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

echo "Player bulk CSV contract OK\n";

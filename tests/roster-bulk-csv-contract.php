<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$viewList = $root . '/component/admin/src/View/Rosters/HtmlView.php';
$controller = $root . '/component/admin/src/Controller/RosterbulkController.php';
$model = $root . '/component/admin/src/Model/RosterbulkModel.php';
$view = $root . '/component/admin/src/View/Rosterbulk/HtmlView.php';
$template = $root . '/component/admin/tmpl/rosterbulk/default.php';

foreach ([$viewList, $controller, $model, $view, $template] as $path) {
    if (!is_file($path)) {
        fwrite(STDERR, "Missing roster bulk file: {$path}\n");
        exit(1);
    }
}

$list = file_get_contents($viewList);
$ctl = file_get_contents($controller);
$mdl = file_get_contents($model);
$vw = file_get_contents($view);
$tpl = file_get_contents($template);

$requirements = [
    [$list, 'view=rosterbulk', 'Rosters toolbar must link to roster bulk add.'],
    [$ctl, 'public function previewCsv()', 'Roster bulk controller must expose CSV preview.'],
    [$ctl, '$this->checkToken()', 'Roster bulk actions must validate CSRF.'],
    [$ctl, "authorise('core.create'", 'Roster bulk actions must enforce create ACL.'],
    [$ctl, 'parseCsvRosterRows', 'Roster bulk controller must parse roster CSV rows.'],
    [$ctl, "'nameteam'", 'Roster bulk CSV must support nameteam.'],
    [$ctl, "'firstname'", 'Roster bulk CSV must support firstname.'],
    [$ctl, "'lastname'", 'Roster bulk CSV must support lastname.'],
    [$ctl, "'shirt_number'", 'Roster bulk CSV must support shirt number aliases.'],
    [$ctl, "'role'", 'Roster bulk CSV must support role aliases.'],
    [$ctl, 'public function addSelected()', 'Roster bulk controller must create selected roster rows.'],
    [$ctl, "'status' => 'pending'", 'New roster rows must start pending.'],
    [$ctl, "'state' => 1", 'New roster rows must be published.'],
    [$mdl, 'getParticipationOptions', 'Roster bulk model must expose participation choices.'],
    [$mdl, 'matchCsvRows', 'Roster bulk model must match CSV rows to Competition players.'],
    [$mdl, 'participation_id', 'Roster bulk model must scope duplicate checks to participation.'],
    [$mdl, 'person_uuid', 'Roster bulk matching must use People-linked player identity.'],
    [$vw, 'sourceSummary', 'Roster bulk view must expose CSV summary.'],
    [$tpl, 'name="source_csv"', 'Roster bulk page must contain CSV upload.'],
    [$tpl, 'name="participation_id"', 'Roster bulk page must choose one participation.'],
    [$tpl, 'Seleziona tutte', 'Roster bulk page must support select all.'],
    [$tpl, 'Non abbinate', 'Roster bulk page must show unmatched names.'],
    [$tpl, 'Numero maglia', 'Roster bulk page must preview shirt number.'],
    [$tpl, 'Ruolo', 'Roster bulk page must preview role.'],
];

foreach ($requirements as [$haystack, $needle, $message]) {
    if (strpos($haystack, $needle) === false) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

echo "Roster bulk CSV contract OK\n";

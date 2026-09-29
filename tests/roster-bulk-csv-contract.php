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
    [$ctl, "'nr'", 'Roster bulk CSV must support nr as shirt number.'],
    [$ctl, "'n'", 'Roster bulk CSV must support n as shirt number.'],
    [$ctl, "if (\$h === '#')", 'Roster bulk CSV must support # as shirt number header.'],
    [$ctl, "'role'", 'Roster bulk CSV must support role aliases.'],
    [$ctl, 'public function addSelected()', 'Roster bulk controller must create selected roster rows.'],
    [$ctl, "'status' => 'pending'", 'New roster rows must start pending.'],
    [$ctl, "'state' => 1", 'New roster rows must be published.'],
    [$mdl, 'getParticipationOptions', 'Roster bulk model must expose participation choices.'],
    [$mdl, 'matchCsvRows', 'Roster bulk model must preserve single-participation matching.'],
    [$mdl, 'participation_id', 'Roster bulk model must scope duplicate checks to participation.'],
    [$mdl, 'person_uuid', 'Roster bulk matching must use People-linked player identity.'],
    [$vw, 'sourceSummary', 'Roster bulk view must expose CSV summary.'],
    [$tpl, 'name="source_csv"', 'Roster bulk page must contain CSV upload.'],
    [$tpl, 'Numero maglia', 'Roster bulk page must preview shirt number.'],
    [$tpl, 'Ruolo', 'Roster bulk page must preview role.'],

    // Season-wide import contract.
    [$mdl, 'getSeasonOptions', 'Roster bulk model must expose season choices before team/participation.'],
    [$mdl, 'getParticipationsForSeason', 'Roster bulk model must resolve only existing participations in the chosen season.'],
    [$mdl, 'matchSeasonCsvRows', 'Roster bulk model must match one file across all season teams.'],
    [$mdl, "'unmatched_teams'", 'Season matching must report teams that cannot be resolved.'],
    [$mdl, "'groups'", 'Season matching must group the preview by participation/team.'],
    [$mdl, "'teams_in_file'", 'Season matching must count teams present in the file.'],
    [$mdl, "'matched_teams'", 'Season matching must count matched season teams.'],
    [$ctl, "getInt('season_id'", 'Roster bulk actions must receive an explicit season.'],
    [$ctl, "getCmd('roster_target'", 'Roster bulk actions must distinguish all-teams and single-team targets.'],
    [$ctl, "=== 'all'", 'Roster bulk controller must support an explicit all-teams mode.'],
    [$ctl, 'matchSeasonCsvRows', 'Roster bulk add must revalidate all-team selections against current data.'],
    [$ctl, "preg_match('/^(\\d+):(\\d+)$/'", 'Posted selections must bind participation and player IDs together.'],
    [$ctl, '$this->rosterExists(', 'Roster bulk add must recheck roster duplicates server-side.'],
    [$vw, 'seasonOptions', 'Roster bulk view must expose season choices.'],
    [$vw, 'groups', 'Roster bulk view must expose team groups for the accordion.'],
    [$vw, 'unmatchedTeams', 'Roster bulk view must expose unmatched team diagnostics.'],
    [$tpl, 'name="season_id"', 'Roster bulk page must choose the season first.'],
    [$tpl, 'name="roster_target"', 'Roster bulk page must choose all teams or one participation second.'],
    [$tpl, 'Tutte le squadre della stagione', 'Roster bulk page must expose all-teams mode.'],
    [$tpl, 'data-season-id=', 'Participation choices must be filtered by season.'],
    [$tpl, 'roster-team-accordion', 'Roster bulk preview must use a per-team accordion.'],
    [$tpl, 'team-check', 'Roster bulk preview must allow selecting all new players in one team.'],
    [$tpl, 'Seleziona tutti i nuovi', 'Roster bulk preview must allow selecting all new players globally.'],
    [$tpl, 'selection[]', 'Roster bulk rows must post participation/player selection keys.'],
    [$tpl, 'Squadre non riconosciute', 'Roster bulk preview must clearly show unmatched teams.'],
    [$tpl, 'Persone non riconosciute', 'Roster bulk preview must clearly show unmatched people.'],
];

foreach ($requirements as [$haystack, $needle, $message]) {
    if (strpos($haystack, $needle) === false) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

echo "Roster bulk CSV contract OK\n";

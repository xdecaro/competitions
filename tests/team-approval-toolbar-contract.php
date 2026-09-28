<?php
declare(strict_types=1);

$root = dirname(__DIR__);

$files = [
    'view' => $root . '/component/admin/src/View/Teams/HtmlView.php',
    'controller' => $root . '/component/admin/src/Controller/TeamsController.php',
    'model' => $root . '/component/admin/src/Model/TeamModel.php',
];

foreach ($files as $name => $path) {
    if (!is_file($path)) {
        fwrite(STDERR, "Missing {$name} file: {$path}\n");
        exit(1);
    }
}

$view = file_get_contents($files['view']);
$controller = file_get_contents($files['controller']);
$model = file_get_contents($files['model']);

$loadLanguage = static function (string $locale) use ($root): string {
    $paths = glob($root . '/component/admin/language/' . $locale . '/com_xdecarocompetitions*.ini') ?: [];
    sort($paths);

    $content = '';
    foreach ($paths as $path) {
        $content .= "\n" . file_get_contents($path);
    }

    return $content;
};

$en = $loadLanguage('en-GB');
$it = $loadLanguage('it-IT');

$requirements = [
    [$view, "teams.approve", 'Teams toolbar must expose Approve bulk action.'],
    [$view, "teams.pending", 'Teams toolbar must expose Pending bulk action.'],
    [$view, "teams.reject", 'Teams toolbar must expose Reject bulk action.'],
    [$controller, 'public function approve()', 'TeamsController must expose approve().'],
    [$controller, 'public function pending()', 'TeamsController must expose pending().'],
    [$controller, 'public function reject()', 'TeamsController must expose reject().'],
    [$controller, 'setApprovalStatus', 'TeamsController must delegate approval changes to the model.'],
    [$controller, '$this->checkToken()', 'Teams approval actions must validate CSRF token.'],
    [$model, 'public function setApprovalStatus', 'TeamModel must implement bulk approval status changes.'],
    [$model, "'pending'", 'TeamModel must support pending approval state.'],
    [$model, "'approved'", 'TeamModel must support approved approval state.'],
    [$model, "'rejected'", 'TeamModel must support rejected approval state.'],
    [$model, 'COM_XDECAROCOMPETITIONS_ERROR_TEAM_APPROVAL_FEDERATION_REQUIRED', 'Team approval must reject an undetermined federation.'],
    [$model, 'LiveSyncHelper::touchModified', 'Team approval changes must touch live-sync modified timestamps.'],
    [$it, 'COM_XDECAROCOMPETITIONS_ERROR_NO_TEAMS_SELECTED=', 'Italian no-team-selected message is missing.'],
    [$it, 'COM_XDECAROCOMPETITIONS_ERROR_TEAM_APPROVAL_FEDERATION_REQUIRED=', 'Italian approval federation validation message is missing.'],
    [$en, 'COM_XDECAROCOMPETITIONS_ERROR_NO_TEAMS_SELECTED=', 'English no-team-selected message is missing.'],
    [$en, 'COM_XDECAROCOMPETITIONS_ERROR_TEAM_APPROVAL_FEDERATION_REQUIRED=', 'English approval federation validation message is missing.'],
];

foreach ($requirements as [$haystack, $needle, $message]) {
    if (strpos($haystack, $needle) === false) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

$bulkFiles = [
    'controller' => $root . '/component/admin/src/Controller/ParticipationbulkController.php',
    'model' => $root . '/component/admin/src/Model/ParticipationbulkModel.php',
    'view' => $root . '/component/admin/src/View/Participationbulk/HtmlView.php',
    'template' => $root . '/component/admin/tmpl/participationbulk/default.php',
];

foreach ($bulkFiles as $name => $path) {
    if (!is_file($path)) {
        fwrite(STDERR, "Missing participation bulk {$name}: {$path}\n");
        exit(1);
    }
}

$bulkController = file_get_contents($bulkFiles['controller']);
$bulkModel = file_get_contents($bulkFiles['model']);
$bulkView = file_get_contents($bulkFiles['view']);
$bulkTemplate = file_get_contents($bulkFiles['template']);
$participationsViewPath = $root . '/component/admin/src/View/Participations/HtmlView.php';
$participationsView = is_file($participationsViewPath) ? file_get_contents($participationsViewPath) : '';

$bulkRequirements = [
    [$participationsView, 'view=participationbulk', 'Participations toolbar must link to the separate bulk-add page.'],
    [$bulkController, 'public function addSelected()', 'Participation bulk controller must expose addSelected().'],
    [$bulkController, 'public function previewCsv()', 'Participation bulk controller must expose CSV preview.'],
    [$bulkController, 'parseCsvTeamNames', 'Participation bulk controller must parse team names from CSV.'],
    [$bulkController, '$this->checkToken()', 'Participation bulk actions must validate CSRF token.'],
    [$bulkController, "authorise('core.create'", 'Participation bulk actions must enforce create ACL.'],
    [$bulkController, "'status' => 'draft'", 'Bulk-created participations must start as draft.'],
    [$bulkController, "'state' => 1", 'Bulk-created participations must be published.'],
    [$bulkModel, 'getSeasonOptions', 'Participation bulk page must provide season choices.'],
    [$bulkModel, 'getAvailableTeams', 'Participation bulk page must provide teams available for the selected season.'],
    [$bulkModel, 'filterTeamsBySourceNames', 'Participation bulk page must filter existing teams by source CSV names.'],
    [$bulkModel, '#__xdecarocompetitions_participations', 'Participation bulk model must exclude existing team/season pairs.'],
    [$bulkView, 'participationbulk.addSelected', 'Participation bulk toolbar must submit selected teams.'],
    [$bulkTemplate, 'name="season_id"', 'Participation bulk page must submit the selected season.'],
    [$bulkTemplate, 'name="cid[]"', 'Participation bulk page must support multi-team selection.'],
    [$bulkTemplate, 'enctype="multipart/form-data"', 'Participation bulk page must support CSV upload.'],
    [$bulkTemplate, 'name="source_csv"', 'Participation bulk page must expose source CSV input.'],
    [$bulkTemplate, 'participationbulk.previewCsv', 'Participation bulk page must submit CSV preview action.'],
    [$bulkTemplate, 'participation-bulk-shell', 'Participation bulk page must use compact responsive layout.'],
];

foreach ($bulkRequirements as [$haystack, $needle, $message]) {
    if (strpos($haystack, $needle) === false) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

echo "Team approval and participation bulk-add contracts OK\n";

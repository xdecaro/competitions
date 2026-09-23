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

echo "Team approval toolbar contract OK\n";

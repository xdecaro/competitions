<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$files = [
    'teamsView' => $root . '/component/admin/src/View/Teams/HtmlView.php',
    'teamsController' => $root . '/component/admin/src/Controller/TeamsController.php',
    'teamModel' => $root . '/component/admin/src/Model/TeamModel.php',
    'teamForm' => $root . '/component/admin/forms/team.xml',
    'teamsTemplate' => $root . '/component/admin/tmpl/teams/default.php',
    'playersView' => $root . '/component/admin/src/View/Players/HtmlView.php',
    'playersController' => $root . '/component/admin/src/Controller/PlayersController.php',
    'playerModel' => $root . '/component/admin/src/Model/PlayerModel.php',
    'playerForm' => $root . '/component/admin/forms/player.xml',
    'playersTemplate' => $root . '/component/admin/tmpl/players/default.php',
    'participationsView' => $root . '/component/admin/src/View/Participations/HtmlView.php',
    'participationsController' => $root . '/component/admin/src/Controller/ParticipationsController.php',
    'participationModel' => $root . '/component/admin/src/Model/ParticipationModel.php',
    'participationTable' => $root . '/component/admin/src/Table/ParticipationTable.php',
    'participationForm' => $root . '/component/admin/forms/participation.xml',
    'participationsTemplate' => $root . '/component/admin/tmpl/participations/default.php',
    'rostersView' => $root . '/component/admin/src/View/Rosters/HtmlView.php',
    'rostersController' => $root . '/component/admin/src/Controller/RostersController.php',
    'rosterModel' => $root . '/component/admin/src/Model/RosterModel.php',
    'rosterTable' => $root . '/component/admin/src/Table/RosterTable.php',
    'rosterForm' => $root . '/component/admin/forms/roster.xml',
    'rostersTemplate' => $root . '/component/admin/tmpl/rosters/default.php',
    'seasonsView' => $root . '/component/admin/src/View/Seasons/HtmlView.php',
    'seasonForm' => $root . '/component/admin/forms/season.xml',
    'seasonTable' => $root . '/component/admin/src/Table/SeasonTable.php',
    'languageHelper' => $root . '/component/admin/src/Helper/LanguageHelper.php',
    'installer' => $root . '/component/script.php',
    'build' => $root . '/tools/build.py',
    'upgradeSql' => $root . '/component/admin/sql/updates/mysql/1.5.58.sql',
];

foreach ($files as $name => $path) {
    if (!is_file($path)) {
        fwrite(STDERR, "Missing {$name}: {$path}\n");
        exit(1);
    }
}

$c = [];
foreach ($files as $name => $path) $c[$name] = file_get_contents($path);

foreach (['teams', 'players', 'participations', 'rosters'] as $scope) {
    $viewKey = $scope . 'View';
    $controllerKey = $scope . 'Controller';
    foreach (['approve', 'pending', 'submit', 'reject'] as $action) {
        $task = $scope . '.' . $action;
        if (strpos($c[$viewKey], $task) === false) {
            fwrite(STDERR, ucfirst($scope) . " toolbar must expose {$action}.\n");
            exit(1);
        }
        if (strpos($c[$controllerKey], 'public function ' . $action . '()') === false) {
            fwrite(STDERR, ucfirst($scope) . "Controller {$action}() missing.\n");
            exit(1);
        }
    }
}

$requirements = [
    [$c['teamModel'], "['pending', 'submitted', 'approved', 'rejected']", 'Team model must accept pending, submitted, approved and rejected.'],
    [$c['playerModel'], "['pending', 'submitted', 'approved', 'rejected']", 'Player model must accept pending, submitted, approved and rejected.'],
    [$c['participationModel'], "['pending', 'submitted', 'approved', 'rejected']", 'Participation model must accept pending, submitted, approved and rejected.'],
    [$c['participationTable'], "['draft', 'pending', 'submitted', 'approved', 'rejected']", 'Participation table must distinguish pending from submitted.'],
    [$c['rosterModel'], "['pending', 'submitted', 'approved', 'rejected']", 'Roster model must accept pending, submitted, approved and rejected.'],
    [$c['rosterTable'], "['pending', 'submitted', 'approved', 'rejected']", 'Roster table must accept submitted.'],
    [$c['teamForm'], 'value="submitted"', 'Team form submitted status missing.'],
    [$c['playerForm'], 'value="submitted"', 'Player form submitted status missing.'],
    [$c['participationForm'], 'value="pending"', 'Participation form pending status missing.'],
    [$c['participationForm'], 'value="submitted"', 'Participation form submitted status missing.'],
    [$c['rosterForm'], 'value="submitted"', 'Roster form submitted status missing.'],
    [$c['participationsController'], "updateWorkflowStatus('pending'", 'Participations pending action must set pending, not submitted.'],
    [$c['participationsController'], "updateWorkflowStatus('submitted'", 'Participations submit action must set submitted.'],
    [$c['teamsTemplate'], "'submitted' => ['COM_XDECAROCOMPETITIONS_APPROVAL_SUBMITTED', 'bg-info text-white']", 'Team Submitted badge must use white text.'],
    [$c['playersTemplate'], "'submitted' => 'bg-info text-white'", 'Player Submitted badge must use white text.'],
    [$c['participationsTemplate'], "'submitted' => ['COM_XDECAROCOMPETITIONS_PARTICIPATION_SUBMITTED', 'bg-info text-white']", 'Participation Submitted badge must use white text.'],
    [$c['rostersTemplate'], "'submitted' => 'bg-info text-white'", 'Roster Submitted badge must use white text.'],
    [$c['seasonForm'], 'name="workflow_status"', 'Season form workflow_status field missing.'],
    [$c['seasonForm'], 'value="draft"', 'Season status draft missing.'],
    [$c['seasonForm'], 'value="awaiting_host"', 'Season status awaiting_host missing.'],
    [$c['seasonForm'], 'value="host_candidate"', 'Season status host_candidate missing.'],
    [$c['seasonForm'], 'value="inspection"', 'Season status inspection missing.'],
    [$c['seasonForm'], 'value="venue_approved"', 'Season status venue_approved missing.'],
    [$c['seasonForm'], 'value="preparation"', 'Season status preparation missing.'],
    [$c['seasonForm'], 'value="ready"', 'Season status ready missing.'],
    [$c['seasonForm'], 'value="in_progress"', 'Season status in_progress missing.'],
    [$c['seasonForm'], 'value="completed"', 'Season status completed missing.'],
    [$c['seasonForm'], 'value="cancelled"', 'Season status cancelled missing.'],
    [$c['seasonTable'], "'awaiting_host'", 'Season table must validate workflow states.'],
    [$c['languageHelper'], "com_xdecarocompetitions.158", 'LanguageHelper must load the 1.5.58 workflow strings.'],
    [$c['installer'], 'ensureSeasonWorkflowSchema', 'Upgrade installer must repair the season workflow column.'],
    [$c['installer'], "ADD COLUMN ' . \$db->quoteName('workflow_status')", 'Installer must add workflow_status when missing.'],
    [$c['build'], '`workflow_status` VARCHAR(32) NOT NULL DEFAULT \'draft\'', 'Fresh-install build must normalize the season workflow column.'],
    [$c['upgradeSql'], '1.5.58 schema marker', '1.5.58 schema marker missing.'],
];

foreach ($requirements as [$haystack, $needle, $message]) {
    if (strpos($haystack, $needle) === false) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

foreach (["seasons.approve", "seasons.pending", "seasons.reject", "seasons.inspection"] as $forbidden) {
    if (strpos($c['seasonsView'], $forbidden) !== false) {
        fwrite(STDERR, "Season workflow must remain inside New/Edit form, not toolbar: {$forbidden}\n");
        exit(1);
    }
}

echo "Workflow status contract OK\n";

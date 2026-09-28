<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$files = [
    'participationsView' => $root . '/component/admin/src/View/Participations/HtmlView.php',
    'participationsController' => $root . '/component/admin/src/Controller/ParticipationsController.php',
    'participationModel' => $root . '/component/admin/src/Model/ParticipationModel.php',
    'rostersView' => $root . '/component/admin/src/View/Rosters/HtmlView.php',
    'rostersController' => $root . '/component/admin/src/Controller/RostersController.php',
    'rosterModel' => $root . '/component/admin/src/Model/RosterModel.php',
    'seasonsView' => $root . '/component/admin/src/View/Seasons/HtmlView.php',
    'seasonForm' => $root . '/component/admin/forms/season.xml',
    'seasonTable' => $root . '/component/admin/src/Table/SeasonTable.php',
    'installSql' => $root . '/component/admin/sql/install.mysql.utf8mb4.sql',
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

$requirements = [
    [$c['participationsView'], "participations.approve", 'Participations toolbar must expose Approve.'],
    [$c['participationsView'], "participations.pending", 'Participations toolbar must expose Pending.'],
    [$c['participationsView'], "participations.reject", 'Participations toolbar must expose Reject.'],
    [$c['participationsController'], 'public function approve()', 'ParticipationsController approve() missing.'],
    [$c['participationsController'], 'public function pending()', 'ParticipationsController pending() missing.'],
    [$c['participationsController'], 'public function reject()', 'ParticipationsController reject() missing.'],
    [$c['participationModel'], 'public function setWorkflowStatus', 'ParticipationModel bulk workflow setter missing.'],
    [$c['rostersView'], "rosters.approve", 'Rosters toolbar must expose Approve.'],
    [$c['rostersView'], "rosters.pending", 'Rosters toolbar must expose Pending.'],
    [$c['rostersView'], "rosters.reject", 'Rosters toolbar must expose Reject.'],
    [$c['rostersController'], 'public function approve()', 'RostersController approve() missing.'],
    [$c['rostersController'], 'public function pending()', 'RostersController pending() missing.'],
    [$c['rostersController'], 'public function reject()', 'RostersController reject() missing.'],
    [$c['rosterModel'], 'public function setWorkflowStatus', 'RosterModel bulk workflow setter missing.'],
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
    [$c['installSql'], '`workflow_status` VARCHAR(32) NOT NULL DEFAULT \'draft\'', 'Fresh install season workflow column missing.'],
    [$c['upgradeSql'], 'ADD COLUMN `workflow_status`', 'Upgrade season workflow column missing.'],
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

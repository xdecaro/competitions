<?php
declare(strict_types=1);
$root = dirname(__DIR__);
$file = $root . '/component/admin/src/Model/SeasonModel.php';
if (!is_file($file)) { fwrite(STDERR, "Missing SeasonModel.php\n"); exit(1); }
$s=(string) file_get_contents($file);
foreach (["onXdecaroCompetitionSeasonDatesChanged", "DispatcherInterface", "loadSeasonDates(", "datesChanged(", "dispatchSeasonDatesChanged("] as $token) {
    if (strpos($s,$token)===false) { fwrite(STDERR, "Missing season-date event token: {$token}\n"); exit(1); }
}
echo "Competitions season date event contract OK\n";

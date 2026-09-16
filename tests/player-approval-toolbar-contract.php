<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$files = [
    'view' => $root . '/component/admin/src/View/Players/HtmlView.php',
    'controller' => $root . '/component/admin/src/Controller/PlayersController.php',
    'model' => $root . '/component/admin/src/Model/PlayerModel.php',
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
    [$view, "players.approve", 'Players toolbar must expose Approve bulk action.'],
    [$view, "players.pending", 'Players toolbar must expose Pending bulk action.'],
    [$view, "players.reject", 'Players toolbar must expose Reject bulk action.'],
    [$controller, 'public function approve()', 'PlayersController must expose approve().'],
    [$controller, 'public function pending()', 'PlayersController must expose pending().'],
    [$controller, 'public function reject()', 'PlayersController must expose reject().'],
    [$controller, 'setApprovalStatus', 'PlayersController must delegate approval changes to the model.'],
    [$model, 'public function setApprovalStatus', 'PlayerModel must implement bulk approval status changes.'],
    [$model, "'pending'", 'PlayerModel must support pending approval state.'],
    [$model, "'approved'", 'PlayerModel must support approved approval state.'],
    [$model, "'rejected'", 'PlayerModel must support rejected approval state.'],
    [$en, 'COM_XDECAROCOMPETITIONS_TOOLBAR_APPROVE=', 'English Approve toolbar label is missing.'],
    [$en, 'COM_XDECAROCOMPETITIONS_TOOLBAR_PENDING=', 'English Pending toolbar label is missing.'],
    [$en, 'COM_XDECAROCOMPETITIONS_TOOLBAR_REJECT=', 'English Reject toolbar label is missing.'],
    [$it, 'COM_XDECAROCOMPETITIONS_TOOLBAR_APPROVE=', 'Italian Approve toolbar label is missing.'],
    [$it, 'COM_XDECAROCOMPETITIONS_TOOLBAR_PENDING=', 'Italian Pending toolbar label is missing.'],
    [$it, 'COM_XDECAROCOMPETITIONS_TOOLBAR_REJECT=', 'Italian Reject toolbar label is missing.'],
];

foreach ($requirements as [$haystack, $needle, $message]) {
    if (strpos($haystack, $needle) === false) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

echo "Player approval toolbar contract OK\n";

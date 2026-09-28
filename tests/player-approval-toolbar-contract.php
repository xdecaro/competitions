<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$files = [
    'view' => $root . '/component/admin/src/View/Players/HtmlView.php',
    'controller' => $root . '/component/admin/src/Controller/PlayersController.php',
    'model' => $root . '/component/admin/src/Model/PlayerModel.php',
    'manifest' => $root . '/component/xdecarocompetitions.xml',
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
$manifest = file_get_contents($files['manifest']);

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
    [$manifest, 'en-GB/com_xdecarocompetitions.144.ini', 'Component manifest must package the English 1.4.4 language fragment.'],
    [$manifest, 'it-IT/com_xdecarocompetitions.144.ini', 'Component manifest must package the Italian 1.4.4 language fragment.'],
];

foreach ($requirements as [$haystack, $needle, $message]) {
    if (strpos($haystack, $needle) === false) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

$bulkFiles = [
    'controller' => $root . '/component/admin/src/Controller/PlayerbulkController.php',
    'model' => $root . '/component/admin/src/Model/PlayerbulkModel.php',
    'view' => $root . '/component/admin/src/View/Playerbulk/HtmlView.php',
    'template' => $root . '/component/admin/tmpl/playerbulk/default.php',
    'peopleService' => $root . '/component/admin/src/Service/PeopleIntegrationService.php',
];

foreach ($bulkFiles as $name => $path) {
    if (!is_file($path)) {
        fwrite(STDERR, "Missing player bulk {$name}: {$path}\n");
        exit(1);
    }
}

$bulkController = file_get_contents($bulkFiles['controller']);
$bulkModel = file_get_contents($bulkFiles['model']);
$bulkView = file_get_contents($bulkFiles['view']);
$bulkTemplate = file_get_contents($bulkFiles['template']);
$peopleService = file_get_contents($bulkFiles['peopleService']);

$bulkRequirements = [
    [$view, 'view=playerbulk', 'Players toolbar must link to the separate bulk-add page.'],
    [$view, 'Aggiungi giocatori', 'Players toolbar must expose the bulk-add label.'],
    [$bulkController, 'public function addSelected()', 'Player bulk controller must expose addSelected().'],
    [$bulkController, '$this->checkToken()', 'Player bulk add must validate CSRF token.'],
    [$bulkController, "authorise('core.create'", 'Player bulk add must enforce create ACL.'],
    [$bulkController, "'approval_status' => 'pending'", 'Bulk-created players must start pending.'],
    [$bulkController, "'state' => 1", 'Bulk-created players must be published.'],
    [$bulkModel, 'getAvailablePeople', 'Player bulk model must load People candidates.'],
    [$bulkModel, '#__xdecarocompetitions_players', 'Player bulk model must exclude already-linked people.'],
    [$bulkView, 'playerbulk.addSelected', 'Player bulk toolbar must submit selected people.'],
    [$bulkTemplate, 'name="person_uuid[]"', 'Player bulk page must support multi-person selection.'],
    [$bulkTemplate, 'Seleziona tutte', 'Player bulk page must expose select-all.'],
    [$bulkTemplate, 'Cerca in People', 'Player bulk page must expose People search.'],
    [$peopleService, 'min(200, $limit)', 'People integration search must allow up to 200 results for bulk selection.'],
];

foreach ($bulkRequirements as [$haystack, $needle, $message]) {
    if (strpos($haystack, $needle) === false) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

echo "Player approval toolbar and bulk-add contracts OK\n";

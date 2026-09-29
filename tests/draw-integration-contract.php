<?php
$root = dirname(__DIR__);
$servicePath = $root . '/component/admin/src/Service/DrawIntegrationService.php';
$componentPath = $root . '/component/admin/src/Extension/CompetitionsComponent.php';
$providerPath = $root . '/component/admin/services/provider.php';
$controllerPath = $root . '/component/admin/src/Controller/ParticipationsController.php';
$participationsViewPath = $root . '/component/admin/src/View/Participations/HtmlView.php';
$viewPath = $root . '/component/admin/src/View/Drawsetup/HtmlView.php';
$templatePath = $root . '/component/admin/tmpl/drawsetup/default.php';
$languageHelperPath = $root . '/component/admin/src/Helper/LanguageHelper.php';
$sqlPath = $root . '/component/admin/sql/updates/mysql/1.6.0.sql';

$failures = [];

if (!is_file($servicePath)) {
    $failures[] = 'DrawIntegrationService.php is missing.';
} else {
    $service = file_get_contents($servicePath);
    foreach ([
        'final class DrawIntegrationService',
        "MIN_DRAW_VERSION = '1.1.0'",
        "DRAW_COMPONENT = 'com_xdecarodraw'",
        "APPROVED_STATUS = 'approved'",
        'function getAvailability',
        'function getSeasonContext',
        'function createSeasonDraw',
        'function getLatestLink',
        "'schema' => 'xdecaro.draw.request.v1'",
        "'entity' => 'season'",
        "'entity' => 'participation'",
        "'type' => 'group'",
    ] as $needle) {
        if (!str_contains($service, $needle)) {
            $failures[] = 'Integration service missing contract marker: ' . $needle;
        }
    }
    if (str_contains($service, '#__xdecarodraw_')) {
        $failures[] = 'Competitions must not read Draw private tables.';
    }
}

foreach ([$componentPath, $providerPath, $controllerPath, $participationsViewPath, $viewPath, $templatePath, $languageHelperPath, $sqlPath] as $path) {
    if (!is_file($path)) {
        $failures[] = basename($path) . ' is missing.';
    }
}

if (is_file($componentPath) && !str_contains(file_get_contents($componentPath), 'function getDrawIntegrationService')) {
    $failures[] = 'CompetitionsComponent must expose getDrawIntegrationService().';
}
if (is_file($providerPath) && !str_contains(file_get_contents($providerPath), 'DrawIntegrationService::class')) {
    $failures[] = 'Provider must register DrawIntegrationService.';
}
if (is_file($controllerPath)) {
    $controller = file_get_contents($controllerPath);
    foreach (['function createDraw', "Session::checkToken('post')", "authorise('core.create', 'com_xdecarocompetitions')", "authorise('core.create', 'com_xdecarodraw')"] as $needle) {
        if (!str_contains($controller, $needle)) {
            $failures[] = 'ParticipationsController missing security/action marker: ' . $needle;
        }
    }
}
if (is_file($participationsViewPath) && !str_contains(file_get_contents($participationsViewPath), 'view=drawsetup&season_id=')) {
    $failures[] = 'Participations toolbar must expose the Draw setup for the selected season.';
}
if (is_file($templatePath) && !str_contains(file_get_contents($templatePath), 'participations.createDraw')) {
    $failures[] = 'Draw setup form must submit to participations.createDraw.';
}
if (is_file($languageHelperPath) && !str_contains(file_get_contents($languageHelperPath), "com_xdecarocompetitions.160")) {
    $failures[] = 'LanguageHelper must load the 1.6.0 Draw language pack.';
}
if (is_file($sqlPath)) {
    $sql = file_get_contents($sqlPath);
    foreach (['#__xdecarocompetitions_draw_links', 'season_id', 'draw_id', 'request_hash', 'group_count'] as $needle) {
        if (!str_contains($sql, $needle)) {
            $failures[] = 'Draw link schema missing marker: ' . $needle;
        }
    }
}

if ($failures) {
    throw new RuntimeException("Competitions Draw integration contract failed:\n- " . implode("\n- ", $failures));
}

echo "Competitions Draw integration contract passed.\n";
<?php
declare(strict_types=1);

$root = dirname(__DIR__);

$teamsView = file_get_contents($root . '/component/admin/src/View/Teams/HtmlView.php');
$importView = file_get_contents($root . '/component/admin/src/View/Teamimport/HtmlView.php');
$importModel = file_get_contents($root . '/component/admin/src/Model/TeamimportModel.php');
$importController = file_get_contents($root . '/component/admin/src/Controller/TeamimportController.php');
$importTemplate = file_get_contents($root . '/component/admin/tmpl/teamimport/default.php');
$importScript = file_get_contents($root . '/component/media/teamimport.js');
$assetRegistry = file_get_contents($root . '/component/media/joomla.asset.json');
$uiHelper = file_get_contents($root . '/component/admin/src/Helper/UiHelper.php');

$checks = [
    [str_contains($teamsView, 'view=teamimport'), 'Teams toolbar must link to the Organizations import view'],
    [str_contains($teamsView, 'COM_XDECAROCOMPETITIONS_TOOLBAR_IMPORT_FROM_ORGANIZATIONS'), 'Teams toolbar import label is missing'],
    [str_contains($importView, 'teamimport.importSelected'), 'Import view must expose Import selected in the Joomla toolbar'],
    [str_contains($importView, "authorise('core.create', 'com_xdecarocompetitions')"), 'Import view must require core.create'],
    [str_contains($importModel, 'OrganizationsIntegrationService'), 'Import preview must use the Organizations integration service'],
    [str_contains($importModel, "searchClubs('', 200)"), 'Import preview must load the public club provider window for live filtering'],
    [str_contains($importModel, 'getActiveSportsFederations'), 'Import preview must inspect active sports affiliations'],
    [!str_contains($importModel, '#__xdecaroorganizations_'), 'Import preview must not access Organizations private tables'],
    [str_contains($importModel, '#__xdecarocompetitions_teams'), 'Import preview must detect existing Competition teams'],
    [str_contains($importModel, '#__xdecarocompetitions_federations'), 'Import preview must resolve mapped Competition federations'],
    [str_contains($importController, '$this->checkToken()'), 'Import submission must validate the CSRF token'],
    [str_contains($importController, "authorise('core.create', 'com_xdecarocompetitions')"), 'Import submission must require core.create'],
    [str_contains($importController, "createModel('Team', 'Administrator'"), 'Import submission must reuse TeamModel business validation'],
    [str_contains($importController, "'approval_status' => 'pending'"), 'Imported teams must default to Pending approval'],
    [str_contains($importController, "'team_type' => 'club'"), 'Imported Organizations records must become Club teams'],
    [str_contains($importController, 'alreadyImported'), 'Import submission must skip existing Organizations links'],
    [str_contains($importTemplate, 'name="cid[]"'), 'Import preview must expose row selection checkboxes'],
    [str_contains($importTemplate, 'data-teamimport-checkall'), 'Import preview must support visible-row select-all'],
    [str_contains($importTemplate, 'COM_XDECAROCOMPETITIONS_TEAMIMPORT_STATUS_NO_AFFILIATION'), 'Import preview must expose missing-affiliation state'],
    [str_contains($importTemplate, 'COM_XDECAROCOMPETITIONS_TEAMIMPORT_STATUS_AMBIGUOUS'), 'Import preview must expose ambiguous-affiliation state'],
    [str_contains($importTemplate, 'COM_XDECAROCOMPETITIONS_TEAMIMPORT_STATUS_IMPORTED'), 'Import preview must expose already-imported state'],
    [str_contains($importTemplate, 'data-teamimport-search'), 'Import preview must expose a live-search field'],
    [str_contains($importTemplate, 'data-teamimport-row'), 'Import preview rows must expose searchable data'],
    [str_contains($importTemplate, 'data-teamimport-selected-count'), 'Import preview must show a persistent selected counter'],
    [str_contains($importTemplate, 'data-teamimport-selected-only'), 'Import preview must support reviewing selected rows only'],
    [str_contains($importTemplate, 'data-teamimport-checkall'), 'Import preview must select only visible rows'],
    [!str_contains($importTemplate, 'method="get"'), 'Live search must not submit/reload the preview page'],
    [str_contains($importScript, "search?.addEventListener('input'"), 'Team import search must filter on every input change'],
    [str_contains($importScript, 'row.hidden = !show'), 'Team import search must filter locally without rebuilding rows'],
    [str_contains($importScript, 'checkedBoxes().length'), 'Team import script must preserve and count selections across searches'],
    [str_contains($importScript, 'visibleSelectable'), 'Select-all must operate on currently visible rows'],
    [str_contains($assetRegistry, 'com_xdecarocompetitions.teamimport'), 'Team import live-search asset must be registered'],
    [str_contains($importView, 'UiHelper::loadTeamImportAsset()'), 'Team import view must load the runtime-registered asset'],
    [str_contains($uiHelper, 'com_xdecarocompetitions.teamimport.runtime'), 'UiHelper must runtime-register the team import script'],
    [str_contains($uiHelper, "'com_xdecarocompetitions/teamimport.js'"), 'Runtime team import asset must point to the installed media file'],
    [str_contains($importTemplate, 'class="input-group"'), 'Clear action must stay aligned with the live-search input'],
    [str_contains($importModel, "searchClubs('', 200)"), 'Import preview must load one stable provider window for client-side live filtering'],
];

foreach ($checks as [$ok, $message]) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

echo "Organizations team import contract OK\n";

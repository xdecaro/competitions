<?php
declare(strict_types=1);

$root = dirname(__DIR__);

$teamsView = file_get_contents($root . '/component/admin/src/View/Teams/HtmlView.php');
$importView = file_get_contents($root . '/component/admin/src/View/Teamimport/HtmlView.php');
$importModel = file_get_contents($root . '/component/admin/src/Model/TeamimportModel.php');
$importController = file_get_contents($root . '/component/admin/src/Controller/TeamimportController.php');
$importTemplate = file_get_contents($root . '/component/admin/tmpl/teamimport/default.php');

$checks = [
    [str_contains($teamsView, 'view=teamimport'), 'Teams toolbar must link to the Organizations import view'],
    [str_contains($teamsView, 'COM_XDECAROCOMPETITIONS_TOOLBAR_IMPORT_FROM_ORGANIZATIONS'), 'Teams toolbar import label is missing'],
    [str_contains($importView, 'teamimport.importSelected'), 'Import view must expose Import selected in the Joomla toolbar'],
    [str_contains($importView, "authorise('core.create', 'com_xdecarocompetitions')"), 'Import view must require core.create'],
    [str_contains($importModel, 'OrganizationsIntegrationService'), 'Import preview must use the Organizations integration service'],
    [str_contains($importModel, 'searchClubs($search, 200)'), 'Import preview must use the public club search with the documented 200-item boundary'],
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
    [str_contains($importTemplate, "HTMLHelper::_('grid.checkall')"), 'Import preview must support select-all'],
    [str_contains($importTemplate, 'COM_XDECAROCOMPETITIONS_TEAMIMPORT_STATUS_NO_AFFILIATION'), 'Import preview must expose missing-affiliation state'],
    [str_contains($importTemplate, 'COM_XDECAROCOMPETITIONS_TEAMIMPORT_STATUS_AMBIGUOUS'), 'Import preview must expose ambiguous-affiliation state'],
    [str_contains($importTemplate, 'COM_XDECAROCOMPETITIONS_TEAMIMPORT_STATUS_IMPORTED'), 'Import preview must expose already-imported state'],
];

foreach ($checks as [$ok, $message]) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

echo "Organizations team import contract OK\n";

<?php
declare(strict_types=1);

$root = dirname(__DIR__);

$service = file_get_contents($root . '/component/admin/src/Service/OrganizationsIntegrationService.php');
$form = file_get_contents($root . '/component/admin/forms/team.xml');
$model = file_get_contents($root . '/component/admin/src/Model/TeamModel.php');
$table = file_get_contents($root . '/component/admin/src/Table/TeamTable.php');
$template = file_get_contents($root . '/component/admin/tmpl/team/edit.php');
$asset = file_get_contents($root . '/component/media/joomla.asset.json');
$script = file_get_contents($root . '/component/media/team-edit.js');
$scope = file_get_contents($root . '/component/admin/src/Helper/TournamentScopeHelper.php');
$schema = file_get_contents($root . '/component/admin/sql/install.mysql.utf8mb4.sql');
$migration = file_get_contents($root . '/component/admin/sql/updates/mysql/1.5.7.sql');
$installer = file_get_contents($root . '/component/script.php');

$checks = [
    [str_contains($service, "'type' => 'club'"), 'Organizations team search must be limited to club type'],
    [str_contains($service, 'searchClubs'), 'Organizations integration must expose club search'],
    [str_contains($service, 'getClub'), 'Organizations integration must expose club lookup'],
    [str_contains($service, 'getActiveSportsFederations'), 'Organizations integration must expose active sports affiliations'],
    [str_contains($service, 'getAffiliations'), 'Organizations integration must consume the public affiliations provider'],
    [str_contains($service, "'sports_affiliation'"), 'Organizations integration must filter sports affiliations'],
    [!str_contains($service, '#__xdecaroorganizations_'), 'Competitions must not read Organizations private tables'],
    [str_contains($form, 'type="TeamOrganization"'), 'Team form must use the Organizations club picker'],
    [str_contains($form, 'name="organization_uuid"'), 'Team form must submit organization_uuid'],
    [!preg_match('/name="name"[^>]*required="true"/', $form), 'Linked club name must not block client validation'],
    [str_contains($model, 'public function save($data): bool'), 'TeamModel save signature must match BaseAdminModel'],
    [str_contains($model, 'getClub($organizationUuid)'), 'TeamModel must resolve the selected canonical club'],
    [str_contains($model, "\$data['name']"), 'TeamModel must refresh canonical name snapshot'],
    [str_contains($model, "\$data['short_name']"), 'TeamModel must refresh canonical code snapshot'],
    [str_contains($model, "if (\$existingUuid !== '')"), 'Existing canonical team link must be immutable'],
    [str_contains($model, "\$data['team_type'] = 'club'"), 'Existing linked club must remain a club'],
    [str_contains($model, 'persistOrganizationUuid'), 'TeamModel must verify organization UUID persistence'],
    [str_contains($model, 'resolveClubFederationId'), 'TeamModel must derive the Competition federation from Organizations affiliation'],
    [str_contains($model, "\$data['federation_id']"), 'TeamModel must replace manual Club federation input with derived federation data'],
    [str_contains($table, "\$linkedClub"), 'Team table must allow a linked Club with undetermined federation'],
    [str_contains($table, 'COM_XDECAROCOMPETITIONS_ERROR_TEAM_FEDERATION_UNDETERMINED_PARTICIPATIONS'), 'Existing participations must block an undetermined federation state'],
    [str_contains($table, 'COM_XDECAROCOMPETITIONS_ERROR_TEAM_ORGANIZATION_DUPLICATE'), 'Team table must reject duplicate Organizations links'],
    [str_contains($template, 'COM_XDECAROCOMPETITIONS_TEAM_LINKED_TO_ORGANIZATIONS'), 'Linked team editor must show canonical Organizations state'],
    [str_contains($template, 'data-team-local-identity'), 'Team editor must expose local identity region for national/legacy teams'],
    [str_contains($template, 'data-team-manual-federation'), 'Team editor must isolate manual federation selection to National/legacy flows'],
    [str_contains($template, 'COM_XDECAROCOMPETITIONS_TEAM_FEDERATION_DERIVED_DESC'), 'Linked Club editor must show the derived federation state'],
    [!preg_match('/name="federation_id"[^>]*required="true"/', $form), 'Manual federation must not be required for canonical Club teams'],
    [str_contains($asset, 'com_xdecarocompetitions.team-edit'), 'Team editor JavaScript asset must be registered'],
    [str_contains($script, "isClub"), 'Team editor JavaScript must switch club/national identity UI'],
    [str_contains($script, 'manualFederation.hidden = isClub'), 'Team editor must hide manual federation for canonical Clubs'],
    [str_contains($scope, 'COM_XDECAROCOMPETITIONS_ERROR_PARTICIPATION_TEAM_FEDERATION_UNDETERMINED'), 'Participation scope must reject teams without a derived federation'],
    [str_contains($schema, 'UNIQUE KEY `uq_competitions_teams_organization_uuid` (`organization_uuid`)'), 'Fresh install schema must include unique team Organizations UUID'],
    [str_contains($migration, 'ALTER TABLE `#__xdecarocompetitions_teams`'), '1.5.7 migration must target teams'],
    [str_contains($migration, 'ADD COLUMN `organization_uuid` CHAR(36) NULL'), '1.5.7 migration must add team organization_uuid'],
    [str_contains($migration, 'ADD UNIQUE KEY `uq_competitions_teams_organization_uuid`'), '1.5.7 migration must add unique team UUID index'],
    [!preg_match('/DROP\s+(TABLE|COLUMN)|TRUNCATE\s+TABLE|DELETE\s+FROM/i', $migration), 'Team Organizations migration must be non-destructive'],
    [str_contains($installer, 'ensureTeamOrganizationLinkSchema'), 'Component update repair must include team Organizations schema'],
];

foreach ($checks as [$ok, $message]) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

echo "Organizations team contract OK\n";

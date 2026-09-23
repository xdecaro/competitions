<?php
declare(strict_types=1);

$root = dirname(__DIR__);

$installer = file_get_contents($root . '/component/script.php');
$model = file_get_contents($root . '/component/admin/src/Model/FederationModel.php');
$manifest = file_get_contents($root . '/component/xdecarocompetitions.xml');
$sync = file_get_contents($root . '/component/admin/src/Controller/SyncController.php');
$helper = file_get_contents($root . '/component/admin/src/Helper/LiveSyncHelper.php');

$checks = [
    [str_contains($manifest, '<scriptfile>script.php</scriptfile>'), 'Component manifest must execute the schema repair script'],
    [str_contains($installer, "'update'"), 'Component installer must run on update'],
    [str_contains($installer, "organization_uuid"), 'Component installer must repair organization_uuid'],
    [str_contains($installer, "uq_competitions_federations_organization_uuid"), 'Component installer must repair the unique organization UUID index'],
    [str_contains($model, 'hasOrganizationUuidColumn()'), 'FederationModel must verify the link column before save'],
    [str_contains($model, 'persistOrganizationUuid('), 'FederationModel must verify canonical UUID persistence'],
    [str_contains($model, 'COM_XDECAROCOMPETITIONS_ERROR_FEDERATION_LINK_NOT_PERSISTED'), 'FederationModel must surface persistence failure'],
    [str_contains($helper, 'int $currentUserId = 0'), 'Live presence must accept current user id'],
    [str_contains($helper, "s.user_id") && str_contains($helper, "currentUserId"), 'Live presence must exclude current user sessions'],
    [str_contains($sync, 'listPresence($db, $entity, $entityId, $clientId, (int) $identity->id)'), 'Sync controller must pass the current user id'],
];

foreach ($checks as [$ok, $message]) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

echo "Federation link persistence contract OK\n";

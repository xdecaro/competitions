from __future__ import annotations

import argparse
import json
import sys
import zipfile
from pathlib import Path
import xml.etree.ElementTree as ET

ROOT = Path(__file__).resolve().parents[1]
VERSION = (ROOT / "VERSION").read_text(encoding="utf-8").strip()
MANIFESTS = [
    ROOT / "component/decarodcl.xml",
    ROOT / "package/pkg_decarodcl.xml",
    ROOT / "plugins/system/decarodcl/decarodcl.xml",
    ROOT / "modules/mod_dcl_matchtimeline/mod_dcl_matchtimeline.xml",
    ROOT / "modules/mod_dcl_countriesfederations/mod_dcl_countriesfederations.xml",
]

EDIT_FORMS = [
    "country",
    "federation",
    "organization",
    "zone",
    "tournament",
    "season",
    "team",
    "participation",
    "player",
    "roster",
    "match",
]

ADMIN_MODELS = [
    "CountryModel.php",
    "FederationModel.php",
    "OrganizationModel.php",
    "ZoneModel.php",
    "TournamentModel.php",
    "SeasonModel.php",
    "TeamModel.php",
    "ParticipationModel.php",
    "PlayerModel.php",
    "RosterModel.php",
    "MatchModel.php",
]

EXPECTED_COMPONENT_FILES = {
    "decarodcl.xml",
    "admin/forms/country.xml",
    "admin/forms/federation.xml",
    "admin/forms/tournament.xml",
    "admin/forms/season.xml",
    "admin/forms/team.xml",
    "admin/forms/participation.xml",
    "admin/forms/player.xml",
    "admin/forms/roster.xml",
    "admin/forms/organization.xml",
    "admin/forms/zone.xml",
    "admin/forms/match.xml",
    "admin/layouts/page/header.php",
    "admin/src/Controller/DisplayController.php",
    "admin/src/Controller/ScopeController.php",
    "admin/src/Controller/SyncController.php",
    "admin/src/Controller/OrganizationController.php",
    "admin/src/Controller/OrganizationsController.php",
    "admin/src/Controller/ZoneController.php",
    "admin/src/Controller/ZonesController.php",
    "admin/src/Controller/MatchController.php",
    "admin/src/Controller/MatchesController.php",
    "admin/src/Helper/LanguageHelper.php",
    "admin/src/Helper/PageHeaderHelper.php",
    "admin/src/Helper/OrganizationAssignmentHelper.php",
    "admin/src/Helper/TournamentScopeHelper.php",
    "admin/src/Helper/LiveSyncHelper.php",
    "admin/src/Model/BaseAdminModel.php",
    "admin/src/Model/OrganizationModel.php",
    "admin/src/Model/OrganizationsModel.php",
    "admin/src/Model/ZoneModel.php",
    "admin/src/Model/ZonesModel.php",
    "admin/src/Model/MatchModel.php",
    "admin/src/Model/MatchesModel.php",
    "admin/src/Model/InformationModel.php",
    "admin/src/Table/OrganizationTable.php",
    "admin/src/Table/ZoneTable.php",
    "admin/src/Table/FederationTable.php",
    "admin/src/Table/TeamTable.php",
    "admin/src/Table/TournamentTable.php",
    "admin/src/Table/ParticipationTable.php",
    "admin/src/Table/SeasonTable.php",
    "admin/src/Table/MatchTable.php",
    "admin/src/View/Organization/HtmlView.php",
    "admin/src/View/Organizations/HtmlView.php",
    "admin/src/View/Zone/HtmlView.php",
    "admin/src/View/Zones/HtmlView.php",
    "admin/src/View/Match/HtmlView.php",
    "admin/src/View/Matches/HtmlView.php",
    "admin/src/View/Information/HtmlView.php",
    "admin/tmpl/dashboard/default.php",
    "admin/tmpl/organization/edit.php",
    "admin/tmpl/organizations/default.php",
    "admin/tmpl/zone/edit.php",
    "admin/tmpl/zones/default.php",
    "admin/tmpl/match/edit.php",
    "admin/tmpl/matches/default.php",
    "admin/tmpl/information/default.php",
    "admin/tmpl/information/core.php",
    "admin/sql/install.0.7.mysql.utf8mb4.sql",
    "admin/sql/install.0.8.mysql.utf8mb4.sql",
    "admin/sql/install.0.10.mysql.utf8mb4.sql",
    "admin/sql/updates/mysql/0.7.0.sql",
    "admin/sql/updates/mysql/0.8.0.sql",
    "admin/sql/updates/mysql/0.10.0.sql",
    "admin/sql/updates/mysql/0.13.0.sql",
    "admin/language/en-GB/com_decarodcl.070.ini",
    "admin/language/en-GB/com_decarodcl.071.ini",
    "admin/language/en-GB/com_decarodcl.080.ini",
    "admin/language/en-GB/com_decarodcl.082.ini",
    "admin/language/en-GB/com_decarodcl.090.ini",
    "admin/language/en-GB/com_decarodcl.100.ini",
    "admin/language/en-GB/com_decarodcl.121.ini",
    "admin/language/it-IT/com_decarodcl.070.ini",
    "admin/language/it-IT/com_decarodcl.071.ini",
    "admin/language/it-IT/com_decarodcl.080.ini",
    "admin/language/it-IT/com_decarodcl.082.ini",
    "admin/language/it-IT/com_decarodcl.090.ini",
    "admin/language/it-IT/com_decarodcl.100.ini",
    "admin/language/it-IT/com_decarodcl.121.ini",
    "media/admin.css",
    "media/live-sync.css",
    "media/live-sync.js",
    "media/scope.js",
    "media/joomla.asset.json",
    "media/css/core-bridge.css",
    "media/css/information.css",
    "media/js/information.js",
}

EXPECTED_TIMELINE_FILES = {
    "mod_dcl_matchtimeline.xml",
    "src/Dispatcher/Dispatcher.php",
    "tmpl/default.php",
    "media/css/site.css",
    "language/en-GB/mod_dcl_matchtimeline.ini",
    "language/it-IT/mod_dcl_matchtimeline.ini",
}

UPDATE_SITE_URL = "https://raw.githubusercontent.com/xdecaro/competitions/main/updates/pkg_decarodcl.xml"
LEGACY_UPDATE_SITE_URL = "https://raw.githubusercontent.com/xdecaro/dcl/main/updates/pkg_decarodcl.xml"


def fail(message: str) -> None:
    print(f"ERROR: {message}", file=sys.stderr)
    raise SystemExit(1)


def xml_version(path: Path) -> str:
    root = ET.parse(path).getroot()
    node = root.find("version")
    return (node.text or "").strip() if node is not None else ""


def validate_no_legacy_table_prefix() -> None:
    legacy = "#__" + "dcl_"
    roots = [ROOT / "component", ROOT / "modules", ROOT / "plugins", ROOT / "package", ROOT / "docs", ROOT / "README.md"]
    for root in roots:
        paths = [root] if root.is_file() else root.rglob("*")
        for candidate in paths:
            if not candidate.is_file() or candidate.suffix.lower() not in {".php", ".sql", ".md", ".xml"}:
                continue
            if legacy in candidate.read_text(encoding="utf-8"):
                fail(f"legacy Competitions table prefix remains in {candidate.relative_to(ROOT)}")


def validate_versions() -> None:
    if not VERSION:
        fail("VERSION is empty")

    for path in MANIFESTS:
        value = xml_version(path)
        if value != VERSION:
            fail(f"{path.relative_to(ROOT)} has version {value!r}, expected {VERSION!r}")

    asset = json.loads((ROOT / "component/media/joomla.asset.json").read_text(encoding="utf-8"))
    if str(asset.get("version", "")).strip() != VERSION:
        fail("component/media/joomla.asset.json top-level version does not match VERSION")

    assets = asset.get("assets", [])
    required_assets = [
        ("com_decarodcl.admin", "style"),
        ("com_decarodcl.filterbar-style", "style"),
        ("com_decarodcl.filterbar", "script"),
        ("com_decarodcl.live-sync-style", "style"),
        ("com_decarodcl.live-sync", "script"),
        ("com_decarodcl.scope", "script"),
        ("com_decarodcl.core-bridge", "style"),
        ("com_decarodcl.information", "style"),
        ("com_decarodcl.information", "script"),
    ]
    for name, asset_type in required_assets:
        item = next((row for row in assets if row.get("name") == name and row.get("type") == asset_type), None)
        if item is None:
            fail(f"Web Asset registry is missing {name} ({asset_type})")
        if str(item.get("version", "")).strip() != VERSION:
            fail(f"Web Asset {name} ({asset_type}) version does not match VERSION")

    updates = ET.parse(ROOT / "updates/pkg_decarodcl.xml").getroot()
    update = updates.find("update")
    if update is None:
        fail("updates/pkg_decarodcl.xml has no <update>")
    if (update.findtext("version") or "").strip() != VERSION:
        fail("update-server version does not match VERSION")
    if (update.findtext("element") or "").strip() != "pkg_decarodcl":
        fail("update-server element must be pkg_decarodcl")
    if (update.findtext("type") or "").strip() != "package":
        fail("update-server type must be package")
    if (update.findtext("client") or "").strip() != "site":
        fail("update-server client must be site for the Competitions package")

    targetplatform = update.find("targetplatform")
    if targetplatform is None or (targetplatform.get("name") or "").strip().lower() != "joomla":
        fail("update-server targetplatform must target Joomla")
    if (targetplatform.get("version") or "").strip() != r"6\.[0-9]+":
        fail("update-server Joomla targetplatform regex is not the expected Joomla 6 pattern")

    download_url = (update.findtext("./downloads/downloadurl") or "").strip()
    expected_download = f"https://github.com/xdecaro/competitions/releases/download/v{VERSION}/pkg_competitions_{VERSION}.zip"
    if download_url != expected_download:
        fail(f"update-server URL must be {expected_download}")

    maintainer_url = (update.findtext("maintainerurl") or "").strip()
    if maintainer_url != "https://github.com/xdecaro/competitions":
        fail("update-server maintainer URL must point to xdecaro/competitions")

    component_manifest = ET.parse(ROOT / "component/decarodcl.xml").getroot()
    submenu_views = {node.get("view") for node in component_manifest.findall("./administration/submenu/menu")}
    for required_view in ("organizations", "zones", "matches", "information"):
        if required_view not in submenu_views:
            fail(f"component submenu is missing view {required_view}")

    admin_folders = {
        (node.text or "").strip()
        for node in component_manifest.findall("./administration/files/folder")
        if node.text
    }
    if "layouts" not in admin_folders:
        fail("component manifest is missing the shared administrator layouts folder")

    component_languages = {
        (node.text or "").strip()
        for node in component_manifest.findall("./administration/languages/language")
        if node.text
    }
    for required_language in (
        "en-GB/com_decarodcl.082.ini",
        "it-IT/com_decarodcl.082.ini",
        "en-GB/com_decarodcl.090.ini",
        "it-IT/com_decarodcl.090.ini",
        "en-GB/com_decarodcl.100.ini",
        "it-IT/com_decarodcl.100.ini",
        "en-GB/com_decarodcl.121.ini",
        "it-IT/com_decarodcl.121.ini",
    ):
        if required_language not in component_languages:
            fail(f"component manifest is missing language file {required_language}")

    component_media = {
        (node.text or "").strip()
        for node in component_manifest.findall("./media/filename")
        if node.text
    }
    for required_media in ("joomla.asset.json", "admin.css", "live-sync.css", "live-sync.js", "scope.js"):
        if required_media not in component_media:
            fail(f"component manifest is missing media file {required_media}")

    install_sql = {
        (node.text or "").strip()
        for node in component_manifest.findall("./install/sql/file")
        if node.text
    }
    expected_install_sql = {
        "sql/install.mysql.utf8mb4.sql",
        "sql/install.0.7.mysql.utf8mb4.sql",
        "sql/install.0.8.mysql.utf8mb4.sql",
        "sql/install.0.10.mysql.utf8mb4.sql",
    }
    if install_sql != expected_install_sql:
        fail(f"component install SQL list mismatch: {sorted(install_sql)}")

    migration_070 = (ROOT / "component/admin/sql/updates/mysql/0.7.0.sql").read_text(encoding="utf-8")
    for required in ("#__decarocompetitions_organizations", "#__decarocompetitions_tournament_organizations", "#__decarocompetitions_season_organizations", "#__decarocompetitions_matches", "match_id"):
        if required not in migration_070:
            fail(f"0.7.0 migration is missing {required}")

    migration_080 = (ROOT / "component/admin/sql/updates/mysql/0.8.0.sql").read_text(encoding="utf-8")
    for required in (
        "#__decarocompetitions_zones",
        "#__decarocompetitions_zone_countries",
        "African zone",
        "Asian zone",
        "European zone",
        "North, Central American and Caribbean zone",
        "Oceania zone",
        "South American zone",
    ):
        if required not in migration_080:
            fail(f"0.8.0 migration is missing {required}")

    migration_100 = (ROOT / "component/admin/sql/updates/mysql/0.10.0.sql").read_text(encoding="utf-8")
    for required in (
        "scope_type",
        "participant_type",
        "team_type",
        "#__decarocompetitions_tournament_countries",
        "#__decarocompetitions_tournament_zones",
        "#__decarocompetitions_changes",
        "#__decarocompetitions_edit_sessions",
    ):
        if required not in migration_100:
            fail(f"0.10.0 migration is missing {required}")

    tournament_form = (ROOT / "component/admin/forms/tournament.xml").read_text(encoding="utf-8")
    for required in ('name="scope_type"', 'name="participant_type"', 'name="country_id"', 'name="zone_ids"'):
        if required not in tournament_form:
            fail(f"Tournament form is missing scope field {required}")

    team_form = (ROOT / "component/admin/forms/team.xml").read_text(encoding="utf-8")
    if 'name="team_type"' not in team_form:
        fail("Team form is missing team_type")

    for form_name in EDIT_FORMS:
        form_text = (ROOT / f"component/admin/forms/{form_name}.xml").read_text(encoding="utf-8")
        if 'name="modified" type="hidden"' not in form_text:
            fail(f"{form_name}.xml is missing the rendered optimistic-lock timestamp")

    for model_name in ADMIN_MODELS:
        model_text = (ROOT / "component/admin/src/Model" / model_name).read_text(encoding="utf-8")
        if "extends BaseAdminModel" not in model_text:
            fail(f"{model_name} must extend BaseAdminModel for global Live Sync")

    base_model = (ROOT / "component/admin/src/Model/BaseAdminModel.php").read_text(encoding="utf-8")
    for required in (
        "LiveSyncHelper::currentModified",
        "LiveSyncHelper::touchModified",
        "LiveSyncHelper::record",
        "__dcl_unmodified__",
    ):
        if required not in base_model:
            fail(f"BaseAdminModel Live Sync locking is missing {required}")

    live_helper = (ROOT / "component/admin/src/Helper/LiveSyncHelper.php").read_text(encoding="utf-8")
    for required in (
        "#__decarocompetitions_changes",
        "#__decarocompetitions_edit_sessions",
        "touchModified",
        "COALESCE(",
        "__dcl_unmodified__",
    ):
        if required not in live_helper:
            fail(f"LiveSyncHelper is missing {required}")

    sync_controller = (ROOT / "component/admin/src/Controller/SyncController.php").read_text(encoding="utf-8")
    for required in ("Session::checkToken('post')", "core.manage", "LiveSyncHelper::listChanges", "LiveSyncHelper::touchPresence"):
        if required not in sync_controller:
            fail(f"SyncController is missing required protection/behaviour {required}")

    scope_controller = (ROOT / "component/admin/src/Controller/ScopeController.php").read_text(encoding="utf-8")
    for required in ("Session::checkToken('post')", "core.manage", "team_type", "#__decarocompetitions_tournament_zones"):
        if required not in scope_controller:
            fail(f"ScopeController is missing required protection/behaviour {required}")

    scope_helper = (ROOT / "component/admin/src/Helper/TournamentScopeHelper.php").read_text(encoding="utf-8")
    for required in (
        "assertTeamAttributesEligible",
        "assertExistingParticipationsCompatible",
        "assertExistingSeasonHostsCompatible",
    ):
        if required not in scope_helper:
            fail(f"TournamentScopeHelper is missing {required}")

    federation_table = (ROOT / "component/admin/src/Table/FederationTable.php").read_text(encoding="utf-8")
    if "assertTeamAttributesEligible" not in federation_table:
        fail("Federation country changes are not protected against existing participations")

    zone_model = (ROOT / "component/admin/src/Model/ZoneModel.php").read_text(encoding="utf-8")
    for required in ("assertExistingParticipationsCompatible", "assertExistingSeasonHostsCompatible"):
        if required not in zone_model:
            fail(f"Zone membership changes are not protected by {required}")

    live_script = (ROOT / "component/media/live-sync.js").read_text(encoding="utf-8")
    for required in ("BroadcastChannel", "jform[modified]", "__dcl_unmodified__", "dcl_client_id"):
        if required not in live_script:
            fail(f"Live Sync browser client is missing {required}")

    information_model = (ROOT / "component/admin/src/Model/InformationModel.php").read_text(encoding="utf-8")
    for required in (
        "extension_versions",
        "installation_consistent",
        "mod_dcl_matchtimeline",
        "mod_dcl_countriesfederations",
        "pkg_xdecarocore",
        "Core by xdecaro",
        "api_available",
        "xdecaro/competitions",
    ):
        if required not in information_model:
            fail(f"InformationModel integrity diagnostics are missing {required}")

    information_template = (ROOT / "component/admin/tmpl/information/default.php").read_text(encoding="utf-8")
    for required in (
        "dcl-information-grid",
        "dcl-card",
        "dcl-information-row",
        "dcl-badge",
        "dcl-information-integration",
        "dcl-information-checks",
        "dcl-information-details",
        "data-dcl-info-copy",
        "COM_DECARODCL_INFO_PUBLIC_API",
        "Core by xdecaro",
        "xdecaro/competitions/releases",
    ):
        if required not in information_template:
            fail(f"Information template is missing required integration/UI marker {required}")

    page_header_layout = (ROOT / "component/admin/layouts/page/header.php").read_text(encoding="utf-8")
    for required in ("dcl-page-header", "dcl-page-header__eyebrow", "dcl-page-header__title", "dcl-page-header__description"):
        if required not in page_header_layout:
            fail(f"shared page header is missing {required}")

    display_controller = (ROOT / "component/admin/src/Controller/DisplayController.php").read_text(encoding="utf-8")
    if "PageHeaderHelper::render" not in display_controller:
        fail("DisplayController does not render the shared page header")
    if "getInt('id', 0)" not in display_controller:
        fail("DisplayController must provide a zero default for the optional record id")

    page_header_helper = (ROOT / "component/admin/src/Helper/PageHeaderHelper.php").read_text(encoding="utf-8")
    if "?int $id = null" not in page_header_helper or "max(0, (int) $id)" not in page_header_helper:
        fail("PageHeaderHelper must defensively normalize an absent record id")

    language_helper = (ROOT / "component/admin/src/Helper/LanguageHelper.php").read_text(encoding="utf-8")
    if "com_decarodcl.090" not in language_helper:
        fail("LanguageHelper does not load the 0.9.0 language file")
    if "com_decarodcl.100" not in language_helper:
        fail("LanguageHelper does not load the 0.10.0 language file")
    if "com_decarodcl.121" not in language_helper:
        fail("LanguageHelper does not load the 0.12.1 language file")

    package = ET.parse(ROOT / "package/pkg_decarodcl.xml").getroot()
    shipped = {node.text.strip() for node in package.findall("./files/file") if node.text}
    expected_shipped = {
        f"com_decarodcl_{VERSION}.zip",
        f"plg_system_decarodcl_{VERSION}.zip",
        f"mod_dcl_matchtimeline_{VERSION}.zip",
        f"mod_dcl_countriesfederations_{VERSION}.zip",
    }
    if shipped != expected_shipped:
        fail(f"package nested ZIP list mismatch: {sorted(shipped)}")

    update_servers = [(node.text or "").strip() for node in package.findall("./updateservers/server")]
    if update_servers != [UPDATE_SITE_URL]:
        fail("package manifest must use only the xdecaro/competitions update server")

    package_script = (ROOT / "package/script.php").read_text(encoding="utf-8")
    for required in (
        UPDATE_SITE_URL,
        LEGACY_UPDATE_SITE_URL,
        "CURRENT_PACKAGE_ELEMENT",
        "normalizeCurrentChildren",
        "cleanupLegacyPackageMetadata",
        "package_id",
        "#__update_sites",
        "#__update_sites_extensions",
    ):
        if required not in package_script:
            fail(f"package metadata repair is missing {required}")


def validate_zip_contents(path: Path, expected: set[str], label: str) -> None:
    if not path.is_file():
        fail(f"missing {path.relative_to(ROOT)}")

    with zipfile.ZipFile(path) as archive:
        names = set(archive.namelist())

    missing = sorted(expected - names)
    if missing:
        fail(f"{label} ZIP is missing required files: {', '.join(missing)}")


def validate_dist() -> None:
    dist = ROOT / "dist"
    component_zip = dist / f"com_decarodcl_{VERSION}.zip"
    timeline_zip = dist / f"mod_dcl_matchtimeline_{VERSION}.zip"
    package_zip = dist / f"pkg_competitions_{VERSION}.zip"

    validate_zip_contents(component_zip, EXPECTED_COMPONENT_FILES, "component")
    validate_zip_contents(timeline_zip, EXPECTED_TIMELINE_FILES, "timeline module")

    if not package_zip.is_file():
        fail(f"missing {package_zip.relative_to(ROOT)}")

    with zipfile.ZipFile(package_zip) as archive:
        package_names = set(archive.namelist())

    expected_nested = {
        "pkg_decarodcl.xml",
        "script.php",
        f"com_decarodcl_{VERSION}.zip",
        f"plg_system_decarodcl_{VERSION}.zip",
        f"mod_dcl_matchtimeline_{VERSION}.zip",
        f"mod_dcl_countriesfederations_{VERSION}.zip",
    }
    missing_nested = sorted(expected_nested - package_names)
    if missing_nested:
        fail(f"package ZIP is missing required files: {', '.join(missing_nested)}")


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--dist", action="store_true")
    args = parser.parse_args()

    validate_no_legacy_table_prefix()
    validate_versions()

    if args.dist:
        validate_dist()

    print(f"Competitions {VERSION} release validation OK")


if __name__ == "__main__":
    main()

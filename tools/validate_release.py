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

EXPECTED_COMPONENT_FILES = {
    "decarodcl.xml",
    "admin/forms/tournament.xml",
    "admin/forms/season.xml",
    "admin/forms/team.xml",
    "admin/forms/participation.xml",
    "admin/forms/player.xml",
    "admin/forms/roster.xml",
    "admin/forms/organization.xml",
    "admin/forms/zone.xml",
    "admin/forms/match.xml",
    "admin/src/Controller/OrganizationController.php",
    "admin/src/Controller/OrganizationsController.php",
    "admin/src/Controller/ZoneController.php",
    "admin/src/Controller/ZonesController.php",
    "admin/src/Controller/MatchController.php",
    "admin/src/Controller/MatchesController.php",
    "admin/src/Helper/LanguageHelper.php",
    "admin/src/Helper/OrganizationAssignmentHelper.php",
    "admin/src/Model/OrganizationModel.php",
    "admin/src/Model/OrganizationsModel.php",
    "admin/src/Model/ZoneModel.php",
    "admin/src/Model/ZonesModel.php",
    "admin/src/Model/MatchModel.php",
    "admin/src/Model/MatchesModel.php",
    "admin/src/Model/InformationModel.php",
    "admin/src/Table/OrganizationTable.php",
    "admin/src/Table/ZoneTable.php",
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
    "admin/sql/install.0.7.mysql.utf8mb4.sql",
    "admin/sql/install.0.8.mysql.utf8mb4.sql",
    "admin/sql/updates/mysql/0.7.0.sql",
    "admin/sql/updates/mysql/0.8.0.sql",
    "admin/language/en-GB/com_decarodcl.070.ini",
    "admin/language/en-GB/com_decarodcl.071.ini",
    "admin/language/en-GB/com_decarodcl.080.ini",
    "admin/language/it-IT/com_decarodcl.070.ini",
    "admin/language/it-IT/com_decarodcl.071.ini",
    "admin/language/it-IT/com_decarodcl.080.ini",
    "media/admin.css",
    "media/joomla.asset.json",
}

EXPECTED_TIMELINE_FILES = {
    "mod_dcl_matchtimeline.xml",
    "src/Dispatcher/Dispatcher.php",
    "tmpl/default.php",
    "media/css/site.css",
    "language/en-GB/mod_dcl_matchtimeline.ini",
    "language/it-IT/mod_dcl_matchtimeline.ini",
}

UPDATE_SITE_URL = "https://raw.githubusercontent.com/xdecaro/dcl/main/updates/pkg_decarodcl.xml"


def fail(message: str) -> None:
    print(f"ERROR: {message}", file=sys.stderr)
    raise SystemExit(1)


def xml_version(path: Path) -> str:
    root = ET.parse(path).getroot()
    node = root.find("version")
    return (node.text or "").strip() if node is not None else ""


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

    for item in asset.get("assets", []):
        if item.get("name") == "com_decarodcl.admin":
            if str(item.get("version", "")).strip() != VERSION:
                fail("com_decarodcl.admin asset version does not match VERSION")
            break
    else:
        fail("com_decarodcl.admin asset is missing")

    updates = ET.parse(ROOT / "updates/pkg_decarodcl.xml").getroot()
    update = updates.find("update")
    if update is None:
        fail("updates/pkg_decarodcl.xml has no <update>")
    if (update.findtext("version") or "").strip() != VERSION:
        fail("update-server version does not match VERSION")

    download_url = (update.findtext("./downloads/downloadurl") or "").strip()
    expected_download = f"/v{VERSION}/pkg_decarodcl_{VERSION}.zip"
    if expected_download not in download_url:
        fail(f"update-server URL does not contain {expected_download}")

    component_manifest = ET.parse(ROOT / "component/decarodcl.xml").getroot()
    submenu_views = {node.get("view") for node in component_manifest.findall("./administration/submenu/menu")}
    for required_view in ("organizations", "zones", "matches", "information"):
        if required_view not in submenu_views:
            fail(f"component submenu is missing view {required_view}")

    install_sql = {
        (node.text or "").strip()
        for node in component_manifest.findall("./install/sql/file")
        if node.text
    }
    expected_install_sql = {
        "sql/install.mysql.utf8mb4.sql",
        "sql/install.0.7.mysql.utf8mb4.sql",
        "sql/install.0.8.mysql.utf8mb4.sql",
    }
    if install_sql != expected_install_sql:
        fail(f"component install SQL list mismatch: {sorted(install_sql)}")

    migration_070 = (ROOT / "component/admin/sql/updates/mysql/0.7.0.sql").read_text(encoding="utf-8")
    for required in ("#__dcl_organizations", "#__dcl_tournament_organizations", "#__dcl_season_organizations", "#__dcl_matches", "match_id"):
        if required not in migration_070:
            fail(f"0.7.0 migration is missing {required}")

    migration_080 = (ROOT / "component/admin/sql/updates/mysql/0.8.0.sql").read_text(encoding="utf-8")
    for required in (
        "#__dcl_zones",
        "#__dcl_zone_countries",
        "African zone",
        "Asian zone",
        "European zone",
        "North, Central American and Caribbean zone",
        "Oceania zone",
        "South American zone",
    ):
        if required not in migration_080:
            fail(f"0.8.0 migration is missing {required}")

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
    if UPDATE_SITE_URL not in update_servers:
        fail("package manifest is missing the Competitions update server")

    package_script = (ROOT / "package/script.php").read_text(encoding="utf-8")
    for required in (UPDATE_SITE_URL, "#__update_sites", "#__update_sites_extensions"):
        if required not in package_script:
            fail(f"package update-site repair is missing {required}")


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
    package_zip = dist / f"pkg_decarodcl_{VERSION}.zip"

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

    validate_versions()

    if args.dist:
        validate_dist()

    print(f"Competitions {VERSION} release validation OK")


if __name__ == "__main__":
    main()

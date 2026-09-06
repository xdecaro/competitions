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
    "admin/src/Controller/TournamentController.php",
    "admin/src/Controller/SeasonController.php",
    "admin/src/Controller/TeamController.php",
    "admin/src/Controller/ParticipationController.php",
    "admin/src/Controller/PlayerController.php",
    "admin/src/Controller/RosterController.php",
    "admin/src/Model/TournamentsModel.php",
    "admin/src/Model/SeasonsModel.php",
    "admin/src/Model/TeamsModel.php",
    "admin/src/Model/ParticipationsModel.php",
    "admin/src/Model/PlayersModel.php",
    "admin/src/Model/RostersModel.php",
    "admin/src/Table/TournamentTable.php",
    "admin/src/Table/SeasonTable.php",
    "admin/src/Table/TeamTable.php",
    "admin/src/Table/ParticipationTable.php",
    "admin/src/Table/PlayerTable.php",
    "admin/src/Table/RosterTable.php",
    "admin/tmpl/dashboard/default.php",
    "admin/tmpl/tournaments/default.php",
    "admin/tmpl/tournament/edit.php",
    "admin/tmpl/seasons/default.php",
    "admin/tmpl/season/edit.php",
    "admin/tmpl/teams/default.php",
    "admin/tmpl/team/edit.php",
    "admin/tmpl/participations/default.php",
    "admin/tmpl/participation/edit.php",
    "admin/tmpl/players/default.php",
    "admin/tmpl/player/edit.php",
    "admin/tmpl/rosters/default.php",
    "admin/tmpl/roster/edit.php",
    "media/admin.css",
    "media/joomla.asset.json",
}


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


def validate_dist() -> None:
    dist = ROOT / "dist"
    component_zip = dist / f"com_decarodcl_{VERSION}.zip"
    package_zip = dist / f"pkg_decarodcl_{VERSION}.zip"

    if not component_zip.is_file():
        fail(f"missing {component_zip.relative_to(ROOT)}")
    if not package_zip.is_file():
        fail(f"missing {package_zip.relative_to(ROOT)}")

    with zipfile.ZipFile(component_zip) as archive:
        names = set(archive.namelist())
    missing = sorted(EXPECTED_COMPONENT_FILES - names)
    if missing:
        fail(f"component ZIP is missing required files: {', '.join(missing)}")

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

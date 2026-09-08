from __future__ import annotations

import argparse
import json
import sys
import zipfile
from pathlib import Path
import xml.etree.ElementTree as ET

ROOT = Path(__file__).resolve().parents[1]
VERSION = (ROOT / "VERSION").read_text(encoding="utf-8").strip()
CURRENT_VERSION = "1.1.0"
CANONICAL_NAMESPACE = "xdecaro\\Component\\Competitions"


def fail(message: str) -> None:
    print(f"ERROR: {message}", file=sys.stderr)
    raise SystemExit(1)


def version(path: Path) -> str:
    node = ET.parse(path).getroot().find("version")
    return (node.text or "").strip() if node is not None else ""


def forbidden_table_prefixes() -> tuple[str, ...]:
    # Build pre-1.0 prefixes without carrying their literal identifiers in the clean tree.
    return (
        "#__" + "d" + "cl_",
        "#__" + "decaro" + "competitions_",
    )


def validate_database_namespace(sql: str, label: str) -> None:
    canonical = "#__xdecarocompetitions_"
    if canonical not in sql:
        fail(f"{label} does not contain the canonical database namespace")
    for prefix in forbidden_table_prefixes():
        if prefix in sql:
            fail(f"{label} contains a pre-1.0 database namespace")


def validate_source() -> None:
    if VERSION != CURRENT_VERSION:
        fail(f"expected {CURRENT_VERSION}, got {VERSION}")

    manifests = [
        ROOT / "component/xdecarocompetitions.xml",
        ROOT / "package/pkg_xdecarocompetitions.xml",
        ROOT / "plugins/system/xdecarocompetitions/xdecarocompetitions.xml",
        ROOT / "modules/mod_xdecarocompetitions_matchtimeline/mod_xdecarocompetitions_matchtimeline.xml",
        ROOT / "modules/mod_xdecarocompetitions_countriesfederations/mod_xdecarocompetitions_countriesfederations.xml",
    ]
    for path in manifests:
        if not path.is_file():
            fail(f"missing manifest {path.relative_to(ROOT)}")
        if version(path) != VERSION:
            fail(f"version mismatch in {path.relative_to(ROOT)}")

    component = ET.parse(ROOT / "component/xdecarocompetitions.xml").getroot()
    if (component.findtext("namespace") or "").strip() != CANONICAL_NAMESPACE:
        fail(f"component namespace is not {CANONICAL_NAMESPACE}")
    if component.find("scriptfile") is not None:
        fail("clean component baseline must not carry a migration script")
    install_files = [(node.text or "").strip() for node in component.findall("./install/sql/file")]
    if install_files != ["sql/install.mysql.utf8mb4.sql"]:
        fail(f"clean install SQL list is not canonical: {install_files}")

    package = ET.parse(ROOT / "package/pkg_xdecarocompetitions.xml").getroot()
    if (package.findtext("packagename") or "").strip() != "xdecarocompetitions":
        fail("package name is not xdecarocompetitions")
    child_ids = {(node.get("type"), node.get("id"), node.get("group")) for node in package.findall("./files/file")}
    required = {
        ("component", "com_xdecarocompetitions", None),
        ("plugin", "xdecarocompetitions", "system"),
        ("module", "mod_xdecarocompetitions_matchtimeline", None),
        ("module", "mod_xdecarocompetitions_countriesfederations", None),
    }
    if not required.issubset(child_ids):
        fail(f"package child identifiers are incomplete: {child_ids}")

    sql = (ROOT / "component/admin/sql/install.mysql.utf8mb4.sql").read_text(encoding="utf-8")
    validate_database_namespace(sql, "fresh-install SQL")
    required_schema_tokens = [
        "#__xdecarocompetitions_organizations",
        "#__xdecarocompetitions_tournament_organizations",
        "#__xdecarocompetitions_season_organizations",
        "#__xdecarocompetitions_matches",
        "#__xdecarocompetitions_zones",
        "#__xdecarocompetitions_zone_countries",
        "#__xdecarocompetitions_tournament_countries",
        "#__xdecarocompetitions_tournament_zones",
        "#__xdecarocompetitions_changes",
        "#__xdecarocompetitions_edit_sessions",
        "`team_type` VARCHAR(20)",
        "`scope_type` VARCHAR(20)",
        "`participant_type` VARCHAR(20)",
        "`match_id` BIGINT UNSIGNED",
    ]
    for token in required_schema_tokens:
        if token not in sql:
            fail(f"canonical fresh-install schema is missing {token}")
    if "ALTER TABLE" in sql.upper():
        fail("canonical fresh-install schema must not replay migration ALTER statements")

    update_dir = ROOT / "component/admin/sql/updates/mysql"
    update_files = sorted(path.name for path in update_dir.glob("*.sql"))
    if update_files != ["1.0.0.sql", "1.1.0.sql"]:
        fail(f"schema history must contain 1.0.0 and 1.1.0 markers, got {update_files}")

    marker = (update_dir / "1.1.0.sql").read_text(encoding="utf-8")
    if "ALTER TABLE" in marker.upper() or "DROP TABLE" in marker.upper() or "TRUNCATE TABLE" in marker.upper():
        fail("1.1.0 namespace-only schema marker must not mutate database data or structure")

    asset = json.loads((ROOT / "component/media/joomla.asset.json").read_text(encoding="utf-8"))
    if str(asset.get("version")) != VERSION:
        fail("Web Asset registry version mismatch")
    names = {row.get("name") for row in asset.get("assets", [])}
    for required_asset in ["com_xdecarocompetitions.admin", "com_xdecarocompetitions.core-bridge"]:
        if required_asset not in names:
            fail(f"missing Web Asset {required_asset}")

    feed = ET.parse(ROOT / "updates/pkg_xdecarocompetitions.xml").getroot().find("update")
    if feed is None:
        fail("update feed has no update element")
    if (feed.findtext("element") or "").strip() != "pkg_xdecarocompetitions":
        fail("update feed element mismatch")
    if (feed.findtext("version") or "").strip() != VERSION:
        fail("update feed version mismatch")
    expected_url = f"https://github.com/xdecaro/competitions/releases/download/v{VERSION}/pkg_xdecarocompetitions_{VERSION}.zip"
    if (feed.findtext("./downloads/downloadurl") or "").strip() != expected_url:
        fail("update feed download URL mismatch")

    core_service = ROOT / "component/admin/src/Service/CoreIntegrationService.php"
    if not core_service.is_file():
        fail("CoreIntegrationService is missing")
    core_text = core_service.read_text(encoding="utf-8")
    if "com_xdecarocompetitions" not in core_text:
        fail("Core public references do not use com_xdecarocompetitions")

    for path in list((ROOT / "component").rglob("*.php")) + list((ROOT / "modules").rglob("*.php")) + list((ROOT / "plugins").rglob("*.php")):
        text = path.read_text(encoding="utf-8")
        if "namespace Xdecaro\\" in text or "use Xdecaro\\" in text:
            fail(f"uppercase vendor namespace remains in {path.relative_to(ROOT)}")


def validate_dist() -> None:
    expected = [
        ROOT / "dist" / f"com_xdecarocompetitions_{VERSION}.zip",
        ROOT / "dist" / f"plg_system_xdecarocompetitions_{VERSION}.zip",
        ROOT / "dist" / f"mod_xdecarocompetitions_matchtimeline_{VERSION}.zip",
        ROOT / "dist" / f"mod_xdecarocompetitions_countriesfederations_{VERSION}.zip",
        ROOT / "dist" / f"pkg_xdecarocompetitions_{VERSION}.zip",
    ]
    for path in expected:
        if not path.is_file():
            fail(f"missing distribution artifact {path.name}")
        with zipfile.ZipFile(path) as archive:
            bad = archive.testzip()
            if bad is not None:
                fail(f"corrupt ZIP member {bad} in {path.name}")

    component = expected[0]
    with zipfile.ZipFile(component) as archive:
        names = set(archive.namelist())
        for required in ["xdecarocompetitions.xml", "admin/sql/install.mysql.utf8mb4.sql", "admin/src/Service/CoreIntegrationService.php"]:
            if required not in names:
                fail(f"component ZIP is missing {required}")
        sql = archive.read("admin/sql/install.mysql.utf8mb4.sql").decode("utf-8")
        validate_database_namespace(sql, "component ZIP install SQL")
        manifest = ET.fromstring(archive.read("xdecarocompetitions.xml"))
        if (manifest.findtext("namespace") or "").strip() != CANONICAL_NAMESPACE:
            fail("component ZIP namespace is not canonical lowercase xdecaro")

    package = expected[-1]
    with zipfile.ZipFile(package) as archive:
        names = set(archive.namelist())
        required = {
            "pkg_xdecarocompetitions.xml",
            f"com_xdecarocompetitions_{VERSION}.zip",
            f"plg_system_xdecarocompetitions_{VERSION}.zip",
            f"mod_xdecarocompetitions_matchtimeline_{VERSION}.zip",
            f"mod_xdecarocompetitions_countriesfederations_{VERSION}.zip",
        }
        if not required.issubset(names):
            fail(f"package ZIP contents are incomplete: {sorted(required - names)}")


parser = argparse.ArgumentParser()
parser.add_argument("--dist", action="store_true")
args = parser.parse_args()
validate_source()
if args.dist:
    validate_dist()
print(f"Competitions {VERSION} validation OK")

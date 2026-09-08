from __future__ import annotations

import json
import re
import shutil
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
VERSION = "1.0.0"

TEXT_SUFFIXES = {".php", ".xml", ".ini", ".sql", ".md", ".json", ".py", ".css", ".js", ".txt"}

CONTENT_REPLACEMENTS = [
    ("#__decarocompetitions_", "#__xdecarocompetitions_"),
    ("#__dcl_", "#__xdecarocompetitions_"),
    ("DECARODCL", "XDECAROCOMPETITIONS"),
    ("Decarodcl", "Competitions"),
    ("decarodcl", "xdecarocompetitions"),
    ("MOD_DCL_MATCHTIMELINE", "MOD_XDECAROCOMPETITIONS_MATCHTIMELINE"),
    ("MOD_DCL_COUNTRIESFEDERATIONS", "MOD_XDECAROCOMPETITIONS_COUNTRIESFEDERATIONS"),
    ("DclMatchTimeline", "CompetitionsMatchTimeline"),
    ("DclCountriesFederations", "CompetitionsCountriesFederations"),
    ("mod_dcl_matchtimeline", "mod_xdecarocompetitions_matchtimeline"),
    ("mod_dcl_countriesfederations", "mod_xdecarocompetitions_countriesfederations"),
    ("DCL", "Competitions"),
    ("Dcl", "Competitions"),
    ("dcl", "competitions"),
]

PATH_REPLACEMENTS = [
    ("decarodcl", "xdecarocompetitions"),
    ("Decarodcl", "Competitions"),
    ("DECARODCL", "XDECAROCOMPETITIONS"),
    ("mod_dcl_matchtimeline", "mod_xdecarocompetitions_matchtimeline"),
    ("mod_dcl_countriesfederations", "mod_xdecarocompetitions_countriesfederations"),
    ("dcl", "xdecarocompetitions"),
    ("Dcl", "Competitions"),
    ("DCL", "XDECAROCOMPETITIONS"),
]


def replace_content(text: str) -> str:
    for old, new in CONTENT_REPLACEMENTS:
        text = text.replace(old, new)
    return text


def merge_component_language(tag: str) -> None:
    folder = ROOT / "component/admin/language" / tag
    main = folder / f"com_decarodcl.ini"
    fragments = sorted(
        p for p in folder.glob("com_decarodcl.*.ini")
        if not p.name.endswith(".sys.ini")
    )
    files = [main, *fragments]
    ordered: list[str] = []
    values: dict[str, str] = {}
    for path in files:
        if not path.exists():
            continue
        for raw in path.read_text(encoding="utf-8").splitlines():
            match = re.match(r"^([A-Z0-9_]+)=(.*)$", raw)
            if not match:
                continue
            key = match.group(1)
            if key not in values:
                ordered.append(key)
            values[key] = raw
    main.write_text(
        "; Competitions by xdecaro — unified language file\n"
        + "\n".join(values[key] for key in ordered)
        + "\n",
        encoding="utf-8",
    )
    for path in fragments:
        path.unlink()


def remove_legacy_schema_files() -> None:
    sql = ROOT / "component/admin/sql"
    for path in sql.glob("install.*.mysql.utf8mb4.sql"):
        if path.name != "install.mysql.utf8mb4.sql":
            path.unlink()
    updates = sql / "updates/mysql"
    if updates.exists():
        for path in updates.iterdir():
            if path.is_file():
                path.unlink()
    updates.mkdir(parents=True, exist_ok=True)
    (updates / "1.0.0.sql").write_text(
        "-- Competitions by xdecaro 1.0.0 clean schema baseline.\n"
        "-- Fresh installations are defined by admin/sql/install.mysql.utf8mb4.sql.\n",
        encoding="utf-8",
    )
    component_script = ROOT / "component/script.php"
    if component_script.exists():
        component_script.unlink()


def transform_text_files() -> None:
    roots = [ROOT / "component", ROOT / "modules", ROOT / "plugins", ROOT / "package", ROOT / "updates", ROOT / "docs", ROOT / "tools"]
    for root in roots:
        if not root.exists():
            continue
        for path in root.rglob("*"):
            if not path.is_file() or ".github" in path.parts:
                continue
            if path.suffix.lower() not in TEXT_SUFFIXES and path.name != "VERSION":
                continue
            try:
                text = path.read_text(encoding="utf-8")
            except UnicodeDecodeError:
                continue
            path.write_text(replace_content(text), encoding="utf-8")
    for path in [ROOT / "AGENTS.md"]:
        if path.exists():
            path.write_text(replace_content(path.read_text(encoding="utf-8")), encoding="utf-8")


def renamed(name: str) -> str:
    result = name
    for old, new in PATH_REPLACEMENTS:
        result = result.replace(old, new)
    return result


def rename_paths() -> None:
    for root_name in ["component", "modules", "plugins", "package", "updates"]:
        root = ROOT / root_name
        if not root.exists():
            continue
        paths = sorted(root.rglob("*"), key=lambda p: len(p.parts), reverse=True)
        for path in paths:
            new_name = renamed(path.name)
            if new_name == path.name:
                continue
            target = path.with_name(new_name)
            if target.exists():
                raise RuntimeError(f"rename target already exists: {target}")
            path.rename(target)


def clean_component_manifest() -> None:
    path = ROOT / "component/xdecarocompetitions.xml"
    text = path.read_text(encoding="utf-8")
    text = re.sub(r"\n\s*<scriptfile>script\.php</scriptfile>", "", text)
    text = re.sub(r"\n\s*<language[^>]*>[^<]*\.0(?:70|71|80|82|90)\.ini</language>", "", text)
    text = re.sub(r"\n\s*<language[^>]*>[^<]*\.(?:100|121)\.ini</language>", "", text)
    text = re.sub(r"\n\s*<file driver=\"mysql\" charset=\"utf8mb4\">sql/install\.[^<]+</file>", "", text)
    text = re.sub(r"<version>[^<]+</version>", f"<version>{VERSION}</version>", text, count=1)
    path.write_text(text, encoding="utf-8")


def set_manifest_versions() -> None:
    manifests = [
        ROOT / "component/xdecarocompetitions.xml",
        ROOT / "package/pkg_xdecarocompetitions.xml",
        ROOT / "plugins/system/xdecarocompetitions/xdecarocompetitions.xml",
        ROOT / "modules/mod_xdecarocompetitions_matchtimeline/mod_xdecarocompetitions_matchtimeline.xml",
        ROOT / "modules/mod_xdecarocompetitions_countriesfederations/mod_xdecarocompetitions_countriesfederations.xml",
    ]
    for path in manifests:
        text = path.read_text(encoding="utf-8")
        text = re.sub(r"<version>[^<]+</version>", f"<version>{VERSION}</version>", text, count=1)
        text = re.sub(r"_[0-9]+\.[0-9]+\.[0-9]+\.zip", f"_{VERSION}.zip", text)
        path.write_text(text, encoding="utf-8")


def rewrite_package_script() -> None:
    path = ROOT / "package/script.php"
    path.write_text(r'''<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;

/**
 * Minimal installer for the clean Competitions by xdecaro package identity.
 * No legacy package, table or update-site migration is performed.
 */
final class PkgXdecarocompetitionsInstallerScript
{
    public function postflight($type, $parent): void
    {
        if (!in_array((string) $type, ['install', 'update', 'discover_install'], true)) {
            return;
        }

        try {
            /** @var DatabaseInterface $db */
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__extensions'))
                ->set($db->quoteName('enabled') . ' = 1')
                ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
                ->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('xdecarocompetitions'));
            $db->setQuery($query)->execute();
        } catch (Throwable $e) {
            Log::add(
                'Competitions package postflight warning: ' . $e->getMessage(),
                Log::WARNING,
                'competitions'
            );
        }
    }
}
''', encoding="utf-8")


def rewrite_update_feed() -> None:
    updates = ROOT / "updates"
    old = updates / "pkg_xdecarocompetitions.xml"
    old.write_text(f'''<?xml version="1.0" encoding="utf-8"?>
<updates>
  <update>
    <name>Competitions by xdecaro</name>
    <description>Competitions by xdecaro package for Joomla 6.</description>
    <element>pkg_xdecarocompetitions</element>
    <type>package</type>
    <client>site</client>
    <version>{VERSION}</version>
    <downloads>
      <downloadurl type="full" format="zip">https://github.com/xdecaro/competitions/releases/download/v{VERSION}/pkg_xdecarocompetitions_{VERSION}.zip</downloadurl>
    </downloads>
    <tags><tag>stable</tag></tags>
    <sha256></sha256>
    <maintainer>Luca De Caro</maintainer>
    <maintainerurl>https://github.com/xdecaro/competitions</maintainerurl>
    <targetplatform name="joomla" version="6\\.[0-9]+" />
    <php_minimum>8.3.0</php_minimum>
  </update>
</updates>
''', encoding="utf-8")


def rewrite_build_tools() -> None:
    (ROOT / "tools/build.py").write_text(r'''from pathlib import Path
import hashlib
import shutil
import zipfile

ROOT = Path(__file__).resolve().parents[1]
VERSION = (ROOT / "VERSION").read_text(encoding="utf-8").strip()
DIST = ROOT / "dist"
DIST.mkdir(exist_ok=True)
FIXED_TIME = (2026, 1, 1, 0, 0, 0)


def zip_dir(src: Path, out: Path) -> None:
    if out.exists():
        out.unlink()
    with zipfile.ZipFile(out, "w", zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
        for path in sorted(src.rglob("*")):
            if not path.is_file() or path.name == ".keep":
                continue
            info = zipfile.ZipInfo(path.relative_to(src).as_posix(), FIXED_TIME)
            info.compress_type = zipfile.ZIP_DEFLATED
            info.external_attr = 0o100644 << 16
            archive.writestr(info, path.read_bytes(), compress_type=zipfile.ZIP_DEFLATED, compresslevel=9)


artifacts = []
component = DIST / f"com_xdecarocompetitions_{VERSION}.zip"
zip_dir(ROOT / "component", component)
artifacts.append(component)

plugin = DIST / f"plg_system_xdecarocompetitions_{VERSION}.zip"
zip_dir(ROOT / "plugins/system/xdecarocompetitions", plugin)
artifacts.append(plugin)

for module in ["mod_xdecarocompetitions_matchtimeline", "mod_xdecarocompetitions_countriesfederations"]:
    artifact = DIST / f"{module}_{VERSION}.zip"
    zip_dir(ROOT / "modules" / module, artifact)
    artifacts.append(artifact)

stage = DIST / "package-stage"
if stage.exists():
    shutil.rmtree(stage)
stage.mkdir()
for artifact in artifacts:
    shutil.copyfile(artifact, stage / artifact.name)
shutil.copyfile(ROOT / "package/pkg_xdecarocompetitions.xml", stage / "pkg_xdecarocompetitions.xml")
shutil.copyfile(ROOT / "package/script.php", stage / "script.php")
shutil.copytree(ROOT / "package/language", stage / "language")

package = DIST / f"pkg_xdecarocompetitions_{VERSION}.zip"
zip_dir(stage, package)
artifacts.append(package)

checksums = [f"{hashlib.sha256(path.read_bytes()).hexdigest()}  {path.name}" for path in artifacts]
(DIST / "SHA256SUMS.txt").write_text("\n".join(checksums) + "\n", encoding="utf-8")
shutil.rmtree(stage)
print(package)
''', encoding="utf-8")

    (ROOT / "tools/update_feed_checksum.py").write_text(r'''from pathlib import Path
import hashlib
import re

ROOT = Path(__file__).resolve().parents[1]
version = (ROOT / "VERSION").read_text(encoding="utf-8").strip()
package = ROOT / "dist" / f"pkg_xdecarocompetitions_{version}.zip"
feed = ROOT / "updates/pkg_xdecarocompetitions.xml"
digest = hashlib.sha256(package.read_bytes()).hexdigest()
text = feed.read_text(encoding="utf-8")
text, count = re.subn(r"<sha256>[^<]*</sha256>", f"<sha256>{digest}</sha256>", text, count=1)
if count != 1:
    raise SystemExit("Unable to update SHA-256 in update feed")
feed.write_text(text, encoding="utf-8")
print(digest)
''', encoding="utf-8")

    (ROOT / "tools/validate_release.py").write_text(r'''from __future__ import annotations

import argparse
import json
import re
import sys
import zipfile
from pathlib import Path
import xml.etree.ElementTree as ET

ROOT = Path(__file__).resolve().parents[1]
VERSION = (ROOT / "VERSION").read_text(encoding="utf-8").strip()
LEGACY_PATTERNS = [
    "com_decarodcl", "pkg_decarodcl", "plg_system_decarodcl", "mod_dcl_",
    "#__dcl_", "#__decarocompetitions_", "Xdecaro\\Component\\Decarodcl",
]


def fail(message: str) -> None:
    print(f"ERROR: {message}", file=sys.stderr)
    raise SystemExit(1)


def version(path: Path) -> str:
    node = ET.parse(path).getroot().find("version")
    return (node.text or "").strip() if node is not None else ""


def validate_source() -> None:
    if VERSION != "1.0.0":
        fail(f"expected clean baseline 1.0.0, got {VERSION}")

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
    if (component.findtext("namespace") or "").strip() != "Xdecaro\\Component\\Competitions":
        fail("component namespace is not Xdecaro\\Component\\Competitions")
    if component.find("scriptfile") is not None:
        fail("clean component baseline must not carry a legacy migration script")
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
    if "#__xdecarocompetitions_" not in sql:
        fail("fresh-install SQL does not use #__xdecarocompetitions_ tables")
    if "#__decarocompetitions_" in sql or "#__dcl_" in sql:
        fail("fresh-install SQL still contains a legacy table prefix")

    update_dir = ROOT / "component/admin/sql/updates/mysql"
    update_files = sorted(path.name for path in update_dir.glob("*.sql"))
    if update_files != ["1.0.0.sql"]:
        fail(f"clean schema history must contain only 1.0.0.sql, got {update_files}")

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

    roots = [ROOT / "component", ROOT / "modules", ROOT / "plugins", ROOT / "package", ROOT / "updates", ROOT / "docs", ROOT / "README.md", ROOT / "AGENTS.md"]
    for root in roots:
        candidates = [root] if root.is_file() else root.rglob("*")
        for path in candidates:
            if not path.is_file():
                continue
            try:
                text = path.read_text(encoding="utf-8")
            except UnicodeDecodeError:
                continue
            for pattern in LEGACY_PATTERNS:
                if pattern in text:
                    fail(f"legacy identifier {pattern!r} remains in {path.relative_to(ROOT)}")
            lower_name = path.name.lower()
            if "decarodcl" in lower_name or lower_name.startswith("mod_dcl_"):
                fail(f"legacy filename remains: {path.relative_to(ROOT)}")

    core_service = ROOT / "component/admin/src/Service/CoreIntegrationService.php"
    if not core_service.is_file():
        fail("CoreIntegrationService is missing")
    core_text = core_service.read_text(encoding="utf-8")
    if "com_xdecarocompetitions" not in core_text:
        fail("Core public references do not use com_xdecarocompetitions")


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
        if "#__xdecarocompetitions_" not in sql or "#__decarocompetitions_" in sql or "#__dcl_" in sql:
            fail("component ZIP has an invalid database namespace")

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
print(f"Competitions {VERSION} clean-base validation OK")
''', encoding="utf-8")


def rewrite_docs() -> None:
    (ROOT / "VERSION").write_text(VERSION + "\n", encoding="utf-8")
    (ROOT / "README.md").write_text('''# Competitions by xdecaro

Competitions by xdecaro is the competition-management component in the xdecaro Joomla ecosystem.

## Stable technical identity

- Component: `com_xdecarocompetitions`
- Package: `pkg_xdecarocompetitions`
- System plugin: `plg_system_xdecarocompetitions`
- Match Timeline module: `mod_xdecarocompetitions_matchtimeline`
- Countries/Federations module: `mod_xdecarocompetitions_countriesfederations`
- PHP component namespace: `Xdecaro\\Component\\Competitions`
- Database tables: `#__xdecarocompetitions_*`
- Repository: `xdecaro/competitions`

## Current version

**1.0.0**

Version 1.0.0 is a clean technical baseline. It intentionally does not provide an automatic migration from earlier experimental package/component identities. Install it as a fresh extension.

## Architecture

Competitions owns competition-domain data: countries and sporting territories, federations, organizations used in competition roles, zones, tournaments, seasons, teams, participations, players, rosters, matches, match events, rankings/coefficient data and synchronization state.

Cross-product integration must use Xdecaro Core public contracts. Other xdecaro components must not read or write Competitions private tables directly.

Core integration remains infrastructure-only: public references, shared administrator design assets, diagnostics and reusable technical services. Competition rules and sports-domain behavior remain in this repository.

## Joomla baseline

The 1.0.0 release targets Joomla 6 and PHP 8.3+. Compatibility with earlier Joomla versions is not claimed until runtime-tested.

## Data policy

Fresh installations create only `#__xdecarocompetitions_*` tables. The 1.0.0 source tree contains no legacy table-prefix migration. Normal future updates must preserve 1.x data and configuration using additive or otherwise safe migrations.
''', encoding="utf-8")
    (ROOT / "CHANGELOG.md").write_text('''# Changelog

## 1.0.0 - 2026-09-08

- Established a clean Competitions by xdecaro technical identity.
- Component: `com_xdecarocompetitions`.
- Package: `pkg_xdecarocompetitions`.
- PHP namespace: `Xdecaro\\Component\\Competitions`.
- Database namespace: `#__xdecarocompetitions_*`.
- Renamed the system plugin and site modules to the xdecaro Competitions namespace.
- Consolidated administrator language files into one current file per language.
- Removed pre-1.0 schema/install migration history from the active 1.0 source tree.
- Preserved current competition-domain behavior and Core by xdecaro integration.
- This is a fresh-install baseline and intentionally does not auto-migrate experimental pre-1.0 identities.
''', encoding="utf-8")


def update_asset_version() -> None:
    path = ROOT / "component/media/joomla.asset.json"
    data = json.loads(path.read_text(encoding="utf-8"))
    data["version"] = VERSION
    for asset in data.get("assets", []):
        asset["version"] = VERSION
    path.write_text(json.dumps(data, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")


def main() -> None:
    for tag in ["en-GB", "it-IT"]:
        merge_component_language(tag)
    remove_legacy_schema_files()
    transform_text_files()
    rename_paths()
    clean_component_manifest()
    set_manifest_versions()
    rewrite_package_script()
    rewrite_update_feed()
    rewrite_build_tools()
    rewrite_docs()
    update_asset_version()
    print("Competitions clean base 1.0.0 prepared")


if __name__ == "__main__":
    main()

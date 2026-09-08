from __future__ import annotations

import json
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
NEW_VERSION = "0.13.0"
OLD_TABLE_PREFIX = "#__" + "dcl_"
NEW_TABLE_PREFIX = "#__decarocompetitions_"
OLD_INDEX_PREFIX = "idx_" + "dcl_"
NEW_INDEX_PREFIX = "idx_competitions_"

EXCLUDED = {
    ROOT / "CHANGELOG.md",
    ROOT / ".github/workflows/db-prefix-reset.yml",
    ROOT / ".github/workflows/db-prefix-rewrite-main.yml",
    ROOT / ".github/workflows/ci.yml",
    ROOT / ".github/workflows/release.yml",
    Path(__file__).resolve(),
}
TEXT_SUFFIXES = {".php", ".sql", ".py", ".xml", ".json", ".md", ".ini", ".js"}


def rewrite_runtime_prefixes() -> None:
    for path in ROOT.rglob("*"):
        if not path.is_file() or ".git" in path.parts or path in EXCLUDED:
            continue
        if path.suffix.lower() not in TEXT_SUFFIXES:
            continue
        text = path.read_text(encoding="utf-8")
        new = text.replace(OLD_TABLE_PREFIX, NEW_TABLE_PREFIX)
        new = new.replace(OLD_INDEX_PREFIX, NEW_INDEX_PREFIX)
        if new != text:
            path.write_text(new, encoding="utf-8")


def write_installer() -> None:
    content = r'''<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;

/**
 * Competitions component schema migration helper.
 *
 * Fresh installations use #__decarocompetitions_* tables. During an update,
 * older DCL-prefixed tables are renamed before Joomla applies schema updates.
 */
final class ComDecarodclInstallerScript
{
    public function preflight(string $type, $parent): bool
    {
        if (!in_array($type, ['install', 'update'], true)) {
            return true;
        }

        return $this->migrateLegacyTablePrefix();
    }

    public function install($parent): bool
    {
        return $this->migrateLegacySchema();
    }

    public function update($parent): bool
    {
        return $this->migrateLegacySchema();
    }

    public function uninstall($parent): bool
    {
        return true;
    }

    private function migrateLegacyTablePrefix(): bool
    {
        try {
            /** @var DatabaseInterface $db */
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $sitePrefix = $db->getPrefix();
            $legacyPrefix = $sitePrefix . 'dcl_';
            $currentPrefix = $sitePrefix . 'decarocompetitions_';
            $tables = $db->getTableList();
            $existing = array_fill_keys($tables, true);
            $renames = [];

            foreach ($tables as $table) {
                if (!str_starts_with($table, $legacyPrefix)) {
                    continue;
                }

                $suffix = substr($table, strlen($legacyPrefix));
                $target = $currentPrefix . $suffix;

                if (isset($existing[$target])) {
                    Log::add(
                        'Competitions database migration stopped because both legacy and current tables exist for ' . $suffix,
                        Log::ERROR,
                        'competitions'
                    );

                    return false;
                }

                $renames[] = $db->quoteName($table) . ' TO ' . $db->quoteName($target);
            }

            if ($renames !== []) {
                $db->setQuery('RENAME TABLE ' . implode(', ', $renames))->execute();
            }

            return true;
        } catch (Throwable $e) {
            Log::add('Competitions table-prefix migration failed: ' . $e->getMessage(), Log::ERROR, 'competitions');

            return false;
        }
    }

    private function migrateLegacySchema(): bool
    {
        try {
            /** @var DatabaseInterface $db */
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $table = $db->replacePrefix('#__decarocompetitions_federations');
            $columns = $db->getTableColumns($table, false);

            if (!isset($columns['country_id'])) {
                $query = 'ALTER TABLE ' . $db->quoteName($table)
                    . ' ADD COLUMN ' . $db->quoteName('country_id')
                    . ' INT UNSIGNED NOT NULL DEFAULT 0 AFTER ' . $db->quoteName('id');
                $db->setQuery($query)->execute();
            }

            $keys = $db->getTableKeys($table);
            $hasIndex = false;
            $legacyIndex = 'idx_' . 'dcl_' . 'federations_country_id';

            foreach ($keys as $key) {
                $keyName = (string) ($key->Key_name ?? '');

                if ($keyName === 'idx_competitions_federations_country_id' || $keyName === $legacyIndex) {
                    $hasIndex = true;
                    break;
                }
            }

            if (!$hasIndex) {
                $query = 'ALTER TABLE ' . $db->quoteName($table)
                    . ' ADD INDEX ' . $db->quoteName('idx_competitions_federations_country_id')
                    . ' (' . $db->quoteName('country_id') . ')';
                $db->setQuery($query)->execute();
            }

            $columns = $db->getTableColumns($table, false);

            if (isset($columns['country_code'])) {
                $countries = $db->replacePrefix('#__decarocompetitions_countries');
                $query = 'UPDATE ' . $db->quoteName($table) . ' AS f'
                    . ' INNER JOIN ' . $db->quoteName($countries) . ' AS c'
                    . ' ON (UPPER(c.' . $db->quoteName('code') . ') = UPPER(f.' . $db->quoteName('country_code') . ')'
                    . ' OR UPPER(c.' . $db->quoteName('iso3') . ') = UPPER(f.' . $db->quoteName('country_code') . '))'
                    . ' SET f.' . $db->quoteName('country_id') . ' = c.' . $db->quoteName('id')
                    . ' WHERE f.' . $db->quoteName('country_id') . ' = 0'
                    . ' AND f.' . $db->quoteName('country_code') . ' IS NOT NULL'
                    . ' AND f.' . $db->quoteName('country_code') . " <> ''";
                $db->setQuery($query)->execute();
            }

            return true;
        } catch (Throwable $e) {
            Log::add('Competitions component migration failed: ' . $e->getMessage(), Log::ERROR, 'competitions');

            return false;
        }
    }
}
'''
    (ROOT / "component/script.php").write_text(content, encoding="utf-8")


def add_schema_marker() -> None:
    path = ROOT / "component/admin/sql/updates/mysql/0.13.0.sql"
    path.write_text(
        "-- Competitions 0.13.0 table-prefix migration is performed by component/script.php preflight.\n"
        "-- Joomla records this schema version after any legacy tables have been renamed.\n",
        encoding="utf-8",
    )


def bump_versions() -> None:
    (ROOT / "VERSION").write_text(NEW_VERSION + "\n", encoding="utf-8")

    manifests = [
        ROOT / "component/decarodcl.xml",
        ROOT / "package/pkg_decarodcl.xml",
        ROOT / "plugins/system/decarodcl/decarodcl.xml",
        ROOT / "modules/mod_dcl_matchtimeline/mod_dcl_matchtimeline.xml",
        ROOT / "modules/mod_dcl_countriesfederations/mod_dcl_countriesfederations.xml",
        ROOT / "updates/pkg_decarodcl.xml",
    ]

    for path in manifests:
        text = path.read_text(encoding="utf-8")
        text, count = re.subn(r"<version>[^<]+</version>", f"<version>{NEW_VERSION}</version>", text, count=1)
        if count != 1:
            raise RuntimeError(f"Unable to update version in {path.relative_to(ROOT)}")
        text = re.sub(
            r"/releases/download/v[^/]+/pkg_competitions_[^<]+\.zip",
            f"/releases/download/v{NEW_VERSION}/pkg_competitions_{NEW_VERSION}.zip",
            text,
        )
        path.write_text(text, encoding="utf-8")

    asset_path = ROOT / "component/media/joomla.asset.json"
    asset = json.loads(asset_path.read_text(encoding="utf-8"))
    asset["version"] = NEW_VERSION
    for item in asset.get("assets", []):
        item["version"] = NEW_VERSION
    asset_path.write_text(json.dumps(asset, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")


def update_docs() -> None:
    readme = ROOT / "README.md"
    text = readme.read_text(encoding="utf-8")
    text, count = re.subn(r"(## Current version\n\n)\*\*[^*]+\*\*", rf"\g<1>**{NEW_VERSION}**", text, count=1)
    if count != 1:
        raise RuntimeError("Unable to update README current version")
    text = text.replace("- Database tables: `#__dcl_*`", "- Database tables: `#__decarocompetitions_*`")
    text = text.replace("dedicated `#__dcl_*` tables", "dedicated `#__decarocompetitions_*` tables")
    readme.write_text(text, encoding="utf-8")

    changelog = ROOT / "CHANGELOG.md"
    text = changelog.read_text(encoding="utf-8")
    marker = "# Changelog\n\n"
    if not text.startswith(marker):
        raise RuntimeError("Unexpected CHANGELOG.md header")
    if "## 0.13.0 - 2026-09-08" not in text:
        entry = """## 0.13.0 - 2026-09-08

- Rebased Competitions database storage from the legacy DCL table prefix to `#__decarocompetitions_*`.
- Fresh installations create only `#__decarocompetitions_*` tables.
- Existing installations are protected by a component preflight migration that renames legacy DCL-prefixed tables before normal Joomla schema updates.
- Updated runtime queries, Table classes, modules, Live Sync helpers, SQL and release validation to the new table prefix.
- Kept `com_decarodcl`, `pkg_decarodcl`, plugin/module identifiers and PHP namespace unchanged in this release to avoid coupling the database cleanup to a separate extension-identity migration.
- No sports-domain behavior is intentionally changed.

"""
        changelog.write_text(marker + entry + text[len(marker):], encoding="utf-8")


def update_validator() -> None:
    path = ROOT / "tools/validate_release.py"
    text = path.read_text(encoding="utf-8")
    anchor = '    "admin/sql/updates/mysql/0.10.0.sql",\n'
    if 'admin/sql/updates/mysql/0.13.0.sql' not in text:
        if anchor not in text:
            raise RuntimeError("Unable to extend expected SQL contents")
        text = text.replace(anchor, anchor + '    "admin/sql/updates/mysql/0.13.0.sql",\n', 1)

    if "def validate_no_legacy_table_prefix()" not in text:
        guard = '''def validate_no_legacy_table_prefix() -> None:
    legacy = "#__" + "dcl_"
    roots = [ROOT / "component", ROOT / "modules", ROOT / "plugins", ROOT / "package", ROOT / "docs", ROOT / "README.md"]
    for root in roots:
        paths = [root] if root.is_file() else root.rglob("*")
        for candidate in paths:
            if not candidate.is_file() or candidate.suffix.lower() not in {".php", ".sql", ".md", ".xml"}:
                continue
            if legacy in candidate.read_text(encoding="utf-8"):
                fail(f"legacy Competitions table prefix remains in {candidate.relative_to(ROOT)}")


'''
        anchor = "def validate_versions() -> None:\n"
        if anchor not in text:
            raise RuntimeError("Unable to add legacy-prefix validator")
        text = text.replace(anchor, guard + anchor, 1)

    if "    validate_no_legacy_table_prefix()\n    validate_versions()" not in text:
        text = text.replace("    validate_versions()\n", "    validate_no_legacy_table_prefix()\n    validate_versions()\n", 1)

    path.write_text(text, encoding="utf-8")


def update_ci_and_release() -> None:
    ci = ROOT / ".github/workflows/ci.yml"
    text = ci.read_text(encoding="utf-8")
    text = text.replace('PACKAGE="dist/pkg_decarodcl_${VERSION}.zip"', 'PACKAGE="dist/pkg_competitions_${VERSION}.zip"')
    ci.write_text(text, encoding="utf-8")

    release = ROOT / ".github/workflows/release.yml"
    text = release.read_text(encoding="utf-8")
    anchor = '''          if [ "$VERSION" = "0.12.2" ]; then
            NOTES="Competitions 0.12.2: public package renamed to pkg_competitions while preserving the historical Joomla package identifier for safe updates."
          fi
'''
    addition = '''          if [ "$VERSION" = "0.13.0" ]; then
            NOTES="Competitions 0.13.0: database tables moved to the decarocompetitions namespace with protected legacy-table migration."
          fi
'''
    if "Competitions 0.13.0: database tables moved" not in text:
        if anchor not in text:
            raise RuntimeError("Unable to add 0.13.0 release notes")
        text = text.replace(anchor, anchor + addition, 1)
    release.write_text(text, encoding="utf-8")


def ensure_no_literal_legacy_prefix() -> None:
    allowed = {ROOT / "CHANGELOG.md", Path(__file__).resolve()}
    offenders: list[str] = []
    for base in (ROOT / "component", ROOT / "modules", ROOT / "plugins", ROOT / "package", ROOT / "tools", ROOT / "docs", ROOT / "README.md"):
        paths = [base] if base.is_file() else base.rglob("*")
        for path in paths:
            if not path.is_file() or path in allowed:
                continue
            if path.suffix.lower() not in TEXT_SUFFIXES:
                continue
            if OLD_TABLE_PREFIX in path.read_text(encoding="utf-8"):
                offenders.append(str(path.relative_to(ROOT)))
    if offenders:
        raise RuntimeError("Legacy DB table prefix remains in: " + ", ".join(offenders))


def main() -> None:
    rewrite_runtime_prefixes()
    write_installer()
    add_schema_marker()
    bump_versions()
    update_docs()
    update_validator()
    update_ci_and_release()
    ensure_no_literal_legacy_prefix()
    print("Competitions DB prefix reset prepared for 0.13.0")


if __name__ == "__main__":
    main()

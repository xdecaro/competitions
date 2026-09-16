from pathlib import Path
import hashlib
import shutil
import zipfile

ROOT = Path(__file__).resolve().parents[1]
VERSION = (ROOT / "VERSION").read_text(encoding="utf-8").strip()
DIST = ROOT / "dist"
DIST.mkdir(exist_ok=True)
FIXED_TIME = (2026, 1, 1, 0, 0, 0)
LEGACY_COMPONENT_OPTION = "com_xdecarocompetitions"
COMPONENT_OPTION = "com_competitions"


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


def stage_extension(source: Path, stage: Path, *, component: bool = False) -> None:
    if stage.exists():
        shutil.rmtree(stage)

    shutil.copytree(source, stage)

    # The clean-install Joomla component element is com_competitions.  Keep
    # xdecaro in the PHP vendor namespace, package, database tables and the
    # companion extension names, but never in the shipped component option.
    for path in stage.rglob("*"):
        if not path.is_file():
            continue

        try:
            text = path.read_text(encoding="utf-8")
        except UnicodeDecodeError:
            continue

        if LEGACY_COMPONENT_OPTION in text:
            path.write_text(text.replace(LEGACY_COMPONENT_OPTION, COMPONENT_OPTION), encoding="utf-8")

    if not component:
        return

    legacy_manifest = stage / "xdecarocompetitions.xml"
    manifest = stage / "competitions.xml"
    legacy_manifest.rename(manifest)

    manifest_text = manifest.read_text(encoding="utf-8")
    if "<element>com_competitions</element>" not in manifest_text:
        manifest_text = manifest_text.replace(
            "<name>COM_XDECAROCOMPETITIONS</name>",
            "<name>COM_XDECAROCOMPETITIONS</name>\n  <element>com_competitions</element>",
            1,
        )
    manifest.write_text(manifest_text, encoding="utf-8")

    for language in ("en-GB", "it-IT"):
        language_dir = stage / "admin" / "language" / language
        for legacy in sorted(language_dir.glob("com_xdecarocompetitions*.ini")):
            legacy.rename(language_dir / legacy.name.replace("com_xdecarocompetitions", "com_competitions", 1))


build_root = DIST / "build-stage"
if build_root.exists():
    shutil.rmtree(build_root)
build_root.mkdir()

artifacts = []
entries = [
    (f"com_competitions_{VERSION}.zip", ROOT / "component", True),
    (f"plg_system_xdecarocompetitions_{VERSION}.zip", ROOT / "plugins/system/xdecarocompetitions", False),
    (f"plg_xdecaroanalytics_competitions_{VERSION}.zip", ROOT / "plugins/xdecaroanalytics/competitions", False),
    (f"plg_task_xdecarocompetitions_{VERSION}.zip", ROOT / "plugins/task/xdecarocompetitions", False),
    (f"mod_xdecarocompetitions_matchtimeline_{VERSION}.zip", ROOT / "modules/mod_xdecarocompetitions_matchtimeline", False),
    (f"mod_xdecarocompetitions_countriesfederations_{VERSION}.zip", ROOT / "modules/mod_xdecarocompetitions_countriesfederations", False),
]

for index, (name, source, is_component) in enumerate(entries):
    staged_source = build_root / f"extension-{index}"
    stage_extension(source, staged_source, component=is_component)
    artifact = DIST / name
    zip_dir(staged_source, artifact)
    artifacts.append(artifact)

package_stage = DIST / "package-stage"
if package_stage.exists():
    shutil.rmtree(package_stage)
package_stage.mkdir()

for artifact in artifacts:
    shutil.copyfile(artifact, package_stage / artifact.name)

shutil.copyfile(ROOT / "package/pkg_xdecarocompetitions.xml", package_stage / "pkg_xdecarocompetitions.xml")
shutil.copyfile(ROOT / "package/script.php", package_stage / "script.php")
shutil.copytree(ROOT / "package/language", package_stage / "language")

package = DIST / f"pkg_xdecarocompetitions_{VERSION}.zip"
zip_dir(package_stage, package)
artifacts.append(package)

(DIST / "SHA256SUMS.txt").write_text(
    "\n".join(f"{hashlib.sha256(path.read_bytes()).hexdigest()}  {path.name}" for path in artifacts) + "\n",
    encoding="utf-8",
)

shutil.rmtree(package_stage)
shutil.rmtree(build_root)
print(package)

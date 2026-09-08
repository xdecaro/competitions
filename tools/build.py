from pathlib import Path
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

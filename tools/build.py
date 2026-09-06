from pathlib import Path
import shutil
import zipfile
import hashlib

ROOT = Path(__file__).resolve().parents[1]
VERSION = (ROOT / "VERSION").read_text(encoding="utf-8").strip()
DIST = ROOT / "dist"
DIST.mkdir(exist_ok=True)

def zip_dir(src: Path, out: Path):
    if out.exists():
        out.unlink()
    with zipfile.ZipFile(out, "w", zipfile.ZIP_DEFLATED) as z:
        for p in sorted(src.rglob("*")):
            if p.is_file() and p.name != ".keep":
                z.write(p, p.relative_to(src))

artifacts = []

component_zip = DIST / f"com_decarodcl_{VERSION}.zip"
zip_dir(ROOT / "component", component_zip)
artifacts.append(component_zip)

plugin_zip = DIST / f"plg_system_decarodcl_{VERSION}.zip"
zip_dir(ROOT / "plugins/system/decarodcl", plugin_zip)
artifacts.append(plugin_zip)

for module in ["mod_dcl_matchtimeline", "mod_dcl_countriesfederations"]:
    out = DIST / f"{module}_{VERSION}.zip"
    zip_dir(ROOT / "modules" / module, out)
    artifacts.append(out)

stage = DIST / "package-stage"
if stage.exists():
    shutil.rmtree(stage)
stage.mkdir()

for p in artifacts:
    shutil.copy2(p, stage / p.name)

shutil.copy2(ROOT / "package/pkg_decarodcl.xml", stage / "pkg_decarodcl.xml")
shutil.copy2(ROOT / "package/script.php", stage / "script.php")
shutil.copytree(ROOT / "package/language", stage / "language")

package_zip = DIST / f"pkg_decarodcl_{VERSION}.zip"
zip_dir(stage, package_zip)
artifacts.append(package_zip)

checksums = []
for p in artifacts:
    digest = hashlib.sha256(p.read_bytes()).hexdigest()
    checksums.append(f"{digest}  {p.name}")

(DIST / "SHA256SUMS.txt").write_text("\n".join(checksums) + "\n", encoding="utf-8")
shutil.rmtree(stage)
print(package_zip)

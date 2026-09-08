from pathlib import Path
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

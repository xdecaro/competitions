from __future__ import annotations

import argparse
import hashlib
import re
from pathlib import Path
import xml.etree.ElementTree as ET

ROOT = Path(__file__).resolve().parents[1]
VERSION = (ROOT / "VERSION").read_text(encoding="utf-8").strip()
FEED = ROOT / "updates/pkg_decarodcl.xml"


def fail(message: str) -> None:
    raise SystemExit(message)


def main() -> None:
    parser = argparse.ArgumentParser(description="Synchronize the Joomla update-feed SHA-256 with the built package ZIP.")
    parser.add_argument(
        "--package",
        type=Path,
        default=ROOT / "dist" / f"pkg_decarodcl_{VERSION}.zip",
        help="Package ZIP whose SHA-256 must be published",
    )
    args = parser.parse_args()

    package = args.package if args.package.is_absolute() else ROOT / args.package
    if not package.is_file():
        fail(f"Package ZIP not found: {package}")

    root = ET.parse(FEED).getroot()
    update = root.find("update")
    if update is None:
        fail("Update feed has no <update> element")

    feed_version = (update.findtext("version") or "").strip()
    if feed_version != VERSION:
        fail(f"Update feed version {feed_version!r} does not match VERSION {VERSION!r}")

    expected_name = f"pkg_decarodcl_{VERSION}.zip"
    download_url = (update.findtext("./downloads/downloadurl") or "").strip()
    if not download_url.endswith("/" + expected_name):
        fail(f"Update feed download URL does not target {expected_name}")

    digest = hashlib.sha256(package.read_bytes()).hexdigest()
    text = FEED.read_text(encoding="utf-8")
    checksum_line = f"    <sha256>{digest}</sha256>"

    if re.search(r"^\s*<sha256>[0-9a-fA-F]{64}</sha256>\s*$", text, flags=re.MULTILINE):
        text = re.sub(
            r"^\s*<sha256>[0-9a-fA-F]{64}</sha256>\s*$",
            checksum_line,
            text,
            count=1,
            flags=re.MULTILINE,
        )
    else:
        marker = "    </tags>\n"
        if marker not in text:
            fail("Unable to locate </tags> in update feed")
        text = text.replace(marker, marker + checksum_line + "\n", 1)

    FEED.write_text(text, encoding="utf-8")
    ET.parse(FEED)
    print(digest)


if __name__ == "__main__":
    main()

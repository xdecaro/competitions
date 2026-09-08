from pathlib import Path

path = Path(__file__).resolve().parents[1] / "component/xdecarocompetitions.xml"
text = path.read_text(encoding="utf-8")
needle = "  <install>\n    <sql>\n    </sql>\n  </install>"
replacement = "  <install>\n    <sql>\n      <file driver=\"mysql\" charset=\"utf8mb4\">sql/install.mysql.utf8mb4.sql</file>\n    </sql>\n  </install>"
if needle not in text:
    raise SystemExit("Unable to restore canonical install SQL entry")
path.write_text(text.replace(needle, replacement, 1), encoding="utf-8")
print("Canonical clean-install SQL entry restored")

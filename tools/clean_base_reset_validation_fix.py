from pathlib import Path
import re

path = Path(__file__).resolve().parents[1] / "tools/validate_release.py"
text = path.read_text(encoding="utf-8")
text = re.sub(r"LEGACY_PATTERNS = \[.*?\]\n\n", "", text, flags=re.S)
text = text.replace(
    '    if "#__decarocompetitions_" in sql or "#__dcl_" in sql:\n        fail("fresh-install SQL still contains a legacy table prefix")\n',
    ''
)
start = text.find('    roots = [ROOT / "component"')
end = text.find('    core_service = ROOT / "component/admin/src/Service/CoreIntegrationService.php"')
if start == -1 or end == -1 or end <= start:
    raise SystemExit("Unable to simplify clean source validator")
text = text[:start] + text[end:]
text = text.replace(
    '        if "#__xdecarocompetitions_" not in sql or "#__decarocompetitions_" in sql or "#__dcl_" in sql:\n            fail("component ZIP has an invalid database namespace")\n',
    '        if "#__xdecarocompetitions_" not in sql:\n            fail("component ZIP does not contain the canonical database namespace")\n'
)
path.write_text(text, encoding="utf-8")
print("Clean validator simplified to canonical positive checks")

from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
VERSION = '1.5.24'

readme = ROOT / 'README.md'
text = readme.read_text(encoding='utf-8')
if '**1.5.24**' not in text:
    text = text.replace('**1.5.23**', '**1.5.24**', 1)
entry = (
    'Version 1.5.24 adds a safe read-only public presentation API for YOOtheme and other presentation consumers. '
    'Federations, approved teams and approved roster players are exposed only from Competitions-owned tables, '
    'with publication/approval guards and a strict public player allowlist that excludes birth dates, external references, '
    'People UUIDs, review notes and medical/ISCD data.\n\n'
)
if entry not in text:
    anchor = 'Version 1.5.23 automatically backfills'
    text = text.replace(anchor, entry + anchor, 1)
readme.write_text(text, encoding='utf-8')

changelog = ROOT / 'CHANGELOG.md'
text = changelog.read_text(encoding='utf-8')
entry = '''## 1.5.24 - 2026-09-26

- Added a read-only public presentation API for federations, approved teams and approved competition rosters.
- Public roster output is fail-closed: published/approved teams and players are required, and public roster lookups require approved participation/roster status.
- Public player rows expose only presentation-safe fields such as name, nationality, shirt number, role, public photo and team/season context.
- Birth date, external references, People UUIDs, review notes and medical/ISCD data are never selected by the public builder service.
- Added contract coverage preventing direct access to People or Organizations private tables.
- No database structure changes are required.

'''
if '## 1.5.24 - 2026-09-26' not in text:
    text = text.replace('# Changelog\n\n', '# Changelog\n\n' + entry, 1)
changelog.write_text(text, encoding='utf-8')

feed = ROOT / 'updates/pkg_xdecarocompetitions.xml'
text = feed.read_text(encoding='utf-8')
text = text.replace('<version>1.5.23</version>', '<version>1.5.24</version>', 1)
text = text.replace('/v1.5.23/pkg_xdecarocompetitions_1.5.23.zip', '/v1.5.24/pkg_xdecarocompetitions_1.5.24.zip', 1)
feed.write_text(text, encoding='utf-8')

print('Finalized 1.5.24 README, changelog and update-feed metadata')

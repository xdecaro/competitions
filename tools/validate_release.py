from __future__ import annotations
import argparse, json, re, sys, zipfile
from pathlib import Path
import xml.etree.ElementTree as ET

ROOT=Path(__file__).resolve().parents[1]
VERSION=(ROOT/'VERSION').read_text(encoding='utf-8').strip()
CURRENT_VERSION='1.2.0'
CANONICAL_NAMESPACE='xdecaro\\Component\\Competitions'

def fail(message:str)->None:
    print('ERROR:',message,file=sys.stderr); raise SystemExit(1)
def version(path:Path)->str:
    node=ET.parse(path).getroot().find('version'); return (node.text or '').strip() if node is not None else ''
def forbidden_table_prefixes(): return ('#__'+'d'+'cl_', '#__'+'decaro'+'competitions_')
def validate_database_namespace(sql:str,label:str)->None:
    if '#__xdecarocompetitions_' not in sql: fail(f'{label} does not contain canonical database namespace')
    for prefix in forbidden_table_prefixes():
        if prefix in sql: fail(f'{label} contains pre-1.0 database namespace')

def validate_source()->None:
    if VERSION!=CURRENT_VERSION: fail(f'expected {CURRENT_VERSION}, got {VERSION}')
    manifests=[ROOT/'component/xdecarocompetitions.xml',ROOT/'package/pkg_xdecarocompetitions.xml',ROOT/'plugins/system/xdecarocompetitions/xdecarocompetitions.xml',ROOT/'plugins/xdecaroanalytics/competitions/competitions.xml',ROOT/'plugins/task/xdecarocompetitions/xdecarocompetitions.xml',ROOT/'modules/mod_xdecarocompetitions_matchtimeline/mod_xdecarocompetitions_matchtimeline.xml',ROOT/'modules/mod_xdecarocompetitions_countriesfederations/mod_xdecarocompetitions_countriesfederations.xml']
    for path in manifests:
        if not path.is_file(): fail(f'missing manifest {path.relative_to(ROOT)}')
        if version(path)!=VERSION: fail(f'version mismatch in {path.relative_to(ROOT)}')
    component=ET.parse(ROOT/'component/xdecarocompetitions.xml').getroot()
    if (component.findtext('namespace') or '').strip()!=CANONICAL_NAMESPACE: fail('component namespace is not canonical')
    install=component.find('./install/sql/file'); uninstall=component.find('./uninstall/sql/file')
    for node,label in ((install,'install'),(uninstall,'uninstall')):
        if node is None or (node.get('driver') or '')!='mysql' or (node.get('charset') or '')!='utf8': fail(f'{label} SQL manifest must use driver=mysql charset=utf8')
    package=ET.parse(ROOT/'package/pkg_xdecarocompetitions.xml').getroot()
    child_ids={(n.get('type'),n.get('id'),n.get('group')) for n in package.findall('./files/file')}
    required={('component','com_xdecarocompetitions',None),('plugin','xdecarocompetitions','system'),('plugin','competitions','xdecaroanalytics'),('plugin','xdecarocompetitions','task'),('module','mod_xdecarocompetitions_matchtimeline',None),('module','mod_xdecarocompetitions_countriesfederations',None)}
    if not required.issubset(child_ids): fail(f'package child identifiers incomplete: {child_ids}')
    sql=(ROOT/'component/admin/sql/install.mysql.utf8mb4.sql').read_text(encoding='utf-8'); validate_database_namespace(sql,'fresh-install SQL')
    for token in ['#__xdecarocompetitions_tournaments','#__xdecarocompetitions_seasons','#__xdecarocompetitions_teams','#__xdecarocompetitions_participations','#__xdecarocompetitions_matches','#__xdecarocompetitions_match_events','#__xdecarocompetitions_rankings','`match_date` DATE','`status` VARCHAR(32)']:
        if token not in sql: fail(f'fresh-install schema missing {token}')
    if 'ALTER TABLE' in sql.upper(): fail('fresh-install schema must not replay ALTER statements')
    update_dir=ROOT/'component/admin/sql/updates/mysql'; update_files=sorted(p.name for p in update_dir.glob('*.sql'))
    if update_files!=['1.0.0.sql','1.1.0.sql','1.2.0.sql']: fail(f'unexpected schema history: {update_files}')
    marker=(update_dir/'1.2.0.sql').read_text(encoding='utf-8').upper()
    if any(word in marker for word in ['ALTER TABLE','DROP TABLE','TRUNCATE TABLE']): fail('1.2.0 marker must be non-destructive')
    asset=json.loads((ROOT/'component/media/joomla.asset.json').read_text(encoding='utf-8'))
    if str(asset.get('version'))!=VERSION: fail('Web Asset registry version mismatch')
    feed=ET.parse(ROOT/'updates/pkg_xdecarocompetitions.xml').getroot().find('update')
    if feed is None or (feed.findtext('version') or '').strip()!=VERSION: fail('update feed version mismatch')
    expected=f'https://github.com/xdecaro/competitions/releases/download/v{VERSION}/pkg_xdecarocompetitions_{VERSION}.zip'
    if (feed.findtext('./downloads/downloadurl') or '').strip()!=expected: fail('update feed download URL mismatch')
    core=(ROOT/'component/admin/src/Service/CoreIntegrationService.php').read_text(encoding='utf-8')
    for token in ["COMPONENT = 'com_xdecarocompetitions'",'CapabilityRegistry','competitions.analytics.provider','competitions.notifications.bridge','competitions.tasks.bridge','competitions.match-reminders']:
        if token not in core: fail(f'Core integration missing {token}')
    cross=(ROOT/'component/admin/src/Service/CrossProductIntegrationService.php').read_text(encoding='utf-8')
    for token in ["bootComponent('com_xdecaronotifications')","bootComponent('com_xdecarotasks')",'getNotificationService','getTaskService']:
        if token not in cross: fail(f'cross-product bridge missing {token}')
    for forbidden in ['#__xdecaronotifications_','#__xdecarotasks_']:
        if forbidden in cross: fail('cross-product bridge must not access external tables')
    analytics=(ROOT/'component/admin/src/Service/AnalyticsSourceService.php').read_text(encoding='utf-8')
    for token in ['core.manage','#__xdecarocompetitions_matches','competitions.matches.upcoming','competitions.rankings.top']:
        if token not in analytics: fail(f'Analytics source missing {token}')
    reminder=(ROOT/'component/admin/src/Service/MatchReminderService.php').read_text(encoding='utf-8')
    for token in ["m.status='scheduled'",'integration','competition-match-upcoming','competition-match-prepare']:
        if token not in reminder: fail(f'Match reminder missing {token}')
    provider=(ROOT/'component/admin/services/provider.php').read_text(encoding='utf-8')
    for token in ['CompetitionsComponent','AnalyticsSourceService','CrossProductIntegrationService','MatchReminderService','DatabaseInterface']:
        if token not in provider: fail(f'DI provider missing {token}')
    for path in list((ROOT/'component').rglob('*.php'))+list((ROOT/'modules').rglob('*.php'))+list((ROOT/'plugins').rglob('*.php')):
        text=path.read_text(encoding='utf-8')
        if 'namespace Xdecaro\\' in text or 'use Xdecaro\\' in text: fail(f'uppercase vendor namespace remains in {path.relative_to(ROOT)}')

def validate_dist()->None:
    names=[f'com_xdecarocompetitions_{VERSION}.zip',f'plg_system_xdecarocompetitions_{VERSION}.zip',f'plg_xdecaroanalytics_competitions_{VERSION}.zip',f'plg_task_xdecarocompetitions_{VERSION}.zip',f'mod_xdecarocompetitions_matchtimeline_{VERSION}.zip',f'mod_xdecarocompetitions_countriesfederations_{VERSION}.zip',f'pkg_xdecarocompetitions_{VERSION}.zip']
    paths=[ROOT/'dist'/n for n in names]
    for path in paths:
        if not path.is_file(): fail(f'missing distribution artifact {path.name}')
        with zipfile.ZipFile(path) as archive:
            bad=archive.testzip()
            if bad is not None: fail(f'corrupt ZIP member {bad} in {path.name}')
    with zipfile.ZipFile(paths[0]) as archive:
        required={'xdecarocompetitions.xml','admin/src/Extension/CompetitionsComponent.php','admin/src/Service/AnalyticsSourceService.php','admin/src/Service/CrossProductIntegrationService.php','admin/src/Service/MatchReminderService.php','admin/sql/updates/mysql/1.2.0.sql'}
        missing=required-set(archive.namelist())
        if missing: fail(f'component ZIP missing {sorted(missing)}')
    with zipfile.ZipFile(paths[-1]) as archive:
        required={'pkg_xdecarocompetitions.xml',*names[:-1]}
        missing=required-set(archive.namelist())
        if missing: fail(f'package ZIP missing {sorted(missing)}')

parser=argparse.ArgumentParser(); parser.add_argument('--dist',action='store_true'); args=parser.parse_args(); validate_source();
if args.dist: validate_dist()
print(f'Competitions {VERSION} validation OK')

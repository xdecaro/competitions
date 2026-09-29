<?php
namespace xdecaro\Component\Competitions\Administrator\Controller;

defined('_JEXEC') or die;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Throwable;
use ZipArchive;

final class RosterbulkController extends BaseController
{
    protected $option = 'com_xdecarocompetitions';

    public function previewCsv(): void
    {
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.create', 'com_xdecarocompetitions')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
        $this->checkToken();

        $seasonId = $this->input->post->getInt('season_id');
        $target = $this->input->post->getCmd('roster_target', '');
        $component = $app->bootComponent('com_xdecarocompetitions');
        $bulkModel = $component->getMVCFactory()->createModel('Rosterbulk', 'Administrator', ['ignore_request' => true]);

        [$seasonId, $target, $participationId] = $this->resolveTarget($bulkModel, $seasonId, $target);
        $redirect = $this->buildBulkRedirect($seasonId, $target);
        if ($seasonId <= 0 || $target === '' || ($target !== 'all' && $participationId <= 0)) {
            $this->setMessage('Seleziona prima una stagione e la squadra/partecipazione.', 'warning');
            $this->setRedirect('index.php?option=com_xdecarocompetitions&view=rosterbulk');
            return;
        }

        $file = (array) $this->input->files->get('source_csv', [], 'array');
        $tmp = (string) ($file['tmp_name'] ?? '');
        $name = basename(str_replace('\\', '/', trim((string) ($file['name'] ?? ''))));
        $size = (int) ($file['size'] ?? 0);
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        $extension = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));

        if ($error !== UPLOAD_ERR_OK || $tmp === '' || !is_uploaded_file($tmp)) {
            $this->setMessage('Seleziona un file CSV o Excel valido.', 'warning');
            $this->setRedirect($redirect);
            return;
        }
        if (
            $size <= 0
            || $size > 4 * 1024 * 1024
            || !in_array($extension, ['csv', 'txt', 'xlsx'], true)
            || !$this->hasAllowedUploadMime($tmp, $extension)
        ) {
            $this->setMessage('Formato file non valido. Sono ammessi CSV, TXT e Excel .xlsx fino a 4 MB.', 'warning');
            $this->setRedirect($redirect);
            return;
        }

        try {
            $rows = $extension === 'xlsx'
                ? $this->parseXlsxRosterRows($tmp)
                : $this->parseCsvRosterRows($tmp);
        } catch (Throwable $e) {
            $this->setMessage('Impossibile leggere il file: ' . trim($e->getMessage()), 'warning');
            $this->setRedirect($redirect);
            return;
        }
        if (!$rows) {
            $this->setMessage('Nel file non sono stati trovati giocatori.', 'warning');
            $this->setRedirect($redirect);
            return;
        }

        if ($target === 'all') {
            $hasTeam = false;
            foreach ($rows as $row) {
                if (trim((string) ($row['team'] ?? '')) !== '') {
                    $hasTeam = true;
                    break;
                }
            }
            if (!$hasTeam) {
                $this->setMessage('Per importare tutte le squadre il file deve contenere la colonna Squadra/Team valorizzata.', 'warning');
                $this->setRedirect($redirect);
                return;
            }
        }

        $app->setUserState('com_xdecarocompetitions.rosterbulk.source_season_id', $seasonId);
        $app->setUserState('com_xdecarocompetitions.rosterbulk.source_target', $target);
        $app->setUserState('com_xdecarocompetitions.rosterbulk.source_participation_id', $participationId);
        $app->setUserState('com_xdecarocompetitions.rosterbulk.source_rows', $rows);
        $app->setUserState('com_xdecarocompetitions.rosterbulk.source_file_name', $name);
        $this->setMessage(sprintf('File caricato: %d righe valide.', count($rows)), 'message');
        $this->setRedirect($redirect);
    }

    public function clearCsv(): void
    {
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.create', 'com_xdecarocompetitions')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
        $this->checkToken();

        $seasonId = $this->input->post->getInt('season_id');
        $target = $this->input->post->getCmd('roster_target', '');
        $app->setUserState('com_xdecarocompetitions.rosterbulk.source_season_id', 0);
        $app->setUserState('com_xdecarocompetitions.rosterbulk.source_target', '');
        $app->setUserState('com_xdecarocompetitions.rosterbulk.source_participation_id', 0);
        $app->setUserState('com_xdecarocompetitions.rosterbulk.source_rows', []);
        $app->setUserState('com_xdecarocompetitions.rosterbulk.source_file_name', '');
        $this->setRedirect($this->buildBulkRedirect($seasonId, $target));
    }

    public function addSelected(): void
    {
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.create', 'com_xdecarocompetitions')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
        $this->checkToken();

        $seasonId = $this->input->post->getInt('season_id');
        $target = $this->input->post->getCmd('roster_target', '');
        $component = $app->bootComponent('com_xdecarocompetitions');
        $bulkModel = $component->getMVCFactory()->createModel('Rosterbulk', 'Administrator', ['ignore_request' => true]);
        [$seasonId, $target, $participationId] = $this->resolveTarget($bulkModel, $seasonId, $target);
        $redirect = $this->buildBulkRedirect($seasonId, $target);

        if ($seasonId <= 0 || $target === '' || ($target !== 'all' && $participationId <= 0)) {
            $this->setMessage('Seleziona una stagione e una squadra/partecipazione.', 'warning');
            $this->setRedirect($redirect);
            return;
        }

        $selection = array_values(array_unique(array_filter(array_map(
            static fn ($value): string => trim((string) $value),
            (array) $this->input->post->get('selection', [], 'array')
        ))));

        // Backward compatibility for the previous single-participation form.
        if ($selection === [] && $target !== 'all' && $participationId > 0) {
            foreach ((array) $this->input->post->get('player_id', [], 'array') as $playerId) {
                $playerId = (int) $playerId;
                if ($playerId > 0) {
                    $selection[] = $participationId . ':' . $playerId;
                }
            }
            $selection = array_values(array_unique($selection));
        }

        if ($selection === []) {
            $this->setMessage('Seleziona almeno un nuovo giocatore.', 'warning');
            $this->setRedirect($redirect);
            return;
        }

        $sourceSeasonId = (int) $app->getUserState('com_xdecarocompetitions.rosterbulk.source_season_id', 0);
        $sourceTarget = (string) $app->getUserState('com_xdecarocompetitions.rosterbulk.source_target', '');
        $rows = (array) $app->getUserState('com_xdecarocompetitions.rosterbulk.source_rows', []);
        if ($sourceSeasonId !== $seasonId || $sourceTarget !== $target || $rows === []) {
            $this->setMessage('Il file di anteprima non corrisponde più alla selezione corrente. Ricarica il file.', 'warning');
            $this->setRedirect($redirect);
            return;
        }

        $allowed = [];
        if ($target === 'all') {
            $match = $bulkModel->matchSeasonCsvRows($seasonId, $rows);
            foreach ($match['groups'] as $group) {
                $pid = (int) ($group['participation_id'] ?? 0);
                foreach ((array) ($group['items'] ?? []) as $item) {
                    $playerId = (int) ($item['player_id'] ?? 0);
                    if ($pid > 0 && $playerId > 0) {
                        $item['participation_id'] = $pid;
                        $allowed[$pid . ':' . $playerId] = $item;
                    }
                }
            }
        } else {
            $match = $bulkModel->matchCsvRows($participationId, $rows);
            foreach ($match['items'] as $item) {
                $playerId = (int) ($item['player_id'] ?? 0);
                if ($playerId > 0) {
                    $item['participation_id'] = $participationId;
                    $allowed[$participationId . ':' . $playerId] = $item;
                }
            }
        }

        $created = $skipped = $failed = 0;
        foreach ($selection as $selectionKey) {
            if (!preg_match('/^(\d+):(\d+)$/', $selectionKey, $parts)) {
                $skipped++;
                continue;
            }

            $selectedParticipationId = (int) $parts[1];
            $playerId = (int) $parts[2];
            $key = $selectedParticipationId . ':' . $playerId;
            if (!isset($allowed[$key]) || $this->rosterExists($selectedParticipationId, $playerId)) {
                $skipped++;
                continue;
            }

            $item = $allowed[$key];
            try {
                $model = $component->getMVCFactory()->createModel('Roster', 'Administrator', ['ignore_request' => true]);
                if (!$model || !$model->save([
                    'id' => 0,
                    'participation_id' => $selectedParticipationId,
                    'player_id' => $playerId,
                    'shirt_number' => $item['shirt_number'],
                    'role' => $item['role'],
                    'status' => 'pending',
                    'review_note' => '',
                    'state' => 1,
                ])) {
                    throw new \RuntimeException($model ? (string) $model->getError() : 'Roster model unavailable.');
                }
                $created++;
            } catch (Throwable $e) {
                $failed++;
                $app->enqueueMessage(trim($e->getMessage()), 'warning');
            }
        }

        $this->setMessage(sprintf('Righe rosa create: %d. Saltate: %d. Errori: %d.', $created, $skipped, $failed), $failed ? 'warning' : 'message');
        if ($target === 'all') {
            $this->setRedirect($redirect);
        } else {
            $this->setRedirect('index.php?option=com_xdecarocompetitions&view=rosters&filter_participation_id=' . $participationId);
        }
    }

    /** @return array{0:int,1:string,2:int} */
    private function resolveTarget(object $bulkModel, int $seasonId, string $target): array
    {
        $target = trim($target);
        if ($target === 'all') {
            return [$seasonId, 'all', 0];
        }

        $participationId = ctype_digit($target) ? (int) $target : 0;
        if ($participationId <= 0) {
            return [$seasonId, '', 0];
        }

        $participation = $bulkModel->getParticipation($participationId);
        if (!$participation) {
            return [$seasonId, '', 0];
        }

        $participationSeasonId = (int) ($participation->season_id ?? 0);
        if ($seasonId <= 0) {
            $seasonId = $participationSeasonId;
        }
        if ($seasonId !== $participationSeasonId) {
            return [$seasonId, '', 0];
        }

        return [$seasonId, (string) $participationId, $participationId];
    }

    private function buildBulkRedirect(int $seasonId, string $target): string
    {
        $url = 'index.php?option=com_xdecarocompetitions&view=rosterbulk';
        if ($seasonId > 0) {
            $url .= '&season_id=' . $seasonId;
        }
        if ($target !== '') {
            $url .= '&roster_target=' . rawurlencode($target);
        }
        return $url;
    }

    private function hasAllowedUploadMime(string $path, string $extension): bool
    {
        if (!class_exists('finfo')) {
            return true;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = strtolower((string) $finfo->file($path));
        if ($extension === 'xlsx') {
            return in_array($mime, [
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/zip',
                'application/x-zip-compressed',
                'application/octet-stream',
            ], true);
        }

        return in_array($mime, [
            'text/plain',
            'text/csv',
            'application/csv',
            'application/vnd.ms-excel',
            'application/octet-stream',
        ], true);
    }

    /** @return array<int,array<string,mixed>> */
    private function parseCsvRosterRows(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new \RuntimeException('file non apribile');
        }

        try {
            $firstLine = fgets($handle);
            if ($firstLine === false) {
                return [];
            }
            $counts = [';' => substr_count($firstLine, ';'), ',' => substr_count($firstLine, ','), "\t" => substr_count($firstLine, "\t")];
            arsort($counts);
            $delimiter = (string) array_key_first($counts);
            if (($counts[$delimiter] ?? 0) <= 0) {
                $delimiter = ',';
            }
            rewind($handle);
            $headers = fgetcsv($handle, 0, $delimiter);
            if (!is_array($headers)) {
                return [];
            }

            $headers = $this->normalizeRosterHeaders($headers);
            $columns = $this->resolveRosterColumns($headers);
            if ($columns['display'] === null && $columns['first'] === null && $columns['last'] === null) {
                return [];
            }

            $rows = [];
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $mapped = $this->mapRosterDataRow($row, $columns);
                if ($mapped !== null) {
                    $rows[] = $mapped;
                }
                if (count($rows) > 10000) {
                    throw new \RuntimeException('il file supera il limite di 10.000 righe');
                }
            }
            return $rows;
        } finally {
            fclose($handle);
        }
    }

    /** @return array<int,array<string,mixed>> */
    private function parseXlsxRosterRows(string $path): array
    {
        if (!class_exists(ZipArchive::class)) {
            throw new \RuntimeException('supporto Excel non disponibile sul server: estensione ZIP PHP mancante');
        }
        if (!class_exists(DOMDocument::class)) {
            throw new \RuntimeException('supporto Excel non disponibile sul server: estensione DOM PHP mancante');
        }

        $zip = new ZipArchive();
        $result = $zip->open($path);
        if ($result !== true) {
            throw new \RuntimeException('file Excel non apribile');
        }

        try {
            $workbookXml = $this->readXlsxEntry($zip, 'xl/workbook.xml', 2 * 1024 * 1024);
            $relsXml = $this->readXlsxEntry($zip, 'xl/_rels/workbook.xml.rels', 2 * 1024 * 1024);
            $workbook = $this->loadXmlDocument($workbookXml);
            $workbookXpath = new DOMXPath($workbook);
            $sheet = $workbookXpath->query('//*[local-name()="sheets"]/*[local-name()="sheet"]')->item(0);
            if (!$sheet instanceof DOMElement) {
                throw new \RuntimeException('il file Excel non contiene fogli leggibili');
            }

            $relationshipId = $sheet->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id');
            if ($relationshipId === '') {
                $relationshipId = $sheet->getAttribute('r:id');
            }
            if ($relationshipId === '') {
                throw new \RuntimeException('relazione del primo foglio Excel non valida');
            }

            $rels = $this->loadXmlDocument($relsXml);
            $relsXpath = new DOMXPath($rels);
            $target = '';
            foreach ($relsXpath->query('//*[local-name()="Relationship"]') as $relationship) {
                if ($relationship instanceof DOMElement && $relationship->getAttribute('Id') === $relationshipId) {
                    $target = $relationship->getAttribute('Target');
                    break;
                }
            }
            $sheetPath = $this->normalizeXlsxSheetPath($target);
            $sheetXml = $this->readXlsxEntry($zip, $sheetPath, 16 * 1024 * 1024);

            $sharedStrings = [];
            if ($zip->locateName('xl/sharedStrings.xml') !== false) {
                $sharedXml = $this->readXlsxEntry($zip, 'xl/sharedStrings.xml', 16 * 1024 * 1024);
                $sharedDoc = $this->loadXmlDocument($sharedXml);
                $sharedXpath = new DOMXPath($sharedDoc);
                foreach ($sharedXpath->query('//*[local-name()="si"]') as $stringNode) {
                    $text = '';
                    foreach ($sharedXpath->query('.//*[local-name()="t"]', $stringNode) as $textNode) {
                        $text .= $textNode->textContent;
                    }
                    $sharedStrings[] = $text;
                }
            }

            $sheetDoc = $this->loadXmlDocument($sheetXml);
            $sheetXpath = new DOMXPath($sheetDoc);
            $matrix = [];
            foreach ($sheetXpath->query('//*[local-name()="sheetData"]/*[local-name()="row"]') as $rowNode) {
                $row = [];
                $fallbackColumn = 0;
                foreach ($sheetXpath->query('./*[local-name()="c"]', $rowNode) as $cell) {
                    if (!$cell instanceof DOMElement) {
                        continue;
                    }
                    $reference = strtoupper($cell->getAttribute('r'));
                    $column = $reference !== '' ? $this->xlsxColumnIndex($reference) : $fallbackColumn;
                    if ($column < 0 || $column > 199) {
                        continue;
                    }
                    $row[$column] = $this->xlsxCellValue($sheetXpath, $cell, $sharedStrings);
                    $fallbackColumn = $column + 1;
                }
                if ($row !== []) {
                    ksort($row);
                    $matrix[] = $row;
                }
                if (count($matrix) > 10001) {
                    throw new \RuntimeException('il file Excel supera il limite di 10.000 righe');
                }
            }

            if ($matrix === []) {
                return [];
            }
            $headers = $this->normalizeRosterHeaders(array_shift($matrix));
            $columns = $this->resolveRosterColumns($headers);
            if ($columns['display'] === null && $columns['first'] === null && $columns['last'] === null) {
                return [];
            }

            $rows = [];
            foreach ($matrix as $row) {
                $mapped = $this->mapRosterDataRow($row, $columns);
                if ($mapped !== null) {
                    $rows[] = $mapped;
                }
            }
            return $rows;
        } finally {
            $zip->close();
        }
    }

    private function readXlsxEntry(ZipArchive $zip, string $name, int $maxBytes): string
    {
        $stat = $zip->statName($name);
        if (!is_array($stat) || !isset($stat['size'])) {
            throw new \RuntimeException('struttura Excel incompleta');
        }
        if ((int) $stat['size'] <= 0 || (int) $stat['size'] > $maxBytes) {
            throw new \RuntimeException('contenuto Excel non valido o troppo grande');
        }
        $content = $zip->getFromName($name);
        if ($content === false || strlen($content) > $maxBytes) {
            throw new \RuntimeException('contenuto Excel non leggibile');
        }
        return $content;
    }

    private function loadXmlDocument(string $xml): DOMDocument
    {
        $previous = libxml_use_internal_errors(true);
        try {
            $document = new DOMDocument();
            if (!$document->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS | LIBXML_COMPACT)) {
                throw new \RuntimeException('XML Excel non valido');
            }
            return $document;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function normalizeXlsxSheetPath(string $target): string
    {
        $target = trim(str_replace('\\', '/', $target));
        if ($target === '') {
            throw new \RuntimeException('foglio Excel non trovato');
        }
        $target = ltrim($target, '/');
        if (!str_starts_with($target, 'xl/')) {
            $target = 'xl/' . $target;
        }
        if (str_contains($target, '../') || !preg_match('#^xl/worksheets/[^/]+\.xml$#i', $target)) {
            throw new \RuntimeException('percorso del foglio Excel non valido');
        }
        return $target;
    }

    private function xlsxColumnIndex(string $reference): int
    {
        if (!preg_match('/^([A-Z]+)/', $reference, $match)) {
            return -1;
        }
        $letters = $match[1];
        $index = 0;
        for ($i = 0, $length = strlen($letters); $i < $length; $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - 64);
        }
        return $index - 1;
    }

    /** @param array<int,string> $sharedStrings */
    private function xlsxCellValue(DOMXPath $xpath, DOMElement $cell, array $sharedStrings): string
    {
        $type = $cell->getAttribute('t');
        if ($type === 'inlineStr') {
            $text = '';
            foreach ($xpath->query('.//*[local-name()="t"]', $cell) as $textNode) {
                $text .= $textNode->textContent;
            }
            return trim($text);
        }

        $valueNode = $xpath->query('./*[local-name()="v"]', $cell)->item(0);
        $value = $valueNode ? trim($valueNode->textContent) : '';
        if ($type === 's' && $value !== '' && ctype_digit($value)) {
            return trim((string) ($sharedStrings[(int) $value] ?? ''));
        }
        return $value;
    }

    /** @param array<int,mixed> $headers @return array<int,string> */
    private function normalizeRosterHeaders(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $index => $header) {
            $h = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header) ?? (string) $header;
            $h = mb_strtolower(trim($h), 'UTF-8');
            if ($h === '#') {
                $h = 'number';
            } else {
                $h = preg_replace('/[^a-z0-9]+/', '_', $h) ?? $h;
            }
            $normalized[(int) $index] = $h;
        }
        return $normalized;
    }

    /** @return array{team:?int,first:?int,last:?int,display:?int,shirt:?int,role:?int} */
    private function resolveRosterColumns(array $headers): array
    {
        return [
            'team' => $this->findHeaderIndex($headers, ['nameteam','team','team_name','squadra','club']),
            'first' => $this->findHeaderIndex($headers, ['firstname','first_name','given_name','nome','name']),
            'last' => $this->findHeaderIndex($headers, ['lastname','last_name','surname','family_name','cognome']),
            'display' => $this->findHeaderIndex($headers, ['display_name','fullname','full_name','person','player','giocatore']),
            'shirt' => $this->findHeaderIndex($headers, ['shirt_number','shirtnumber','number','nr','n','numero','numero_maglia','jersey_number']),
            'role' => $this->findHeaderIndex($headers, ['role','ruolo','type','tipo','position','posizione']),
        ];
    }

    /** @param array<int,mixed> $row @param array{team:?int,first:?int,last:?int,display:?int,shirt:?int,role:?int} $columns */
    private function mapRosterDataRow(array $row, array $columns): ?array
    {
        $display = $columns['display'];
        $first = $columns['first'];
        $last = $columns['last'];
        $team = $columns['team'];
        $shirt = $columns['shirt'];
        $role = $columns['role'];

        $name = $display !== null ? trim((string) ($row[$display] ?? '')) : '';
        if ($name === '') {
            $name = trim(($first !== null ? (string) ($row[$first] ?? '') : '') . ' ' . ($last !== null ? (string) ($row[$last] ?? '') : ''));
        }
        if ($name === '') {
            return null;
        }

        return [
            'team' => $team !== null ? trim((string) ($row[$team] ?? '')) : '',
            'name' => mb_substr($name, 0, 300, 'UTF-8'),
            'shirt_number' => $shirt !== null ? trim((string) ($row[$shirt] ?? '')) : '',
            'role' => $role !== null ? mb_substr(trim((string) ($row[$role] ?? '')), 0, 300, 'UTF-8') : '',
        ];
    }

    private function findHeaderIndex(array $headers, array $aliases): ?int
    {
        foreach ($aliases as $alias) {
            $i = array_search($alias, $headers, true);
            if ($i !== false) {
                return (int) $i;
            }
        }
        return null;
    }

    private function rosterExists(int $participationId, int $playerId): bool
    {
        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__xdecarocompetitions_rosters'))
            ->where($db->quoteName('participation_id') . ' = :pid')
            ->where($db->quoteName('player_id') . ' = :plid')
            ->bind(':pid', $participationId, ParameterType::INTEGER)
            ->bind(':plid', $playerId, ParameterType::INTEGER);
        return (int) $db->setQuery($query)->loadResult() > 0;
    }
}

<?php
namespace xdecaro\Component\Competitions\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Throwable;

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
            $this->setMessage('Seleziona un file CSV valido.', 'warning');
            $this->setRedirect($redirect);
            return;
        }
        if ($size <= 0 || $size > 4 * 1024 * 1024 || !in_array($extension, ['csv', 'txt'], true) || !$this->hasAllowedCsvMime($tmp)) {
            $this->setMessage('Formato CSV non valido.', 'warning');
            $this->setRedirect($redirect);
            return;
        }

        try {
            $rows = $this->parseCsvRosterRows($tmp);
        } catch (Throwable $e) {
            $this->setMessage('Impossibile leggere il CSV: ' . trim($e->getMessage()), 'warning');
            $this->setRedirect($redirect);
            return;
        }
        if (!$rows) {
            $this->setMessage('Nel CSV non sono stati trovati giocatori.', 'warning');
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
                $this->setMessage('Per importare tutte le squadre il CSV deve contenere la colonna Squadra/Team valorizzata.', 'warning');
                $this->setRedirect($redirect);
                return;
            }
        }

        $app->setUserState('com_xdecarocompetitions.rosterbulk.source_season_id', $seasonId);
        $app->setUserState('com_xdecarocompetitions.rosterbulk.source_target', $target);
        $app->setUserState('com_xdecarocompetitions.rosterbulk.source_participation_id', $participationId);
        $app->setUserState('com_xdecarocompetitions.rosterbulk.source_rows', $rows);
        $app->setUserState('com_xdecarocompetitions.rosterbulk.source_file_name', $name);
        $this->setMessage(sprintf('CSV caricato: %d righe valide.', count($rows)), 'message');
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
            $this->setMessage('Il file di anteprima non corrisponde più alla selezione corrente. Ricarica il CSV.', 'warning');
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

    /**
     * @return array{0:int,1:string,2:int}
     */
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

    private function hasAllowedCsvMime(string $path): bool
    {
        if (!class_exists('finfo')) {
            return true;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = strtolower((string) $finfo->file($path));
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
            $headers = array_map(static function ($h): string {
                $h = preg_replace('/^\xEF\xBB\xBF/', '', (string) $h) ?? (string) $h;
                $h = mb_strtolower(trim($h), 'UTF-8');
                if ($h === '#') {
                    return 'number';
                }
                return preg_replace('/[^a-z0-9]+/', '_', $h) ?? $h;
            }, $headers);

            $team = $this->findHeaderIndex($headers, ['nameteam','team','team_name','squadra','club']);
            $first = $this->findHeaderIndex($headers, ['firstname','first_name','given_name','nome','name']);
            $last = $this->findHeaderIndex($headers, ['lastname','last_name','surname','family_name','cognome']);
            $display = $this->findHeaderIndex($headers, ['display_name','fullname','full_name','person','player','giocatore']);
            $shirt = $this->findHeaderIndex($headers, ['shirt_number','shirtnumber','number','nr','n','numero','numero_maglia','jersey_number']);
            $role = $this->findHeaderIndex($headers, ['role','ruolo','type','tipo','position','posizione']);
            if ($display === null && $first === null && $last === null) {
                return [];
            }

            $rows = [];
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $name = $display !== null ? trim((string) ($row[$display] ?? '')) : '';
                if ($name === '') {
                    $name = trim(($first !== null ? (string) ($row[$first] ?? '') : '') . ' ' . ($last !== null ? (string) ($row[$last] ?? '') : ''));
                }
                if ($name === '') {
                    continue;
                }
                $rows[] = [
                    'team' => $team !== null ? trim((string) ($row[$team] ?? '')) : '',
                    'name' => $name,
                    'shirt_number' => $shirt !== null ? trim((string) ($row[$shirt] ?? '')) : '',
                    'role' => $role !== null ? trim((string) ($row[$role] ?? '')) : '',
                ];
            }
            return $rows;
        } finally {
            fclose($handle);
        }
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

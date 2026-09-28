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

        $participationId = $this->input->post->getInt('participation_id');
        $redirect = 'index.php?option=com_xdecarocompetitions&view=rosterbulk&participation_id=' . $participationId;
        $file = (array) $this->input->files->get('source_csv', [], 'array');
        $tmp = (string) ($file['tmp_name'] ?? '');
        $name = trim((string) ($file['name'] ?? ''));
        $size = (int) ($file['size'] ?? 0);
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($participationId <= 0) {
            $this->setMessage('Seleziona prima una partecipazione.', 'warning');
            $this->setRedirect('index.php?option=com_xdecarocompetitions&view=rosterbulk');
            return;
        }
        if ($error !== UPLOAD_ERR_OK || $tmp === '' || !is_uploaded_file($tmp)) {
            $this->setMessage('Seleziona un file CSV valido.', 'warning');
            $this->setRedirect($redirect);
            return;
        }
        if ($size <= 0 || $size > 4 * 1024 * 1024 || !in_array(strtolower((string) pathinfo($name, PATHINFO_EXTENSION)), ['csv', 'txt'], true)) {
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
        $participationId = $this->input->post->getInt('participation_id');
        $app->setUserState('com_xdecarocompetitions.rosterbulk.source_participation_id', 0);
        $app->setUserState('com_xdecarocompetitions.rosterbulk.source_rows', []);
        $app->setUserState('com_xdecarocompetitions.rosterbulk.source_file_name', '');
        $this->setRedirect('index.php?option=com_xdecarocompetitions&view=rosterbulk&participation_id=' . $participationId);
    }

    public function addSelected(): void
    {
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.create', 'com_xdecarocompetitions')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
        $this->checkToken();

        $participationId = $this->input->post->getInt('participation_id');
        $selected = array_values(array_unique(array_filter(array_map('intval', (array) $this->input->post->get('player_id', [], 'array')))));
        if ($participationId <= 0 || !$selected) {
            $this->setMessage('Seleziona una partecipazione e almeno un giocatore.', 'warning');
            $this->setRedirect('index.php?option=com_xdecarocompetitions&view=rosterbulk&participation_id=' . $participationId);
            return;
        }

        $rows = (array) $app->getUserState('com_xdecarocompetitions.rosterbulk.source_rows', []);
        $component = $app->bootComponent('com_xdecarocompetitions');
        $bulkModel = $component->getMVCFactory()->createModel('Rosterbulk', 'Administrator', ['ignore_request' => true]);
        $match = $bulkModel->matchCsvRows($participationId, $rows);
        $byPlayer = [];
        foreach ($match['items'] as $item) {
            $byPlayer[(int) $item['player_id']] = $item;
        }

        $created = $skipped = $failed = 0;
        foreach ($selected as $playerId) {
            if (!isset($byPlayer[$playerId]) || $this->rosterExists($participationId, $playerId)) {
                $skipped++;
                continue;
            }
            $item = $byPlayer[$playerId];
            try {
                $model = $component->getMVCFactory()->createModel('Roster', 'Administrator', ['ignore_request' => true]);
                if (!$model || !$model->save([
                    'id' => 0,
                    'participation_id' => $participationId,
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
        $this->setRedirect('index.php?option=com_xdecarocompetitions&view=rosters&filter_participation_id=' . $participationId);
    }

    /** @return array<int,array<string,mixed>> */
    private function parseCsvRosterRows(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) throw new \RuntimeException('file non apribile');
        try {
            $firstLine = fgets($handle);
            if ($firstLine === false) return [];
            $counts = [';' => substr_count($firstLine, ';'), ',' => substr_count($firstLine, ','), "\t" => substr_count($firstLine, "\t")];
            arsort($counts); $delimiter = (string) array_key_first($counts); if (($counts[$delimiter] ?? 0) <= 0) $delimiter = ',';
            rewind($handle);
            $headers = fgetcsv($handle, 0, $delimiter);
            if (!is_array($headers)) return [];
            $headers = array_map(static function ($h): string {
                $h = preg_replace('/^\xEF\xBB\xBF/', '', (string) $h) ?? (string) $h;
                $h = mb_strtolower(trim($h), 'UTF-8');
                return preg_replace('/[^a-z0-9]+/', '_', $h) ?? $h;
            }, $headers);

            $team = $this->findHeaderIndex($headers, ['nameteam','team','team_name','squadra','club']);
            $first = $this->findHeaderIndex($headers, ['firstname','first_name','given_name','nome','name']);
            $last = $this->findHeaderIndex($headers, ['lastname','last_name','surname','family_name','cognome']);
            $display = $this->findHeaderIndex($headers, ['display_name','fullname','full_name','person','player','giocatore']);
            $shirt = $this->findHeaderIndex($headers, ['shirt_number','shirtnumber','number','numero','numero_maglia','jersey_number']);
            $role = $this->findHeaderIndex($headers, ['role','ruolo','type','tipo','position','posizione']);
            if ($display === null && $first === null && $last === null) return [];

            $rows = [];
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $name = $display !== null ? trim((string) ($row[$display] ?? '')) : '';
                if ($name === '') {
                    $name = trim(($first !== null ? (string) ($row[$first] ?? '') : '') . ' ' . ($last !== null ? (string) ($row[$last] ?? '') : ''));
                }
                if ($name === '') continue;
                $rows[] = [
                    'team' => $team !== null ? trim((string) ($row[$team] ?? '')) : '',
                    'name' => $name,
                    'shirt_number' => $shirt !== null ? trim((string) ($row[$shirt] ?? '')) : '',
                    'role' => $role !== null ? trim((string) ($row[$role] ?? '')) : '',
                ];
            }
            return $rows;
        } finally { fclose($handle); }
    }

    private function findHeaderIndex(array $headers, array $aliases): ?int
    {
        foreach ($aliases as $alias) { $i = array_search($alias, $headers, true); if ($i !== false) return (int) $i; }
        return null;
    }

    private function rosterExists(int $participationId, int $playerId): bool
    {
        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__xdecarocompetitions_rosters'))
            ->where($db->quoteName('participation_id') . ' = :pid')->where($db->quoteName('player_id') . ' = :plid')
            ->bind(':pid', $participationId, ParameterType::INTEGER)->bind(':plid', $playerId, ParameterType::INTEGER);
        return (int) $db->setQuery($query)->loadResult() > 0;
    }
}

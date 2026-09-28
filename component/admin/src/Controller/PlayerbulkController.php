<?php
namespace xdecaro\Component\Competitions\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Throwable;

final class PlayerbulkController extends BaseController
{
    protected $option = 'com_xdecarocompetitions';

    public function previewCsv(): void
    {
        $app = Factory::getApplication();
        $user = $app->getIdentity();

        if (!$user->authorise('core.create', 'com_xdecarocompetitions')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $this->checkToken();
        $file = (array) $this->input->files->get('source_csv', [], 'array');
        $tmp = (string) ($file['tmp_name'] ?? '');
        $name = trim((string) ($file['name'] ?? ''));
        $size = (int) ($file['size'] ?? 0);
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        $redirect = 'index.php?option=com_xdecarocompetitions&view=playerbulk';

        if ($error !== UPLOAD_ERR_OK || $tmp === '' || !is_uploaded_file($tmp)) {
            $this->setMessage('Seleziona un file CSV valido.', 'warning');
            $this->setRedirect($redirect);
            return;
        }

        if ($size <= 0 || $size > 4 * 1024 * 1024) {
            $this->setMessage('Il CSV deve essere compreso tra 1 byte e 4 MB.', 'warning');
            $this->setRedirect($redirect);
            return;
        }

        if (!in_array(strtolower((string) pathinfo($name, PATHINFO_EXTENSION)), ['csv', 'txt'], true)) {
            $this->setMessage('Formato non valido: usa un file CSV.', 'warning');
            $this->setRedirect($redirect);
            return;
        }

        try {
            $names = $this->parseCsvPersonNames($tmp);
        } catch (Throwable $e) {
            $this->setMessage('Impossibile leggere il CSV: ' . trim($e->getMessage()), 'warning');
            $this->setRedirect($redirect);
            return;
        }

        if (!$names) {
            $this->setMessage('Nel CSV non sono stati trovati nominativi. Sono supportate colonne firstname/lastname, first_name/last_name, nome/cognome oppure display_name.', 'warning');
            $this->setRedirect($redirect);
            return;
        }

        $app->setUserState('com_xdecarocompetitions.playerbulk.source_person_names', $names);
        $app->setUserState('com_xdecarocompetitions.playerbulk.source_file_name', $name);
        $this->setMessage(sprintf('CSV caricato: %d persone uniche trovate.', count($names)), 'message');
        $this->setRedirect($redirect);
    }

    public function clearCsv(): void
    {
        $app = Factory::getApplication();
        $user = $app->getIdentity();

        if (!$user->authorise('core.create', 'com_xdecarocompetitions')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $this->checkToken();
        $app->setUserState('com_xdecarocompetitions.playerbulk.source_person_names', []);
        $app->setUserState('com_xdecarocompetitions.playerbulk.source_file_name', '');
        $this->setRedirect('index.php?option=com_xdecarocompetitions&view=playerbulk');
    }

    public function addSelected(): void
    {
        $app = Factory::getApplication();
        $user = $app->getIdentity();

        if (!$user->authorise('core.create', 'com_xdecarocompetitions')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $this->checkToken();

        $uuids = array_values(array_unique(array_filter(array_map(
            static fn ($uuid): string => strtolower(trim((string) $uuid)),
            (array) $this->input->post->get('person_uuid', [], 'array')
        ), static fn (string $uuid): bool => (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid))));

        $search = trim($this->input->post->getString('search', ''));
        $redirect = 'index.php?option=com_xdecarocompetitions&view=playerbulk'
            . ($search !== '' ? '&search=' . rawurlencode($search) : '');

        if (!$uuids) {
            $this->setMessage('Seleziona almeno una persona.', 'warning');
            $this->setRedirect($redirect);
            return;
        }

        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $component = $app->bootComponent('com_xdecarocompetitions');
        $mvcFactory = $component->getMVCFactory();
        $created = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($uuids as $uuid) {
            if ($this->playerExists($db, $uuid)) {
                $skipped++;
                continue;
            }

            try {
                $model = $mvcFactory->createModel('Player', 'Administrator', ['ignore_request' => true]);

                if (!$model || !$model->save([
                    'id' => 0,
                    'person_uuid' => $uuid,
                    'approval_status' => 'pending',
                    'state' => 1,
                ])) {
                    throw new \RuntimeException($model ? (string) $model->getError() : 'Player model unavailable.');
                }

                $created++;
            } catch (Throwable $e) {
                $failed++;
                $app->enqueueMessage(trim($e->getMessage()), 'warning');
            }
        }

        $this->setMessage(
            sprintf('Giocatori creati: %d. Saltati: %d. Errori: %d.', $created, $skipped, $failed),
            $failed > 0 ? 'warning' : 'message'
        );
        $this->setRedirect('index.php?option=com_xdecarocompetitions&view=players');
    }

    /** @return array<int, string> */
    private function parseCsvPersonNames(string $path): array
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

            $headers = array_map(static function ($header): string {
                $header = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header) ?? (string) $header;
                $header = mb_strtolower(trim($header), 'UTF-8');
                return preg_replace('/[^a-z0-9]+/', '_', $header) ?? $header;
            }, $headers);

            $firstAliases = ['firstname', 'first_name', 'given_name', 'nome', 'name'];
            $lastAliases = ['lastname', 'last_name', 'surname', 'family_name', 'cognome'];
            $displayAliases = ['display_name', 'fullname', 'full_name', 'person', 'player', 'giocatore'];
            $firstIndex = $this->findHeaderIndex($headers, $firstAliases);
            $lastIndex = $this->findHeaderIndex($headers, $lastAliases);
            $displayIndex = $this->findHeaderIndex($headers, $displayAliases);

            if ($displayIndex === null && $firstIndex === null && $lastIndex === null) {
                return [];
            }

            $names = [];
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $display = $displayIndex !== null ? trim((string) ($row[$displayIndex] ?? '')) : '';
                if ($display === '') {
                    $first = $firstIndex !== null ? trim((string) ($row[$firstIndex] ?? '')) : '';
                    $last = $lastIndex !== null ? trim((string) ($row[$lastIndex] ?? '')) : '';
                    $display = trim($first . ' ' . $last);
                }
                if ($display === '') {
                    continue;
                }
                $key = mb_strtoupper(preg_replace('/\s+/u', ' ', $display) ?? $display, 'UTF-8');
                $names[$key] = $display;
            }

            return array_values($names);
        } finally {
            fclose($handle);
        }
    }

    /** @param array<int, string> $headers @param array<int, string> $aliases */
    private function findHeaderIndex(array $headers, array $aliases): ?int
    {
        foreach ($aliases as $alias) {
            $index = array_search($alias, $headers, true);
            if ($index !== false) {
                return (int) $index;
            }
        }
        return null;
    }

    private function playerExists(DatabaseInterface $db, string $uuid): bool
    {
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__xdecarocompetitions_players'))
            ->where($db->quoteName('person_uuid') . ' = :personUuid')
            ->bind(':personUuid', $uuid, ParameterType::STRING);

        return (int) $db->setQuery($query)->loadResult() > 0;
    }
}

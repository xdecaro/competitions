<?php
namespace xdecaro\Component\Competitions\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Throwable;

final class ParticipationbulkController extends BaseController
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
        $seasonId = $this->input->post->getInt('season_id');
        $redirect = 'index.php?option=com_xdecarocompetitions&view=participationbulk&season_id=' . $seasonId;

        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        if ($seasonId <= 0 || !$this->seasonExists($db, $seasonId)) {
            $this->setMessage('Seleziona una stagione valida prima di caricare il CSV.', 'warning');
            $this->setRedirect('index.php?option=com_xdecarocompetitions&view=participationbulk');
            return;
        }

        $file = (array) $this->input->files->get('source_csv', [], 'array');
        $tmp = (string) ($file['tmp_name'] ?? '');
        $name = trim((string) ($file['name'] ?? ''));
        $size = (int) ($file['size'] ?? 0);
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error !== UPLOAD_ERR_OK || $tmp === '' || !is_uploaded_file($tmp)) {
            $this->setMessage('Seleziona un file CSV valido.', 'warning');
            $this->setRedirect($redirect);
            return;
        }

        if ($size <= 0 || $size > 2 * 1024 * 1024) {
            $this->setMessage('Il CSV deve essere compreso tra 1 byte e 2 MB.', 'warning');
            $this->setRedirect($redirect);
            return;
        }

        $extension = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($extension, ['csv', 'txt'], true)) {
            $this->setMessage('Formato non valido: usa un file CSV.', 'warning');
            $this->setRedirect($redirect);
            return;
        }

        try {
            $names = $this->parseCsvTeamNames($tmp);
        } catch (Throwable $e) {
            $this->setMessage('Impossibile leggere il CSV: ' . trim($e->getMessage()), 'warning');
            $this->setRedirect($redirect);
            return;
        }

        if (!$names) {
            $this->setMessage('Nel CSV non è stata trovata alcuna squadra. È richiesta una colonna nameteam, team, team_name o squadra.', 'warning');
            $this->setRedirect($redirect);
            return;
        }

        $app->setUserState('com_xdecarocompetitions.participationbulk.source_season_id', $seasonId);
        $app->setUserState('com_xdecarocompetitions.participationbulk.source_team_names', $names);
        $app->setUserState('com_xdecarocompetitions.participationbulk.source_file_name', $name);

        $this->setMessage(sprintf('CSV caricato: %d squadre uniche trovate.', count($names)), 'message');
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
        $seasonId = $this->input->post->getInt('season_id');
        $app->setUserState('com_xdecarocompetitions.participationbulk.source_season_id', 0);
        $app->setUserState('com_xdecarocompetitions.participationbulk.source_team_names', []);
        $app->setUserState('com_xdecarocompetitions.participationbulk.source_file_name', '');
        $this->setRedirect('index.php?option=com_xdecarocompetitions&view=participationbulk&season_id=' . $seasonId);
    }

    public function addSelected(): void
    {
        $app = Factory::getApplication();
        $user = $app->getIdentity();

        if (!$user->authorise('core.create', 'com_xdecarocompetitions')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $this->checkToken();

        $seasonId = $this->input->post->getInt('season_id');
        $teamIds = array_values(array_unique(array_filter(array_map(
            'intval',
            (array) $this->input->post->get('cid', [], 'array')
        ), static fn (int $id): bool => $id > 0)));

        $redirect = 'index.php?option=com_xdecarocompetitions&view=participationbulk&season_id=' . $seasonId;

        if ($seasonId <= 0 || !$teamIds) {
            $this->setMessage('Seleziona una stagione e almeno una squadra.', 'warning');
            $this->setRedirect($redirect);
            return;
        }

        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        if (!$this->seasonExists($db, $seasonId)) {
            $this->setMessage('La stagione selezionata non è valida.', 'warning');
            $this->setRedirect('index.php?option=com_xdecarocompetitions&view=participationbulk');
            return;
        }

        $component = $app->bootComponent('com_xdecarocompetitions');
        $mvcFactory = $component->getMVCFactory();
        $created = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($teamIds as $teamId) {
            if (!$this->teamExists($db, $teamId) || $this->participationExists($db, $teamId, $seasonId)) {
                $skipped++;
                continue;
            }

            try {
                $model = $mvcFactory->createModel('Participation', 'Administrator', ['ignore_request' => true]);

                if (!$model || !$model->save([
                    'id' => 0,
                    'team_id' => $teamId,
                    'season_id' => $seasonId,
                    'status' => 'draft',
                    'submitted_at' => null,
                    'reviewed_at' => null,
                    'reviewed_by' => 0,
                    'review_note' => '',
                    'state' => 1,
                ])) {
                    throw new \RuntimeException($model ? (string) $model->getError() : 'Participation model unavailable.');
                }

                $created++;
            } catch (Throwable $e) {
                $failed++;
                $app->enqueueMessage(trim($e->getMessage()), 'warning');
            }
        }

        $this->setMessage(
            sprintf('Partecipazioni create: %d. Saltate: %d. Errori: %d.', $created, $skipped, $failed),
            $failed > 0 ? 'warning' : 'message'
        );
        $this->setRedirect(
            'index.php?option=com_xdecarocompetitions&view=participations&filter_season_id=' . $seasonId
        );
    }

    /** @return array<int, string> */
    private function parseCsvTeamNames(string $path): array
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

            $delimiterCounts = [
                ';' => substr_count($firstLine, ';'),
                ',' => substr_count($firstLine, ','),
                "\t" => substr_count($firstLine, "\t"),
            ];
            arsort($delimiterCounts);
            $delimiter = (string) array_key_first($delimiterCounts);
            if (($delimiterCounts[$delimiter] ?? 0) <= 0) {
                $delimiter = ',';
            }

            rewind($handle);
            $headers = fgetcsv($handle, 0, $delimiter);
            if (!is_array($headers)) {
                return [];
            }

            $normalizedHeaders = array_map(static function ($header): string {
                $header = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header) ?? (string) $header;
                $header = mb_strtolower(trim($header), 'UTF-8');
                return preg_replace('/[^a-z0-9_]+/', '_', $header) ?? $header;
            }, $headers);

            $aliases = ['nameteam', 'team', 'team_name', 'squadra'];
            $teamIndex = null;
            foreach ($aliases as $alias) {
                $index = array_search($alias, $normalizedHeaders, true);
                if ($index !== false) {
                    $teamIndex = (int) $index;
                    break;
                }
            }

            if ($teamIndex === null) {
                return [];
            }

            $names = [];
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $name = trim((string) ($row[$teamIndex] ?? ''));
                if ($name === '') {
                    continue;
                }
                $key = mb_strtoupper(preg_replace('/\s+/u', ' ', $name) ?? $name, 'UTF-8');
                $names[$key] = $name;
            }

            return array_values($names);
        } finally {
            fclose($handle);
        }
    }

    private function seasonExists(DatabaseInterface $db, int $seasonId): bool
    {
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__xdecarocompetitions_seasons'))
            ->where($db->quoteName('id') . ' = :id')
            ->where($db->quoteName('state') . ' <> -2')
            ->bind(':id', $seasonId, ParameterType::INTEGER);

        return (int) $db->setQuery($query)->loadResult() > 0;
    }

    private function teamExists(DatabaseInterface $db, int $teamId): bool
    {
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__xdecarocompetitions_teams'))
            ->where($db->quoteName('id') . ' = :id')
            ->where($db->quoteName('state') . ' <> -2')
            ->bind(':id', $teamId, ParameterType::INTEGER);

        return (int) $db->setQuery($query)->loadResult() > 0;
    }

    private function participationExists(DatabaseInterface $db, int $teamId, int $seasonId): bool
    {
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__xdecarocompetitions_participations'))
            ->where($db->quoteName('team_id') . ' = :teamId')
            ->where($db->quoteName('season_id') . ' = :seasonId')
            ->bind(':teamId', $teamId, ParameterType::INTEGER)
            ->bind(':seasonId', $seasonId, ParameterType::INTEGER);

        return (int) $db->setQuery($query)->loadResult() > 0;
    }
}

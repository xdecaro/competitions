<?php
namespace xdecaro\Component\Competitions\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\Database\DatabaseInterface;
use Throwable;
use xdecaro\Component\Competitions\Administrator\Helper\LanguageHelper;

final class TeamimportController extends BaseController
{
    protected $option = 'com_xdecarocompetitions';

    public function importSelected(): void
    {
        LanguageHelper::load();
        $app = Factory::getApplication();
        $user = $app->getIdentity();

        if (!$user->authorise('core.create', 'com_xdecarocompetitions')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $this->checkToken();

        $uuids = array_values(array_unique(array_filter(array_map(
            static function ($value): string {
                $value = strtolower(trim((string) $value));

                return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $value)
                    ? $value
                    : '';
            },
            (array) $this->input->post->get('cid', [], 'array')
        ))));

        $redirect = 'index.php?option=com_xdecarocompetitions&view=teamimport';

        if (!$uuids) {
            $this->setMessage(Text::_('COM_XDECAROCOMPETITIONS_TEAMIMPORT_ERROR_NOTHING_SELECTED'), 'warning');
            $this->setRedirect($redirect);
            return;
        }

        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $component = $app->bootComponent('com_xdecarocompetitions');
        $mvcFactory = $component->getMVCFactory();

        $imported = 0;
        $skipped = 0;
        $failed = 0;
        $errors = [];

        foreach ($uuids as $uuid) {
            if ($this->alreadyImported($db, $uuid)) {
                $skipped++;
                continue;
            }

            try {
                $teamModel = $mvcFactory->createModel('Team', 'Administrator', ['ignore_request' => true]);

                if (!$teamModel) {
                    throw new \RuntimeException('Team model is unavailable.');
                }

                $data = [
                    'id' => 0,
                    'modified' => '',
                    'organization_uuid' => $uuid,
                    'team_type' => 'club',
                    'federation_id' => 0,
                    'owner_user_id' => 0,
                    'name' => '',
                    'short_name' => '',
                    'alias' => '',
                    'logo' => '',
                    'city' => '',
                    'email' => '',
                    'phone' => '',
                    'website' => '',
                    'approval_status' => 'pending',
                    'rejection_reason' => '',
                    'state' => 1,
                    'ordering' => 0,
                ];

                if (!$teamModel->save($data)) {
                    throw new \RuntimeException((string) $teamModel->getError());
                }

                $imported++;
            } catch (Throwable $e) {
                $failed++;
                $errors[] = $uuid . ' — ' . trim($e->getMessage());
            }
        }

        $this->setMessage(
            Text::sprintf(
                'COM_XDECAROCOMPETITIONS_TEAMIMPORT_RESULT',
                $imported,
                $skipped,
                $failed
            ),
            $failed > 0 ? 'warning' : 'message'
        );

        foreach (array_slice($errors, 0, 10) as $error) {
            $app->enqueueMessage(
                Text::sprintf('COM_XDECAROCOMPETITIONS_TEAMIMPORT_ERROR_ROW', $error),
                'warning'
            );
        }

        if (count($errors) > 10) {
            $app->enqueueMessage(
                Text::sprintf(
                    'COM_XDECAROCOMPETITIONS_TEAMIMPORT_ERROR_MORE',
                    count($errors) - 10
                ),
                'warning'
            );
        }

        $this->setRedirect($redirect);
    }

    private function alreadyImported(DatabaseInterface $db, string $uuid): bool
    {
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__xdecarocompetitions_teams'))
            ->where($db->quoteName('organization_uuid') . ' = :organizationUuid')
            ->bind(':organizationUuid', $uuid);

        return (int) $db->setQuery($query)->loadResult() > 0;
    }
}

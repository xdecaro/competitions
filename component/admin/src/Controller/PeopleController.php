<?php
namespace xdecaro\Component\Competitions\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use RuntimeException;
use Throwable;
use xdecaro\Component\Competitions\Administrator\Service\PeopleIntegrationService;

final class PeopleController extends BaseController
{
    private function people(): PeopleIntegrationService
    {
        $component = Factory::getApplication()->bootComponent('com_xdecarocompetitions');
        if (!is_object($component) || !method_exists($component, 'getPeopleIntegrationService')) {
            throw new RuntimeException('Competitions People integration service is unavailable.');
        }

        return $component->getPeopleIntegrationService();
    }

    public function search(): void
    {
        $this->checkToken('get');
        $app = Factory::getApplication();
        $user = $app->getIdentity();

        if (!$user->authorise('core.manage', 'com_xdecarocompetitions')) {
            throw new RuntimeException('Not authorised', 403);
        }

        try {
            $q = trim($this->input->getString('q', ''));
            $rows = $this->people()->searchPeople($q, 20);
            $result = [];

            foreach ($rows as $row) {
                $uuid = strtolower(trim((string) ($row['uuid'] ?? '')));
                if ($uuid === '') {
                    continue;
                }

                $result[] = [
                    'uuid' => $uuid,
                    'display_name' => (string) ($row['display_name'] ?? ''),
                    'first_name' => (string) ($row['first_name'] ?? ''),
                    'last_name' => (string) ($row['last_name'] ?? ''),
                    'email' => (string) ($row['email'] ?? ''),
                    'phone' => (string) ($row['phone'] ?? ''),
                ];
            }

            echo new JsonResponse($result);
        } catch (Throwable $e) {
            echo new JsonResponse(null, $e->getMessage(), true);
        }

        $app->close();
    }

    public function profile(): void
    {
        $this->checkToken('get');
        $app = Factory::getApplication();
        $user = $app->getIdentity();

        if (!$user->authorise('core.manage', 'com_xdecarocompetitions')) {
            throw new RuntimeException('Not authorised', 403);
        }

        try {
            $uuid = strtolower(trim($this->input->getString('uuid', '')));
            if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid)) {
                throw new RuntimeException('Invalid People UUID.', 400);
            }

            $person = $this->people()->getProfilePerson($uuid);
            if (!$person) {
                throw new RuntimeException('People person not found.', 404);
            }

            echo new JsonResponse([
                'uuid' => $uuid,
                'display_name' => (string) ($person['display_name'] ?? ''),
                'first_name' => (string) ($person['first_name'] ?? ''),
                'last_name' => (string) ($person['last_name'] ?? ''),
                'birth_date' => (string) ($person['birth_date'] ?? ''),
                'nationality_code' => (string) ($person['nationality_code'] ?? ''),
            ]);
        } catch (Throwable $e) {
            echo new JsonResponse(null, $e->getMessage(), true);
        }

        $app->close();
    }
}

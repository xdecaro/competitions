<?php
namespace xdecaro\Component\Competitions\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use RuntimeException;
use Throwable;

final class PeopleIntegrationService
{
    public function isAvailable(): bool
    {
        try {
            $this->provider();
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function searchPeople(string $search, int $limit = 20): array
    {
        try {
            return (array) $this->provider()->searchPeople(['search' => trim($search)], max(1, min(50, $limit)), false);
        } catch (Throwable $e) {
            throw new RuntimeException('People search is unavailable: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    public function getPerson(string $uuid, bool $sensitive = false): ?array
    {
        $uuid = strtolower(trim($uuid));
        if ($uuid === '') {
            return null;
        }

        try {
            return $this->provider()->getPerson($uuid, $sensitive);
        } catch (Throwable $e) {
            throw new RuntimeException('People person lookup is unavailable: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    public function getProfilePerson(string $uuid): ?array
    {
        try {
            return $this->getPerson($uuid, true);
        } catch (Throwable) {
            return $this->getPerson($uuid, false);
        }
    }

    private function provider(): object
    {
        try {
            $component = Factory::getApplication()->bootComponent('com_xdecaropeople');
        } catch (Throwable $e) {
            throw new RuntimeException('People component could not be booted.', 0, $e);
        }

        if (!is_object($component) || !method_exists($component, 'getPersonProviderService')) {
            throw new RuntimeException('People public person provider is unavailable.');
        }

        $provider = $component->getPersonProviderService();
        if (!is_object($provider)
            || !method_exists($provider, 'getPerson')
            || !method_exists($provider, 'searchPeople')) {
            throw new RuntimeException('People public person provider is incompatible.');
        }

        return $provider;
    }
}

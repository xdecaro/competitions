<?php
namespace xdecaro\Component\Competitions\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use RuntimeException;
use Throwable;

final class PlayerbulkModel extends BaseDatabaseModel
{
    /** @return array<int, array<string, mixed>> */
    public function getAvailablePeople(string $search = ''): array
    {
        return $this->excludeLinkedPeople($this->searchPeople(trim($search), 200));
    }

    /** @param array<int, string> $sourceNames @return array<int, array<string, mixed>> */
    public function getPeopleForSourceNames(array $sourceNames): array
    {
        $found = [];

        foreach ($sourceNames as $sourceName) {
            $sourceKey = $this->normalizePersonName((string) $sourceName);
            if ($sourceKey === '') {
                continue;
            }

            foreach ($this->searchPeople((string) $sourceName, 20) as $person) {
                $uuid = strtolower(trim((string) ($person['uuid'] ?? '')));
                if ($uuid === '') {
                    continue;
                }

                $candidateNames = [
                    (string) ($person['display_name'] ?? ''),
                    trim((string) ($person['first_name'] ?? '') . ' ' . (string) ($person['last_name'] ?? '')),
                    trim((string) ($person['last_name'] ?? '') . ' ' . (string) ($person['first_name'] ?? '')),
                ];

                foreach ($candidateNames as $candidate) {
                    if ($this->normalizePersonName($candidate) === $sourceKey) {
                        $found[$uuid] = $person;
                        break;
                    }
                }
            }
        }

        uasort($found, static function (array $a, array $b): int {
            $aName = trim((string) ($a['last_name'] ?? '') . ' ' . (string) ($a['first_name'] ?? ''));
            $bName = trim((string) ($b['last_name'] ?? '') . ' ' . (string) ($b['first_name'] ?? ''));
            return strcasecmp($aName, $bName);
        });

        return array_values($found);
    }

    /** @param array<int, array<string,mixed>> $people @return array<int, array<string,mixed>> */
    public function excludeLinkedPeople(array $people): array
    {
        if (!$people) {
            return [];
        }

        $linked = $this->getLinkedPersonUuids();
        return array_values(array_filter($people, static function (array $person) use ($linked): bool {
            $uuid = strtolower(trim((string) ($person['uuid'] ?? '')));
            return $uuid !== '' && !isset($linked[$uuid]);
        }));
    }

    /** @param array<int, array<string,mixed>> $people @param array<int,string> $sourceNames */
    public function filterPeopleBySourceNames(array $people, array $sourceNames): array
    {
        if (!$sourceNames) {
            return $people;
        }

        $wanted = [];
        foreach ($sourceNames as $name) {
            $key = $this->normalizePersonName((string) $name);
            if ($key !== '') {
                $wanted[$key] = true;
            }
        }

        return array_values(array_filter($people, function (array $person) use ($wanted): bool {
            $candidates = [
                (string) ($person['display_name'] ?? ''),
                trim((string) ($person['first_name'] ?? '') . ' ' . (string) ($person['last_name'] ?? '')),
                trim((string) ($person['last_name'] ?? '') . ' ' . (string) ($person['first_name'] ?? '')),
            ];
            foreach ($candidates as $candidate) {
                if (isset($wanted[$this->normalizePersonName($candidate)])) {
                    return true;
                }
            }
            return false;
        }));
    }

    /** @param array<int, array<string,mixed>> $people @param array<int,string> $sourceNames @return array<int,string> */
    public function getUnmatchedSourceNames(array $people, array $sourceNames): array
    {
        $matched = [];
        foreach ($people as $person) {
            foreach ([
                (string) ($person['display_name'] ?? ''),
                trim((string) ($person['first_name'] ?? '') . ' ' . (string) ($person['last_name'] ?? '')),
                trim((string) ($person['last_name'] ?? '') . ' ' . (string) ($person['first_name'] ?? '')),
            ] as $candidate) {
                $key = $this->normalizePersonName($candidate);
                if ($key !== '') {
                    $matched[$key] = true;
                }
            }
        }

        $unmatched = [];
        foreach ($sourceNames as $sourceName) {
            $key = $this->normalizePersonName((string) $sourceName);
            if ($key !== '' && !isset($matched[$key])) {
                $unmatched[$key] = (string) $sourceName;
            }
        }

        return array_values($unmatched);
    }

    /** @param array<int,array<string,mixed>> $people @param array<int,string> $sourceNames */
    public function getSourceFilterSummary(array $people, array $sourceNames): array
    {
        $source = [];
        foreach ($sourceNames as $name) {
            $key = $this->normalizePersonName((string) $name);
            if ($key !== '') {
                $source[$key] = true;
            }
        }
        $unmatched = $this->getUnmatchedSourceNames($people, $sourceNames);
        return [
            'source' => count($source),
            'matched' => max(0, count($source) - count($unmatched)),
            'unmatched' => count($unmatched),
        ];
    }

    public function normalizePersonName(string $value): string
    {
        $value = html_entity_decode(trim($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = mb_strtoupper($value, 'UTF-8');
        $value = strtr($value, [
            'À'=>'A','Á'=>'A','Â'=>'A','Ã'=>'A','Ä'=>'A','Å'=>'A','Æ'=>'AE',
            'Ç'=>'C','È'=>'E','É'=>'E','Ê'=>'E','Ë'=>'E','Ì'=>'I','Í'=>'I','Î'=>'I','Ï'=>'I',
            'Ñ'=>'N','Ò'=>'O','Ó'=>'O','Ô'=>'O','Õ'=>'O','Ö'=>'O','Ø'=>'O',
            'Ù'=>'U','Ú'=>'U','Û'=>'U','Ü'=>'U','Ý'=>'Y','Ÿ'=>'Y','Š'=>'S','Ž'=>'Z','Đ'=>'D','Ł'=>'L',
        ]);
        $value = preg_replace('/[^A-Z0-9]+/u', ' ', $value) ?? $value;
        return trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
    }

    /** @return array<int,array<string,mixed>> */
    private function searchPeople(string $search, int $limit): array
    {
        try {
            $component = Factory::getApplication()->bootComponent('com_xdecarocompetitions');
            if (!is_object($component) || !method_exists($component, 'getPeopleIntegrationService')) {
                throw new RuntimeException('People integration unavailable.');
            }
            return (array) $component->getPeopleIntegrationService()->searchPeople($search, $limit);
        } catch (Throwable $e) {
            throw new RuntimeException('People search is unavailable: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /** @return array<string,bool> */
    private function getLinkedPersonUuids(): array
    {
        $db = $this->getDatabase();
        $uuids = (array) $db->setQuery(
            $db->getQuery(true)
                ->select($db->quoteName('person_uuid'))
                ->from($db->quoteName('#__xdecarocompetitions_players'))
                ->where($db->quoteName('person_uuid') . ' IS NOT NULL')
        )->loadColumn();

        return array_fill_keys(array_filter(array_map(
            static fn ($uuid): string => strtolower(trim((string) $uuid)),
            $uuids
        )), true);
    }
}

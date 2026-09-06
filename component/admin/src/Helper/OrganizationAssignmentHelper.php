<?php
namespace Xdecaro\Component\Decarodcl\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class OrganizationAssignmentHelper
{
    public static function normalizeIds(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $values = is_array($value) ? $value : [$value];
        $ids = [];

        foreach ($values as $item) {
            $id = (int) $item;

            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }

    public static function load(DatabaseInterface $db, string $table, string $foreignKey, int $entityId): array
    {
        if ($entityId <= 0) {
            return [];
        }

        $query = $db->getQuery(true)
            ->select([$db->quoteName('organization_id'), $db->quoteName('role')])
            ->from($db->quoteName($table))
            ->where($db->quoteName($foreignKey) . ' = :entityId')
            ->order([$db->quoteName('role') . ' ASC', $db->quoteName('ordering') . ' ASC', $db->quoteName('id') . ' ASC'])
            ->bind(':entityId', $entityId, ParameterType::INTEGER);

        $rows = $db->setQuery($query)->loadObjectList() ?: [];
        $result = [];

        foreach ($rows as $row) {
            $role = (string) $row->role;
            $result[$role] ??= [];
            $result[$role][] = (int) $row->organization_id;
        }

        return $result;
    }

    public static function validate(DatabaseInterface $db, array $roleMap): void
    {
        LanguageHelper::load();
        $ids = [];

        foreach ($roleMap as $role => $roleIds) {
            if (!is_string($role) || $role === '') {
                throw new \RuntimeException(Text::_('COM_DECARODCL_ERROR_ORGANIZATION_REFERENCE_INVALID'));
            }

            foreach ($roleIds as $id) {
                $id = (int) $id;

                if ($id > 0) {
                    $ids[$id] = $id;
                }
            }
        }

        foreach ($ids as $id) {
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__dcl_organizations'))
                ->where($db->quoteName('id') . ' = :organizationId')
                ->where($db->quoteName('state') . ' <> -2')
                ->bind(':organizationId', $id, ParameterType::INTEGER);

            if ((int) $db->setQuery($query)->loadResult() === 0) {
                throw new \RuntimeException(Text::_('COM_DECARODCL_ERROR_ORGANIZATION_REFERENCE_INVALID'));
            }
        }
    }

    public static function sync(DatabaseInterface $db, string $table, string $foreignKey, int $entityId, array $roleMap): void
    {
        $delete = $db->getQuery(true)
            ->delete($db->quoteName($table))
            ->where($db->quoteName($foreignKey) . ' = :entityId')
            ->bind(':entityId', $entityId, ParameterType::INTEGER);

        $db->setQuery($delete)->execute();

        foreach ($roleMap as $role => $organizationIds) {
            $ordering = 1;

            foreach ($organizationIds as $organizationId) {
                $organizationId = (int) $organizationId;

                if ($organizationId <= 0) {
                    continue;
                }

                $insert = $db->getQuery(true)
                    ->insert($db->quoteName($table))
                    ->columns([$db->quoteName($foreignKey), $db->quoteName('organization_id'), $db->quoteName('role'), $db->quoteName('ordering')])
                    ->values(':entityId, :organizationId, :role, :ordering')
                    ->bind(':entityId', $entityId, ParameterType::INTEGER)
                    ->bind(':organizationId', $organizationId, ParameterType::INTEGER)
                    ->bind(':role', $role)
                    ->bind(':ordering', $ordering, ParameterType::INTEGER);

                $db->setQuery($insert)->execute();
                $ordering++;
            }
        }
    }
}

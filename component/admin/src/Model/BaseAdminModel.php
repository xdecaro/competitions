<?php
namespace Xdecaro\Component\Decarodcl\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Xdecaro\Component\Decarodcl\Administrator\Helper\LanguageHelper;
use Xdecaro\Component\Decarodcl\Administrator\Helper\LiveSyncHelper;

abstract class BaseAdminModel extends AdminModel
{
    private const LEGACY_LOCK = '__dcl_unmodified__';

    public function save($data): bool
    {
        $entity = LiveSyncHelper::entityFromModelName($this->getName());
        $id = (int) ($data['id'] ?? 0);
        $isNew = $id <= 0;

        // Every edit form carries the record's `modified` value as rendered.
        // Older/seeded records can legitimately have NULL here, so use a
        // deterministic sentinel until their first save. Remove the field
        // before Joomla binds submitted data: the table owns the new timestamp.
        $expectedModified = trim((string) ($data['modified'] ?? '')) ?: self::LEGACY_LOCK;
        unset($data['modified']);

        if (!$isNew && $entity !== '') {
            $currentModified = LiveSyncHelper::currentModified($this->getDatabase(), $entity, $id);

            if ($currentModified === null || $currentModified !== $expectedModified) {
                LanguageHelper::load();
                $this->setError(Text::_('COM_DECARODCL_ERROR_LIVE_CONFLICT'));

                return false;
            }
        }

        if (!parent::save($data)) {
            return false;
        }

        $savedId = (int) $this->getState($this->getName() . '.id');

        if ($savedId > 0 && $entity !== '') {
            LiveSyncHelper::record($this->getDatabase(), $entity, $savedId, $isNew ? 'create' : 'update');
        }

        return true;
    }

    public function publish(&$pks, $value = 1): bool
    {
        $ids = array_values(array_filter(array_map('intval', (array) $pks)));

        if (!parent::publish($pks, $value)) {
            return false;
        }

        $entity = LiveSyncHelper::entityFromModelName($this->getName());

        if ($entity !== '') {
            foreach ($ids as $id) {
                LiveSyncHelper::record($this->getDatabase(), $entity, $id, 'state');
            }
        }

        return true;
    }

    public function delete(&$pks): bool
    {
        $ids = array_values(array_filter(array_map('intval', (array) $pks)));

        if (!parent::delete($pks)) {
            return false;
        }

        $entity = LiveSyncHelper::entityFromModelName($this->getName());

        if ($entity !== '') {
            foreach ($ids as $id) {
                LiveSyncHelper::record($this->getDatabase(), $entity, $id, 'delete');
            }
        }

        return true;
    }
}

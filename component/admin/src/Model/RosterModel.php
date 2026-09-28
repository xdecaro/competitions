<?php
namespace xdecaro\Component\Competitions\Administrator\Model;

defined('_JEXEC') or die;

use InvalidArgumentException;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use RuntimeException;
use Throwable;
use xdecaro\Component\Competitions\Administrator\Helper\LiveSyncHelper;

final class RosterModel extends BaseAdminModel
{
    public function getTable($type = 'Roster', $prefix = 'Administrator', $config = []): Table
    {
        return parent::getTable($type, $prefix, $config);
    }

    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm(
            'com_xdecarocompetitions.roster',
            'roster',
            ['control' => 'jform', 'load_data' => $loadData]
        );
    }

    public function setWorkflowStatus(&$pks, string $status): bool
    {
        $status = strtolower(trim($status));

        if (!in_array($status, ['pending', 'submitted', 'approved', 'rejected'], true)) {
            throw new InvalidArgumentException('Invalid roster workflow status: ' . $status);
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', (array) $pks))));

        if (!$ids) {
            $this->setError(Text::_('COM_XDECAROCOMPETITIONS_ERROR_NO_ROSTERS_SELECTED'));
            return false;
        }

        $db = $this->getDatabase();
        $started = false;

        try {
            $db->transactionStart();
            $started = true;

            foreach ($ids as $id) {
                $table = $this->getTable();

                if (!$table->load($id)) {
                    throw new RuntimeException('Roster entry not found: ' . $id);
                }

                $table->status = $status;

                if (!$table->check() || !$table->store()) {
                    throw new RuntimeException((string) $table->getError());
                }
            }

            $db->transactionCommit();
            $started = false;

            foreach ($ids as $id) {
                LiveSyncHelper::record($db, 'roster', $id, 'update');
            }

            $pks = $ids;
            return true;
        } catch (Throwable $e) {
            if ($started) {
                try {
                    $db->transactionRollback();
                } catch (Throwable) {
                }
            }

            $this->setError($e->getMessage());
            return false;
        }
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_xdecarocompetitions.edit.roster.data', []);

        if (!$data) {
            $data = $this->getItem();
        }

        return $data;
    }

    protected function preprocessForm(Form $form, $data, $group = 'content'): void
    {
        parent::preprocessForm($form, $data, $group);

        if (!Factory::getApplication()->getIdentity()->authorise('core.edit.state', 'com_xdecarocompetitions')) {
            $form->setFieldAttribute('state', 'disabled', 'true');
            $form->setFieldAttribute('state', 'readonly', 'true');
            $form->setFieldAttribute('status', 'disabled', 'true');
            $form->setFieldAttribute('status', 'readonly', 'true');
        }
    }

    protected function prepareTable($table): void
    {
        if (!$table->id && (int) $table->ordering === 0) {
            $table->ordering = $table->getNextOrder();
        }
    }
}

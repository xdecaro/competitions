<?php
namespace Xdecaro\Component\Decarodcl\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;

final class FederationModel extends AdminModel
{
    public function getTable($type = 'Federation', $prefix = 'Administrator', $config = []): Table
    {
        return parent::getTable($type, $prefix, $config);
    }

    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm('com_decarodcl.federation', 'federation', ['control' => 'jform', 'load_data' => $loadData]);
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_decarodcl.edit.federation.data', []);

        if (!$data) {
            $data = $this->getItem();
        }

        return $data;
    }

    protected function prepareTable($table): void
    {
        if (!$table->id && (int) $table->ordering === 0) {
            $table->ordering = $table->getNextOrder();
        }
    }
}

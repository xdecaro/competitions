<?php
namespace Xdecaro\Component\Decarodcl\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;

final class OrganizationModel extends AdminModel
{
    public function getTable($type = 'Organization', $prefix = 'Administrator', $config = []): Table
    {
        return parent::getTable($type, $prefix, $config);
    }

    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm('com_decarodcl.organization', 'organization', ['control' => 'jform', 'load_data' => $loadData]);
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_decarodcl.edit.organization.data', []);
        if (!$data) {
            $data = $this->getItem();
        }
        return $data;
    }

    protected function preprocessForm(Form $form, $data, $group = 'content'): void
    {
        parent::preprocessForm($form, $data, $group);
        if (!Factory::getApplication()->getIdentity()->authorise('core.edit.state', 'com_decarodcl')) {
            $form->setFieldAttribute('state', 'disabled', 'true');
            $form->setFieldAttribute('state', 'readonly', 'true');
        }
    }

    protected function prepareTable($table): void
    {
        if (!$table->id && (int) $table->ordering === 0) {
            $table->ordering = $table->getNextOrder();
        }
    }
}

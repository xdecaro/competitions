<?php
namespace xdecaro\Component\Competitions\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\ParameterType;
use RuntimeException;
use Throwable;

final class PlayerModel extends BaseAdminModel
{
    public function getTable($type = 'Player', $prefix = 'Administrator', $config = []): Table
    {
        return parent::getTable($type, $prefix, $config);
    }

    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm(
            'com_xdecarocompetitions.player',
            'player',
            ['control' => 'jform', 'load_data' => $loadData]
        );
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_xdecarocompetitions.edit.player.data', []);

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
            $form->setFieldAttribute('approval_status', 'disabled', 'true');
            $form->setFieldAttribute('approval_status', 'readonly', 'true');
        }
    }

    protected function prepareTable($table): void
    {
        $uuid = strtolower(trim((string) ($table->person_uuid ?? '')));
        if ($uuid === '') {
            return;
        }

        $mustResolve = empty($table->id);

        if (!$mustResolve) {
            $id = (int) $table->id;
            $query = $this->getDatabase()->getQuery(true)
                ->select($this->getDatabase()->quoteName('person_uuid'))
                ->from($this->getDatabase()->quoteName('#__xdecarocompetitions_players'))
                ->where($this->getDatabase()->quoteName('id') . ' = :id')
                ->bind(':id', $id, ParameterType::INTEGER);
            $currentUuid = strtolower(trim((string) $this->getDatabase()->setQuery($query, 0, 1)->loadResult()));
            $mustResolve = $currentUuid !== $uuid;
        }

        try {
            $component = Factory::getApplication()->bootComponent('com_xdecarocompetitions');
            if (!is_object($component) || !method_exists($component, 'getPeopleIntegrationService')) {
                throw new RuntimeException(Text::_('COM_XDECAROCOMPETITIONS_ERROR_PEOPLE_UNAVAILABLE'));
            }

            $person = $component->getPeopleIntegrationService()->getPerson($uuid, false);
            if (!$person) {
                if ($mustResolve) {
                    throw new RuntimeException(Text::_('COM_XDECAROCOMPETITIONS_ERROR_PLAYER_PERSON_INVALID'));
                }
                return;
            }

            $firstName = trim((string) ($person['first_name'] ?? ''));
            $lastName = trim((string) ($person['last_name'] ?? ''));
            if ($firstName === '' || $lastName === '') {
                if ($mustResolve) {
                    throw new RuntimeException(Text::_('COM_XDECAROCOMPETITIONS_ERROR_PLAYER_PERSON_INVALID'));
                }
                return;
            }

            $table->person_uuid = $uuid;
            $table->first_name = $firstName;
            $table->last_name = $lastName;
        } catch (Throwable $e) {
            if ($mustResolve) {
                if ($e instanceof RuntimeException && $e->getMessage() !== '') {
                    throw $e;
                }
                throw new RuntimeException(Text::_('COM_XDECAROCOMPETITIONS_ERROR_PEOPLE_UNAVAILABLE'), 0, $e);
            }
            // Existing linked players remain editable when People is temporarily unavailable.
            // Their stored identity snapshot is preserved until People can be queried again.
        }
    }
}

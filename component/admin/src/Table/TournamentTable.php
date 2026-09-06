<?php
namespace Xdecaro\Component\Decarodcl\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\ParameterType;

final class TournamentTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__dcl_tournaments', 'id', $db);
    }

    public function check(): bool
    {
        $this->name = trim((string) $this->name);
        $this->code = strtoupper(trim((string) $this->code));
        $this->discipline = trim((string) $this->discipline) ?: null;
        $this->gender = trim((string) $this->gender) ?: null;

        if ($this->name === '') {
            $this->setError(Text::_('COM_DECARODCL_ERROR_TOURNAMENT_NAME_REQUIRED'));
            return false;
        }

        if (!preg_match('/^[A-Z0-9_-]{2,50}$/', $this->code)) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_TOURNAMENT_CODE_INVALID'));
            return false;
        }

        if ($this->discipline === null) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_TOURNAMENT_DISCIPLINE_REQUIRED'));
            return false;
        }

        $allowedGenders = [null, 'men', 'women', 'mixed', 'open'];

        if (!in_array($this->gender, $allowedGenders, true)) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_TOURNAMENT_GENDER_INVALID'));
            return false;
        }

        $db = $this->getDbo();
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__dcl_tournaments'))
            ->where($db->quoteName('code') . ' = :code')
            ->where($db->quoteName('id') . ' <> :id')
            ->bind(':code', $this->code)
            ->bind(':id', $this->id, ParameterType::INTEGER);

        if ((int) $db->setQuery($query)->loadResult() > 0) {
            $this->setError(Text::_('COM_DECARODCL_ERROR_TOURNAMENT_CODE_DUPLICATE'));
            return false;
        }

        return parent::check();
    }

    public function store($updateNulls = true): bool
    {
        $now = Factory::getDate()->toSql();
        $userId = (int) Factory::getApplication()->getIdentity()->id;

        if (!$this->id) {
            $this->created = $this->created ?: $now;
            $this->created_by = $this->created_by ?: $userId;
        }

        $this->modified = $now;
        $this->modified_by = $userId;

        return parent::store($updateNulls);
    }
}

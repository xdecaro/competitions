<?php
namespace xdecaro\Component\Competitions\Administrator\View\Match;

defined('_JEXEC') or die;
use Joomla\CMS\Factory;use Joomla\CMS\Language\Text;use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;use Joomla\CMS\Toolbar\ToolbarHelper;use xdecaro\Component\Competitions\Administrator\Helper\UiHelper;
final class HtmlView extends BaseHtmlView
{
    public $form;public $item;public $state;
    public function display($tpl=null): void { $user=Factory::getApplication()->getIdentity();if(!$user->authorise('core.manage','com_xdecarocompetitions'))throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403);UiHelper::loadAssets();$this->form=$this->get('Form');$this->item=$this->get('Item');$this->state=$this->get('State');if(count($errors=$this->get('Errors')))throw new \RuntimeException(implode("\n",$errors));$isNew=empty($this->item->id);ToolbarHelper::title($isNew?Text::_('COM_XDECAROCOMPETITIONS_MATCH_NEW'):Text::_('COM_XDECAROCOMPETITIONS_MATCH_EDIT'),'calendar');if($user->authorise($isNew?'core.create':'core.edit','com_xdecarocompetitions')){ToolbarHelper::apply('match.apply');ToolbarHelper::save('match.save');ToolbarHelper::save2new('match.save2new');}ToolbarHelper::cancel('match.cancel',$isNew?'JTOOLBAR_CANCEL':'JTOOLBAR_CLOSE');$this->addTemplatePath(JPATH_COMPONENT_ADMINISTRATOR.'/tmpl/match');parent::display($tpl); }
}

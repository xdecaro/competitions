<?php
namespace Xdecaro\Component\Decarodcl\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Xdecaro\Component\Decarodcl\Administrator\Helper\PageHeaderHelper;

final class DisplayController extends BaseController
{
    protected $default_view = 'dashboard';

    public function display($cachable = false, $urlparams = [])
    {
        $bufferLevel = ob_get_level();
        ob_start();

        try {
            $result = parent::display($cachable, $urlparams);
            $content = (string) ob_get_clean();
        } catch (\Throwable $e) {
            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }

            throw $e;
        }

        echo PageHeaderHelper::render(
            $this->input->getCmd('view', $this->default_view),
            $this->input->getCmd('layout', 'default'),
            $this->input->getInt('id')
        );
        echo $content;

        return $result;
    }
}

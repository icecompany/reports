<?php
use Joomla\CMS\MVC\Controller\BaseController;

defined('_JEXEC') or die;

class ReportsController extends BaseController
{
    public function execute($task)
    {
        $view = $this->input->getString('view', null);
        if ($view === 'complain_basic_old') {
            parent::addModelPath(JPATH_ADMINISTRATOR . "/components/com_prj/models/");
        }
        parent::execute($task);
    }
}

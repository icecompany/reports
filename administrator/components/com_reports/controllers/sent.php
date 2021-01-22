<?php
use Joomla\CMS\MVC\Controller\AdminController;

defined('_JEXEC') or die;

class ReportsControllerSent extends AdminController
{
    public function save_report(): void
    {
        $model = $this->getModel();
        $model->saveReport();
        jexit();
    }

    public function getModel($name = 'SentInvites', $prefix = 'ReportsModel', $config = array())
    {
        return parent::getModel($name, $prefix, $config);
    }
}

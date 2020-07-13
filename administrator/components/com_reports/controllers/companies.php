<?php
use Joomla\CMS\MVC\Controller\AdminController;

defined('_JEXEC') or die;

class ReportsControllerCompanies extends AdminController
{
    public function save_report(): void
    {
        $model = $this->getModel();
        $model->saveReport();
        jexit();
    }

    public function getModel($name = 'Companies', $prefix = 'ReportsModel', $config = array())
    {
        return parent::getModel($name, $prefix, $config);
    }
}

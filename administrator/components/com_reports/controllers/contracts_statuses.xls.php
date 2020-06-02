<?php
use Joomla\CMS\MVC\Controller\AdminController;

defined('_JEXEC') or die;

class ReportsControllerContracts_statuses extends AdminController
{
    public function execute($task): void
    {
        $model = $this->getModel();
        $model->export();
        jexit();
    }

    public function getModel($name = 'Contracts_statuses', $prefix = 'ReportsModel', $config = array())
    {
        return parent::getModel($name, $prefix, $config);
    }
}

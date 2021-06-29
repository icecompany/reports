<?php
use Joomla\CMS\MVC\Controller\AdminController;

defined('_JEXEC') or die;

class ReportsControllerP1 extends AdminController
{
    public function execute($task): void
    {
        $model = $this->getModel();
        $model->export();
        jexit();
    }

    public function getModel($name = 'P1', $prefix = 'ReportsModel', $config = ['export' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }
}

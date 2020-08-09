<?php
use Joomla\CMS\MVC\Controller\AdminController;

defined('_JEXEC') or die;

class ReportsControllerPrice extends AdminController
{
    public function execute($task): void
    {
        $model = $this->getModel();
        $model->export();
        jexit();
    }

    public function getModel($name = 'Price', $prefix = 'ReportsModel', $config = array())
    {
        return parent::getModel($name, $prefix, $config);
    }
}

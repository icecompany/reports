<?php
use Joomla\CMS\MVC\Controller\AdminController;

defined('_JEXEC') or die;

class ReportsControllerReports extends AdminController
{
    public function getModel($name = 'Report', $prefix = 'ReportsModel', $config = array())
    {
        return parent::getModel($name, $prefix, $config);
    }
}

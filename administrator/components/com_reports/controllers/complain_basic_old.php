<?php
use Joomla\CMS\MVC\Controller\AdminController;

defined('_JEXEC') or die;

class ReportsControllerComplain_basic_old extends AdminController
{
    public function __construct($config = array())
    {
        parent::__construct($config);
    }

    public function getModel($name = 'Complain_basic_old', $prefix = 'ReportsModel', $config = array())
    {
        return parent::getModel($name, $prefix, $config);
    }
}

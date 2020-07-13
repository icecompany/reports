<?php
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\FormController;

class ReportsControllerReport extends FormController {
    public function display($cachable = false, $urlparams = array())
    {
        return parent::display($cachable, $urlparams);
    }
}
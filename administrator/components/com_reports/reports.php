<?php
use Joomla\CMS\MVC\Controller\BaseController;

defined('_JEXEC') or die;

// Access check.
if (!JFactory::getUser()->authorise('core.manage', 'com_reports'))
{
	throw new InvalidArgumentException(JText::sprintf('JERROR_ALERTNOAUTHOR'), 404);
}

// Require the helper
require_once JPATH_ADMINISTRATOR . "/components/com_prj/helpers/prj.php";
require_once JPATH_COMPONENT_ADMINISTRATOR . '/helpers/reports.php';

// Execute the task
$controller = BaseController::getInstance('reports');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();

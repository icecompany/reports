<?php
/**
 * @package    cron
 *
 * @author     Антон <your@email.com>
 * @copyright  A copyright
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       http://your.url.com
 */

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

defined('_JEXEC') or die;
JFactory::getLanguage()->load('com_reports', JPATH_ADMINISTRATOR . "/components/com_reports", 'ru-RU', true);
JFactory::getLanguage()->load('com_mkv', JPATH_ADMINISTRATOR . "/components/com_mkv", 'ru-RU', true);
JFactory::getLanguage()->load('com_companies', JPATH_ADMINISTRATOR . "/components/com_companies", 'ru-RU', true);
JFactory::getLanguage()->load('com_contracts', JPATH_ADMINISTRATOR . "/components/com_contracts", 'ru-RU', true);
require_once JPATH_ADMINISTRATOR . "/components/com_prj/helpers/prj.php";
require_once JPATH_ADMINISTRATOR . "/components/com_mkv/helpers/mkv.php";
require_once JPATH_ADMINISTRATOR . "/components/com_contracts/helpers/contracts.php";
require_once JPATH_ADMINISTRATOR . "/components/com_companies/helpers/companies.php";
require_once JPATH_COMPONENT_ADMINISTRATOR . '/helpers/reports.php';
require_once JPATH_ADMINISTRATOR . '/components/com_companies/passwd.php';

$controller = BaseController::getInstance('reports');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();

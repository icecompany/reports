<?php
use Joomla\CMS\Table\Table;

defined('_JEXEC') or die;

class TableReportsReports extends Table
{
	var $id = null;
	var $managerID = null;
	var $title = null;
	var $type = null;
	var $type_show = null;
	var $params = null;
	var $day_1 = null;
	var $day_2 = null;
	var $day_3 = null;
	var $day_4 = null;
	var $day_5 = null;
	var $day_6 = null;
	var $day_7 = null;
	var $cron_hour = null;
	var $cron_minute = null;
	var $cron_enabled = null;

	public function __construct(JDatabaseDriver $db)
	{
		parent::__construct('#__mkv_reports', 'id', $db);
	}
}
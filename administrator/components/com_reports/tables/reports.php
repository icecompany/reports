<?php
use Joomla\CMS\Table\Table;

defined('_JEXEC') or die;

class TableReportsReports extends Table
{
	var $id = null;
	var $managerID = null;
	var $title = null;
	var $type = null;
	var $params = null;

	public function __construct(JDatabaseDriver $db)
	{
		parent::__construct('#__mkv_reports', 'id', $db);
	}
}
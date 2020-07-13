<?php
defined('_JEXEC') or die;

$app = JFactory::getApplication('administrator');
jimport('joomla.user.helper');
$options = array('remember' => true);
if (JFactory::getApplication()->login(['username' => 'admin', 'password' => '44dupyx579'])) {
    JTable::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . "/tables");
    JModelLegacy::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . "/models", "ReportsModel");
    $model = JModelLegacy::getInstance("Reports", "ReportsModel", ['cron' => true]);
    $items = $model->getItems();
    if (empty($items['items'])) die();
    exit(var_dump(ReportsHelper::getConfig('username')));
    die();
} else {
    echo "error";
}

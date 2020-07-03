<?php
defined('_JEXEC') or die;

$app = JFactory::getApplication('administrator');
jimport('joomla.user.helper');
$options = array('remember' => true);
if (JFactory::getApplication()->login(['username' => '', 'password' => ''])) {
    JModelLegacy::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . "/models", "ReportsModel");
    $model = JModelLegacy::getInstance("Close_day_quotes", "ReportsModel");
    $model->export();
    echo 'Вы успешно авторизированны';
    die();
} else {
    echo "error";
}

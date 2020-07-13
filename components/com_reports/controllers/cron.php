<?php
use Joomla\CMS\MVC\Controller\BaseController;

defined('_JEXEC') or die;

class ReportsControllerCron extends BaseController
{
    public function execute($task = null)
    {
        if (!$this->auth()) die("Bad username or password in component's settings");
        $reports = $this->getReports();
        foreach ($reports as $report) {
            $name = ucfirst($report['type']);
            if ($name === 'Companies') {
                $model = $this->getModel($name, 'ReportsModel', ['cron' => true]);
                $params = json_decode($report['params'], true);
                if ($params['manager'] !== null) $model->setState('filter.manager', $params['manager']);
                if ($params['item'] !== null && !empty($params['item'])) $model->setState('filter.item', $params['item']);
                if ($params['status'] !== null && !empty($params['status'])) $model->setState('filter.status', $params['status']);
                if ($params['fields'] !== null && !empty($params['fields'])) $model->setState('filter.fields', $params['fields']);
                $model->export($report['managerID'], $report['title']);
            }
        }
        die('Success');
    }

    private function getReports(): array {
        JTable::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . "/tables");
        JModelLegacy::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . "/models", "ReportsModel");
        $model = JModelLegacy::getInstance("Reports", "ReportsModel", ['cron' => true]);
        $items = $model->getItems();
        if (empty($items['items'])) die(JDate::getInstance('+3 hour')->toSql() . ' Not reports in plan at current time');
        return $items['items'];
    }

    private function auth(): bool
    {
        $username = ReportsHelper::getConfig('cron_username', null);
        $password = ReportsHelper::getConfig('cron_password', null);
        if ($username === null || $password === null) die("Not set username and password in component's settings");
        jimport('joomla.user.helper');
        return JFactory::getApplication('administrator')->login(['username' => $username, 'password' => $password], ['remember' => true]);
    }
}

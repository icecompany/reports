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
            $model = $this->getModel($name, 'ReportsModel', ['cron' => true]);
            $params = json_decode($report['params'], true);
            if ($name === 'Companies') {
                if ($params['manager'] !== null) $model->setState('filter.manager', $params['manager']);
                if ($params['item'] !== null && !empty($params['item'])) $model->setState('filter.item', $params['item']);
                if ($params['status'] !== null && !empty($params['status'])) $model->setState('filter.status', $params['status']);
                if ($params['fields'] !== null && !empty($params['fields'])) $model->setState('filter.fields', $params['fields']);
            }
            if ($name === 'entinvites') {
                $model->setState('filter.date_1', JDate::getInstance("-1 {$params['cron_interval']}")->format("Y-m-d"));
                $model->setState('filter.date_2', JDate::getInstance("+3 hour")->format("Y-m-d"));
            }
            $model->export($report['managerID'], $report['title']);
            $notify = [];
            $notify['managerID'] = $report['managerID'];
            $notify['contractID'] = NULL;
            $notify['text'] = JText::sprintf('COM_REPORTS_MSG_REPORT_HAS_SENT', $report['title'], JFactory::getUser($report['managerID'])->email);
            SchedulerHelper::sendNotify($notify);
        }

        die(date('Y-m-d H:i:s') . ': Success');
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

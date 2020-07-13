<?php
use Joomla\CMS\MVC\Model\ListModel;

defined('_JEXEC') or die;

class ReportsModelReports extends ListModel
{
    public function __construct($config = array())
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'r.id',
                'r.title',
                'manager',
                'search',
            );
        }
        $this->cron = $config['cron'] ?? false;
        parent::__construct($config);
    }

    protected function _getListQuery()
    {
        $query = $this->_db->getQuery(true);

        /* Сортировка */
        $orderCol = $this->state->get('list.ordering');
        $orderDirn = $this->state->get('list.direction');

        //Ограничение длины списка
        $limit = 0;

        $query
            ->select("r.*")
            ->select("u.name as manager")
            ->from("#__mkv_reports r")
            ->leftJoin("#__users u on u.id = r.managerID");

        if ($this->cron) {
            $query->where("r.cron_enabled = 1");
            $dat = JDate::getInstance('+3 hour');
            $query->where("r.cron_hour = {$this->_db->q($dat->hour)}");
            $query->where("r.cron_minute = {$this->_db->q($dat->minute)}");
        }

        if (!ReportsHelper::canDo('core.cron.all') && !$this->cron) {
            $userID = JFactory::getUser()->id;
            $query->where("r.managerID = {$this->_db->q($userID)}");
        }

        $search = $this->getState('filter.search');
        if (!empty($search)) {
            if (stripos($search, 'id:') !== false) { //Поиск по ID
                $id = explode(':', $search);
                $id = $id[1];
                if (is_numeric($id)) {
                    $query->where("r.id = {$this->_db->q($id)}");
                }
            }
            else {
                $text = $this->_db->q("%{$search}%");
                $query->where("(r.title like {$text})");
            }
        }

        $query->order($this->_db->escape($orderCol . ' ' . $orderDirn));
        $this->setState('list.limit', $limit);

        return $query;
    }

    public function getItems()
    {
        $items = parent::getItems();
        $result = ['items' => []];
        $return = ReportsHelper::getReturnUrl();
        foreach ($items as $item) {
            $arr = [];
            $arr['id'] = $item->id;
            $arr['title'] = $item->title;
            $arr['manager'] = $item->manager;
            $arr['type_show'] = $item->type_show;
            $arr['type'] = $item->type;
            $arr['params'] = $item->params;
            $arr['managerID'] = $item->managerID;
            $url = JRoute::_("index.php?option={$this->option}&amp;task=report.edit&amp;id={$item->id}&amp;return={$return}");
            $arr['edit_link'] = JHtml::link($url, $item->title);
            $params = json_decode($item->params);
            $tmp = ["option" => $this->option, "view" => $item->type, 'reportID' => $item->id];
            foreach ($params as $field => $val) {
                if ($val === null) continue;
                $tmp["filter_{$field}"] = $val;
            }
            $query = http_build_query($tmp);
            $url = JRoute::_("index.php?" . $query);
            $arr['report_link'] = JHtml::link($url, $item->title);
            $result['items'][] = $arr;
        }
        return $result;
    }

    protected function populateState($ordering = 'r.title', $direction = 'asc')
    {
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);
        parent::populateState($ordering, $direction);
        PrjHelper::check_refresh();
    }

    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search');
        return parent::getStoreId($id);
    }

    private $cron;
}

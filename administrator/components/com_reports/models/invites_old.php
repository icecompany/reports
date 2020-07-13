<?php
use Joomla\CMS\MVC\Model\ListModel;

defined('_JEXEC') or die;

/**
 * Сравнение проданных элементов по разным проектам
 *
 * @package   cron
 * @since     1.0.0
 */
class ReportsModelInvites_old extends ListModel
{
    public function __construct($config = array())
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'manager, company',
                'manager',
                'search',
            );
        }
        parent::__construct($config);
    }

    protected function _getListQuery()
    {
        $query = $this->_db->getQuery(true);
        $date = JDate::getInstance('2020-03-23')->toSql();
        $query
            ->select("distinct ifnull(e.title_ru_short, ifnull(e.title_ru_full, e.title_en)) as company")
            ->select("c.id as contractID")
            ->select("u.name as manager")
            ->from("#__prj_contracts c")
            ->leftJoin("#__prj_exp e on e.id = c.expID")
            ->leftJoin("#__prj_user_action_log ual on ual.itemID = c.id")
            ->leftJoin("#__users u on u.id = c.managerID")
            ->where("c.prjID = 11 and c.status not in (-1, 7, 8) and ual.action like 'add' and ual.section like 'contract' and ual.dat > '2020-03-23' and c.invite_date is null");
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            $text = $this->_db->q("%{$search}%");
            $query->where("(e.title_ru_short like {$text}) or e.title_ru_full like {$text} or e.title_en like {$text}");
        }
        $manager = $this->getState('filter.manager');
        if (is_numeric($manager)) {
            $query
                ->where("c.managerID = {$this->_db->q($manager)}");
        }
        $limit = 0;
        $this->setState('list.limit', $limit);
        /* Сортировка */
        $orderCol = 'manager, company';
        $orderDirn = 'ASC';
        $query->order($this->_db->escape($orderCol . ' ' . $orderDirn));

        return $query;
    }

    public function getItems()
    {
        $result = array();
        $items = parent::getItems();
        $return = PrjHelper::getReturnUrl();
        foreach ($items as $item) {
            $arr = [];
            $url = JRoute::_("index.php?option=com_projects&amp;task=contract.edit&amp;id={$item->contractID}&amp;return={$return}");
            $arr['edit_link'] = JHtml::link($url, $item->company);
            $arr['manager'] = $item->manager;
            $result[] = $arr;
        }

        return $result;
    }

    /* Сортировка по умолчанию */
    protected function populateState($ordering = 'manager, company', $direction = 'asc')
    {
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);
        $manager = $this->getUserStateFromRequest($this->context . '.filter.manager', 'filter_manager', '', 'string');
        $this->setState('filter.manager', $manager);
        ReportsHelper::check_refresh();
    }

    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.manager');
        return parent::getStoreId($id);
    }
}

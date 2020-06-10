<?php
use Joomla\CMS\MVC\Model\ListModel;

defined('_JEXEC') or die;

/**
 * Сравнение проданных элементов по разным проектам
 *
 * @package   reports
 * @since     1.0.0
 */
class ReportsModelInvites extends ListModel
{
    public function __construct($config = array())
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'manager, company',
                'search',
                'si.invite_date',
            );
        }
        parent::__construct($config);
    }

    protected function _getListQuery()
    {
        $query = $this->_db->getQuery(true);
        $date = JDate::getInstance('2020-03-23')->toSql();
        $query
            ->select("distinct e.title as company")
            ->select("e.director_name, e.director_post, e.email, e.phone_1, e.phone_1_additional")
            ->select("c.id as contractID, s.title as status")
            ->select("si.invite_date, si.invite_outgoing_number, si.invite_incoming_number")
            ->select("u.name as manager")
            ->from("#__mkv_contracts c")
            ->leftJoin("#__mkv_contract_sent_info si on si.contractID = c.id")
            ->leftJoin("#__mkv_contract_statuses s on s.code = c.status")
            ->leftJoin("#__mkv_companies e on e.id = c.companyID")
            ->leftJoin("#__prj_user_action_log ual on ual.itemID = c.id")
            ->leftJoin("#__users u on u.id = c.managerID")
            ->where("c.projectID = 11 and c.status not in (-1, 7, 8) and ual.action like 'add' and ual.section like 'contract' and ual.dat > '2020-03-23'");

        $search = $this->getState('filter.search');
        if (!empty($search)) {
            $text = $this->_db->q("%{$search}%");
            $query->where("(company like {$text})");
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
            $arr['company'] = $item->company;
            $arr['status'] = $item->status;
            $arr['director_name'] = $item->director_name;
            $arr['director_post'] = $item->director_post;
            $arr['email'] = $item->email;
            $arr['phone_1'] = $item->phone_1;
            $arr['phone_1_additional'] = $item->phone_1_additional;
            $arr['invite_date'] = (!empty($item->invite_date)) ? JDate::getInstance($item->invite_date)->format("d.m.Y") : '';
            $arr['invite_outgoing_number'] = $item->invite_outgoing_number;
            $arr['invite_incoming_number'] = $item->invite_incoming_number;
            $result[] = $arr;
        }

        return $result;
    }

    /* Сортировка по умолчанию */
    protected function populateState($ordering = 'manager, company', $direction = 'asc')
    {
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);
        ReportsHelper::check_refresh();
    }

    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search');
        return parent::getStoreId($id);
    }
}

<?php
use Joomla\CMS\MVC\Model\ListModel;

defined('_JEXEC') or die;

/**
 * Сравнение проданных элементов по разным проектам
 *
 * @package   reports
 * @since     1.0.0
 */
class ReportsModelComplain_basic_old extends ListModel
{
    public function __construct($config = array())
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'manager',
                'projects',
                'date',
            );
        }
        parent::__construct($config);
    }

    protected function _getListQuery()
    {
        $query = $this->_db->getQuery(true);
        $date = JDate::getInstance($this->getState('filter.date'));
        $query
            ->select("c.prjID as projectID")
            ->from("#__prj_user_action_log ual")
            ->leftJoin("#__prj_contracts c on c.id = ual.itemID")
            ->leftJoin("#__prj_projects p on p.id = c.prjID")
            ->where("ual.section like 'contract' and ual.action like 'add'")
            ->where("ual.dat <= if(p.date_end < {$this->_db->q($date->toSql())}, date_add({$this->_db->q($date->toSql())}, interval - 1 year), {$this->_db->q($date)})")
            ->group("c.prjID");

        $projects = $this->getState('filter.projects');
        if (!empty($projects) && is_array($projects)) {
            $prjs = implode(", ", $projects);
            $query->andWhere("c.prjID in ({$prjs})");
        }
        $manager = $this->getState('filter.manager');
        if (is_numeric($manager)) {
            $query
                ->where("c.managerID = {$this->_db->q($manager)}")
                ->select("count(ual.id) as contracts");
        }
        else {
            $query->select("count(ual.id) + if(c.prjID = 5, 2042, 0) as contracts");
        }

        return $query;
    }

    public function getItems()
    {
        $result = array();
        $items = parent::getItems();
        $filter_projects = $this->state->get('filter.projects');
        $filter_date = $this->state->get('filter.date');
        $pm = parent::getInstance('Projects', 'PrjModel', array('ids' => $filter_projects));
        $projects = $pm->getItems();
        $dogovors = $this->getContracts(JDate::getInstance($filter_date)->format("Y-m-d"), $filter_projects);
        $amounts = $this->getContractsAmounts(JDate::getInstance($filter_date)->format("Y-m-d"), $filter_projects);
        foreach ($items as $item) {
            if (!isset($result['items'][$item->projectID])) $result['items'][$item->projectID] = array();
            $result['items'][$item->projectID]['contracts'] = $item->contracts;
            $result['items'][$item->projectID]['dogovors'] = $dogovors[$item->projectID];
            $result['items'][$item->projectID]['amounts'] = $amounts[$item->projectID];
        }
        $result['projects'] = $projects['items'];

        return $result;
    }

    private function getContracts(string $date, array $projects): array
    {
        $date = JDate::getInstance($date);
        $query = $this->_db->getQuery(true);
        $projects = implode(", ", $projects);
        $query
            ->select("c.prjID, count(c.id) as cnt")
            ->from("#__prj_contracts c")
            ->leftJoin("#__prj_projects p on p.id = c.prjID")
            ->where("c.dat <= if(p.date_end < {$this->_db->q($date->toSql())}, date_add({$this->_db->q($date->toSql())}, interval -1 year), {$this->_db->q($date)})")
            ->where("c.status = 1 and c.prjID in ({$projects})")
            ->group("c.prjID");
        $manager = $this->state->get('filter.manager');
        if (is_numeric($manager)) {
            $query->where("c.managerID = {$this->_db->q($manager)}");
        }
        $items = $this->_db->setQuery($query)->loadAssocList('prjID');
        $result = array();
        foreach ($items as $projectID => $item) {
            $result[$projectID] = $item['cnt'];
        }
        return $result;
    }

    private function getContractsAmounts(string $date, array $projects): array
    {
        $date = JDate::getInstance($date);
        $query = $this->_db->getQuery(true);
        $projects = implode(", ", $projects);
        $query
            ->select("c.prjID, a.currency, sum(a.price) as amount")
            ->from("#__prj_contracts c")
            ->leftJoin("#__prj_contract_amounts a on a.contractID = c.id")
            ->leftJoin("#__prj_projects p on p.id = c.prjID")
            ->where("c.dat <= if(p.date_end < {$this->_db->q($date->toSql())}, date_add({$this->_db->q($date->toSql())}, interval -1 year), {$this->_db->q($date)})")
            ->where("c.status = 1 and c.prjID in ({$projects})")
            ->group("c.prjID, a.currency");
        $manager = $this->state->get('filter.manager');
        if (is_numeric($manager)) {
            $query->where("c.managerID = {$this->_db->q($manager)}");
        }
        $items = $this->_db->setQuery($query)->loadAssocList();
        $result = array();
        foreach ($items as $item) {
            if (!empty($item['currency'])) $result[$item['prjID']][$item['currency']] = number_format((float) $item['amount'], 2, '.', ' ');
        }
        return $result;
    }

    /* Сортировка по умолчанию */
    protected function populateState($ordering = 'manager', $direction = 'asc')
    {
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);
        $date = $this->getUserStateFromRequest($this->context . '.filter.date', 'filter_date', JDate::getInstance()->format("Y-m-d"), 'string');
        $this->setState('filter.date', $date);
        $manager = $this->getUserStateFromRequest($this->context . '.filter.manager', 'filter_manager', '', 'string');
        $this->setState('filter.manager', $manager);
        $projects = $this->getUserStateFromRequest($this->context . '.filter.projects', 'filter_projects', array(5, 11));
        $this->setState('filter.projects', $projects);
        ReportsHelper::check_refresh();
    }

    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.date');
        $id .= ':' . $this->getState('filter.manager');
        $id .= ':' . $this->getState('filter.projects');
        return parent::getStoreId($id);
    }
}

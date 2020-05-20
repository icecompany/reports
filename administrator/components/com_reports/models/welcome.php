<?php
use Joomla\CMS\MVC\Model\ListModel;

defined('_JEXEC') or die;

/**
 * Сравнение проданных элементов по разным проектам
 *
 * @package   reports
 * @since     1.0.0
 */
class ReportsModelWelcome extends ListModel
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
        $query
            ->select("e.title as company, e.id as companyID")
            ->select("u.name as manager, e.site")
            ->select("ci.itemID, ci.value")
            ->select("i.title as item, i.square_type, i.type")
            ->from("#__mkv_contract_items ci")
            ->leftJoin("#__mkv_price_items i on i.id = ci.itemID")
            ->leftJoin("#__mkv_price_sections ps on ps.id = i.sectionID")
            ->leftJoin("#__mkv_contracts c on c.id = ci.contractID")
            ->leftJoin("#__mkv_companies e on e.id = c.companyID")
            ->leftJoin("#__users u on u.id = c.managerID")
            ->where("(i.square_type is not null or i.type like 'welcome')");
        $project = PrjHelper::getActiveProject();
        $search = $this->setState('filter.search');
        if (!empty($search)) {
            $text = $this->_db->q("%{$search}%");
            $query->where("e.title like {$text}");
        }
        $this->setState('list.limit', 0);
        if (is_numeric($project)) {
            $query->where("c.projectID = {$this->_db->q($project)}");
            $priceID = $this->getPriceID($project);
            if ($priceID > 0) $query->where("ps.priceID = {$this->_db->q($priceID)}");
        }

        return $query;
    }

    public function getItems()
    {
        $result = ['items' => [], 'companies' => [], 'price' => [], 'welcome_manual' => [], 'welcome_automatic' => [], 'calculate' => []];
        $items = parent::getItems();

        foreach ($items as $item) {
            $arr = [];
            $arr['company'] = $item->company;
            $arr['site'] = $item->site;
            $manager = explode(' ', $item->manager);
            $arr['manager'] = $manager[0];
            if (!isset($result['companies'][$item->companyID])) $result['companies'][$item->companyID] = $arr;
            if (!isset($result['price'][$item->itemID])) $result['price'][$item->itemID] = $item->item;
            if (!isset($result['items'][$item->companyID][$item->itemID])) $result['items'][$item->companyID][$item->itemID] = 0;
            $result['items'][$item->companyID][$item->itemID] += $item->value;
            if ($item->type === 'welcome') {
                if (!isset($result['welcome_manual'][$item->companyID])) $result['welcome_manual'][$item->companyID] = 0;
                $result['welcome_manual'][$item->companyID] += $item->value;
            }
            else {
                if (!isset($result['calculate'][$item->companyID])) $result['calculate'][$item->companyID] = 0;
                switch ($item->square_type) {
                    case 1:
                    case 2:
                    case 5:
                    case 7:
                    case 8: {
                        if (!isset($result['welcome_automatic'][$item->companyID]['pavilion'])) $result['welcome_automatic'][$item->companyID]['pavilion'] = 0;
                        $result['welcome_automatic'][$item->companyID]['pavilion'] += $item->value;
                        $result['calculate'][$item->companyID] += $item->value;
                        break;
                    }
                    case 4: {
                        if (!isset($result['welcome_automatic'][$item->companyID]['open_building'])) $result['welcome_automatic'][$item->companyID]['open_building'] = 0;
                        $result['welcome_automatic'][$item->companyID]['open_building'] += $item->value;
                        $result['calculate'][$item->companyID] += $item->value;
                        break;
                    }
                    case 3:
                    case 6: {
                        if (!isset($result['welcome_automatic'][$item->companyID]['open_demo'])) $result['welcome_automatic'][$item->companyID]['open_demo'] = 0;
                        $result['welcome_automatic'][$item->companyID]['open_demo'] += $item->value;
                        $result['calculate'][$item->companyID] += round($item->value / 2);
                        break;
                    }
                }
            }
        }

        return $result;
    }

    private function getPriceID(int $projectID): int
    {
        JTable::addIncludePath(JPATH_ADMINISTRATOR . "/components/com_prj/tables");
        $table = JTable::getInstance('Projects', 'TablePrj');
        $table->load($projectID);
        return $table->priceID ?? 0;
    }

    /* Сортировка по умолчанию */
    protected function populateState($ordering = 'e.title', $direction = 'ASC')
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

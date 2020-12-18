<?php
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\ListModel;

class ReportsModelSquaresByDates extends ListModel
{
    public function __construct(array $config)
    {
        if (empty($config['filter_fields']))
        {
            $config['filter_fields'] = array(
                'c.id',
            );
        }
        parent::__construct($config);
        $this->date_1 = JDate::getInstance($config['date_1'])->format("Y-m-d");
        $this->date_2 = JDate::getInstance($config['date_2'])->format("Y-m-d");
        $this->project = (int) $config['projectID'] ?? 0;
    }

    protected function _getListQuery()
    {
        $query = $this->_db->getQuery(true);
        $query
            ->select("c.managerID, pi.square_type, c.currency")
            ->select("if(c.dat <= {$this->_db->q($this->date_1)}, 'week', 'dynamic') as period")
            ->select("sum(ci.value) as square, sum(ci.amount) as amount")
            ->from("#__mkv_contract_items ci")
            ->leftJoin("#__mkv_contracts c on c.id = ci.contractID")
            ->leftJoin("#__mkv_price_items pi on ci.itemID = pi.id")
            ->where("(pi.type = 'square' and c.dat is not null and c.projectID = {$this->_db->q($this->project)} and c.dat <= {$this->_db->q($this->date_2)})")
            ->group("c.managerID, period, pi.square_type, c.currency, period");

        $this->setState('list.limit', 0);

        return $query;
    }

    public function getItems()
    {
        $items = parent::getItems();
        $result = ['managers' => [], 'total' => [], 'total_by_period' => [], 'total_by_manager' => []];
        foreach ($items as $item)
        {
            $result['managers'][$item->managerID][$item->period][$item->square_type][$item->currency]['square'] = (float) $item->square;
            $result['managers'][$item->managerID][$item->period][$item->square_type][$item->currency]['amount'] = (float) $item->amount;

            $result['total'][$item->currency][$item->square_type][$item->period]['square'] += (float) $item->square;
            $result['total'][$item->currency][$item->square_type][$item->period]['amount'] += (float) $item->amount;
            $result['total_by_period'][$item->currency][$item->period]['square'] += (float) $item->square;
            $result['total_by_period'][$item->currency][$item->period]['amount'] += (float) $item->amount;
            $result['total_by_manager'][$item->managerID][$item->period][$item->currency]['square'] += (float) $item->square;
            $result['total_by_manager'][$item->managerID][$item->period][$item->currency]['amount'] += (float) $item->amount;
        }
        //Динамика по менеджерам
        foreach ($items as $item)
        {
            foreach (['rub', 'usd', 'eur'] as $currency) {
                $result['managers'][$item->managerID]['current'][$item->square_type][$currency]['square'] = $result['managers'][$item->managerID]['week'][$item->square_type][$currency]['square'] + $result['managers'][$item->managerID]['dynamic'][$item->square_type][$currency]['square'];
                $result['managers'][$item->managerID]['current'][$item->square_type][$currency]['amount'] = $result['managers'][$item->managerID]['week'][$item->square_type][$currency]['amount'] + $result['managers'][$item->managerID]['dynamic'][$item->square_type][$currency]['amount'];
                $result['total_by_manager'][$item->managerID]['current'][$currency]['square'] = $result['total_by_manager'][$item->managerID]['week'][$currency]['square'] + $result['total_by_manager'][$item->managerID]['dynamic'][$currency]['square'];
                $result['total_by_manager'][$item->managerID]['current'][$currency]['amount'] = $result['total_by_manager'][$item->managerID]['week'][$currency]['amount'] + $result['total_by_manager'][$item->managerID]['dynamic'][$currency]['amount'];
            }
        }
        //Общая динамика
        foreach ($result['total'] as $currency => $square) {
            foreach ($square as $square_type => $period) {
                $result['total'][$currency][$square_type]['current']['square'] = $result['total'][$currency][$square_type]['week']['square'] + $result['total'][$currency][$square_type]['dynamic']['square'];
                $result['total'][$currency][$square_type]['current']['amount'] = $result['total'][$currency][$square_type]['week']['amount'] + $result['total'][$currency][$square_type]['dynamic']['amount'];
                $result['total_by_period'][$currency]['current']['square'] = $result['total_by_period'][$currency]['week']['square'] + $result['total_by_period'][$currency]['dynamic']['square'];
                $result['total_by_period'][$currency]['current']['amount'] = $result['total_by_period'][$currency]['week']['amount'] + $result['total_by_period'][$currency]['dynamic']['amount'];
            }
        }
        return $result;
    }

    /* Сортировка по умолчанию */
    protected function populateState($ordering = 'c.id', $direction = 'ASC')
    {
        parent::populateState($ordering, $direction);
    }

    protected function getStoreId($id = '')
    {
        return parent::getStoreId($id);
    }

    private $date_1, $date_2, $project;
}
<?php
use Joomla\CMS\MVC\Model\ListModel;

defined('_JEXEC') or die;

class ReportsModelSales extends ListModel
{
    public function __construct($config = array())
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'pi.title',
                'manager',
            );
        }
        parent::__construct($config);
        $input = JFactory::getApplication()->input;
        $format = $input->getString('format', 'html');
        $this->export = ($format !== 'html');
        $this->heads = [
            'item' => 'COM_MKV_HEAD_ITEMS',
            'count' => 'COM_REPORTS_HEAD_COUNT',
            'rub' => 'COM_REPORTS_HEAD_TOTAL_RUB',
            'usd' => 'COM_REPORTS_HEAD_TOTAL_USD',
            'eur' => 'COM_REPORTS_HEAD_TOTAL_EUR',
        ];
    }

    protected function _getListQuery()
    {
        $query = $this->_db->getQuery(true);
        $query
            ->select("ci.itemID, pi.title as price_item, c.currency, sum(ci.value) as cnt, sum(ci.amount) as amount")
            ->from("#__mkv_contract_items ci")
            ->leftJoin("#__mkv_price_items pi on ci.itemID = pi.id")
            ->leftJoin("#__mkv_contracts c on ci.contractID = c.id")
            ->where("(c.status != 0 and ci.value > 0)")
            ->group("ci.itemID, c.currency");

        $projectID = PrjHelper::getActiveProject();
        if (is_numeric($projectID)) {
            $query->where("c.projectID = {$this->_db->q($projectID)}");
        }

        $manager = $this->getState('filter.manager');
        if (is_numeric($manager)) {
            $query->where("c.managerID = {$this->_db->q($manager)}");
        }

        $search = $this->setState('filter.search');
        if (!empty($search)) {
            $text = $this->_db->q("%{$search}%");
            $query->where("pi.title like {$text}");
        }
        $limit = 0;
        $this->setState('list.limit', $limit);

        return $query;
    }

    public function getItems()
    {
        $result = ['items' => [], 'total' => ['rub' => 0, 'usd' => 0, 'eur' => 0]];
        $items = parent::getItems();

        foreach ($items as $item) {
            if (!isset($result['items'][$item->itemID])) {
                $result['items'][$item->itemID] = [];
                $result['items'][$item->itemID]['item'] = $item->price_item;
                $url = JRoute::_("index.php?option=com_contracts&amp;view=items&amp;itemID={$item->itemID}");
                if (!$this->export) $result['items'][$item->itemID]['item'] = JHtml::link($url, $result['items'][$item->itemID]['item'], ['target' => "_blank"]);
                $result['items'][$item->itemID]['rub'] = 0;
                $result['items'][$item->itemID]['usd'] = 0;
                $result['items'][$item->itemID]['eur'] = 0;
                $result['total'][$item->itemID]['count'] = 0;
                $result['total'][$item->itemID]['rub'] = 0;
                $result['total'][$item->itemID]['usd'] = 0;
                $result['total'][$item->itemID]['eur'] = 0;
            }
            $currency = mb_strtoupper($item->currency);
            $amount = number_format((float) $item->amount, 2, '.', ' ');
            $result['items'][$item->itemID]['count'] += $item->cnt;
            $result['items'][$item->itemID][$item->currency] = (!$this->export) ? JText::sprintf("COM_REPORTS_HEAD_COMPLAIN_BASIC_OLD_AMOUNT_{$currency}_SUM", $amount) : $item->amount;
            $result['total'][$item->currency] += $item->amount;
        }
        if (!$this->export) {
            foreach (['rub', 'usd', 'eur'] as $currency) {
                $amount = number_format((float) $result['total'][$currency], 2, '.', ' ');
                $up = mb_strtoupper($currency);
                $result['total'][$currency] = JText::sprintf("COM_REPORTS_HEAD_COMPLAIN_BASIC_OLD_AMOUNT_{$up}_SUM", $amount);
            }
        }
        return $result;
    }

    public function export()
    {
        $items = $this->getItems();
        JLoader::discover('PHPExcel', JPATH_LIBRARIES);
        JLoader::register('PHPExcel', JPATH_LIBRARIES . '/PHPExcel.php');

        $xls = new PHPExcel();
        $xls->setActiveSheetIndex(0);
        $sheet = $xls->getActiveSheet();

        //Ширина столбцов
        $width = ["A" => 140, "B" => 14, "C" => 20, "D" => 20, "E" => 20];
        foreach ($width as $col => $value) $sheet->getColumnDimension($col)->setWidth($value);

        //Заголовки
        $j = 0;
        foreach ($this->heads as $item => $head) $sheet->setCellValueByColumnAndRow($j++, 1, JText::sprintf($head));

        $sheet->setTitle(JText::sprintf('COM_REPORTS_TITLE_SALES'));

        //Данные
        $row = 2; //Строка, с которой начнаются данные
        $col = 0;
        foreach ($items['items'] as $itemID => $item) {
            foreach ($this->heads as $elem => $head) {
                $sheet->setCellValueExplicitByColumnAndRow($col++, $row, $item[$elem], PHPExcel_Cell_DataType::TYPE_STRING);
            }
            $col = 0;
            $row++;
        }
        header("Expires: Mon, 1 Apr 1974 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
        header("Cache-Control: no-cache, must-revalidate");
        header("Pragma: public");
        header("Content-type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=Sales.xls");
        $objWriter = PHPExcel_IOFactory::createWriter($xls, 'Excel5');
        $objWriter->save('php://output');
        jexit();
    }

    /* Сортировка по умолчанию */
    protected function populateState($ordering = 'pi.title', $direction = 'ASC')
    {
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);
        $manager = $this->getUserStateFromRequest($this->context . '.filter.manager', 'filter_manager');
        $this->setState('filter.manager', $manager);
        PrjHelper::check_refresh();
    }

    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.managerID');
        return parent::getStoreId($id);
    }

    private $heads, $export;
}

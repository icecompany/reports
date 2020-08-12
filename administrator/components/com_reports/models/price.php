<?php
use Joomla\CMS\MVC\Model\ListModel;

defined('_JEXEC') or die;

/**
 * Сравнение проданных элементов по разным проектам
 *
 * @package   cron
 * @since     1.0.0
 */
class ReportsModelPrice extends ListModel
{
    public function __construct($config = array())
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'manager',
                'items',
                'status',
                'search',
                'contract_number',
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
            ->select("c.id as contractID, ifnull(c.number_free, c.number) as contract_number")
            ->from("#__mkv_contract_items ci")
            ->leftJoin("#__mkv_price_items i on i.id = ci.itemID")
            ->leftJoin("#__mkv_price_sections ps on ps.id = i.sectionID")
            ->leftJoin("#__mkv_contracts c on c.id = ci.contractID")
            ->leftJoin("#__mkv_companies e on e.id = c.companyID")
            ->leftJoin("#__users u on u.id = c.managerID");
        $items = $this->getState('filter.items');
        $project = PrjHelper::getActiveProject();
        if (is_numeric($project)) {
            $query->where("c.projectID = {$this->_db->q($project)}");
            $priceID = $this->getPriceID($project);
            if ($priceID > 0) $query->where("ps.priceID = {$this->_db->q($priceID)}");
        }
        if (!empty($items) && is_array($items)) {
            $ids = implode(', ', $items);
            if (!empty($ids)) $query->where("ci.itemID in ({$ids})");
        }
        $status = $this->getState('filter.status');
        if (is_array($status) && !empty($status)) {
            $statuses = implode(", ", $status);
            if (in_array(101, $status)) {
                $query->where("(c.status in ({$statuses}) or c.status is null)");
            } else {
                $query->where("c.status in ({$statuses})");
            }
        }

        $manager = $this->getState('filter.manager');
        if (is_numeric($manager)) {
            $query->where("c.managerID = {$this->_db->q($manager)}");
        }
        $search = $this->setState('filter.search');
        if (!empty($search)) {
            $text = $this->_db->q("%{$search}%");
            $query->where("e.title like {$text}");
        }
        $query->order("e.title");
        $this->setState('list.limit', 0);

        return $query;
    }

    public function getItems()
    {
        $result = ['items' => [], 'total' => []];
        $items = parent::getItems();
        $ids = [];

        foreach ($items as $item) {
            if (!in_array($item->contractID, $ids) && $item->contractID != null) $ids[] = $item->contractID;
            if (!isset($result['price'][$item->itemID])) $result['price'][$item->itemID] = $item->item;
            if (!isset($result['items'][$item->companyID])) {
                $result['items'][$item->companyID]['company'] = $item->company;
                $result['items'][$item->companyID]['contract_number'] = $item->contract_number;
                $result['items'][$item->companyID]['manager'] = MkvHelper::getLastAndFirstNames($item->manager);
                $result['items'][$item->companyID]['price'] = [];
            }
            if (!isset($result['items'][$item->companyID]['price'][$item->itemID])) $result['items'][$item->companyID]['price'][$item->itemID] = 0;
            $result['items'][$item->companyID]['price'][$item->itemID] += $item->value;
            if (!isset($result['total'][$item->itemID])) $result['total'][$item->itemID] = 0;
            $result['total'][$item->itemID] += $item->value;
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

        $sheet->getStyle("A2")->getFont()->setBold(true);

        //Ширина столбцов
        $width = ["A" => 19, "B" => 60, "C" => 20];
        foreach ($width as $col => $value) $sheet->getColumnDimension($col)->setWidth($value);

        $sheet->setCellValue("A1", JText::sprintf('COM_MKV_HEAD_CONTRACT_NUMBER'));
        $sheet->setCellValue("B1", JText::sprintf('COM_REPORTS_HEAD_COMPANY'));
        $sheet->setCellValue("C1", JText::sprintf('COM_REPORTS_HEAD_MANAGER'));
        $col = 3;
        foreach ($items['price'] as $id => $title) {
            $sheet->setCellValueByColumnAndRow($col, 1, $title);
            $col++;
        }

        //Итого
        $sheet->mergeCells("A2:C2");
        $sheet->setCellValue("A2", JText::sprintf('COM_REPORTS_HEAD_TOTAL'));
        $sheet->getStyle("A2")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

        $col = 3;
        foreach ($items['price'] as $itemID => $title) {
            $sheet->setCellValueByColumnAndRow($col, 2, $items['total'][$itemID] ?? 0);
            $col++;
        }

        $sheet->setTitle(JText::sprintf('COM_REPORTS_MENU_PRICE'));

        //Данные. Один проход цикла - одна строка
        $row = 3; //Строка, с которой начнаются данные
        $col = 3;
        foreach ($items['items'] as $companyID => $company) {
            $sheet->setCellValue("A{$row}", $company['contract_number']);
            $sheet->setCellValue("B{$row}", $company['company']);
            $sheet->setCellValue("C{$row}", $company['manager']);
            foreach ($items['price'] as $itemID => $title) {
                $sheet->setCellValueByColumnAndRow($col, $row, $items['items'][$companyID]['price'][$itemID] ?? 0);
                $col++;
            }
            $row++;
            $col = 3;
        }
        header("Expires: Mon, 1 Apr 1974 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
        header("Cache-Control: no-cache, must-revalidate");
        header("Pragma: public");
        header("Content-type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=Price.xls");
        $objWriter = PHPExcel_IOFactory::createWriter($xls, 'Excel5');
        $objWriter->save('php://output');
        jexit();
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
        $items = $this->getUserStateFromRequest($this->context . '.filter.items', 'filter_items');
        $this->setState('filter.items', $items);
        $status = $this->getUserStateFromRequest($this->context . '.filter.status', 'filter_status');
        $this->setState('filter.status', $status);
        $manager = $this->getUserStateFromRequest($this->context . '.filter.manager', 'filter_manager');
        $this->setState('filter.manager', $manager);
        ReportsHelper::check_refresh();
    }

    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.items');
        $id .= ':' . $this->getState('filter.status');
        $id .= ':' . $this->getState('filter.manager');
        return parent::getStoreId($id);
    }
}

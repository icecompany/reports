<?php
use Joomla\CMS\MVC\Model\ListModel;

defined('_JEXEC') or die;

class ReportsModelP2 extends ListModel
{
    public function __construct($config = array())
    {
        $this->heads = [
            'title' => [
                'text' => 'COM_REPORTS_HEAD_COMPANY',
                'column' => 'e.title',
                'type' => 'company',
                'itemID' => 'companyID1',
            ],
            'form' => [
                'text' => 'COM_REPORTS_HEAD_FORM_INVOLVEMENT',
                'column' => 'form',
            ],
            'coexp' => [
                'text' => 'COM_REPORTS_HEAD_COEXP',
                'column' => 'e1.title',
                'type' => 'company',
                'itemID' => 'companyID2',
            ],
            'stand' => [
                'text' => 'COM_MKV_HEAD_STANDS',
            ],
        ];

        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = MkvHelper::getSortedFields($this->heads, ['search']);
        }
        $this->export = $config['export'] ?? false;

        parent::__construct($config);
    }

    protected function _getListQuery()
    {
        $query = $this->_db->getQuery(true);
        $query
            ->select("e.title, e.id as companyID1")
            ->select("if(cp.companyID is not null, 'COEXP', 'EXP') as form")
            ->select("e1.title as coexp, e1.id as companyID2")
            ->select("group_concat(ifnull(s1.number, s.number) separator ', ') as stand")
            ->from("#__mkv_contract_stands cs")
            ->rightJoin("#__mkv_contracts c on cs.contractID = c.id")
            ->leftJoin("#__mkv_contract_parents cp on c.id = cp.contractID")
            ->leftJoin("#__mkv_stands s on cs.standID = s.id")
            ->leftJoin("#__mkv_contract_stands cs1 on cp.contractStandID = cs1.id")
            ->leftJoin("#__mkv_stands s1 on cs1.standID = s1.id")
            ->leftJoin("#__mkv_companies e on c.companyID = e.id")
            ->leftJoin("#__mkv_companies e1 on cp.companyID = e1.id")
            ->where("(cs.id is not null or cp.companyID is not null)")
            ->group("e.title, e.id, form, coexp, e1.id");

        $project = PrjHelper::getActiveProject();
        $search = $this->setState('filter.search');
        if (!empty($search)) {
            $text = $this->_db->q("%{$search}%");
            $query->where("(e.title like {$text} or e1.title like {$text})");
        }

        $this->setState('list.limit', (!$this->export) ? $this->getState('list.limit') : 0);

        if (is_numeric($project)) {
            $query->where("c.projectID = {$this->_db->q($project)}");
        }

        /* Сортировка */
        $orderCol = $this->state->get('list.ordering', 'e.title');
        $orderDirn = $this->state->get('list.direction', 'ASC');
        $query->order($this->_db->escape($orderCol . ' ' . $orderDirn));

        return $query;
    }

    public function getItems()
    {
        $result = ['items' => []];
        $items = parent::getItems();
        $return = ReportsHelper::getReturnUrl();

        foreach ($items as $item) {
            $arr = [];
            foreach ($this->heads as $field => $params) {
                $itemField = $params['itemID'];
                $arr[$field] = MkvHelper::formatField($params['type'], $item->$field, $item->$itemField, $return, $this->export);
            }
            $arr['form'] = JText::sprintf("COM_REPORTS_HEAD_FORM_INVOLVEMENT_{$item->form}");

            $result['items'][] = $arr;
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

        //Заголовки
        $cell = 1;
        $row = 1;
        $sheet
            ->setCellValueByColumnAndRow(0, $row, "№п/п")
            ->getStyleByColumnAndRow(0, $row)->getFont()->setBold(true);
        foreach ($this->heads as $field => $params) {
            $sheet
                ->setCellValueByColumnAndRow($cell, $row, JText::sprintf($params['text']))
                ->getStyleByColumnAndRow($cell, $row)->getFont()->setBold(true);
            $cell++;
        }
        //Данные
        $cell = 0;
        $row = 2;
        foreach ($items['items'] as $item) {
            $sheet->setCellValueByColumnAndRow($cell, $row, $row - 1);
            $cell++;
            foreach ($this->heads as $field => $params) {
                $sheet->setCellValueByColumnAndRow($cell, $row, $item[$field]);
                $cell++;
            }
            $row++;
            $cell = 0;
        }
        //Фильтр
        $sheet->setAutoFilterByColumnAndRow(1, 1, count($this->heads), count($items['items']) + 1);

        $sheet->setTitle(JText::sprintf('COM_REPORTS_MENU_QUARTER_P2_SHORT'));

        header("Expires: Mon, 1 Apr 1974 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
        header("Cache-Control: no-cache, must-revalidate");
        header("Pragma: public");
        header("Content-type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=p2.xls");
        $objWriter = PHPExcel_IOFactory::createWriter($xls, 'Excel5');
        $objWriter->save('php://output');
        jexit();
    }

    public function getTableHeads(): array
    {
        return $this->heads ?? [];
    }

    public function getTableFooterColspan(): int
    {
        return (!empty($this->heads)) ? (count($this->heads) + 1) : 1;
    }

    /* Сортировка по умолчанию */
    protected function populateState($ordering = 'e.title', $direction = 'ASC')
    {
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);
        parent::populateState($ordering, $direction);
        ReportsHelper::check_refresh();
    }

    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search');
        return parent::getStoreId($id);
    }

    private $export, $heads;
}

<?php
use Joomla\CMS\MVC\Model\ListModel;

defined('_JEXEC') or die;

class ReportsModelP1 extends ListModel
{
    public function __construct($config = array())
    {
        $this->heads = [
            'title' => [
                'text' => 'COM_REPORTS_HEAD_COMPANY',
                'column' => 'e.title',
                'type' => 'company',
                'itemID' => 'companyID',
            ],
            'invite_date' => [
                'text' => 'COM_REPORTS_HEAD_INVITE_DATA',
                'column' => 'si.invite_date',
                'type' => 'date'
            ],
            'invite_outgoing_number' => [
                'text' => 'COM_REPORTS_HEAD_INVITE_SENT_NUMBER',
                'column' => 'si.invite_outgoing_number',
            ],
            'invite_incoming_number' => [
                'text' => 'COM_REPORTS_HEAD_INVITE_INCOMING_NUMBER',
                'column' => 'si.invite_incoming_number',
            ],
            'manager' => [
                'text' => 'COM_REPORTS_HEAD_MANAGER',
                'column' => 'u.name',
                'type' => 'manager',
            ],
            'email' => [
                'text' => 'COM_REPORTS_HEAD_INVITE_EMAIL',
                'type' => 'email',
            ],
            'status' => [
                'text' => 'COM_REPORTS_HEAD_INVITE_RESULT',
                'column' => 'c.status',
                'type' => 'status',
                'itemID' => 'contractID',
            ],
            'fio' => [
                'text' => 'COM_REPORTS_HEAD_INVITE_CONTACT_NAME',
                'type' => 'contact',
                'itemID' => 'contactID',
            ],
            'post' => [
                'text' => 'COM_REPORTS_HEAD_INVITE_CONTACT_POST',
            ],
            'contact_email' => [
                'text' => 'COM_REPORTS_HEAD_INVITE_CONTACT_EMAIL',
                'type' => 'email',
            ],
            'contact_phone' => [
                'text' => 'COM_REPORTS_HEAD_INVITE_CONTACT_PHONE',
            ],
            'contact_additional' => [
                'text' => 'COM_REPORTS_HEAD_INVITE_PHONE_ADDITIONAL',
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
            ->select("e.title, c.companyID, c.id as contractID")
            ->select("si.invite_date, si.invite_outgoing_number, si.invite_incoming_number")
            ->select("u.name as manager")
            ->select("e.email")
            ->select("st.title as status")
            ->select("ec_m.id as contactID, ec_m.fio, ec_m.post")
            ->select("aes_decrypt(ec_m.email, @pass) as contact_email")
            ->select("aes_decrypt(ec_m.phone_work, @pass) as contact_phone")
            ->select("ec_m.phone_work_additional as contact_additional")
            ->from("#__mkv_contracts c")
            ->leftJoin("#__mkv_companies e on c.companyID = e.id")
            ->leftJoin("#__mkv_contract_sent_info si on c.id = si.contractID")
            ->leftJoin("#__mkv_contract_incoming_info ii on c.id = ii.contractID")
            ->leftJoin("#__mkv_companies_contacts ec_m on ec_m.id = (select ec.id from #__mkv_companies_contacts ec where ec.companyID = e.id order by ec.main desc limit 1)")
            ->leftJoin("#__mkv_contract_statuses st on c.status = st.code")
            ->leftJoin("#__users u on u.id = c.managerID")
            ->where("c.status not in (7, 8)");

        $project = PrjHelper::getActiveProject();
        $search = $this->setState('filter.search');
        if (!empty($search)) {
            $text = $this->_db->q("%{$search}%");
            $query->where("e.title like {$text}");
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
            foreach (['invite_outgoing_number', 'invite_incoming_number'] as $field) $arr[$field] = $item->$field ?? JText::sprintf('COM_REPORTS_HEAD_BN');

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

        $sheet->getStyle("A2")->getFont()->setBold(true);
        $sheet->getStyle("G")->getFont()->setBold(true);

        //Ширина столбцов
        $width = ["A" => 60, "B" => 13, "C" => 13, "D" => 25, "E" => 40, "F" => 20, "G" => 20, "H" => 20];
        foreach ($width as $col => $value) $sheet->getColumnDimension($col)->setWidth($value);

        $sheet->setCellValue("A1", JText::sprintf('COM_REPORTS_HEAD_COMPANY'));
        $sheet->setCellValue("B1", JText::sprintf('COM_REPORTS_HEAD_MANAGER'));
        $sheet->setCellValue("C1", JText::sprintf('COM_REPORTS_HEAD_STANDS'));
        $sheet->setCellValue("D1", JText::sprintf('COM_REPORTS_HEAD_SITE'));
        $sheet->setCellValue("E1", JText::sprintf('COM_REPORTS_HEAD_CONTACTS'));
        $sheet->setCellValue("F1", JText::sprintf('COM_REPORTS_HEAD_WELCOME_CALCULATE'));
        $sheet->setCellValue("G1", JText::sprintf('COM_REPORTS_HEAD_WELCOME_PRINT'));
        $sheet->setCellValue("H1", JText::sprintf('COM_REPORTS_HEAD_WELCOME_ELECTRON'));
        $col = 8;
        foreach ($items['price'] as $id => $title) {
            $sheet->setCellValueByColumnAndRow($col, 1, $title);
            $col++;
        }

        //Итого
        $sheet->mergeCells("A2:E2");
        $sheet->setCellValue("A2", JText::sprintf('COM_REPORTS_HEAD_TOTAL'));
        $sheet->setCellValue("F2", $items['total']['calculate'] ?? 0);
        $sheet->setCellValue("G2", $items['total']['print'] ?? 0);
        $sheet->setCellValue("H2", $items['total']['electron'] ?? 0);
        $sheet->getStyle("A2")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

        $col = 8;
        foreach ($items['price'] as $itemID => $title) {
            $sheet->setCellValueByColumnAndRow($col, 2, $items['total']['price'][$itemID] ?? 0);
            $col++;
        }

        $sheet->setTitle(JText::sprintf('COM_REPORTS_MENU_WELCOME'));

        //Данные. Один проход цикла - одна строка
        $row = 3; //Строка, с которой начнаются данные
        $col = 8;
        foreach ($items['items'] as $companyID => $company) {
            $sheet->setCellValue("A{$row}", $company['company']);
            $sheet->setCellValue("B{$row}", $company['manager']);
            $sheet->setCellValueExplicit("C{$row}", $items['stands'][$companyID], PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue("D{$row}", $company['site']);
            $sheet->setCellValue("E{$row}", $items['contacts'][$companyID]);
            $sheet->setCellValue("F{$row}", $company['calculate'] ?? 0);
            $sheet->setCellValue("G{$row}", $company['print'] ?? 0);
            $sheet->setCellValue("H{$row}", $company['electron'] ?? 0);
            foreach ($items['price'] as $itemID => $title) {
                $sheet->setCellValueByColumnAndRow($col, $row, $items['items'][$companyID]['price'][$itemID] ?? 0);
                $col++;
            }
            $row++;
            $col = 8;
        }
        header("Expires: Mon, 1 Apr 1974 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
        header("Cache-Control: no-cache, must-revalidate");
        header("Pragma: public");
        header("Content-type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=p1.xls");
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

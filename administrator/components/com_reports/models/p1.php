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

        $sheet->setTitle(JText::sprintf('COM_REPORTS_MENU_QUARTER_P1_SHORT'));

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

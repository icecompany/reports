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
        $this->heads = [
            'company' => 'COM_REPORTS_HEAD_COMPANY',
            'invite_date' => 'COM_REPORTS_HEAD_INVITE_DATA',
            'invite_outgoing_number' => 'COM_REPORTS_HEAD_INVITE_SENT_NUMBER',
            'invite_incoming_number' => 'COM_REPORTS_HEAD_INVITE_INCOMING_NUMBER',
            'manager' => 'COM_REPORTS_HEAD_MANAGER',
            'email' => 'COM_REPORTS_HEAD_INVITE_EMAIL',
            'status' => 'COM_REPORTS_HEAD_INVITE_RESULT',
            'director_name' => 'COM_REPORTS_HEAD_INVITE_CONTACT_NAME',
            'director_post' => 'COM_REPORTS_HEAD_INVITE_CONTACT_POST',
            'phone_1' => 'COM_REPORTS_HEAD_INVITE_CONTACT_PHONE',
        ];
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
            $arr['manager'] = MkvHelper::getLastAndFirstNames($item->manager);
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

    public function export()
    {
        $items = $this->getItems();
        JLoader::discover('PHPExcel', JPATH_LIBRARIES);
        JLoader::register('PHPExcel', JPATH_LIBRARIES . '/PHPExcel.php');

        $xls = new PHPExcel();
        $xls->setActiveSheetIndex(0);
        $sheet = $xls->getActiveSheet();

        //Ширина столбцов
        $width = ["A" => 60, "B" => 13, "C" => 13, "D" => 13, "E" => 22, "F" => 30, "G" => 28, "H" => 40, "I" => 26, "J" => 20];
        foreach ($width as $col => $value) $sheet->getColumnDimension($col)->setWidth($value);
        //Заголовки
        $j = 0;
        foreach ($this->heads as $item => $head) $sheet->setCellValueByColumnAndRow($j++, 1, JText::sprintf($head));

        $sheet->setTitle(JText::sprintf('COM_REPORTS_MENU_INVITES_EXPORT'));

        //Данные
        $row = 2; //Строка, с которой начнаются данные
        $col = 0;
        foreach ($items as $item) {
            foreach ($this->heads as $elem => $head) {
                $sheet->setCellValueByColumnAndRow($col++, $row, $item[$elem]);
            }
            $col = 0;
            $row++;
        }
        header("Expires: Mon, 1 Apr 1974 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
        header("Cache-Control: no-cache, must-revalidate");
        header("Pragma: public");
        header("Content-type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=Invites.xls");
        $objWriter = PHPExcel_IOFactory::createWriter($xls, 'Excel5');
        $objWriter->save('php://output');
        jexit();
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

    private $heads;
}

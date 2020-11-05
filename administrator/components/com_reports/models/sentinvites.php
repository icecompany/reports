<?php
use Joomla\CMS\MVC\Model\ListModel;

defined('_JEXEC') or die;

/**
 * Сравнение проданных элементов по разным проектам
 *
 * @package   cron
 * @since     1.0.0
 */
class ReportsModelSentInvites extends ListModel
{
    public function __construct($config = array())
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'manager, company',
                'search',
                'date_1', 'date_2',
                'si.invite_date',
            );
        }
        parent::__construct($config);
        $this->heads = [
            'manager' => 'COM_REPORTS_HEAD_MANAGER',
            'company' => 'COM_REPORTS_HEAD_COMPANY',
            'invite_date' => 'COM_REPORTS_HEAD_INVITE_DATA',
            'invite_outgoing_number' => 'COM_REPORTS_HEAD_INVITE_SENT_NUMBER',
            'invite_incoming_number' => 'COM_REPORTS_HEAD_INVITE_INCOMING_NUMBER',
            'email' => 'COM_REPORTS_HEAD_INVITE_EMAIL',
            'status' => 'COM_REPORTS_HEAD_INVITE_RESULT',
            'director_name' => 'COM_REPORTS_HEAD_INVITE_CONTACT_NAME',
            'director_post' => 'COM_REPORTS_HEAD_INVITE_CONTACT_POST',
            'phone_1' => 'COM_REPORTS_HEAD_INVITE_CONTACT_PHONE',
        ];
    }

    protected function _getListQuery()
    {
        $this->_db->setQuery("call s7vi9_mkv_save_managers_stat()")->execute();

        $query = $this->_db->getQuery(true);
        $query
            ->select("u.name as manager")
            ->select("ms.dat, ms.managerID")
            ->select("ms.status_0 + ms.status_1 + ms.status_2 + ms.status_3 + ms.status_4 + ms.status_10 as invites")
            ->select("ms.status_0, ms.status_1, ms.status_2, ms.status_3, ms.status_4, ms.status_10")
            ->from("#__mkv_managers_stat ms")
            ->leftJoin("s7vi9_users u on ms.managerID = u.id");
        $project = PrjHelper::getActiveProject();
        if (is_numeric($project)) {
            $query->where("ms.projectID = {$this->_db->q($project)}");
        }

        $date_1 = $this->getState('filter.date_1');
        $date_2 = $this->getState('filter.date_2');
        if (!empty($date_1) && !empty($date_2) && $date_1 !== '0000-00-00 00:00:00' && $date_2 !== '0000-00-00 00:00:00')
        {
            $d1 = JDate::getInstance($date_1)->format("Y-m-d");
            $d2 = JDate::getInstance($date_2)->format("Y-m-d");
            $query->where("(ms.dat = {$this->_db->q($d1)} or ms.dat = {$this->_db->q($d2)})");
        }

        $search = $this->getState('filter.search');
        if (!empty($search)) {
            $text = $this->_db->q("%{$search}%");
            $query->where("(u.name like {$text})");
        }

        $limit = 0;
        $this->setState('list.limit', $limit);
        /* Сортировка */
        $orderCol = 'u.name';
        $orderDirn = 'ASC';
        $query->order($this->_db->escape($orderCol . ' ' . $orderDirn));

        return $query;
    }

    public function getItems()
    {
        $result = ['items' => [], 'statuses' => [], 'week' => [], 'dynamic' => [], 'managers' => [], 'total' => ['today' => 0, 'week' => 0, 'dynamic_by_statuses' => [], 'dynamic' => 0, 'statuses_week' => [], 'statuses' => []]];
        $items = parent::getItems();
        foreach ($items as $item) {
            $now = JDate::getInstance($this->state->get('filter.date_2'))->format("Y-m-d");

            if (!isset($result['managers'][$item->managerID])) $result['managers'][$item->managerID] = MkvHelper::getLastAndFirstNames($item->manager);
            if (!isset($result['items'][$item->managerID])) $result['items'][$item->managerID] = [];
            $day = ($item->dat !== $now) ? 'week' : 'today';
            $result['items'][$item->managerID][$day] = $item->invites;
            if (isset($result['items'][$item->managerID]['today']) && isset($result['items'][$item->managerID]['week'])) {
                $result['items'][$item->managerID]['dynamic'] = $result['items'][$item->managerID]['today'] - $result['items'][$item->managerID]['week'];
            }
            $result['total'][$day] += $item->invites;
            if ($day !== 'today') {
                $result['week'][$item->managerID][0] = $item->status_0 ?? 0;
                $result['week'][$item->managerID][1] = $item->status_1 ?? 0;
                $result['week'][$item->managerID][2] = $item->status_2 ?? 0;
                $result['week'][$item->managerID][3] = $item->status_3 ?? 0;
                $result['week'][$item->managerID][4] = $item->status_4 ?? 0;
                $result['week'][$item->managerID][10] = $item->status_10 ?? 0;
                if (!isset($result['total']['statuses_week'][0])) $result['total']['statuses_week'][0] = 0;
                if (!isset($result['total']['statuses_week'][1])) $result['total']['statuses_week'][1] = 0;
                if (!isset($result['total']['statuses_week'][2])) $result['total']['statuses_week'][2] = 0;
                if (!isset($result['total']['statuses_week'][3])) $result['total']['statuses_week'][3] = 0;
                if (!isset($result['total']['statuses_week'][4])) $result['total']['statuses_week'][4] = 0;
                if (!isset($result['total']['statuses_week'][10])) $result['total']['statuses_week'][10] = 0;
                $result['total']['statuses_week'][0] += $item->status_0;
                $result['total']['statuses_week'][1] += $item->status_1;
                $result['total']['statuses_week'][2] += $item->status_2;
                $result['total']['statuses_week'][3] += $item->status_3;
                $result['total']['statuses_week'][4] += $item->status_4;
                $result['total']['statuses_week'][10] += $item->status_10;
            }
            else {
                $result['statuses'][$item->managerID][0] = $item->status_0 ?? 0;
                $result['statuses'][$item->managerID][1] = $item->status_1 ?? 0;
                $result['statuses'][$item->managerID][2] = $item->status_2 ?? 0;
                $result['statuses'][$item->managerID][3] = $item->status_3 ?? 0;
                $result['statuses'][$item->managerID][4] = $item->status_4 ?? 0;
                $result['statuses'][$item->managerID][10] = $item->status_10 ?? 0;
                if (!isset($result['total']['statuses'][0])) $result['total']['statuses'][0] = 0;
                if (!isset($result['total']['statuses'][1])) $result['total']['statuses'][1] = 0;
                if (!isset($result['total']['statuses'][2])) $result['total']['statuses'][2] = 0;
                if (!isset($result['total']['statuses'][3])) $result['total']['statuses'][3] = 0;
                if (!isset($result['total']['statuses'][4])) $result['total']['statuses'][4] = 0;
                if (!isset($result['total']['statuses'][10])) $result['total']['statuses'][10] = 0;
                $result['total']['statuses'][0] += $item->status_0;
                $result['total']['statuses'][1] += $item->status_1;
                $result['total']['statuses'][2] += $item->status_2;
                $result['total']['statuses'][3] += $item->status_3;
                $result['total']['statuses'][4] += $item->status_4;
                $result['total']['statuses'][10] += $item->status_10;
                $result['dynamic'][$item->managerID][0] = $item->status_0 - $result['week'][$item->managerID][0];
                $result['dynamic'][$item->managerID][1] = $item->status_1 - $result['week'][$item->managerID][1];
                $result['dynamic'][$item->managerID][2] = $item->status_2 - $result['week'][$item->managerID][2];
                $result['dynamic'][$item->managerID][3] = $item->status_3 - $result['week'][$item->managerID][3];
                $result['dynamic'][$item->managerID][4] = $item->status_4 - $result['week'][$item->managerID][4];
                $result['dynamic'][$item->managerID][10] = $item->status_10 - $result['week'][$item->managerID][10];
            }
        }
        $result['total']['dynamic'] = $result['total']['today'] - $result['total']['week'];
        foreach ([0, 1, 2, 3, 4, 10] as $status) {
            $result['total']['dynamic_by_statuses'][$status] = $result['total']['statuses'][$status] - $result['total']['statuses_week'][$status];
        }
        return $result;
    }

    public function export()
    {
        $items = $this->getItems();
        JLoader::discover('PHPExcel', JPATH_LIBRARIES);
        JLoader::register('PHPExcel', JPATH_LIBRARIES . '/PHPExcel.php');

        $date_1 = JDate::getInstance($this->state->get('filter.date_1'))->format("d.m.Y");
        $date_2 = JDate::getInstance($this->state->get('filter.date_2'))->format("d.m.Y");

        $xls = new PHPExcel();
        $xls->setActiveSheetIndex(0);
        $sheet = $xls->getActiveSheet();

        $statuses = $this->loadContractStatuses();

        //Объединение ячеек
        $merge = ["B1:D1", "E1:G1", "H1:J1", "K1:M1", "N1:P1", "Q1:S1", "T1:V1"];
        foreach ($merge as $cell) $sheet->mergeCells($cell);
        $sheet->getStyle("B1:T1")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        //Ширина столбцов
        $width = ["A" => 20, "B" => 10, "C" => 10, "D" => 10, "E" => 10, "F" => 10, "G" => 10, "H" => 10, "I" => 10, "J" => 10, "K" => 10, "L" => 10, "M" => 10,
            "N" => 10, "O" => 10, "P" => 10, "Q" => 10, "R" => 10, "S" => 10, "T" => 20, "U" => 20, "V" => 10];
        foreach ($width as $col => $value) $sheet->getColumnDimension($col)->setWidth($value);
        //Заголовки
        $sheet->setCellValue("A1", JText::sprintf('COM_REPORTS_HEAD_STATUS'));
        $sheet->setCellValue("A2", JText::sprintf('COM_MKV_HEAD_MANAGER'));
        $sheet->setCellValue("B1", $statuses[0]);
        $sheet->setCellValue("B2", $date_1);
        $sheet->setCellValue("C2", $date_2);
        $sheet->setCellValue("D2", JText::sprintf('COM_REPORTS_HEAD_DYNAMIC'));
        $sheet->setCellValue("E1", $statuses[1]);
        $sheet->setCellValue("E2", $date_1);
        $sheet->setCellValue("F2", $date_2);
        $sheet->setCellValue("G2", JText::sprintf('COM_REPORTS_HEAD_DYNAMIC'));
        $sheet->setCellValue("H1", $statuses[2]);
        $sheet->setCellValue("H2", $date_1);
        $sheet->setCellValue("I2", $date_2);
        $sheet->setCellValue("J2", JText::sprintf('COM_REPORTS_HEAD_DYNAMIC'));
        $sheet->setCellValue("K1", $statuses[3]);
        $sheet->setCellValue("K2", $date_1);
        $sheet->setCellValue("L2", $date_2);
        $sheet->setCellValue("M2", JText::sprintf('COM_REPORTS_HEAD_DYNAMIC'));
        $sheet->setCellValue("N1", $statuses[4]);
        $sheet->setCellValue("N2", $date_1);
        $sheet->setCellValue("O2", $date_2);
        $sheet->setCellValue("P2", JText::sprintf('COM_REPORTS_HEAD_DYNAMIC'));
        $sheet->setCellValue("Q1", $statuses[10]);
        $sheet->setCellValue("Q2", $date_1);
        $sheet->setCellValue("R2", $date_2);
        $sheet->setCellValue("S2", JText::sprintf('COM_REPORTS_HEAD_DYNAMIC'));
        $sheet->setCellValue("T1", JText::sprintf('COM_REPORTS_HEAD_TOTAL'));
        $sheet->setCellValue("T2", JText::sprintf('COM_REPORTS_HEAD_SENT_OLD_WEEK'));
        $sheet->setCellValue("U2", JText::sprintf('COM_REPORTS_HEAD_SENT_CURRENT_WEEK'));
        $sheet->setCellValue("V2", JText::sprintf('COM_REPORTS_HEAD_DYNAMIC'));

        $sheet->setTitle(JText::sprintf('COM_REPORTS_MENU_SENT_INVITES'));

        //Данные
        $row = 3; //Строка, с которой начнаются данные
        foreach ($items['items'] as $managerID => $item) {
            if ((int) $item['today'] === 0 && (int) $item['week'] === 0) continue;
            $sheet->setCellValue("A{$row}", $items['managers'][$managerID]);
            $sheet->setCellValue("B{$row}", $items['week'][$managerID][0]);
            $sheet->setCellValue("C{$row}", $items['statuses'][$managerID][0]);
            $sheet->setCellValue("D{$row}", $items['dynamic'][$managerID][0]);
            $sheet->setCellValue("E{$row}", $items['week'][$managerID][1]);
            $sheet->setCellValue("F{$row}", $items['statuses'][$managerID][1]);
            $sheet->setCellValue("G{$row}", $items['dynamic'][$managerID][1]);
            $sheet->setCellValue("H{$row}", $items['week'][$managerID][2]);
            $sheet->setCellValue("I{$row}", $items['statuses'][$managerID][2]);
            $sheet->setCellValue("J{$row}", $items['dynamic'][$managerID][2]);
            $sheet->setCellValue("K{$row}", $items['week'][$managerID][3]);
            $sheet->setCellValue("L{$row}", $items['statuses'][$managerID][3]);
            $sheet->setCellValue("M{$row}", $items['dynamic'][$managerID][3]);
            $sheet->setCellValue("N{$row}", $items['week'][$managerID][4]);
            $sheet->setCellValue("O{$row}", $items['statuses'][$managerID][4]);
            $sheet->setCellValue("P{$row}", $items['dynamic'][$managerID][4]);
            $sheet->setCellValue("Q{$row}", $items['week'][$managerID][10]);
            $sheet->setCellValue("R{$row}", $items['statuses'][$managerID][10]);
            $sheet->setCellValue("S{$row}", $items['dynamic'][$managerID][10]);
            $sheet->setCellValue("T{$row}", $item['today']);
            $sheet->setCellValue("U{$row}", $item['week']);
            $sheet->setCellValue("V{$row}", $item['dynamic']);
            $row++;
        }
        //Итого
        $sheet->setCellValue("A{$row}", JText::sprintf('COM_REPORTS_HEAD_TOTAL'));
        $sheet->setCellValue("B{$row}", $items['total']['statuses_week'][0]);
        $sheet->setCellValue("C{$row}", $items['total']['statuses'][0]);
        $sheet->setCellValue("D{$row}", $items['total']['dynamic_by_statuses'][0]);
        $sheet->setCellValue("E{$row}", $items['total']['statuses_week'][1]);
        $sheet->setCellValue("F{$row}", $items['total']['statuses'][1]);
        $sheet->setCellValue("G{$row}", $items['total']['dynamic_by_statuses'][1]);
        $sheet->setCellValue("H{$row}", $items['total']['statuses_week'][2]);
        $sheet->setCellValue("I{$row}", $items['total']['statuses'][2]);
        $sheet->setCellValue("J{$row}", $items['total']['dynamic_by_statuses'][2]);
        $sheet->setCellValue("K{$row}", $items['total']['statuses_week'][3]);
        $sheet->setCellValue("L{$row}", $items['total']['statuses'][3]);
        $sheet->setCellValue("M{$row}", $items['total']['dynamic_by_statuses'][3]);
        $sheet->setCellValue("N{$row}", $items['total']['statuses_week'][4]);
        $sheet->setCellValue("O{$row}", $items['total']['statuses'][4]);
        $sheet->setCellValue("P{$row}", $items['total']['dynamic_by_statuses'][4]);
        $sheet->setCellValue("Q{$row}", $items['total']['statuses_week'][10]);
        $sheet->setCellValue("R{$row}", $items['total']['statuses'][10]);
        $sheet->setCellValue("S{$row}", $items['total']['dynamic_by_statuses'][10]);
        $sheet->setCellValue("T{$row}", $items['total']['week']);
        $sheet->setCellValue("U{$row}", $items['total']['today']);
        $sheet->setCellValue("V{$row}", $items['total']['dynamic']);

        header("Expires: Mon, 1 Apr 1974 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
        header("Cache-Control: no-cache, must-revalidate");
        header("Pragma: public");
        header("Content-type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=SentInvites.xls");
        $objWriter = PHPExcel_IOFactory::createWriter($xls, 'Excel5');
        $objWriter->save('php://output');
        jexit();
    }

    private function loadContractStatuses(): array
    {
        JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . "/components/com_contracts/models", "ContractsModel");
        $model = JModelLegacy::getInstance("Statuses", "ContractsModel");
        $items = $model->getItems();
        $statuses = [];
        foreach ($items['items'] as $item) $statuses[$item['code']] = $item['title'];
        return $statuses;
    }

    /* Сортировка по умолчанию */
    protected function populateState($ordering = 'manager, company', $direction = 'ASC')
    {
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);
        $date_1 = $this->getUserStateFromRequest($this->context . '.filter.date_1', 'filter_date_1', JDate::getInstance("-1 week")->format("Y-m-d"), 'string', false);
        $this->setState('filter.date_1', $date_1);
        $date_2 = $this->getUserStateFromRequest($this->context . '.filter.date_2', 'filter_date_2', JDate::getInstance()->format("Y-m-d"), 'string', false);
        $this->setState('filter.date_2', $date_2);
        ReportsHelper::check_refresh();
    }

    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.date_1');
        $id .= ':' . $this->getState('filter.date_2');
        return parent::getStoreId($id);
    }

    private $heads;
}
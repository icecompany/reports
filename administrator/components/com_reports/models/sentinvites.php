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
            ->leftJoin("s7vi9_users u on ms.managerID = u.id")
            ->where("(ms.dat = curdate() or ms.dat = date_add(curdate(), interval -1 week))");
        $project = PrjHelper::getActiveProject();
        if (is_numeric($project)) {
            $query->where("ms.projectID = {$this->_db->q($project)}");
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
        $result = ['items' => [], 'statuses' => [], 'managers' => [], 'total' => ['today' => 0, 'week' => 0, 'statuses' => []]];
        $items = parent::getItems();
        foreach ($items as $item) {
            if (!isset($result['managers'][$item->managerID])) $result['managers'][$item->managerID] = MkvHelper::getLastAndFirstNames($item->manager);
            if (!isset($result['items'][$item->managerID])) $result['items'][$item->managerID] = [];
            $day = ($item->dat !== JDate::getInstance()->format("Y-m-d")) ? 'week' : 'today';
            $result['items'][$item->managerID][$day] = $item->invites;
            if (isset($result['items'][$item->managerID]['today']) && isset($result['items'][$item->managerID]['week'])) {
                $result['items'][$item->managerID]['dynamic'] = $result['items'][$item->managerID]['today'] - $result['items'][$item->managerID]['week'];
            }
            $result['total'][$day] += $item->invites;
            if ($day === 'today') {
                $result['statuses'][$item->managerID][0] = $item->status_0;
                $result['statuses'][$item->managerID][1] = $item->status_1;
                $result['statuses'][$item->managerID][2] = $item->status_2;
                $result['statuses'][$item->managerID][3] = $item->status_3;
                $result['statuses'][$item->managerID][4] = $item->status_4;
                $result['statuses'][$item->managerID][10] = $item->status_10;
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
            }
        }
        $result['total']['dynamic'] = $result['total']['today'] - $result['total']['week'];
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

        $statuses = $this->loadContractStatuses();

        //Ширина столбцов
        $width = ["A" => 20, "B" => 6, "C" => 9, "D" => 26, "E" => 20, "F" => 18, "G" => 5, "H" => 28, "I" => 33, "J" => 12];
        foreach ($width as $col => $value) $sheet->getColumnDimension($col)->setWidth($value);
        //Заголовки
        $j = 0;
        $sheet->setCellValue("A1", JText::sprintf('COM_MKV_HEAD_MANAGER'));
        $sheet->setCellValue("B1", $statuses[0]);
        $sheet->setCellValue("C1", $statuses[1]);
        $sheet->setCellValue("D1", $statuses[2]);
        $sheet->setCellValue("E1", $statuses[3]);
        $sheet->setCellValue("F1", $statuses[4]);
        $sheet->setCellValue("G1", $statuses[10]);
        $sheet->setCellValue("H1", JText::sprintf('COM_REPORTS_HEAD_SENT_CURRENT_WEEK'));
        $sheet->setCellValue("I1", JText::sprintf('COM_REPORTS_HEAD_SENT_OLD_WEEK'));
        $sheet->setCellValue("J1", JText::sprintf('COM_REPORTS_HEAD_DYNAMIC'));

        $sheet->setTitle(JText::sprintf('COM_REPORTS_MENU_SENT_INVITES'));

        //Данные
        $row = 2; //Строка, с которой начнаются данные
        foreach ($items['items'] as $managerID => $item) {
            if ((int) $item['today'] === 0 && (int) $item['week'] === 0) continue;
            $sheet->setCellValue("A{$row}", $items['managers'][$managerID]);
            $sheet->setCellValue("B{$row}", $items['statuses'][$managerID][0]);
            $sheet->setCellValue("C{$row}", $items['statuses'][$managerID][1]);
            $sheet->setCellValue("D{$row}", $items['statuses'][$managerID][2]);
            $sheet->setCellValue("E{$row}", $items['statuses'][$managerID][3]);
            $sheet->setCellValue("F{$row}", $items['statuses'][$managerID][4]);
            $sheet->setCellValue("G{$row}", $items['statuses'][$managerID][10]);
            $sheet->setCellValue("H{$row}", $item['today']);
            $sheet->setCellValue("I{$row}", $item['week']);
            $sheet->setCellValue("J{$row}", $item['dynamic']);
            $row++;
        }
        //Итого
        $sheet->setCellValue("A{$row}", JText::sprintf('COM_REPORTS_HEAD_TOTAL'));
        $sheet->setCellValue("B{$row}", $items['total']['statuses'][0]);
        $sheet->setCellValue("C{$row}", $items['total']['statuses'][1]);
        $sheet->setCellValue("D{$row}", $items['total']['statuses'][2]);
        $sheet->setCellValue("E{$row}", $items['total']['statuses'][3]);
        $sheet->setCellValue("F{$row}", $items['total']['statuses'][4]);
        $sheet->setCellValue("G{$row}", $items['total']['statuses'][10]);
        $sheet->setCellValue("H{$row}", $items['total']['today']);
        $sheet->setCellValue("I{$row}", $items['total']['week']);
        $sheet->setCellValue("J{$row}", $items['total']['dynamic']);

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

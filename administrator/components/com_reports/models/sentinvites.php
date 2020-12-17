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
            ->select("ms.*")
            ->select("ms.status_0 + ms.status_1 + ms.status_2 + ms.status_3 + ms.status_4 + ms.status_10 as invites")
            ->from("#__mkv_managers_stat ms")
            ->leftJoin("s7vi9_users u on ms.managerID = u.id");

        $project = PrjHelper::getActiveProject();
        $date_1 = $this->getState('filter.date_1');
        $date_2 = $this->getState('filter.date_2');

        if (is_numeric($project)) {
            if (!empty($date_1) && !empty($date_2) && $date_1 !== '0000-00-00 00:00:00' && $date_2 !== '0000-00-00 00:00:00') {
                $d1 = JDate::getInstance($date_1)->format("Y-m-d");
                $d2 = JDate::getInstance($date_2)->format("Y-m-d");
                $diff = JDate::getInstance($date_2)->diff(JDate::getInstance($date_1));
                if ($diff->days > 350) {
                    $previous = PrjHelper::getPreviousProject($project);
                    if (is_numeric($previous)) {
                        $query->where("((ms.projectID = {$this->_db->q($previous)} and ms.dat = {$this->_db->q($d1)}) or (ms.projectID = {$this->_db->q($project)} and ms.dat = {$this->_db->q($d2)}))");
                    }
                } else {
                    $query->where("(ms.projectID = {$this->_db->q($project)} and ((ms.dat = {$this->_db->q($d1)}) or (ms.dat = {$this->_db->q($d2)})))");
                }
            }
        }

        //Отсеиваем исключённых пользователей
        $exception_group = ReportsHelper::getConfig('exception_users');
        if (!empty($exception_group)) {
            $not_users = implode(', ', MkvHelper::getGroupUsers($exception_group) ?? []);
            if (!empty($not_users)) {
                $query->where("ms.managerID not in ({$not_users})");
            }
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
        $result = ['items' => [], 'statuses' => [], 'week' => [], 'dynamic' => [], 'managers' => [], 'invites' => [], 'payments' => [], 'total' => ['today' => 0, 'week' => 0, 'dynamic_by_statuses' => [], 'dynamic' => 0, 'statuses_week' => [], 'statuses' => []]];
        $items = parent::getItems();
        foreach ($items as $item) {
            $now = JDate::getInstance($this->state->get('filter.date_2'))->format("Y-m-d");

            if (!isset($result['managers'][$item->managerID])) $result['managers'][$item->managerID] = MkvHelper::getLastAndFirstNames($item->manager);
            if (!isset($result['items'][$item->managerID])) $result['items'][$item->managerID] = [];
            $day = ($item->dat !== $now) ? 'week' : 'today';
            $result['items'][$item->managerID][$day] = $item->invites;
            $result['total'][$day] += $item->invites;
            if ($day !== 'today') {
                $result['week'][$item->managerID][0] = (int) $item->status_0;
                $result['week'][$item->managerID][1] = (int) $item->status_1;
                $result['week'][$item->managerID][2] = (int) $item->status_2;
                $result['week'][$item->managerID][3] = (int) $item->status_3;
                $result['week'][$item->managerID][4] = (int) $item->status_4;
                $result['week'][$item->managerID][10] = (int) $item->status_10;
                if (!isset($result['total']['statuses_week'][0])) $result['total']['statuses_week'][0] = 0;
                if (!isset($result['total']['statuses_week'][1])) $result['total']['statuses_week'][1] = 0;
                if (!isset($result['total']['statuses_week'][2])) $result['total']['statuses_week'][2] = 0;
                if (!isset($result['total']['statuses_week'][3])) $result['total']['statuses_week'][3] = 0;
                if (!isset($result['total']['statuses_week'][4])) $result['total']['statuses_week'][4] = 0;
                if (!isset($result['total']['statuses_week'][10])) $result['total']['statuses_week'][10] = 0;
                $result['total']['statuses_week'][0] += (int) $item->status_0;
                $result['total']['statuses_week'][1] += (int) $item->status_1;
                $result['total']['statuses_week'][2] += (int) $item->status_2;
                $result['total']['statuses_week'][3] += (int) $item->status_3;
                $result['total']['statuses_week'][4] += (int) $item->status_4;
                $result['total']['statuses_week'][10] += (int) $item->status_10;
            }
            else {
                $result['statuses'][$item->managerID][0] = (int) $item->status_0 ?? 0;
                $result['statuses'][$item->managerID][1] = (int) $item->status_1 ?? 0;
                $result['statuses'][$item->managerID][2] = (int) $item->status_2 ?? 0;
                $result['statuses'][$item->managerID][3] = (int) $item->status_3 ?? 0;
                $result['statuses'][$item->managerID][4] = (int) $item->status_4 ?? 0;
                $result['statuses'][$item->managerID][10] = (int) $item->status_10 ?? 0;
                if (!isset($result['total']['statuses'][0])) $result['total']['statuses'][0] = 0;
                if (!isset($result['total']['statuses'][1])) $result['total']['statuses'][1] = 0;
                if (!isset($result['total']['statuses'][2])) $result['total']['statuses'][2] = 0;
                if (!isset($result['total']['statuses'][3])) $result['total']['statuses'][3] = 0;
                if (!isset($result['total']['statuses'][4])) $result['total']['statuses'][4] = 0;
                if (!isset($result['total']['statuses'][10])) $result['total']['statuses'][10] = 0;
                $result['total']['statuses'][0] += (int) $item->status_0;
                $result['total']['statuses'][1] += (int) $item->status_1;
                $result['total']['statuses'][2] += (int) $item->status_2;
                $result['total']['statuses'][3] += (int) $item->status_3;
                $result['total']['statuses'][4] += (int) $item->status_4;
                $result['total']['statuses'][10] += (int) $item->status_10;
            }
            if (isset($result['items'][$item->managerID]['today']) && isset($result['items'][$item->managerID]['week'])) {
                $result['items'][$item->managerID]['dynamic'] = (int) $result['items'][$item->managerID]['today'] - (int) $result['items'][$item->managerID]['week'];
            }
        }
        $result['total']['dynamic'] = (int) $result['total']['today'] - (int) $result['total']['week'];

        $project = PrjHelper::getActiveProject();
        $previous = PrjHelper::getPreviousProject($project);
        $result['payments'] = $this->getPayments($previous, $project);
        $result['invites'] = $this->getSentInvites($previous, $project);

        foreach ([0, 1, 2, 3, 4, 10] as $status) {
            foreach ($result['managers'] as $managerID => $manager) {
                $result['dynamic'][$managerID][$status] = $result['statuses'][$managerID][$status] - $result['week'][$managerID][$status];
            }
            $result['total']['dynamic_by_statuses'][$status] = (int) $result['total']['statuses'][$status] - (int) $result['total']['statuses_week'][$status];
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

        $xls->setActiveSheetIndex();
        $sheet = $xls->getActiveSheet();

        $statuses = $this->loadContractStatuses();

        //Объединение ячеек
        $merge = ["B1:V1", "B2:D2", "E2:G2", "H2:J2", "K2:M2", "N2:P2", "Q2:S2", "T2:V2"];
        foreach ($merge as $cell) $sheet->mergeCells($cell);
        $sheet->getStyle("B1:AE3")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $sheet->freezePane("B4");

        //Ширина столбцов
        $width = ["A" => 20, "B" => 10, "C" => 10, "D" => 10, "E" => 10, "F" => 10, "G" => 10, "H" => 10, "I" => 10, "J" => 10, "K" => 10, "L" => 10, "M" => 10,
            "N" => 10, "O" => 10, "P" => 10, "Q" => 10, "R" => 10, "S" => 10, "T" => 20, "U" => 20, "V" => 10];
        foreach ($width as $col => $value) $sheet->getColumnDimension($col)->setWidth($value);
        //Заголовки
        $sheet->setCellValue("A1", JText::sprintf('COM_REPORTS_HEAD_STATUS'));
        $sheet->setCellValue("A2", JText::sprintf('COM_REPORTS_HEAD_PERIOD'));
        $sheet->setCellValue("A3", JText::sprintf('COM_MKV_HEAD_MANAGER'));
        $sheet->setCellValue("B2", $statuses[0]);
        $sheet->setCellValue("B3", $date_1);
        $sheet->setCellValue("C3", $date_2);
        $sheet->setCellValue("D3", JText::sprintf('COM_REPORTS_HEAD_DYNAMIC'));
        $sheet->setCellValue("E2", $statuses[1]);
        $sheet->setCellValue("E3", $date_1);
        $sheet->setCellValue("F3", $date_2);
        $sheet->setCellValue("G3", JText::sprintf('COM_REPORTS_HEAD_DYNAMIC'));
        $sheet->setCellValue("H2", $statuses[2]);
        $sheet->setCellValue("H3", $date_1);
        $sheet->setCellValue("I3", $date_2);
        $sheet->setCellValue("J3", JText::sprintf('COM_REPORTS_HEAD_DYNAMIC'));
        $sheet->setCellValue("K2", $statuses[3]);
        $sheet->setCellValue("K3", $date_1);
        $sheet->setCellValue("L3", $date_2);
        $sheet->setCellValue("M3", JText::sprintf('COM_REPORTS_HEAD_DYNAMIC'));
        $sheet->setCellValue("N2", $statuses[4]);
        $sheet->setCellValue("N3", $date_1);
        $sheet->setCellValue("O3", $date_2);
        $sheet->setCellValue("P3", JText::sprintf('COM_REPORTS_HEAD_DYNAMIC'));
        $sheet->setCellValue("Q2", $statuses[10]);
        $sheet->setCellValue("Q3", $date_1);
        $sheet->setCellValue("R3", $date_2);
        $sheet->setCellValue("S3", JText::sprintf('COM_REPORTS_HEAD_DYNAMIC'));
        $sheet->setCellValue("T2", JText::sprintf('COM_REPORTS_HEAD_TOTAL'));
        $sheet->setCellValue("T3", JText::sprintf('COM_REPORTS_HEAD_SENT_OLD_WEEK'));
        $sheet->setCellValue("U3", JText::sprintf('COM_REPORTS_HEAD_SENT_CURRENT_WEEK'));
        $sheet->setCellValue("V3", JText::sprintf('COM_REPORTS_HEAD_DYNAMIC'));

        $sheet->setTitle(JText::sprintf('COM_REPORTS_MENU_STATUSES_DYNAMIC'));

        //Данные
        $row = 4; //Строка, с которой начнаются данные
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
            $sheet->setCellValue("T{$row}", $item['week']);
            $sheet->setCellValue("U{$row}", $item['today']);
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

        //Платежи
        $xls->createSheet();
        $xls->setActiveSheetIndex(1);
        $sheet = $xls->getActiveSheet();

        //Объединение ячеек
        $merge = ["B1:J1", "B2:D2", "E2:G2", "H2:J2"];
        foreach ($merge as $cell) $sheet->mergeCells($cell);
        $sheet->getStyle("B1:J3")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $sheet->freezePane("B4");

        //Ширина столбцов
        $width = ["A" => 20, "B" => 14, "C" => 11, "D" => 11, "E" => 14, "F" => 11, "G" => 11, "H" => 14, "I" => 11, "J" => 11];

        foreach ($width as $col => $value) $sheet->getColumnDimension($col)->setWidth($value);
        //Заголовки
        $sheet->setCellValue("A1", JText::sprintf('COM_REPORTS_HEAD_STATUS'));
        $sheet->setCellValue("A2", JText::sprintf('COM_REPORTS_HEAD_PERIOD'));
        $sheet->setCellValue("A3", JText::sprintf('COM_MKV_HEAD_MANAGER'));
        $sheet->setCellValue("B1", JText::sprintf('COM_REPORTS_HEAD_PAYMENTS'));
        $sheet->setCellValue("B2", $date_1);
        $sheet->setCellValue("E2", $date_2);
        $sheet->setCellValue("H2", JText::sprintf('COM_REPORTS_HEAD_DYNAMIC'));
        $sheet->setCellValue("B3", JText::sprintf('COM_REPORTS_HEAD_RUB'));
        $sheet->setCellValue("C3", JText::sprintf('COM_REPORTS_HEAD_USD'));
        $sheet->setCellValue("D3", JText::sprintf('COM_REPORTS_HEAD_EUR'));
        $sheet->setCellValue("E3", JText::sprintf('COM_REPORTS_HEAD_RUB'));
        $sheet->setCellValue("F3", JText::sprintf('COM_REPORTS_HEAD_USD'));
        $sheet->setCellValue("G3", JText::sprintf('COM_REPORTS_HEAD_EUR'));
        $sheet->setCellValue("H3", JText::sprintf('COM_REPORTS_HEAD_RUB'));
        $sheet->setCellValue("I3", JText::sprintf('COM_REPORTS_HEAD_USD'));
        $sheet->setCellValue("J3", JText::sprintf('COM_REPORTS_HEAD_EUR'));

        $sheet->setTitle(JText::sprintf('COM_REPORTS_HEAD_PAYMENTS'));

        //Данные
        $row = 4; //Строка, с которой начнаются данные
        foreach ($items['items'] as $managerID => $item) {
            if ((int) $item['current'] === 0 && (int) $item['week'] === 0) continue;
            $sheet->setCellValue("A{$row}", $items['managers'][$managerID]);
            $sheet->setCellValue("B{$row}", $items['payments'][$managerID]['week']['rub'] ?? number_format(0, MKV_FORMAT_DEC_COUNT, MKV_FORMAT_SEPARATOR_FRACTION, ''));
            $sheet->setCellValue("C{$row}", $items['payments'][$managerID]['week']['usd'] ?? number_format(0, MKV_FORMAT_DEC_COUNT, MKV_FORMAT_SEPARATOR_FRACTION, ''));
            $sheet->setCellValue("D{$row}", $items['payments'][$managerID]['week']['eur'] ?? number_format(0, MKV_FORMAT_DEC_COUNT, MKV_FORMAT_SEPARATOR_FRACTION, ''));
            $sheet->setCellValue("E{$row}", $items['payments'][$managerID]['current']['rub'] ?? number_format(0, MKV_FORMAT_DEC_COUNT, MKV_FORMAT_SEPARATOR_FRACTION, ''));
            $sheet->setCellValue("F{$row}", $items['payments'][$managerID]['current']['usd'] ?? number_format(0, MKV_FORMAT_DEC_COUNT, MKV_FORMAT_SEPARATOR_FRACTION, ''));
            $sheet->setCellValue("G{$row}", $items['payments'][$managerID]['current']['eur'] ?? number_format(0, MKV_FORMAT_DEC_COUNT, MKV_FORMAT_SEPARATOR_FRACTION, ''));
            $sheet->setCellValue("H{$row}", $items['payments'][$managerID]['dynamic']['rub'] ?? number_format(0, MKV_FORMAT_DEC_COUNT, MKV_FORMAT_SEPARATOR_FRACTION, ''));
            $sheet->setCellValue("I{$row}", $items['payments'][$managerID]['dynamic']['usd'] ?? number_format(0, MKV_FORMAT_DEC_COUNT, MKV_FORMAT_SEPARATOR_FRACTION, ''));
            $sheet->setCellValue("J{$row}", $items['payments'][$managerID]['dynamic']['eur'] ?? number_format(0, MKV_FORMAT_DEC_COUNT, MKV_FORMAT_SEPARATOR_FRACTION, ''));
            $row++;
        }
        //Итого
        $sheet->setCellValue("A{$row}", JText::sprintf('COM_REPORTS_HEAD_TOTAL'));
        $sheet->setCellValue("B{$row}", $items['payments']['total']['week']['rub'] ?? number_format(0, MKV_FORMAT_DEC_COUNT, MKV_FORMAT_SEPARATOR_FRACTION, ''));
        $sheet->setCellValue("C{$row}", $items['payments']['total']['week']['usd'] ?? number_format(0, MKV_FORMAT_DEC_COUNT, MKV_FORMAT_SEPARATOR_FRACTION, ''));
        $sheet->setCellValue("D{$row}", $items['payments']['total']['week']['eur'] ?? number_format(0, MKV_FORMAT_DEC_COUNT, MKV_FORMAT_SEPARATOR_FRACTION, ''));
        $sheet->setCellValue("E{$row}", $items['payments']['total']['current']['rub'] ?? number_format(0, MKV_FORMAT_DEC_COUNT, MKV_FORMAT_SEPARATOR_FRACTION, ''));
        $sheet->setCellValue("F{$row}", $items['payments']['total']['current']['usd'] ?? number_format(0, MKV_FORMAT_DEC_COUNT, MKV_FORMAT_SEPARATOR_FRACTION, ''));
        $sheet->setCellValue("G{$row}", $items['payments']['total']['current']['eur'] ?? number_format(0, MKV_FORMAT_DEC_COUNT, MKV_FORMAT_SEPARATOR_FRACTION, ''));
        $sheet->setCellValue("H{$row}", $items['payments']['total']['dynamic']['rub'] ?? number_format(0, MKV_FORMAT_DEC_COUNT, MKV_FORMAT_SEPARATOR_FRACTION, ''));
        $sheet->setCellValue("I{$row}", $items['payments']['total']['dynamic']['usd'] ?? number_format(0, MKV_FORMAT_DEC_COUNT, MKV_FORMAT_SEPARATOR_FRACTION, ''));
        $sheet->setCellValue("J{$row}", $items['payments']['total']['dynamic']['eur'] ?? number_format(0, MKV_FORMAT_DEC_COUNT, MKV_FORMAT_SEPARATOR_FRACTION, ''));

        //Отправленные приглашения
        $xls->createSheet();
        $xls->setActiveSheetIndex(2);
        $sheet = $xls->getActiveSheet();

        //Объединение ячеек
        $merge = ["B1:D1"];
        foreach ($merge as $cell) $sheet->mergeCells($cell);
        $sheet->getStyle("A1:D2")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $sheet->freezePane("B3");

        //Ширина столбцов
        $width = ["A" => 20, "B" => 11, "C" => 11, "D" => 11];

        foreach ($width as $col => $value) $sheet->getColumnDimension($col)->setWidth($value);
        //Заголовки
        $sheet->setCellValue("A2", JText::sprintf('COM_MKV_HEAD_MANAGER'));
        $sheet->setCellValue("B1", JText::sprintf('COM_REPORTS_HEAD_SENT_ITEMS'));
        $sheet->setCellValue("B2", $date_1);
        $sheet->setCellValue("C2", $date_2);
        $sheet->setCellValue("D2", JText::sprintf('COM_REPORTS_HEAD_DYNAMIC'));

        $sheet->setTitle(JText::sprintf('COM_REPORTS_HEAD_SENT_ITEMS'));

        //Данные
        $row = 3; //Строка, с которой начнаются данные
        foreach ($items['invites']['managers'] as $managerID => $item) {
            if ((int) $item['current'] === 0 && (int) $item['week'] === 0) continue;
            $sheet->setCellValue("A{$row}", $items['managers'][$managerID]);
            $sheet->setCellValue("B{$row}", $items['invites']['managers'][$managerID]['week'] ?? 0);
            $sheet->setCellValue("C{$row}", $items['invites']['managers'][$managerID]['current'] ?? 0);
            $sheet->setCellValue("D{$row}", $items['invites']['managers'][$managerID]['dynamic'] ?? 0);
            $row++;
        }
        //Итого
        $sheet->setCellValue("A{$row}", JText::sprintf('COM_REPORTS_HEAD_TOTAL'));
        $sheet->setCellValue("B{$row}", $items['invites']['total']['week'] ?? 0);
        $sheet->setCellValue("C{$row}", $items['invites']['total']['current'] ?? 0);
        $sheet->setCellValue("D{$row}", $items['invites']['total']['dynamic'] ?? 0);

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

    private function getSentInvites(int $project_1, int $project_2)
    {
        $date_1 = JDate::getInstance($this->state->get('filter.date_1'));
        $date_2 = JDate::getInstance($this->state->get('filter.date_2'));
        $diff = JDate::getInstance($this->state->get('filter.date_2'))->diff($date_1);
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query
            ->select("c.managerID, c.projectID, count(c.id) as cnt")
            ->from("#__mkv_contract_sent_info si")
            ->leftJoin("#__mkv_contracts c on c.id = si.contractID");
        if ((int) $diff->days > 350) {
            $query
                ->where("(c.projectID = {$project_1} and si.invite_date <= {$db->q($date_1)}) or (c.projectID = {$db->q($project_2)} and si.invite_date <= {$db->q($date_2)})")
                ->group("c.managerID, c.projectID");
        }
        else {
            $query
                ->select("if(si.invite_date <= {$db->q($date_1)}, 'week' , 'dynamic') as {$db->qn('period')}")
                ->where("c.projectID = {$project_2} and si.invite_date <= {$db->q($date_2)}")
                ->group("c.managerID, c.projectID, period");
        }

        $items = $db->setQuery($query)->loadAssocList();
        $result = ['total' => ['week' => 0, 'current' => 0, 'dynamic' => 0], 'managers' => []];
        if ((int) $diff->days > 350) {
            foreach ($items as $item) {
                $period = ((int) $item['projectID'] !== (int) $project_1) ? 'current' : 'week';
                $result['managers'][$item['managerID']][$period] = (int) ($item['cnt'] ?? 0);
                $result['total'][$period] += (int) ($item['cnt'] ?? 0);
            }
        }
        else {
            foreach ($items as $item) {
                $result['managers'][$item['managerID']][$item['period']] = (int) ($item['cnt'] ?? 0);
                $result['managers'][$item['managerID']]['current'] = $result['managers'][$item['managerID']]['week'] + $result['managers'][$item['managerID']]['dynamic'];
            }
        }
        foreach ($result['managers'] as $managerID => $invites) {
            $result['managers'][$managerID]['dynamic'] = (int) ($result['managers'][$managerID]['current'] - $result['managers'][$managerID]['week']);
            foreach (['dynamic', 'current', 'week'] as $period) $result['total'][$period] += $result['managers'][$managerID][$period];
        }

        return $result;
    }

    private function getPayments(int $project_1, int $project_2)
    {
        $prefix = "FinancesModel";
        JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . "/components/com_finances/models", $prefix);
        $date_1 = JDate::getInstance($this->state->get('filter.date_1'));
        $date_2 = JDate::getInstance($this->state->get('filter.date_2'));
        $diff = JDate::getInstance($this->state->get('filter.date_2'))->diff($date_1);
        if ((int) $diff->days > 350) {
            $name = "PaymentsOnDatesByProjects";
            $params = ['project_1' => $project_1, 'project_2' => $project_2, 'date_1' => $date_1, 'date_2' => $date_2];
        }
        else {
            $name = "PaymentsOnDates";
            $params = ['projectID' => $project_2, 'date_1' => $date_1, 'date_2' => $date_2];
        }
        $model = JModelLegacy::getInstance($name, $prefix, $params);
        $items = $model->getItems();
        $total = ['dynamic' => ['rub' => 0, 'usd' => 0, 'eur' => 0], 'week' => ['rub' => 0, 'usd' => 0, 'eur' => 0], 'current' => ['rub' => 0, 'usd' => 0, 'eur' => 0]];
        if ((int) $diff->days > 350) {
            foreach (array_keys($items) as $managerID) {
                foreach (['rub', 'usd', 'eur'] as $currency) {
                    $total['dynamic'][$currency] += (float) ($items[$managerID][$project_2][$currency] - $items[$managerID][$project_1][$currency]);
                    $total['week'][$currency] += (float) $items[$managerID][$project_1][$currency];
                    $total['current'][$currency] += (float) $items[$managerID][$project_2][$currency];
                    $items[$managerID]['dynamic'][$currency] = number_format((float) ($items[$managerID][$project_2][$currency] - $items[$managerID][$project_1][$currency]), MKV_FORMAT_DEC_COUNT, MKV_FORMAT_SEPARATOR_FRACTION, '');
                    $items[$managerID]['week'][$currency] = number_format($items[$managerID][$project_1][$currency], MKV_FORMAT_DEC_COUNT, MKV_FORMAT_SEPARATOR_FRACTION, '');
                    $items[$managerID]['current'][$currency] = number_format($items[$managerID][$project_2][$currency], MKV_FORMAT_DEC_COUNT, MKV_FORMAT_SEPARATOR_FRACTION, '');
                    unset($items[$managerID][$project_1], $items[$managerID][$project_2]);
                }
            }
        }
        else {
            foreach (array_keys($items) as $managerID) {
                foreach (['rub', 'usd', 'eur'] as $currency) {
                    $total['dynamic'][$currency] += (float) ($items[$managerID]['dynamic'][$currency]);
                    $total['week'][$currency] += $items[$managerID]['week'][$currency];
                    $total['current'][$currency] += $items[$managerID]['current'][$currency];
                    $items[$managerID]['dynamic'][$currency] = number_format((float) ($items[$managerID]['dynamic'][$currency]), MKV_FORMAT_DEC_COUNT, MKV_FORMAT_SEPARATOR_FRACTION, '');
                    $items[$managerID]['week'][$currency] = number_format($items[$managerID]['week'][$currency], MKV_FORMAT_DEC_COUNT, MKV_FORMAT_SEPARATOR_FRACTION, '');
                    $items[$managerID]['current'][$currency] = number_format($items[$managerID]['current'][$currency], MKV_FORMAT_DEC_COUNT, MKV_FORMAT_SEPARATOR_FRACTION, '');
                }
            }
        }
        foreach (['dynamic', 'week', 'current'] as $period) {
            foreach (['rub', 'usd', 'eur'] as $currency) {
                $items['total'][$period][$currency] = number_format((float) $total[$period][$currency], MKV_FORMAT_DEC_COUNT, MKV_FORMAT_SEPARATOR_FRACTION, '');
            }
        }
        return $items;
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

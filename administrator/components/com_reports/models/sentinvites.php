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
            ->select("ms.`status_-1`, ms.status_0 + ms.status_1 + ms.status_2 + ms.status_3 + ms.status_4 + ms.status_5 + ms.status_6 + ms.status_9 + ms.status_10 as invites")
            ->from("#__mkv_managers_stat ms")
            ->leftJoin("s7vi9_users u on ms.managerID = u.id")
            ->having("invites > 0");

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
        $result = ['statuses' => [], 'squares' => [], 'invites' => [], 'payments' => []];
        $items = parent::getItems();
        $statuses = [-1, 0, 1, 2, 3, 4, 5, 6, 9, 10];
        $periods = ['week', 'current', 'dynamic'];
        $result_new = [
            'managers' => [], //Список менеджеров
            'statuses' => [],
            'total' => [
                'super' => ['week' => 0, 'current' => 0, 'dynamic' => 0], //Итоговая суммарная статистика
                'manager' => [], //По менеджерам
                'statuses' => [] //По статусам
            ]
        ];
        $now = JDate::getInstance($this->state->get('filter.date_2'))->format("Y-m-d");
        foreach ($items as $item) {
            if (!isset($result_new['managers'][$item->managerID])) {
                //Инициализация массива
                $result_new['managers'][$item->managerID] = MkvHelper::getLastAndFirstNames($item->manager);
                foreach ($statuses as $status) {
                    foreach ($periods as $period) {
                        if (!isset($result_new['statuses'][$item->managerID][$status][$period])) $result_new['statuses'][$item->managerID][$status][$period] = 0;
                        if (!isset($result_new['total']['manager'][$item->managerID][$period])) $result_new['total']['manager'][$item->managerID][$period] = 0;
                        if (!isset($result_new['total']['statuses'][$status][$period])) $result_new['total']['statuses'][$status][$period] = 0;
                    }
                }
            }
            $day = ($item->dat !== $now) ? 'week' : 'current';
            foreach ($statuses as $status) {
                $elem = "status_{$status}";
                //Плюсуем значения статусов
                $result_new['statuses'][$item->managerID][$status][$day] += (int) ($item->$elem);
                $result_new['total']['super'][$day] += (int) ($item->$elem);
                $result_new['total']['manager'][$item->managerID][$day] += (int) ($item->$elem);
                $result_new['total']['statuses'][$status][$day] += (int) ($item->$elem);
                //Плюсуем динамику
                $result_new['total']['super']['dynamic'] = (int) ($result_new['total']['super']['current'] - $result_new['total']['super']['week']);
                $result_new['statuses'][$item->managerID][$status]['dynamic'] = (int) ($result_new['statuses'][$item->managerID][$status]['current'] - $result_new['statuses'][$item->managerID][$status]['week']);
                $result_new['total']['manager'][$item->managerID]['dynamic'] = (int) ($result_new['total']['manager'][$item->managerID]['current'] - $result_new['total']['manager'][$item->managerID]['week']);
                $result_new['total']['statuses'][$status]['dynamic'] = (int) ($result_new['total']['statuses'][$status]['current'] - $result_new['total']['statuses'][$status]['week']);
            }
        }

        $project = PrjHelper::getActiveProject();
        $previous = PrjHelper::getPreviousProject($project);

        $result['statuses'] = $result_new;
        $result['payments'] = $this->getPayments($previous, $project);
        $result['invites'] = $this->getSentInvites($previous, $project);
        $result['squares'] = $this->getSquares($previous, $project);

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

        //Статусы
        $xls->setActiveSheetIndex();
        $sheet = $xls->getActiveSheet();
        $sheet->setTitle(JText::sprintf('COM_REPORTS_MENU_STATUSES_DYNAMIC'));

        $statuses = $this->loadContractStatuses();
        $status_codes = [-1, 0, 1, 2, 3, 4, 5, 6, 9, 10];

        //Объединение ячеек
        $merge = ["B1:AH1",
            "B2:D2", "E2:G2", "H2:J2", "K2:M2", "N2:P2", "Q2:S2", "T2:V2", "W2:Y2", "Z2:AB2", "AC2:AE2", "AF2:AH2"];
        foreach ($merge as $cell) $sheet->mergeCells($cell);
        $sheet->getStyle("B1:AH3")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $sheet->freezePane("B4");

        //Ширина столбцов
        $width = ["A" => 20, "B" => 20, "C" => 20, "D" => 10, "E" => 10, "F" => 10, "G" => 10, "H" => 10, "I" => 10, "J" => 10, "K" => 10, "L" => 10, "M" => 10,
            "N" => 10, "O" => 10, "P" => 10, "Q" => 10, "R" => 10, "S" => 10, "T" => 10, "U" => 10, "V" => 10, "W" => 10, "X" => 10, "Y" => 10,
            "Z" => 10, "AA" => 10, "AB" => 10, "AC" => 10, "AD" => 10, "AE" => 10, "AF" => 10, "AG" => 10, "AH" => 10];
        foreach ($width as $col => $value) $sheet->getColumnDimension($col)->setWidth($value);
        //Заголовки
        $sheet->setCellValue("A1", JText::sprintf('COM_REPORTS_HEAD_STATUS'));
        $sheet->setCellValue("B1", JText::sprintf('COM_REPORTS_MENU_STATUSES_DYNAMIC'));
        $sheet->setCellValue("A2", JText::sprintf('COM_REPORTS_HEAD_PERIOD'));
        $sheet->setCellValue("A3", JText::sprintf('COM_MKV_HEAD_MANAGER'));
        //Итого
        $sheet->setCellValue("B2", JText::sprintf('COM_REPORTS_HEAD_TOTAL'));
        $sheet->setCellValue("B3", JText::sprintf('COM_REPORTS_HEAD_SENT_OLD_WEEK'));
        $sheet->setCellValue("C3", JText::sprintf('COM_REPORTS_HEAD_SENT_CURRENT_WEEK'));
        $sheet->setCellValue("D3", JText::sprintf('COM_REPORTS_HEAD_DYNAMIC'));
        //Статусы
        $column = 4;
        foreach ($status_codes as $code) {
            $status_title = ($code !== -1) ? $statuses[$code] : JText::sprintf('COM_MKV_STATUS_IN_PROJECT');
            $sheet->setCellValueByColumnAndRow($column, 2, $status_title);
            foreach ([$date_1, $date_2, JText::sprintf('COM_REPORTS_HEAD_DYNAMIC')] as $date) {
                $sheet->setCellValueByColumnAndRow($column, 3, $date);
                $column++;
            }
        }

        //Данные
        $periods = ['week', 'current', 'dynamic'];
        $row = 4; //Строка, с которой начнаются данные
        foreach ($items['statuses']['managers'] as $managerID => $manager) {
            $sheet->setCellValue("A{$row}", $manager);
            //Итого (по менеджерам)
            $column = 1;
            foreach ($periods as $period) {
                $sheet->setCellValueByColumnAndRow($column, $row, $items['statuses']['total']['manager'][$managerID][$period]);
                $column++;
            }
            //Значения по статусам
            foreach ($status_codes as $status) {
                foreach ($periods as $period) {
                    $sheet->setCellValueByColumnAndRow($column, $row, $items['statuses']['statuses'][$managerID][$status][$period]);
                    $column++;
                }
            }
            $row++;
        }
        //Итого (последняя строка)
        $sheet->setCellValue("A{$row}", JText::sprintf('COM_REPORTS_HEAD_TOTAL'));
        $column = 1;
        //Итого общее
        foreach ($periods as $period) {
            $sheet->setCellValueByColumnAndRow($column, $row, $items['statuses']['total']['super'][$period]);
            $column++;
        }
        //Итого по статусам
        foreach ($status_codes as $status) {
            foreach ($periods as $period) {
                $sheet->setCellValueByColumnAndRow($column, $row, $items['statuses']['total']['statuses'][$status][$period]);
                $column++;
            }
        }


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
        foreach ($items['payments'] as $managerID => $item) {
            if ((int) $item['current'] === 0 && (int) $item['week'] === 0) continue;
            if (is_numeric($managerID)) {
                $sheet->setCellValue("A{$row}", MkvHelper::getLastAndFirstNames(JFactory::getUser($managerID)->name));
            }
            else continue;
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
            $sheet->setCellValue("A{$row}", MkvHelper::getLastAndFirstNames(JFactory::getUser($managerID)->name));
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

        //Площади
        $xls->createSheet();
        $xls->setActiveSheetIndex(3);
        $sheet = $xls->getActiveSheet();
        $sheet->setTitle(JText::sprintf('COM_REPORTS_HEAD_SQUARES'));
        $sheet->getPageSetup()->setFitToHeight(0);
        $sheet->getPageSetup()->setFitToHeight(1);

        //Объединение ячеек
        $merge = [
            "B1:G1", "H1:M1", "N1:S1", "T1:Y1", "Z1:AE1", "AF1:AK1", "AL1:AQ1", "AR1:AW1", "AX1:BC1", "BD1:BI1",
            "B2:C2", "D2:E2", "F2:G2", //Итого
            "H2:I2", "J2:K2", "L2:M2", //Экспоместо
            "N2:O2", "P2:Q2", "R2:S2", //Премиум
            "T2:U2", "V2:W2", "X2:Y2", //Улица демонстрация
            "Z2:AA2", "AB2:AC2", "AD2:AE2", //Улица застройка
            "AF2:AG2", "AH2:AI2", "AJ2:AK2", //ВПК
            "AL2:AM2", "AN2:AO2", "AP2:AQ2", //ВПК улица
            "AR2:AS2", "AT2:AU2", "AV2:AW2", //Второй премиум
            "AX2:AY2", "AZ2:BA2", "BB2:BC2", //Второй
            "BD2:BE2", "BF2:BG2", "BH2:BI2" //НПФ ОРТ
        ];
        foreach ($merge as $cell) $sheet->mergeCells($cell);
        $sheet->freezePane("B4");

        //Ширина столбцов
        $width = ["A" => 20, "B" => 13, "C" => 9, "D" => 13, "E" => 9, "F" => 13, "G" => 9, "H" => 13, "I" => 9, "J" => 13, "K" => 9, "L" => 13, "M" => 9,
            "N" => 13, "O" => 9, "P" => 13, "Q" => 9, "R" => 13, "S" => 9, "T" => 13, "U" => 9, "V" => 13, "W" => 9, "X" => 13, "Y" => 9,
            "Z" => 13, "AA" => 9, "AB" => 13, "AC" => 9, "AD" => 13, "AE" => 9, "AF" => 13, "AG" => 9, "AH" => 13, "AI" => 9, "AJ" => 13, "AK" => 9,
            "AL" => 13, "AM" => 9, "AN" => 13, "AO" => 9, "AP" => 13, "AQ" => 9, "AR" => 13, "AS" => 9, "AT" => 13, "AU" => 9, "AV" => 13, "AW" => 9, "AX" => 13,
            "AY" => 9, "AZ" => 13, "BA" => 9, "BB" => 13, "BC" => 9, "BD" => 13, "BE" => 9, "BF" => 13, "BG" => 9, "BH" => 13, "BI" => 9];
        foreach ($width as $col => $value) $sheet->getColumnDimension($col)->setWidth($value);
        $sheet->getStyle("B1:BI3")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        //Заголовки
        $sheet->setCellValue("A1", JText::sprintf('COM_REPORTS_HEAD_SQUARE'));
        $sheet->setCellValue("A2", JText::sprintf('COM_REPORTS_HEAD_PERIOD'));
        $sheet->setCellValue("A3", JText::sprintf('COM_MKV_HEAD_MANAGER'));
        //Итого
        $sheet->setCellValue("B1", JText::sprintf('COM_REPORTS_HEAD_TOTAL'));
        $sheet->setCellValue("B2", $date_1);
        $sheet->setCellValue("D2", $date_2);
        $sheet->setCellValue("F2", JText::sprintf('COM_REPORTS_HEAD_DYNAMIC'));
        $sheet->setCellValue("B3", JText::sprintf('COM_REPORTS_HEAD_RUB'));
        $sheet->setCellValue("C3", JText::sprintf('COM_REPORTS_HEAD_METRES'));
        $sheet->setCellValue("D3", JText::sprintf('COM_REPORTS_HEAD_RUB'));
        $sheet->setCellValue("E3", JText::sprintf('COM_REPORTS_HEAD_METRES'));
        $sheet->setCellValue("F3", JText::sprintf('COM_REPORTS_HEAD_RUB'));
        $sheet->setCellValue("G3", JText::sprintf('COM_REPORTS_HEAD_METRES'));
        //Экспоместо
        $sheet->setCellValue("H1", JText::sprintf('COM_PRICES_ITEM_SQUARE_TYPE_1'));
        $sheet->setCellValue("H2", $date_1);
        $sheet->setCellValue("J2", $date_2);
        $sheet->setCellValue("L2", JText::sprintf('COM_REPORTS_HEAD_DYNAMIC'));
        $sheet->setCellValue("H3", JText::sprintf('COM_REPORTS_HEAD_RUB'));
        $sheet->setCellValue("I3", JText::sprintf('COM_REPORTS_HEAD_METRES'));
        $sheet->setCellValue("J3", JText::sprintf('COM_REPORTS_HEAD_RUB'));
        $sheet->setCellValue("K3", JText::sprintf('COM_REPORTS_HEAD_METRES'));
        $sheet->setCellValue("L3", JText::sprintf('COM_REPORTS_HEAD_RUB'));
        $sheet->setCellValue("M3", JText::sprintf('COM_REPORTS_HEAD_METRES'));
        //Премиум
        $sheet->setCellValue("N1", JText::sprintf('COM_PRICES_ITEM_SQUARE_TYPE_2'));
        $sheet->setCellValue("N2", $date_1);
        $sheet->setCellValue("P2", $date_2);
        $sheet->setCellValue("R2", JText::sprintf('COM_REPORTS_HEAD_DYNAMIC'));
        $sheet->setCellValue("N3", JText::sprintf('COM_REPORTS_HEAD_RUB'));
        $sheet->setCellValue("O3", JText::sprintf('COM_REPORTS_HEAD_METRES'));
        $sheet->setCellValue("P3", JText::sprintf('COM_REPORTS_HEAD_RUB'));
        $sheet->setCellValue("Q3", JText::sprintf('COM_REPORTS_HEAD_METRES'));
        $sheet->setCellValue("R3", JText::sprintf('COM_REPORTS_HEAD_RUB'));
        $sheet->setCellValue("S3", JText::sprintf('COM_REPORTS_HEAD_METRES'));
        //Улица демонстрация
        $sheet->setCellValue("T1", JText::sprintf('COM_PRICES_ITEM_SQUARE_TYPE_3'));
        $sheet->setCellValue("T2", $date_1);
        $sheet->setCellValue("V2", $date_2);
        $sheet->setCellValue("X2", JText::sprintf('COM_REPORTS_HEAD_DYNAMIC'));
        $sheet->setCellValue("T3", JText::sprintf('COM_REPORTS_HEAD_RUB'));
        $sheet->setCellValue("U3", JText::sprintf('COM_REPORTS_HEAD_METRES'));
        $sheet->setCellValue("V3", JText::sprintf('COM_REPORTS_HEAD_RUB'));
        $sheet->setCellValue("W3", JText::sprintf('COM_REPORTS_HEAD_METRES'));
        $sheet->setCellValue("X3", JText::sprintf('COM_REPORTS_HEAD_RUB'));
        $sheet->setCellValue("Y3", JText::sprintf('COM_REPORTS_HEAD_METRES'));
        //Улица застройка
        $sheet->setCellValue("Z1", JText::sprintf('COM_PRICES_ITEM_SQUARE_TYPE_4'));
        $sheet->setCellValue("Z2", $date_1);
        $sheet->setCellValue("AB2", $date_2);
        $sheet->setCellValue("AD2", JText::sprintf('COM_REPORTS_HEAD_DYNAMIC'));
        $sheet->setCellValue("Z3", JText::sprintf('COM_REPORTS_HEAD_RUB'));
        $sheet->setCellValue("AA3", JText::sprintf('COM_REPORTS_HEAD_METRES'));
        $sheet->setCellValue("AB3", JText::sprintf('COM_REPORTS_HEAD_RUB'));
        $sheet->setCellValue("AC3", JText::sprintf('COM_REPORTS_HEAD_METRES'));
        $sheet->setCellValue("AD3", JText::sprintf('COM_REPORTS_HEAD_RUB'));
        $sheet->setCellValue("AE3", JText::sprintf('COM_REPORTS_HEAD_METRES'));
        //ВПК
        $sheet->setCellValue("AF1", JText::sprintf('COM_PRICES_ITEM_SQUARE_TYPE_5'));
        $sheet->setCellValue("AF2", $date_1);
        $sheet->setCellValue("AH2", $date_2);
        $sheet->setCellValue("AJ2", JText::sprintf('COM_REPORTS_HEAD_DYNAMIC'));
        $sheet->setCellValue("AF3", JText::sprintf('COM_REPORTS_HEAD_RUB'));
        $sheet->setCellValue("AG3", JText::sprintf('COM_REPORTS_HEAD_METRES'));
        $sheet->setCellValue("AH3", JText::sprintf('COM_REPORTS_HEAD_RUB'));
        $sheet->setCellValue("AI3", JText::sprintf('COM_REPORTS_HEAD_METRES'));
        $sheet->setCellValue("AJ3", JText::sprintf('COM_REPORTS_HEAD_RUB'));
        $sheet->setCellValue("AK3", JText::sprintf('COM_REPORTS_HEAD_METRES'));
        //ВПК улица
        $sheet->setCellValue("AL1", JText::sprintf('COM_PRICES_ITEM_SQUARE_TYPE_6'));
        $sheet->setCellValue("AL2", $date_1);
        $sheet->setCellValue("AN2", $date_2);
        $sheet->setCellValue("AP2", JText::sprintf('COM_REPORTS_HEAD_DYNAMIC'));
        $sheet->setCellValue("AL3", JText::sprintf('COM_REPORTS_HEAD_RUB'));
        $sheet->setCellValue("AM3", JText::sprintf('COM_REPORTS_HEAD_METRES'));
        $sheet->setCellValue("AN3", JText::sprintf('COM_REPORTS_HEAD_RUB'));
        $sheet->setCellValue("AO3", JText::sprintf('COM_REPORTS_HEAD_METRES'));
        $sheet->setCellValue("AP3", JText::sprintf('COM_REPORTS_HEAD_RUB'));
        $sheet->setCellValue("AQ3", JText::sprintf('COM_REPORTS_HEAD_METRES'));
        //Второй премиум
        $sheet->setCellValue("AR1", JText::sprintf('COM_PRICES_ITEM_SQUARE_TYPE_7'));
        $sheet->setCellValue("AR2", $date_1);
        $sheet->setCellValue("AT2", $date_2);
        $sheet->setCellValue("AV2", JText::sprintf('COM_REPORTS_HEAD_DYNAMIC'));
        $sheet->setCellValue("AR3", JText::sprintf('COM_REPORTS_HEAD_RUB'));
        $sheet->setCellValue("AS3", JText::sprintf('COM_REPORTS_HEAD_METRES'));
        $sheet->setCellValue("AT3", JText::sprintf('COM_REPORTS_HEAD_RUB'));
        $sheet->setCellValue("AU3", JText::sprintf('COM_REPORTS_HEAD_METRES'));
        $sheet->setCellValue("AV3", JText::sprintf('COM_REPORTS_HEAD_RUB'));
        $sheet->setCellValue("AW3", JText::sprintf('COM_REPORTS_HEAD_METRES'));
        //Второй
        $sheet->setCellValue("AX1", JText::sprintf('COM_PRICES_ITEM_SQUARE_TYPE_8'));
        $sheet->setCellValue("AX2", $date_1);
        $sheet->setCellValue("AZ2", $date_2);
        $sheet->setCellValue("BB2", JText::sprintf('COM_REPORTS_HEAD_DYNAMIC'));
        $sheet->setCellValue("AX3", JText::sprintf('COM_REPORTS_HEAD_RUB'));
        $sheet->setCellValue("AY3", JText::sprintf('COM_REPORTS_HEAD_METRES'));
        $sheet->setCellValue("AZ3", JText::sprintf('COM_REPORTS_HEAD_RUB'));
        $sheet->setCellValue("BA3", JText::sprintf('COM_REPORTS_HEAD_METRES'));
        $sheet->setCellValue("BB3", JText::sprintf('COM_REPORTS_HEAD_RUB'));
        $sheet->setCellValue("BC3", JText::sprintf('COM_REPORTS_HEAD_METRES'));

        //Данные
        $row = 4; //Строка, с которой начнаются данные
        foreach ($items['squares']['managers'] as $managerID => $item) {
            $sheet->setCellValue("A{$row}", MkvHelper::getLastAndFirstNames(JFactory::getUser($managerID)->name));
            //Итого
            $sheet->setCellValue("B{$row}", $items['squares']['total_by_manager'][$managerID]['week']['rub']['amount'] ?? 0);
            $sheet->setCellValue("C{$row}", $items['squares']['total_by_manager'][$managerID]['week']['rub']['square'] ?? 0);
            $sheet->setCellValue("D{$row}", $items['squares']['total_by_manager'][$managerID]['current']['rub']['amount'] ?? 0);
            $sheet->setCellValue("E{$row}", $items['squares']['total_by_manager'][$managerID]['current']['rub']['square'] ?? 0);
            $sheet->setCellValue("F{$row}", $items['squares']['total_by_manager'][$managerID]['dynamic']['rub']['amount'] ?? 0);
            $sheet->setCellValue("G{$row}", $items['squares']['total_by_manager'][$managerID]['dynamic']['rub']['square'] ?? 0);
            //Экспоместо
            $sheet->setCellValue("H{$row}", $items['squares']['managers'][$managerID]['week'][1]['rub']['amount'] ?? 0);
            $sheet->setCellValue("I{$row}", $items['squares']['managers'][$managerID]['week'][1]['rub']['square'] ?? 0);
            $sheet->setCellValue("J{$row}", $items['squares']['managers'][$managerID]['current'][1]['rub']['amount'] ?? 0);
            $sheet->setCellValue("K{$row}", $items['squares']['managers'][$managerID]['current'][1]['rub']['square'] ?? 0);
            $sheet->setCellValue("L{$row}", $items['squares']['managers'][$managerID]['dynamic'][1]['rub']['amount'] ?? 0);
            $sheet->setCellValue("M{$row}", $items['squares']['managers'][$managerID]['dynamic'][1]['rub']['square'] ?? 0);
            //Премиум
            $sheet->setCellValue("N{$row}", $items['squares']['managers'][$managerID]['week'][2]['rub']['amount'] ?? 0);
            $sheet->setCellValue("O{$row}", $items['squares']['managers'][$managerID]['week'][2]['rub']['square'] ?? 0);
            $sheet->setCellValue("P{$row}", $items['squares']['managers'][$managerID]['current'][2]['rub']['amount'] ?? 0);
            $sheet->setCellValue("Q{$row}", $items['squares']['managers'][$managerID]['current'][2]['rub']['square'] ?? 0);
            $sheet->setCellValue("R{$row}", $items['squares']['managers'][$managerID]['dynamic'][2]['rub']['amount'] ?? 0);
            $sheet->setCellValue("S{$row}", $items['squares']['managers'][$managerID]['dynamic'][2]['rub']['square'] ?? 0);
            //Улица демонстрация
            $sheet->setCellValue("T{$row}", $items['squares']['managers'][$managerID]['week'][3]['rub']['amount'] ?? 0);
            $sheet->setCellValue("U{$row}", $items['squares']['managers'][$managerID]['week'][3]['rub']['square'] ?? 0);
            $sheet->setCellValue("V{$row}", $items['squares']['managers'][$managerID]['current'][3]['rub']['amount'] ?? 0);
            $sheet->setCellValue("W{$row}", $items['squares']['managers'][$managerID]['current'][3]['rub']['square'] ?? 0);
            $sheet->setCellValue("X{$row}", $items['squares']['managers'][$managerID]['dynamic'][3]['rub']['amount'] ?? 0);
            $sheet->setCellValue("Y{$row}", $items['squares']['managers'][$managerID]['dynamic'][3]['rub']['square'] ?? 0);
            //Улица застройка
            $sheet->setCellValue("Z{$row}", $items['squares']['managers'][$managerID]['week'][4]['rub']['amount'] ?? 0);
            $sheet->setCellValue("AA{$row}", $items['squares']['managers'][$managerID]['week'][4]['rub']['square'] ?? 0);
            $sheet->setCellValue("AB{$row}", $items['squares']['managers'][$managerID]['current'][4]['rub']['amount'] ?? 0);
            $sheet->setCellValue("AC{$row}", $items['squares']['managers'][$managerID]['current'][4]['rub']['square'] ?? 0);
            $sheet->setCellValue("AD{$row}", $items['squares']['managers'][$managerID]['dynamic'][4]['rub']['amount'] ?? 0);
            $sheet->setCellValue("AE{$row}", $items['squares']['managers'][$managerID]['dynamic'][4]['rub']['square'] ?? 0);
            //ВПК
            $sheet->setCellValue("AF{$row}", $items['squares']['managers'][$managerID]['week'][5]['rub']['amount'] ?? 0);
            $sheet->setCellValue("AG{$row}", $items['squares']['managers'][$managerID]['week'][5]['rub']['square'] ?? 0);
            $sheet->setCellValue("AH{$row}", $items['squares']['managers'][$managerID]['current'][5]['rub']['amount'] ?? 0);
            $sheet->setCellValue("AI{$row}", $items['squares']['managers'][$managerID]['current'][5]['rub']['square'] ?? 0);
            $sheet->setCellValue("AJ{$row}", $items['squares']['managers'][$managerID]['dynamic'][5]['rub']['amount'] ?? 0);
            $sheet->setCellValue("AK{$row}", $items['squares']['managers'][$managerID]['dynamic'][5]['rub']['square'] ?? 0);
            //ВПК улица
            $sheet->setCellValue("AL{$row}", $items['squares']['managers'][$managerID]['week'][6]['rub']['amount'] ?? 0);
            $sheet->setCellValue("AM{$row}", $items['squares']['managers'][$managerID]['week'][6]['rub']['square'] ?? 0);
            $sheet->setCellValue("AN{$row}", $items['squares']['managers'][$managerID]['current'][6]['rub']['amount'] ?? 0);
            $sheet->setCellValue("AO{$row}", $items['squares']['managers'][$managerID]['current'][6]['rub']['square'] ?? 0);
            $sheet->setCellValue("AP{$row}", $items['squares']['managers'][$managerID]['dynamic'][6]['rub']['amount'] ?? 0);
            $sheet->setCellValue("AQ{$row}", $items['squares']['managers'][$managerID]['dynamic'][6]['rub']['square'] ?? 0);
            //Второй премиум
            $sheet->setCellValue("AR{$row}", $items['squares']['managers'][$managerID]['week'][7]['rub']['amount'] ?? 0);
            $sheet->setCellValue("AS{$row}", $items['squares']['managers'][$managerID]['week'][7]['rub']['square'] ?? 0);
            $sheet->setCellValue("AT{$row}", $items['squares']['managers'][$managerID]['current'][7]['rub']['amount'] ?? 0);
            $sheet->setCellValue("AU{$row}", $items['squares']['managers'][$managerID]['current'][7]['rub']['square'] ?? 0);
            $sheet->setCellValue("AV{$row}", $items['squares']['managers'][$managerID]['dynamic'][7]['rub']['amount'] ?? 0);
            $sheet->setCellValue("AW{$row}", $items['squares']['managers'][$managerID]['dynamic'][7]['rub']['square'] ?? 0);
            //Второй премиум
            $sheet->setCellValue("AX{$row}", $items['squares']['managers'][$managerID]['week'][8]['rub']['amount'] ?? 0);
            $sheet->setCellValue("AY{$row}", $items['squares']['managers'][$managerID]['week'][8]['rub']['square'] ?? 0);
            $sheet->setCellValue("AZ{$row}", $items['squares']['managers'][$managerID]['current'][8]['rub']['amount'] ?? 0);
            $sheet->setCellValue("BA{$row}", $items['squares']['managers'][$managerID]['current'][8]['rub']['square'] ?? 0);
            $sheet->setCellValue("BB{$row}", $items['squares']['managers'][$managerID]['dynamic'][8]['rub']['amount'] ?? 0);
            $sheet->setCellValue("BC{$row}", $items['squares']['managers'][$managerID]['dynamic'][8]['rub']['square'] ?? 0);
            $row++;
        }
        //Итого
        $sheet->setCellValue("A{$row}", JText::sprintf('COM_REPORTS_HEAD_TOTAL'));
        $sheet->setCellValue("B{$row}", $items['squares']['total_by_period']['rub']['week']['amount'] ?? 0);
        $sheet->setCellValue("C{$row}", $items['squares']['total_by_period']['rub']['week']['square'] ?? 0);
        $sheet->setCellValue("D{$row}", $items['squares']['total_by_period']['rub']['current']['amount'] ?? 0);
        $sheet->setCellValue("E{$row}", $items['squares']['total_by_period']['rub']['current']['square'] ?? 0);
        $sheet->setCellValue("F{$row}", $items['squares']['total_by_period']['rub']['dynamic']['amount'] ?? 0);
        $sheet->setCellValue("G{$row}", $items['squares']['total_by_period']['rub']['dynamic']['square'] ?? 0);

        $sheet->setCellValue("H{$row}", $items['squares']['total']['rub'][1]['week']['amount'] ?? 0);
        $sheet->setCellValue("I{$row}", $items['squares']['total']['rub'][1]['week']['square'] ?? 0);
        $sheet->setCellValue("J{$row}", $items['squares']['total']['rub'][1]['current']['amount'] ?? 0);
        $sheet->setCellValue("K{$row}", $items['squares']['total']['rub'][1]['current']['square'] ?? 0);
        $sheet->setCellValue("L{$row}", $items['squares']['total']['rub'][1]['dynamic']['amount'] ?? 0);
        $sheet->setCellValue("M{$row}", $items['squares']['total']['rub'][1]['dynamic']['square'] ?? 0);

        $sheet->setCellValue("N{$row}", $items['squares']['total']['rub'][2]['week']['amount'] ?? 0);
        $sheet->setCellValue("O{$row}", $items['squares']['total']['rub'][2]['week']['square'] ?? 0);
        $sheet->setCellValue("P{$row}", $items['squares']['total']['rub'][2]['current']['amount'] ?? 0);
        $sheet->setCellValue("Q{$row}", $items['squares']['total']['rub'][2]['current']['square'] ?? 0);
        $sheet->setCellValue("R{$row}", $items['squares']['total']['rub'][2]['dynamic']['amount'] ?? 0);
        $sheet->setCellValue("S{$row}", $items['squares']['total']['rub'][2]['dynamic']['square'] ?? 0);

        $sheet->setCellValue("T{$row}", $items['squares']['total']['rub'][3]['week']['amount'] ?? 0);
        $sheet->setCellValue("U{$row}", $items['squares']['total']['rub'][3]['week']['square'] ?? 0);
        $sheet->setCellValue("V{$row}", $items['squares']['total']['rub'][3]['current']['amount'] ?? 0);
        $sheet->setCellValue("W{$row}", $items['squares']['total']['rub'][3]['current']['square'] ?? 0);
        $sheet->setCellValue("X{$row}", $items['squares']['total']['rub'][3]['dynamic']['amount'] ?? 0);
        $sheet->setCellValue("Y{$row}", $items['squares']['total']['rub'][3]['dynamic']['square'] ?? 0);

        $sheet->setCellValue("Z{$row}", $items['squares']['total']['rub'][4]['week']['amount'] ?? 0);
        $sheet->setCellValue("AA{$row}", $items['squares']['total']['rub'][4]['week']['square'] ?? 0);
        $sheet->setCellValue("AB{$row}", $items['squares']['total']['rub'][4]['current']['amount'] ?? 0);
        $sheet->setCellValue("AC{$row}", $items['squares']['total']['rub'][4]['current']['square'] ?? 0);
        $sheet->setCellValue("AD{$row}", $items['squares']['total']['rub'][4]['dynamic']['amount'] ?? 0);
        $sheet->setCellValue("AE{$row}", $items['squares']['total']['rub'][4]['dynamic']['square'] ?? 0);

        $sheet->setCellValue("AF{$row}", $items['squares']['total']['rub'][5]['week']['amount'] ?? 0);
        $sheet->setCellValue("AG{$row}", $items['squares']['total']['rub'][5]['week']['square'] ?? 0);
        $sheet->setCellValue("AH{$row}", $items['squares']['total']['rub'][5]['current']['amount'] ?? 0);
        $sheet->setCellValue("AI{$row}", $items['squares']['total']['rub'][5]['current']['square'] ?? 0);
        $sheet->setCellValue("AJ{$row}", $items['squares']['total']['rub'][5]['dynamic']['amount'] ?? 0);
        $sheet->setCellValue("AK{$row}", $items['squares']['total']['rub'][5]['dynamic']['square'] ?? 0);

        $sheet->setCellValue("AL{$row}", $items['squares']['total']['rub'][6]['week']['amount'] ?? 0);
        $sheet->setCellValue("AM{$row}", $items['squares']['total']['rub'][6]['week']['square'] ?? 0);
        $sheet->setCellValue("AN{$row}", $items['squares']['total']['rub'][6]['current']['amount'] ?? 0);
        $sheet->setCellValue("AO{$row}", $items['squares']['total']['rub'][6]['current']['square'] ?? 0);
        $sheet->setCellValue("AP{$row}", $items['squares']['total']['rub'][6]['dynamic']['amount'] ?? 0);
        $sheet->setCellValue("AQ{$row}", $items['squares']['total']['rub'][6]['dynamic']['square'] ?? 0);

        $sheet->setCellValue("AR{$row}", $items['squares']['total']['rub'][7]['week']['amount'] ?? 0);
        $sheet->setCellValue("AS{$row}", $items['squares']['total']['rub'][7]['week']['square'] ?? 0);
        $sheet->setCellValue("AT{$row}", $items['squares']['total']['rub'][7]['current']['amount'] ?? 0);
        $sheet->setCellValue("AU{$row}", $items['squares']['total']['rub'][7]['current']['square'] ?? 0);
        $sheet->setCellValue("AV{$row}", $items['squares']['total']['rub'][7]['dynamic']['amount'] ?? 0);
        $sheet->setCellValue("AW{$row}", $items['squares']['total']['rub'][7]['dynamic']['square'] ?? 0);

        $sheet->setCellValue("AX{$row}", $items['squares']['total']['rub'][8]['week']['amount'] ?? 0);
        $sheet->setCellValue("AY{$row}", $items['squares']['total']['rub'][8]['week']['square'] ?? 0);
        $sheet->setCellValue("AZ{$row}", $items['squares']['total']['rub'][8]['current']['amount'] ?? 0);
        $sheet->setCellValue("BA{$row}", $items['squares']['total']['rub'][8]['current']['square'] ?? 0);
        $sheet->setCellValue("BB{$row}", $items['squares']['total']['rub'][8]['dynamic']['amount'] ?? 0);
        $sheet->setCellValue("BC{$row}", $items['squares']['total']['rub'][8]['dynamic']['square'] ?? 0);

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

    private function getSquares(int $project_1, int $project_2)
    {
        $prefix = "ReportsModel";
        $date_1 = JDate::getInstance($this->state->get('filter.date_1'));
        $date_2 = JDate::getInstance($this->state->get('filter.date_2'));
        $diff = JDate::getInstance($this->state->get('filter.date_2'))->diff($date_1);
        if ((int) $diff->days > 350) {
            $name = "SquaresByProjects";
            $params = ['project_1' => $project_1, 'project_2' => $project_2, 'date_1' => $date_1, 'date_2' => $date_2];
        }
        else {
            $name = "SquaresByDates";
            $params = ['projectID' => $project_2, 'date_1' => $date_1, 'date_2' => $date_2];
        }
        $model = JModelLegacy::getInstance($name, $prefix, $params);
        $items = $model->getItems();
        return $items ?? [];
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

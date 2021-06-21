<?php
use Joomla\CMS\MVC\Model\ListModel;

defined('_JEXEC') or die;

/**
 * Сравнение проданных элементов по разным проектам
 *
 * @package   cron
 * @since     1.0.0
 */
class ReportsModelWelcome extends ListModel
{
    public function __construct($config = array())
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'manager',
                'projects',
                'date',
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
            ->select("c.id as contractID")
            ->select("if(i.type != 'welcome', 0, 1) as is_welcome")
            ->from("#__mkv_contract_items ci")
            ->leftJoin("#__mkv_price_items i on i.id = ci.itemID")
            ->leftJoin("#__mkv_price_sections ps on ps.id = i.sectionID")
            ->leftJoin("#__mkv_contracts c on c.id = ci.contractID")
            ->leftJoin("#__mkv_companies e on e.id = c.companyID")
            ->leftJoin("#__users u on u.id = c.managerID")
            ->where("(i.square_type is not null or i.type = 'welcome')");
        $project = PrjHelper::getActiveProject();
        $search = $this->setState('filter.search');
        if (!empty($search)) {
            $text = $this->_db->q("%{$search}%");
            $query->where("e.title like {$text}");
        }
        $this->setState('list.limit', 0);
        if (is_numeric($project)) {
            $query->where("c.projectID = {$this->_db->q($project)}");
            $priceID = $this->getPriceID($project);
            if ($priceID > 0) $query->where("ps.priceID = {$this->_db->q($priceID)}");
        }

        return $query;
    }

    public function getItems()
    {
        $result = ['items' => [], 'price' => [], 'stands' => [], 'total' => ['price' => [], 'calculate' => 0, 'print' => 0, 'electron' => 0]];
        $items = parent::getItems();
        $ids = [];

        foreach ($items as $item) {
            if (!in_array($item->contractID, $ids) && $item->contractID != null) $ids[] = $item->contractID;
            if (!isset($result['price'][$item->itemID])) $result['price'][$item->itemID] = $item->item;
            if (!isset($result['items'][$item->companyID])) {
                $result['items'][$item->companyID]['company'] = $item->company;
                $result['items'][$item->companyID]['site'] = $item->site;
                $result['items'][$item->companyID]['site_link'] = JHtml::link(JRoute::_($item->site), $item->site, ['target' => '_blank']);
                $manager = explode(' ', $item->manager);
                $result['items'][$item->companyID]['manager'] = $manager[0];
                $result['items'][$item->companyID]['price'] = [];
                $result['items'][$item->companyID]['print'] = 0;
                $result['items'][$item->companyID]['electron'] = 0;
            }
            if (!isset($result['items'][$item->companyID]['price'][$item->itemID])) $result['items'][$item->companyID]['price'][$item->itemID] = 0;
            $result['items'][$item->companyID]['price'][$item->itemID] += $item->value;
            if ($item->type !== 'welcome') {
                if (!isset($result['items'][$item->companyID]['calculate'])) $result['items'][$item->companyID]['calculate'] = 0;
                switch ($item->square_type) {
                    case 1:
                    case 2:
                    case 4:
                    case 5:
                    case 7:
                    case 8: {
                        $result['items'][$item->companyID]['calculate'] += $item->value;
                        $result['total']['calculate'] += $item->value;
                        break;
                    }
                    case 3:
                    case 6: {
                        $result['items'][$item->companyID]['calculate'] += round($item->value / 2);
                        $result['total']['calculate'] += round($item->value / 2);
                        break;
                    }
                }
            }
            else {
                if (!isset($result['total']['print'])) $result['total']['print'] = 0;
                $result['items'][$item->companyID]['print'] = (float) $item->value;
                $result['total']['print'] += (float) $item->value;
            }
            if (!isset($result['total']['price'][$item->itemID])) $result['total']['price'][$item->itemID] = 0;
            $result['total']['price'][$item->itemID] += $item->value;
        }
        foreach ($result['items'] as $companyID => $arr) {
            $result['items'][$companyID]['electron'] = ($result['items'][$companyID]['calculate'] - $result['items'][$companyID]['print']) ?? 0;
        }
        $result['total']['electron'] = $result['total']['calculate'] - $result['total']['print'];
        $result['stands'] = $this->getStands($ids ?? []);
        $result['contacts'] = $this->getContacts(array_keys($result['items']) ?? []);
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
        header("Content-Disposition: attachment; filename=Welcome.xls");
        $objWriter = PHPExcel_IOFactory::createWriter($xls, 'Excel5');
        $objWriter->save('php://output');
        jexit();
    }

    private function getContacts(array $companyIDs = []): array
    {
        if (empty($companyIDs)) return [];
        JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . "/components/com_companies/models");
        $model = JModelLegacy::getInstance('Contacts', 'CompaniesModel', ['companyIDs' => $companyIDs, 'ignore_request' => true]);
        $contacts = $model->getItems();
        $result = [];
        foreach ($contacts as $contact) {
            $arr = [];
            if (!empty($contact['fio'])) $arr[] = $contact['fio'];
            if (!empty($contact['post'])) $arr[] = $contact['post'];
            if (!empty($contact['phone_work'])) $arr[] = $contact['phone_work'];
            if (!empty($contact['phone_mobile'])) $arr[] = $contact['phone_mobile'];
            if (!empty($contact['email'])) $arr[] = $contact['email'];
            $result[$contact['companyID']][] = implode(', ', $arr);
        }
        foreach (array_keys($result) as $companyID) $result[$companyID] = implode('; ', $result[$companyID]);
        return $result;
    }

    private function getStands(array $contractIDs = []): array
    {
        if (empty($contractIDs)) return [];
        JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . "/components/com_contracts/models");
        $model = JModelLegacy::getInstance('StandsLight', 'ContractsModel', ['contractIDs' => $contractIDs, 'byCompanyID' => true, 'byContractID' => false, 'ignore_request' => true]);
        $stands = $model->getItems();
        foreach (array_keys($stands) as $companyID) $stands[$companyID] = implode(', ', $stands[$companyID]);
        return $stands;
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
        ReportsHelper::check_refresh();
    }

    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search');
        return parent::getStoreId($id);
    }
}

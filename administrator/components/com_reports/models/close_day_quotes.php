<?php
use Joomla\CMS\MVC\Model\ListModel;

defined('_JEXEC') or die;

/**
 * Сравнение проданных элементов по разным проектам
 *
 * @package   cron
 * @since     1.0.0
 */
class ReportsModelClose_day_quotes extends ListModel
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
        $this->heads = [
            'pavilions' => 'COM_REPORTS_HEAD_PAVILION',
            'stands' => 'COM_MKV_HEAD_STANDS',
            'in_pavilion' => 'COM_REPORTS_HEAD_SQUARE_IN_PAVILION',
            'in_street' => 'COM_REPORTS_HEAD_SQUARE_IN_STREET',
            'company' => 'COM_MKV_HEAD_COMPANY',
            'quotes_pavilion' => 'COM_REPORTS_HEAD_QUOTES_IN_PAVILION',
            'quotes_street' => 'COM_REPORTS_HEAD_QUOTES_IN_STREET',
            'quotes_reg' => 'COM_REPORTS_HEAD_QUOTES_IN_REG',
            'total' => 'COM_REPORTS_HEAD_TOTAL',
            'manager' => 'COM_MKV_HEAD_MANAGER',
        ];
        $this->save = JFactory::getApplication()->input->getBool('save', false);
    }

    protected function _getListQuery()
    {
        $query = $this->_db->getQuery(true);
        $query
            ->select("sp.title as pavilion")
            ->select("s.number as stand")
            ->select("sum(if(square_type=1, s.square,if(square_type=2, s.square,if(square_type=5, s.square, if(square_type=7, s.square, if(square_type=8, s.square, 0)))))) as in_pavilion")
            ->select("sum(if(square_type=3, s.square,if(square_type=4, s.square,if(square_type=6, s.square, 0)))) as in_street")
            ->select("e.title as company")
            ->select("u.name as manager")
            ->select("c.companyID")
            ->select("ci.contractID")
            ->from("#__mkv_contract_items ci")
            ->leftJoin("#__mkv_contract_stands cs on ci.contractStandID = cs.id")
            ->leftJoin("#__mkv_price_items pi on ci.itemID = pi.id")
            ->leftJoin("#__mkv_stands s on cs.standID = s.id")
            ->leftJoin("#__mkv_stand_pavilions sp on s.pavilionID = sp.id")
            ->leftJoin("#__mkv_contracts c on ci.contractID = c.id")
            ->leftJoin("#__mkv_companies e on c.companyID = e.id")
            ->leftJoin("#__users u on u.id = c.managerID")
            ->where("(c.projectID = 11 and pi.square_type is not null and s.id is not null)")
            ->group("pavilion, stand, company, manager, c.companyID, ci.contractID")
            ->order("stand");
        $project = PrjHelper::getActiveProject();
        $search = $this->setState('filter.search');
        if (!empty($search)) {
            $text = $this->_db->q("%{$search}%");
            $query->where("(e.title like {$text} or e.title_full like {$text} or stand like {$text})");
        }
        $this->setState('list.limit', 0);
        if (is_numeric($project)) {
            $query->where("c.projectID = {$this->_db->q($project)}");
        }

        return $query;
    }

    public function getItems()
    {
        $result = ['items' => [], 'total' => 0];
        $items = parent::getItems();
        $cid = [];

        foreach ($items as $item) {
            if (!isset($result['items'][$item->companyID])) {
                $result['items'][$item->companyID] = [];
                $result['items'][$item->companyID]['company'] = $item->company;
                $url = JRoute::_("index.php?option=com_contracts&amp;task=contract.edit&amp;id={$item->contractID}");
                $result['items'][$item->companyID]['contract_link'] = JHtml::link($url, $item->company, ['target' => '_blank']);
                $result['items'][$item->companyID]['pavilions'] = [];
                $result['items'][$item->companyID]['stands'] = [];
                $result['items'][$item->companyID]['in_pavilion'] = 0;
                $result['items'][$item->companyID]['in_street'] = 0;
                $result['items'][$item->companyID]['quotes_pavilion'] = 0;
                $result['items'][$item->companyID]['quotes_street'] = 0;
                $result['items'][$item->companyID]['quotes_reg'] = 0;
                $result['items'][$item->companyID]['total'] = 0;
                $result['items'][$item->companyID]['contractID'] = $item->contractID;
                $result['items'][$item->companyID]['manager'] = MkvHelper::getLastAndFirstNames($item->manager);
                if (!in_array($item->contractID, $cid)) $cid[] = $item->contractID;
            }
            $result['items'][$item->companyID]['pavilions'][] = $item->pavilion;
            $result['items'][$item->companyID]['stands'][] = $item->stand;
            if ($item->in_pavilion > 0) {
                $result['items'][$item->companyID]['in_pavilion'] += $item->in_pavilion;
            }
            if ($item->in_street > 0) {
                $result['items'][$item->companyID]['in_street'] += $item->in_street;
            }
            $result['items'][$item->companyID]['total'] += $result['items'][$item->companyID]['quotes_pavilion'] + $result['items'][$item->companyID]['quotes_street'];
        }
        $regs = $this->getRegs($cid);
        foreach ($result['items'] as $companyID => $arr) {
            $result['items'][$companyID]['pavilions'] = implode(', ', $result['items'][$companyID]['pavilions']);
            $result['items'][$companyID]['stands'] = implode(', ', $result['items'][$companyID]['stands']);
            $result['items'][$companyID]['quotes_pavilion'] = ($result['items'][$companyID]['in_pavilion'] <= 30) ? ($result['items'][$companyID]['in_pavilion']) > 0 ? 2 : 0 : round($result['items'][$companyID]['in_pavilion'] / 15);
            $result['items'][$companyID]['quotes_street'] = ($result['items'][$companyID]['in_street'] <= 30) ? ($result['items'][$companyID]['in_street'] > 0) ? 2 : 0 : round($result['items'][$companyID]['in_street'] / 30);
            $result['items'][$companyID]['quotes_reg'] = $regs[$arr['contractID']] ?? 0;
            $result['items'][$companyID]['total'] = $result['items'][$companyID]['quotes_pavilion'] + $result['items'][$companyID]['quotes_street'] + $result['items'][$companyID]['quotes_reg'];
            $result['total'] += $result['items'][$companyID]['total'];
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
        $width = ["A" => 12, "B" => 13, "C" => 14, "D" => 14, "E" => 96, "F" => 12, "G" => 12, "H" => 13, "I" => 13, "J" => 20];
        foreach ($width as $col => $value) $sheet->getColumnDimension($col)->setWidth($value);

        //Заголовки
        $j = 0;
        foreach ($this->heads as $item => $head) $sheet->setCellValueByColumnAndRow($j++, 1, JText::sprintf($head));

        $sheet->setTitle(JText::sprintf('COM_REPORTS_MENU_CLOSE_DAY_QUOTES'));

        //Итого
        $sheet->mergeCells("A2:H2");
        $sheet->setCellValueExplicit("A2", JText::sprintf('COM_REPORTS_HEAD_TOTAL'), PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->setCellValueExplicit("I2", $items['total'], PHPExcel_Cell_DataType::TYPE_STRING);
        //Данные
        $row = 3; //Строка, с которой начнаются данные
        $col = 0;
        foreach ($items['items'] as $companyID => $item) {
            foreach ($this->heads as $elem => $head) {
                $sheet->setCellValueExplicitByColumnAndRow($col++, $row, $item[$elem], PHPExcel_Cell_DataType::TYPE_STRING);
            }
            $col = 0;
            $row++;
        }
        if (!$this->save) {
            header("Expires: Mon, 1 Apr 1974 05:00:00 GMT");
            header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
            header("Cache-Control: no-cache, must-revalidate");
            header("Pragma: public");
            header("Content-type: application/vnd.ms-excel");
            header("Content-Disposition: attachment; filename=Close_day_quotes.xls");
        }
        $objWriter = PHPExcel_IOFactory::createWriter($xls, 'Excel5');
        $t = time();
        $path_full = JPATH_SITE . "/cron/Close_day_quotes_{$t}.xls";
        $filename = "Close_day_quotes_{$t}.xls";
        $objWriter->save((!$this->save) ? 'php://output' : $path_full);
        if ($this->save) {
            jimport('joomla.mail.helper');
            $mailer = JFactory::getMailer();
            $mailer->addAttachment($path_full, $filename);
            $mailer->isHtml(true);
            $mailer->Encoding = 'base64';
            $mailer->addRecipient("asharikov@icecompany.org", "Антон Михайлович");
            $mailer->setFrom("xakepok@xakepok.com", "MKV");
            $mailer->setBody("Во вложении");
            $mailer->setSubject("Report: Close day quotes " . JFactory::getDate()->format("Y-m-d"));
            var_dump($mailer->Send());
            unlink($path_full);
        }
        jexit();
    }

    //Получаем рег взносы
    private function getRegs(array $contractIDs = []): array
    {
        if (empty($contractIDs)) return [];
        $ids = implode(', ', $contractIDs);
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query
            ->select("ci.contractID, sum(ci.value) as reg")
            ->from("#__mkv_contract_items ci")
            ->leftJoin("#__mkv_price_items pi on pi.id = ci.itemID")
            ->where("pi.type like 'reg'")
            ->where("ci.contractID in ({$ids})")
            ->group("ci.contractID");
        $items = $db->setQuery($query)->loadAssocList('contractID');
        if (empty($items)) return [];
        $result = [];
        foreach ($items as $contractID => $item) $result[$contractID] = ($item['reg'] > 0) ? $item['reg'] - 1 : 0;
        return $result;
    }

    /* Сортировка по умолчанию */
    protected function populateState($ordering = 'stand', $direction = 'ASC')
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

    private $heads, $save;
}

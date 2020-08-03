<?php
use Joomla\CMS\MVC\Model\ListModel;

defined('_JEXEC') or die;

class ReportsModelEquipments extends ListModel
{
    public function __construct($config = array())
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'pi.title',
                'date_1',
                'date_2',
                'itemID',
            );
        }
        parent::__construct($config);
        $input = JFactory::getApplication()->input;
        $format = $input->getString('format', 'html');
        $this->export = ($format !== 'html');
    }

    protected function _getListQuery()
    {
        $query = $this->_db->getQuery(true);
        $query
            ->select("pi.title as item")
            ->select("s.dat, s.value")
            ->from("#__mkv_sales s")
            ->leftJoin("#__mkv_projects p on p.id = s.projectID")
            ->leftJoin("#__mkv_price_items pi on pi.id = s.itemID");

        $projectID = PrjHelper::getActiveProject();
        if (is_numeric($projectID)) {
            $query->where("s.projectID = {$this->_db->q($projectID)}");
        }
        $date_1 = $this->getState('filter.date_1');
        $date_2 = $this->getState('filter.date_2');
        if (!empty($date_1) && $date_1 !== '0000-00-00') {
            $date_1 = JDate::getInstance($date_1)->format("Y-m-d");
            if (!empty($date_2) && $date_2 !== '0000-00-00') {
                $date_2 = JDate::getInstance($date_2)->format("Y-m-d");
                $query->where("(s.dat like {$this->_db->q($date_1)} or s.dat like {$this->_db->q($date_2)})");
            }
            else $query->where("s.dat like {$this->_db->q($date_1)}");
        }
        $search = $this->setState('filter.search');
        if (!empty($search)) {
            $text = $this->_db->q("%{$search}%");
            $query->where("pi.title like {$text}");
        }
        $limit = (!$this->export) ? 100 : 0;
        $this->setState('list.limit', $limit);

        return $query;
    }

    public function getItems()
    {
        $projects = $this->getState('filter.projects');
        $result = ['items' => [], 'projects' => $this->getProjects($projects)];
        $items = parent::getItems();
        $return = PrjHelper::getReturnUrl();

        foreach ($items as $item) {
            if (!isset($result['items'][$item->companyID])) {
                $result['items'][$item->companyID] = [];
                $url = JRoute::_("index.php?option=com_companies&amp;task=company.edit&amp;id={$item->companyID}&amp;return={$return}");
                $result['items'][$item->companyID]['company_link'] = JHtml::link($url, $item->company);
                $result['items'][$item->companyID]['company'] = $item->company;
                $result['items'][$item->companyID]['id'] = $item->companyID;
            }
            if (!$this->export) {
                $url = JRoute::_("index.php?option=com_contracts&amp;task=contract.edit&amp;id={$item->contractID}&amp;return={$return}");
                if (!empty($item->contractID)) $item->status = JHtml::link($url, $item->status ?? JText::sprintf('COM_MKV_STATUS_IN_PROJECT'));
            }
            if (!empty($item->contractID)) $result['items'][$item->companyID][$item->projectID][] = $item->status ?? JText::sprintf('COM_MKV_STATUS_IN_PROJECT');
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
        $width = ["A" => 60];
        foreach ($width as $col => $value) $sheet->getColumnDimension($col)->setWidth($value);

        $sheet->setCellValue("A1", JText::sprintf('COM_MKV_HEAD_COMPANY'));
        $col = 1;
        foreach ($items['projects'] as $projectID => $project) {
            $sheet->setCellValueByColumnAndRow($col, 1, $project);
            $col++;
        }

        $sheet->setTitle(JText::sprintf('COM_REPORTS_MENU_COMPANIES_CONTRACT_STATUSES_FOR_EXPORT'));

        //Данные. Один проход цикла - одна строка
        $row = 2; //Строка, с которой начнаются данные
        $col = 1;
        foreach ($items['items'] as $companyID => $company) {
            $sheet->setCellValue("A{$row}", $company['company']);
            foreach ($items['projects'] as $projectID => $project) {
                $sheet->setCellValueByColumnAndRow($col, $row, implode(', ', $items['items'][$companyID][$projectID]));
                $col++;
            }
            $row++;
            $col = 1;
        }
        header("Expires: Mon, 1 Apr 1974 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
        header("Cache-Control: no-cache, must-revalidate");
        header("Pragma: public");
        header("Content-type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=Contracts_statuses.xls");
        $objWriter = PHPExcel_IOFactory::createWriter($xls, 'Excel5');
        $objWriter->save('php://output');
        jexit();
    }

    /* Сортировка по умолчанию */
    protected function populateState($ordering = 'pi.title', $direction = 'ASC')
    {
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);
        $date_1 = $this->getUserStateFromRequest($this->context . '.filter.date_1', 'filter_date_1', JDate::getInstance()->format("Y-m-d"));
        $this->setState('filter.date_1', $date_1);
        $date_2 = $this->getUserStateFromRequest($this->context . '.filter.date_2', 'date_2');
        $this->setState('filter.date_2', $date_2);
        $itemID = $this->getUserStateFromRequest($this->context . '.filter.itemID', 'filter_itemID');
        $this->setState('filter.itemID', $itemID);
        PrjHelper::check_refresh();
    }

    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.itemID');
        $id .= ':' . $this->getState('filter.date_1');
        $id .= ':' . $this->getState('filter.date_2');
        return parent::getStoreId($id);
    }

    private $heads, $export;
}

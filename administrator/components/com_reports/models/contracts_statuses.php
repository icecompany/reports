<?php
use Joomla\CMS\MVC\Model\ListModel;

defined('_JEXEC') or die;

/**
 * Сравнение проданных элементов по разным проектам
 *
 * @package   reports
 * @since     1.0.0
 */
class ReportsModelContracts_statuses extends ListModel
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
        $input = JFactory::getApplication()->input;
        $format = $input->getString('format', 'html');
        $this->export = ($format !== 'html');
    }

    protected function _getListQuery()
    {
        $query = $this->_db->getQuery(true);
        $query
            ->select("e.title as company, e.id as companyID")
            ->select("c.id as contractID, c.projectID")
            ->select("s.title as status")
            ->from("#__mkv_companies e")
            ->leftJoin("#__mkv_contracts c on c.companyID = e.id")
            ->leftJoin("#__mkv_contract_statuses s on s.code = c.status");
        $projects = $this->getState('filter.projects');
        if (is_numeric($projects)) {
            $ids = implode(', ', $projects);
            $query->where("c.projectID in ($ids)");
        }
        $search = $this->setState('filter.search');
        if (!empty($search)) {
            $text = $this->_db->q("%{$search}%");
            $query->where("e.title like {$text}");
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

    private function getProjects(array $projectIDs): array
    {
        if (empty($projectIDs)) return [];
        JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . "/components/com_prj/models", "PrjModel");
        $model = JModelLegacy::getInstance('Projects', 'PrjModel', ['ids' => $projectIDs, 'ignore_request' => true]);
        $items = $model->getItems();
        $projects = [];
        foreach ($items['items'] as $item) $projects[$item['id']] = $item['title'];
        return $projects;
    }

    /* Сортировка по умолчанию */
    protected function populateState($ordering = 'e.title', $direction = 'ASC')
    {
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);
        $projects = $this->getUserStateFromRequest($this->context . '.filter.projects', 'filter_projects', [5, 11]);
        $this->setState('filter.projects', $projects);
        PrjHelper::check_refresh();
    }

    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.projects');
        return parent::getStoreId($id);
    }

    private $export;
}

<?php
use Joomla\CMS\MVC\Model\ListModel;

defined('_JEXEC') or die;

/**
 * Сравнение проданных элементов по разным проектам
 *
 * @package   reports
 * @since     1.0.0
 */
class ReportsModelCompanies extends ListModel
{
    public function __construct($config = array())
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'search',
                'manager',
                'fields',
                'status',
                'item',
            );
        }
        $format = JFactory::getApplication()->input->getString('format', 'html');
        parent::__construct($config);
        $this->export = ($format === 'xls');
        $this->heads = ReportsHelper::getHeads();
    }

    protected function _getListQuery()
    {
        $query = $this->_db->getQuery(true);
        $query
            ->select("e.title as company, c.id as contractID, c.companyID")
            ->from("#__mkv_contracts c")
            ->leftJoin("#__mkv_contract_incoming_info cii on cii.contractID = c.id")
            ->leftJoin("#__mkv_companies e on e.id = c.companyID");

        $fields = $this->getState('filter.fields');
        if (!empty($fields) && is_array($fields)) {
            if (in_array('company_full', $fields)) {
                $query->select("e.title_full as company_full");
            }
            if (in_array('doc_status', $fields)) {
                $query->select("cii.doc_status");
            }
            if (in_array('director_name', $fields)) {
                $query->select("e.director_name");
            }
            if (in_array('director_post', $fields)) {
                $query->select("e.director_post");
            }
            if (in_array('phones', $fields)) {
                $query->select("e.phone_1, e.phone_1_additional, e.phone_2, e.phone_2_additional");
            }
            if (in_array('manager', $fields)) {
                $query
                    ->select("u.name as manager")
                    ->leftJoin("#__users u on u.id = c.managerID");
            }
            if (in_array('contract_amount', $fields)) {
                $query->select("c.amount as contract_amount");
            }
            if (in_array('contract_payments', $fields)) {
                $query->select("c.payments as contract_payments");
            }
            if (in_array('contract_debt', $fields)) {
                $query->select("c.debt as contract_debt");
            }
            if (in_array('email', $fields)) {
                $query->select("e.email");
            }
            if (in_array('site', $fields)) {
                $query->select("e.site");
            }
            if (in_array('address_legal', $fields)) {
                $query
                    ->select("concat_ws(', ', cntr_l.name, reg_l.name, city_l.name, e.legal_index, e.legal_street, e.legal_house) as address_legal")
                    ->leftJoin("#__grph_cities city_l on city_l.id = e.legal_city")
                    ->leftJoin("#__grph_regions reg_l on reg_l.id = city_l.region_id")
                    ->leftJoin("#__grph_countries cntr_l on cntr_l.id = reg_l.country_id");
            }
            if (in_array('address_legal', $fields)) {
                $query
                    ->select("concat_ws(', ', cntr_f.name, reg_f.name, city_f.name, e.fact_index, e.fact_street, e.fact_house) as address_fact")
                    ->leftJoin("#__grph_cities city_f on city_f.id = e.fact_city")
                    ->leftJoin("#__grph_regions reg_f on reg_f.id = city_f.region_id")
                    ->leftJoin("#__grph_countries cntr_f on cntr_f.id = reg_f.country_id");
            }
            if (in_array('contract_status', $fields)) {
                $query
                    ->select("s.title as contract_status")
                    ->leftJoin("#__mkv_contract_statuses s on s.code = c.status");
            }
            if (in_array('contract_number', $fields)) {
                $query
                    ->select("ifnull(c.number_free, c.number) as contract_number");
            }
            if (in_array('contract_date', $fields)) {
                $query
                    ->select("c.dat as contract_date");
            }
        }

        $project = PrjHelper::getActiveProject();
        if (is_numeric($project)) {
            $query->where("c.projectID = {$this->_db->q($project)}");
        }
        $manager = $this->getState('filter.manager');
        if (is_numeric($manager)) {
            $query
                ->where("c.managerID = {$this->_db->q($manager)}");
        }
        $status = $this->getState('filter.status');
        if (is_array($status) && !empty($status)) {
            $statuses = implode(", ", $status);
            if (in_array(101, $status)) {
                $query->where("(c.status in ({$statuses}) or c.status is null)");
            } else {
                $query->where("c.status in ({$statuses})");
            }
        }
        $search = $this->setState('filter.search');
        if (!empty($search)) {
            $text = $this->_db->q("%{$search}%");
            $query->where("(e.title like {$text} or e.title_full like {$text})");
        }
        $item = $this->getState('filter.item');
        if (is_array($item) && !empty($item)) {
            $item_ids = implode(', ', $item);
            $query
                ->leftJoin("#__mkv_contract_items ci on ci.contractID = c.id")
                ->where("ci.itemID in ({$item_ids})");
        }
        $limit = (!$this->export) ? $this->getState('list.limit') : 0;
        $this->setState('list.limit', $limit);

        /* Сортировка */
        $orderCol = $this->state->get('list.ordering', 'e.title');
        $orderDirn = $this->state->get('list.direction', 'ASC');
        $query->order($this->_db->escape($orderCol . ' ' . $orderDirn));

        return $query;
    }

    public function getItems()
    {
        $fields = $this->getState('filter.fields');
        $result = ['items' => []];
        $items = parent::getItems();
        $companyIDs = [];

        foreach ($items as $item) {
            if (!isset($result['items'][$item->contractID])) {
                if (array_search($item->companyID, $companyIDs) === false) $companyIDs[] = $item->companyID;
                $result['items'][$item->contractID] = [];
                $result['items'][$item->contractID]['company'] = $item->company;
                $result['items'][$item->contractID]['companyID'] = $item->companyID;
                if (!empty($fields) && is_array($fields)) {
                    if (in_array('company_full', $fields)) {
                        $result['items'][$item->contractID]['company_full'] = $item->company_full;
                    } else unset($this->heads['company_full']);
                    if (in_array('director_name', $fields)) {
                        $result['items'][$item->contractID]['director_name'] = $item->director_name;
                    } else unset($this->heads['director_name']);
                    if (in_array('director_post', $fields)) {
                        $result['items'][$item->contractID]['director_post'] = $item->director_post;
                    } else unset($this->heads['director_post']);
                    if (in_array('doc_status', $fields)) {
                        $result['items'][$item->contractID]['doc_status'] = JText::sprintf("COM_CONTRACTS_DOC_STATUS_{$item->doc_status}_SHORT");
                    } else unset($this->heads['doc_status']);
                    if (in_array('phones', $fields)) {
                        $phones = [];
                        if (!empty($item->phone_1)) $phones[] = (!empty($phone_1_additional)) ? JText::sprintf('COM_REPORTS_HEAD_PHONE_FULL', $item->phone_1, $item->phone_1_additional) : $item->phone_1;
                        if (!empty($item->phone_2)) $phones[] = (!empty($phone_2_additional)) ? JText::sprintf('COM_REPORTS_HEAD_PHONE_FULL', $item->phone_2, $item->phone_2_additional) : $item->phone_2;
                        $result['items'][$item->contractID]['phones'] = implode(', ', $phones);
                    } else unset($this->heads['phones']);
                    if (in_array('contract_amount', $fields)) {
                        $result['items'][$item->contractID]['contract_amount'] = $item->contract_amount;
                    } else unset($this->heads['contract_amount']);
                    if (in_array('contract_payments', $fields)) {
                        $result['items'][$item->contractID]['contract_payments'] = $item->contract_payments;
                    } else unset($this->heads['contract_payments']);
                    if (in_array('contract_debt', $fields)) {
                        $result['items'][$item->contractID]['contract_debt'] = $item->contract_debt;
                    } else unset($this->heads['contract_debt']);
                    if (in_array('manager', $fields)) {
                        $result['items'][$item->contractID]['manager'] = MkvHelper::getLastAndFirstNames($item->manager);
                    } else unset($this->heads['manager']);
                    if (in_array('email', $fields)) {
                        $result['items'][$item->contractID]['email'] = $item->email;
                    } else unset($this->heads['email']);
                    if (in_array('site', $fields)) {
                        $result['items'][$item->contractID]['site'] = $item->site;
                    } else unset($this->heads['site']);
                    if (in_array('address_legal', $fields)) {
                        $result['items'][$item->contractID]['address_legal'] = $item->address_legal;
                    } else unset($this->heads['address_legal']);
                    if (in_array('address_fact', $fields)) {
                        $result['items'][$item->contractID]['address_fact'] = $item->address_fact;
                    } else unset($this->heads['address_fact']);
                    if (in_array('contract_status', $fields)) {
                        $result['items'][$item->contractID]['contract_status'] = $item->contract_status ?? JText::sprintf('COM_MKV_STATUS_IN_PROJECT');
                    } else unset($this->heads['contract_status']);
                    if (in_array('contract_number', $fields)) {
                        $result['items'][$item->contractID]['contract_number'] = $item->contract_number;
                    } else unset($this->heads['contract_number']);
                    if (in_array('contract_date', $fields)) {
                        $result['items'][$item->contractID]['contract_date'] = (!empty($item->contract_date)) ? JDate::getInstance($item->contract_date)->format("d.m.Y") : '';
                    } else unset($this->heads['contract_date']);
                }
            }
        }
        if (in_array('stands', $fields)) {
            $stands = $this->getStands(array_keys($result['items'] ?? []));
            foreach ($result['items'] as $contractID => $item) $result['items'][$contractID]['stands'] = $stands[$contractID];
        } else unset($this->heads['stands']);
        if (in_array('contacts', $fields)) {
            $contacts = $this->getContacts($companyIDs ?? []);
            foreach ($result['items'] as $contractID => $item) $result['items'][$contractID]['contacts'] = $contacts[$item['companyID']];
        } else unset($this->heads['contacts']);
        if (in_array('thematics', $fields)) {
            $thematics = $this->getThematics(array_keys($result['items'] ?? []));
            foreach ($result['items'] as $contractID => $item) $result['items'][$contractID]['thematics'] = $thematics[$contractID];
        } else unset($this->heads['thematics']);
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
        $j = 0;
        foreach ($this->heads as $item => $head) $sheet->setCellValueByColumnAndRow($j++, 1, JText::sprintf($head));

        $sheet->setTitle(JText::sprintf('COM_REPORTS_MENU_COMPANIES'));

        //Данные
        $row = 2; //Строка, с которой начнаются данные
        $col = 0;
        foreach ($items['items'] as $contractID => $item) {
            foreach ($this->heads as $elem => $head) {
                $sheet->setCellValueExplicitByColumnAndRow($col++, $row, $item[$elem], PHPExcel_Cell_DataType::TYPE_STRING);
            }
            $col = 0;
            $row++;
        }
        header("Expires: Mon, 1 Apr 1974 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
        header("Cache-Control: no-cache, must-revalidate");
        header("Pragma: public");
        header("Content-type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=Companies.xls");
        $objWriter = PHPExcel_IOFactory::createWriter($xls, 'Excel5');
        $objWriter->save('php://output');
        jexit();
    }

    public function saveReport() {
        //Массив с состоянием фильтров
        $preset = [];
        $preset['manager'] = $this->getFilterForm()->getValue('manager', 'filter');
        $preset['item'] = $this->getFilterForm()->getValue('item', 'filter');
        $preset['status'] = $this->getFilterForm()->getValue('status', 'filter');
        $preset['fields'] = $this->getFilterForm()->getValue('fields', 'filter');

        $userID = JFactory::getUser()->id;

        //Массив для вставки в базу
        $data = [];
        $data['params'] = json_encode($preset);
        $data['managerID'] = $userID;
        $data['type'] = JFactory::getApplication()->input->getString('view');

        //Проверка уже существующих отчётов на наличие такого же
        $table = parent::getTable('Reports', 'TableReports');
        $table->load($data);
        if ($table->id !== null) {
            $message = JText::sprintf('COM_REPORTS_MSG_REPORT_ALREADY_EXISTS', $table->title);
            $type = 'warning';
        }
        else {
            $table->save($data);
            $data['id'] = $table->id;
            $data['title'] = JText::sprintf('COM_REPORTS_NEW_REPORT_TITLE', $table->id);
            $data['type_show'] = JText::sprintf('COM_REPORTS_MENU_COMPANIES');
            $table->save($data);
            $message = JText::sprintf('COM_REPORTS_MSG_REPORT_SAVE', $table->id);
            $type = 'message';
        }
        $uri = JUri::getInstance($_SERVER['HTTP_REFERER']);
        JFactory::getApplication()->enqueueMessage($message, $type);
        JFactory::getApplication()->redirect($uri->toString());
    }

    public function getReport() {
        $reportID = JFactory::getApplication()->input->getInt('reportID', 0);
        if ($reportID === 0) return;
        $table = JTable::getInstance('Reports', 'TableReports');
        $table->load($reportID);
        return $table;
    }

    private function getStands(array $ids = []): array
    {
        if (empty($ids)) return [];
        JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . "/components/com_contracts/models", "ContractsModel");
        $model = JModelLegacy::getInstance('StandsLight', 'ContractsModel', ['contractIDs' => $ids, 'byContractID' => true, 'byCompanyID' => false]);
        $items = $model->getItems();
        $result = [];
        $tmp = [];
        foreach ($items as $contractID => $data) {
            foreach ($data as $item) $tmp[$contractID][] = $item['number'];
        }
        foreach ($tmp as $contractID => $stand) $result[$contractID] = implode(', ', $stand);
        return $result;
    }

    private function getThematics(array $ids = []): array
    {
        if (empty($ids)) return [];
        JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . "/components/com_contracts/models", "ContractsModel");
        $model = JModelLegacy::getInstance('Thematics', 'ContractsModel', ['contractIDs' => $ids]);
        $items = $model->getItems();
        $result = [];
        foreach ($items as $contractID => $thematics) $result[$contractID] = implode(', ', $thematics);
        return $result;
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

    /* Сортировка по умолчанию */
    protected function populateState($ordering = 'e.title', $direction = 'ASC')
    {
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);
        $manager = $this->getUserStateFromRequest($this->context . '.filter.manager', 'filter_manager');
        $this->setState('filter.manager', $manager);
        $status = $this->getUserStateFromRequest($this->context . '.filter.status', 'filter_status');
        $this->setState('filter.status', $status);
        $fields = $this->getUserStateFromRequest($this->context . '.filter.fields', 'filter_fields');
        $this->setState('filter.fields', $fields);
        $item = $this->getUserStateFromRequest($this->context . '.filter.item', 'filter_item');
        $this->setState('filter.item', $item);
        parent::populateState($ordering, $direction);
        ReportsHelper::check_refresh();
    }

    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.manager');
        $id .= ':' . $this->getState('filter.status');
        $id .= ':' . $this->getState('filter.fields');
        $id .= ':' . $this->getState('filter.item');
        return parent::getStoreId($id);
    }

    private $export, $heads;
}

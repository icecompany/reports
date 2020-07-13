<?php
use Joomla\CMS\HTML\HTMLHelper;

defined('_JEXEC') or die;

class ReportsHelper
{
	public function addSubmenu($vName)
	{
		HTMLHelper::_('sidebar.addEntry', JText::sprintf('COM_REPORTS'), 'index.php?option=com_reports&view=reports', $vName === 'reports');
		HTMLHelper::_('sidebar.addEntry', JText::sprintf('COM_REPORTS_MENU_COMPANIES'), 'index.php?option=com_reports&view=companies', $vName === 'companies');
		HTMLHelper::_('sidebar.addEntry', JText::sprintf('COM_REPORTS_MENU_CLOSE_DAY_QUOTES'), 'index.php?option=com_reports&view=close_day_quotes', $vName === 'close_day_quotes');
		HTMLHelper::_('sidebar.addEntry', JText::sprintf('COM_REPORTS_MENU_WELCOME'), 'index.php?option=com_reports&view=welcome', $vName === 'welcome');
        HTMLHelper::_('sidebar.addEntry', JText::sprintf('COM_REPORTS_MENU_INVITES'), 'index.php?option=com_reports&view=invites', $vName === 'invites');
        HTMLHelper::_('sidebar.addEntry', JText::sprintf('COM_REPORTS_MENU_COMPANIES_CONTRACT_STATUSES'), 'index.php?option=com_reports&view=contracts_statuses', $vName === 'contracts_statuses');
        PrjHelper::addActiveProjectFilter();
	}

    public static function getHeads(string $type = 'companies'): array
    {
        $heads['companies'] = [
            'company' => 'COM_MKV_HEAD_COMPANY',
            'company_full' => 'COM_REPORTS_HEAD_COMPANY_TITLE_FULL',
            'stands' => 'COM_MKV_HEAD_STANDS',
            'manager' => 'COM_MKV_HEAD_MANAGER',
            'contract_status' => 'COM_MKV_HEAD_CONTRACT_STATUS',
            'contract_number' => 'COM_MKV_HEAD_CONTRACT_NUMBER',
            'contract_date' => 'COM_MKV_HEAD_CONTRACT_DATE',
            'doc_status' => 'COM_REPORTS_HEAD_DOC_STATUS',
            'director_name' => 'COM_REPORTS_HEAD_COMPANY_DIRECTOR_NAME',
            'director_post' => 'COM_REPORTS_HEAD_COMPANY_DIRECTOR_POST',
            'contract_amount' => 'COM_MKV_HEAD_AMOUNT',
            'contract_payments' => 'COM_MKV_HEAD_PAYED',
            'contract_debt' => 'COM_MKV_HEAD_DEBT',
            'email' => 'COM_REPORTS_HEAD_EMAIL',
            'site' => 'COM_REPORTS_HEAD_SITE',
            'phones' => 'COM_REPORTS_HEAD_PHONES',
            'contacts' => 'COM_REPORTS_HEAD_CONTACTS',
            'address_legal' => 'COM_REPORTS_HEAD_ADDRESS_LEGAL',
            'address_fact' => 'COM_REPORTS_HEAD_ADDRESS_FACT',
            'thematics' => 'COM_REPORTS_HEAD_THEMATICS',
        ];
        return $heads[$type];
	}

    /**
     * Проверяет необходимость перезагрузить страницу. Используется для возврата на предыдущую страницу при отправке формы в админке
     * @throws Exception
     * @since 1.0.4
     */
    public static function check_refresh(): void
    {
        $refresh = JFactory::getApplication()->input->getBool('refresh', false);
        if ($refresh) {
            $current = JUri::getInstance(self::getCurrentUrl());
            $current->delVar('refresh');
            JFactory::getApplication()->redirect($current);
        }
    }

    /**
     * Возвращает параметр ID из реферера
     * @since 1.0.1
     * @return int ID Элемента
     */
    public static function getItemID(): int
    {
        $uri = JUri::getInstance($_SERVER['HTTP_REFERER']);
        return (int) $uri->getVar('id') ?? 0;
    }

    /**
     * Возвращает URL для обработки формы
     * @return string
     * @since 1.0.0
     * @throws
     */
    public static function getActionUrl(): string
    {
        $uri = JUri::getInstance();
        $uri->setVar('refresh', '1');
        $query = $uri->getQuery();
        $client = (!JFactory::getApplication()->isClient('administrator')) ? 'site' : 'administrator';
        return JRoute::link($client, "index.php?{$query}");
    }

    /**
     * Возвращает текущий URL
     * @return string
     * @since 1.0.0
     * @throws
     */
    public static function getCurrentUrl(): string
    {
        $uri = JUri::getInstance();
        $query = $uri->getQuery();
        return "index.php?{$query}";
    }

    /**
     * Возвращает URL для возврата (текущий адрес страницы)
     * @return string
     * @since 1.0.0
     */
    public static function getReturnUrl(): string
    {
        $uri = JUri::getInstance();
        $query = $uri->getQuery();
        return base64_encode("index.php?{$query}");
    }

    /**
     * Возвращает URL для обработки формы левой панели
     * @return string
     * @since 1.0.0
     */
    public static function getSidebarAction():string
    {
        $return = self::getReturnUrl();
        return JRoute::_("index.php?return={$return}");
    }

    public static function canDo(string $action): bool
    {
        return JFactory::getUser()->authorise($action, 'com_reports');
    }

    public static function getConfig(string $param, $default = null)
    {
        $config = JComponentHelper::getParams("com_reports");
        return $config->get($param, $default);
    }
}

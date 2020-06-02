<?php
use Joomla\CMS\MVC\View\HtmlView;

defined('_JEXEC') or die;

class ReportsViewContracts_statuses extends HtmlView
{
    protected $sidebar = '';
    public $items, $pagination, $state, $filterForm, $activeFilters;

    public function display($tpl = null)
    {
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->state = $this->get('State');
        $this->filterForm = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

        $this->filterForm->setValue('projects', 'filter', $this->state->get('filter.projects'));
        $this->filterForm->addFieldPath(JPATH_ADMINISTRATOR . "/components/com_prj/models/fields");

        // Show the toolbar
        $this->toolbar();

        // Show the sidebar
        ReportsHelper::addSubmenu('contracts_statuses');
        $this->sidebar = JHtmlSidebar::render();

        // Display it all
        return parent::display($tpl);
    }

    private function toolbar()
    {
        JToolBarHelper::title(JText::sprintf('COM_REPORTS_MENU_COMPANIES_CONTRACT_STATUSES'), 'tree-2');
        JToolbarHelper::custom('contract_statuses.download', 'download', 'download', JText::sprintf('COM_MKV_BUTTON_EXPORT_TO_EXCEL'), false);
        if (ReportsHelper::canDo('core.admin'))
        {
            JToolBarHelper::preferences('com_reports');
        }
    }
}

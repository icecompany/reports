<?php
use Joomla\CMS\MVC\View\HtmlView;

defined('_JEXEC') or die;

class ReportsViewP2 extends HtmlView
{
    protected $sidebar = '';
    public $items, $pagination, $state, $filterForm, $activeFilters, $tableHeads, $tableFooterColspan;

    public function display($tpl = null)
    {
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->state = $this->get('State');
        $this->filterForm = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');
        $this->tableHeads = $this->get('TableHeads');
        $this->tableFooterColspan = $this->get('TableFooterColspan');

        // Show the toolbar
        $this->toolbar();

        // Show the sidebar
        ReportsHelper::addSubmenu('p2');
        $this->sidebar = JHtmlSidebar::render();

        // Display it all
        return parent::display($tpl);
    }

    private function toolbar()
    {
        JToolBarHelper::title(JText::sprintf('COM_REPORTS_MENU_QUARTER_P2'), 'list-2');
        JToolbarHelper::custom('p2.download', 'download', 'download', JText::sprintf('COM_MKV_BUTTON_EXPORT_TO_EXCEL'), false);
        if (ReportsHelper::canDo('core.admin'))
        {
            JToolBarHelper::preferences('com_reports');
        }
    }
}

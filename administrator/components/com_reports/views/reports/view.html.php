<?php
use Joomla\CMS\MVC\View\HtmlView;

defined('_JEXEC') or die;

class ReportsViewReports extends HtmlView
{
    protected $sidebar = '';
    public $items, $pagination, $uid, $state, $filterForm, $activeFilters;

    public function display($tpl = null)
    {
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->state = $this->get('State');
        $this->filterForm = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

        // Show the toolbar
        $this->toolbar();

        // Show the sidebar
        ReportsHelper::addSubmenu('reports');
        $this->sidebar = JHtmlSidebar::render();

        // Display it all
        return parent::display($tpl);
    }

    private function toolbar()
    {
        JToolBarHelper::title(JText::sprintf('COM_REPORTS_MENU_MY_REPORTS'), 'flash');

        if (ReportsHelper::canDo('core.edit'))
        {
            JToolbarHelper::editList('report.edit');
        }
        if (ReportsHelper::canDo('core.delete'))
        {
            JToolbarHelper::deleteList('COM_REPORTS_DELETE_QUESTION_REPORT', 'cron.delete');
        }
        if (ReportsHelper::canDo('core.admin'))
        {
            JToolBarHelper::preferences('com_reports');
        }
    }
}

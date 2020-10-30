<?php
use Joomla\CMS\MVC\View\HtmlView;

defined('_JEXEC') or die;

class ReportsViewSentInvites extends HtmlView
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
        ReportsHelper::addSubmenu('SentInvites');
        $this->sidebar = JHtmlSidebar::render();

        // Display it all
        return parent::display($tpl);
    }

    private function toolbar()
    {
        JToolBarHelper::title(JText::sprintf('COM_REPORTS_MENU_SENT_INVITES'), 'signup');
        JToolbarHelper::custom('sentInvites.download', 'download', 'download', JText::sprintf('COM_MKV_BUTTON_EXPORT_TO_EXCEL'), false);
        if (ReportsHelper::canDo('core.admin'))
        {
            JToolBarHelper::preferences('com_reports');
        }
    }
}

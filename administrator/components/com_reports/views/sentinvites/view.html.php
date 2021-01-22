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

        $this->filterForm->setValue('date_1', 'filter', $this->state->get('filter.date_1'));
        $this->filterForm->setValue('date_2', 'filter', $this->state->get('filter.date_2'));
        $this->filterForm->setValue('cron_interval', 'filter', $this->state->get('filter.cron_interval'));

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
        JToolbarHelper::custom('sentInvites.save_report', 'plus', 'plus', JText::sprintf('COM_REPORTS_BUTTON_SAVE_REPORT'), false);
        if (ReportsHelper::canDo('core.admin'))
        {
            JToolBarHelper::preferences('com_reports');
        }
    }
}

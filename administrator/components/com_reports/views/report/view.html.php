<?php
defined('_JEXEC') or die;
use Joomla\CMS\MVC\View\HtmlView;

class ReportsViewReport extends HtmlView {
    protected $item, $form, $script;

    public function display($tmp = null) {
        $this->form = $this->get('Form');
        $this->item = $this->get('Item');
        $this->script = $this->get('Script');

        $this->addToolbar();
        $this->setDocument();

        parent::display($tmp);
    }

    protected function addToolbar() {
	    JToolBarHelper::apply('report.apply', 'JTOOLBAR_APPLY');
        JToolbarHelper::save('report.save', 'JTOOLBAR_SAVE');
        JToolbarHelper::cancel('report.cancel', 'JTOOLBAR_CLOSE');
        JFactory::getApplication()->input->set('hidemainmenu', true);
    }

    protected function setDocument() {
        $title = JText::sprintf('COM_REPORTS_PAGE_TITLE_EDIT_REPORT', $this->item->title);
        JToolbarHelper::title($title, 'wrench');
        JHtml::_('bootstrap.framework');
    }
}
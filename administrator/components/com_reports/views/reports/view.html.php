<?php
/**
 * @package    reports
 *
 * @author     Антон <your@email.com>
 * @copyright  A copyright
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       http://your.url.com
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;

defined('_JEXEC') or die;

/**
 * Reports view.
 *
 * @package   reports
 * @since     1.0.0
 */
class ReportsViewReports extends HtmlView
{
	/**
	 * Reports helper
	 *
	 * @var    ReportsHelper
	 * @since  1.0.0
	 */
	protected $helper;

	/**
	 * The sidebar to show
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $sidebar = '';

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a JError object.
	 *
	 * @see     fetch()
	 * @since   1.0.0
	 */
	public function display($tpl = null)
	{
		// Show the toolbar
		$this->toolbar();

		// Show the sidebar
		$this->helper = new ReportsHelper;
		$this->helper->addSubmenu('reports');
		$this->sidebar = HTMLHelper::_('sidebar.render');

		// Display it all
		return parent::display($tpl);
	}

	/**
	 * Displays a toolbar for a specific page.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	private function toolbar()
	{
		ToolBarHelper::title(Text::_('COM_REPORTS'), '');

		// Options button.
		if (Factory::getUser()->authorise('core.admin', 'com_reports'))
		{
			ToolBarHelper::preferences('com_reports');
		}
	}
}

<?php
use Joomla\CMS\MVC\Model\ListModel;

defined('_JEXEC') or die;

/**
 * Сравнение проданных элементов по разным проектам
 *
 * @package   reports
 * @since     1.0.0
 */
class ReportsModelComplain_basic_old extends ListModel
{
    public function __construct($config = array())
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'manager',
                'projects',
            );
        }
        parent::__construct($config);
    }

    protected function _getListQuery()
    {
        $query = $this->_db->getQuery(true);

        return $query;
    }

    public function getItems()
    {
        $items = parent::getItems();
        $pm = parent::getInstance('Projects', 'PrjModel', array('ids' => $this->state->get('filter.projects')));
        $projects = $pm->getItems();
        $result = array('items' => $items, 'projects' => $projects['items']);

        return $result;
    }

    /* Сортировка по умолчанию */
    protected function populateState($ordering = 'manager', $direction = 'asc')
    {
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);
        $manager = $this->getUserStateFromRequest($this->context . '.filter.manager', 'filter_manager', '', 'string');
        $this->setState('filter.manager', $manager);
        $projects = $this->getUserStateFromRequest($this->context . '.filter.projects', 'filter_projects', array(5, 11));
        $this->setState('filter.projects', $projects);
        ReportsHelper::check_refresh();
    }

    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.manager');
        $id .= ':' . $this->getState('filter.projects');
        return parent::getStoreId($id);
    }
}

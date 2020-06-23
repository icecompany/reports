<?php
defined('_JEXEC') or die;
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

class JFormFieldFields extends JFormFieldList
{
    protected $type = 'Fields';
    protected $loadExternally = 0;

    protected function getOptions()
    {
        $heads = ReportsHelper::getHeads();
        $options = array();

        foreach ($heads as $field => $title) {
            $options[] = JHtml::_('select.option', $field, JText::sprintf($title));
        }

        if (!$this->loadExternally) {
            $options = array_merge(parent::getOptions(), $options);
        }

        return $options;
    }

    public function getOptionsExternally()
    {
        $this->loadExternally = 1;
        return $this->getOptions();
    }
}
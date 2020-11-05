<?php
defined('_JEXEC') or die;
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');
JHtml::_('searchtools.form');

use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('stylesheet', 'com_reports/style.css', array('version' => 'auto', 'relative' => true));
HTMLHelper::_('script', 'com_reports/script.js', array('version' => 'auto', 'relative' => true));
?>
<script>
    Joomla.submitbutton = function (task) {
        let form = document.querySelector('#adminForm');
        if (task === 'sentInvites.download') {
            location.href = 'index.php?option=com_reports&task=sent.execute&format=xls';
            return false;
        }
        else Joomla.submitform(task, form);
    };
</script>
<div class="row-fluid">
    <div id="j-sidebar-container" class="span2">
        <form action="<?php echo PrjHelper::getSidebarAction(); ?>" method="post">
            <?php echo $this->sidebar; ?>
        </form>
    </div>
    <div id="j-main-container" class="span10">
        <form action="<?php echo ReportsHelper::getActionUrl(); ?>" method="post"
              name="adminForm" id="adminForm">
            <?php echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
            <?php
            $text = (!is_numeric(PrjHelper::getActiveProject())) ? 'COM_REPORTS_MSG_PROJECT_IS_NOT_SELECTED' : 'COM_REPORTS_MSG_DATA_IS_LOAD';
            echo JText::sprintf($text);
            ?>
            <input type="hidden" name="task" value=""/>
            <input type="hidden" name="boxchecked" value="0"/>
            <?php echo JHtml::_('form.token');?>
        </form>
    </div>
</div>

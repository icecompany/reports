<?php
defined('_JEXEC') or die;
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));
?>
<tr>
    <th style="width: 1%;">
        <?php echo JHtml::_('grid.checkall'); ?>
    </th>
    <th style="width: 1%;">
        №
    </th>
    <th>
        <?php echo JHtml::_('searchtools.sort', 'COM_MKV_HEAD_TITLE', 'r.title', $listDirn, $listOrder); ?>
    </th>
    <th>
        <?php echo JText::sprintf('COM_REPORTS_HEAD_REPORTS_TYPE'); ?>
    </th>
    <?php if (ReportsHelper::canDo('core.reports.all')): ?>
        <th>
            <?php echo JHtml::_('searchtools.sort', 'COM_MKV_HEAD_MANAGER', 'manager', $listDirn, $listOrder); ?>
        </th>
    <?php endif; ?>
    <th style="width: 1%;">
        <?php echo JHtml::_('searchtools.sort', 'ID', 'r.id', $listDirn, $listOrder); ?>
    </th>
</tr>

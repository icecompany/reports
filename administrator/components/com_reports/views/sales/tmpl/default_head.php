<?php
defined('_JEXEC') or die;
$listOrder    = $this->escape($this->state->get('list.ordering'));
$listDirn    = $this->escape($this->state->get('list.direction'));
?>

<tr>
    <th style="width: 1%">№</th>
    <th style="width: 60%"><?php echo JText::sprintf('COM_MKV_HEAD_ITEMS');?></th>
    <th style="width: 10%"><?php echo JText::sprintf('COM_REPORTS_HEAD_COUNT');?></th>
    <th><?php echo JText::sprintf('COM_REPORTS_HEAD_TOTAL_RUB'); ?></th>
    <th><?php echo JText::sprintf('COM_REPORTS_HEAD_TOTAL_USD');?></th>
    <th><?php echo JText::sprintf('COM_REPORTS_HEAD_TOTAL_EUR');?></th>
</tr>

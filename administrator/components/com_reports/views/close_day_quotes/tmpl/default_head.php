<?php
defined('_JEXEC') or die;
$listOrder    = $this->escape($this->state->get('list.ordering'));
$listDirn    = $this->escape($this->state->get('list.direction'));
?>

<tr>
    <th style="width: 1%">№</th>
    <th style="width: 5%"><?php echo JText::sprintf('COM_REPORTS_HEAD_PAVILION');?></th>
    <th style="width: 5%"><?php echo JText::sprintf('COM_MKV_HEAD_STANDS'); ?></th>
    <th style="width: 5%"><?php echo JText::sprintf('COM_REPORTS_HEAD_SQUARE_IN_PAVILION');?></th>
    <th style="width: 5%"><?php echo JText::sprintf('COM_REPORTS_HEAD_SQUARE_IN_STREET');?></th>
    <th><?php echo JText::sprintf('COM_MKV_HEAD_COMPANY');?></th>
    <th style="width: 5%"><?php echo JText::sprintf('COM_REPORTS_HEAD_QUOTES_IN_PAVILION');?></th>
    <th style="width: 5%"><?php echo JText::sprintf('COM_REPORTS_HEAD_QUOTES_IN_STREET');?></th>
    <th style="width: 5%"><?php echo JText::sprintf('COM_REPORTS_HEAD_QUOTES_IN_REG');?></th>
    <th style="width: 5%"><?php echo JText::sprintf('COM_REPORTS_HEAD_TOTAL');?></th>
    <th><?php echo JText::sprintf('COM_MKV_HEAD_MANAGER');?></th>
</tr>

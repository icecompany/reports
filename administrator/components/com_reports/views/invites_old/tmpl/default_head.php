<?php
defined('_JEXEC') or die;
$listOrder    = $this->escape($this->state->get('list.ordering'));
$listDirn    = $this->escape($this->state->get('list.direction'));
?>

<tr>
    <th>№</th>
    <th><?php echo JText::sprintf('COM_REPORTS_HEAD_COMPANY');?></th>
    <th><?php echo JText::sprintf('COM_REPORTS_HEAD_MANAGER');?></th>
</tr>

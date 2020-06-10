<?php
defined('_JEXEC') or die;
$listOrder    = $this->escape($this->state->get('list.ordering'));
$listDirn    = $this->escape($this->state->get('list.direction'));
?>

<tr>
    <th>№</th>
    <th><?php echo JText::sprintf('COM_REPORTS_HEAD_COMPANY');?></th>
    <th><?php echo JHtml::_('searchtools.sort', 'COM_REPORTS_HEAD_INVITE_DATA', 'si.invite_date', $listDirn, $listOrder); ?></th>
    <th><?php echo JText::sprintf('COM_REPORTS_HEAD_INVITE_SENT_NUMBER');?></th>
    <th><?php echo JText::sprintf('COM_REPORTS_HEAD_INVITE_INCOMING_NUMBER');?></th>
    <th><?php echo JText::sprintf('COM_REPORTS_HEAD_MANAGER');?></th>
    <th><?php echo JText::sprintf('COM_REPORTS_HEAD_INVITE_EMAIL');?></th>
    <th><?php echo JText::sprintf('COM_REPORTS_HEAD_INVITE_RESULT');?></th>
    <th><?php echo JText::sprintf('COM_REPORTS_HEAD_INVITE_CONTACT_NAME');?></th>
    <th><?php echo JText::sprintf('COM_REPORTS_HEAD_INVITE_CONTACT_POST');?></th>
    <th><?php echo JText::sprintf('COM_REPORTS_HEAD_INVITE_CONTACT_PHONE');?></th>
</tr>

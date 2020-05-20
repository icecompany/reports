<?php
defined('_JEXEC') or die;
$listOrder    = $this->escape($this->state->get('list.ordering'));
$listDirn    = $this->escape($this->state->get('list.direction'));
?>

<tr>
    <th>№</th>
    <th><?php echo JText::sprintf('COM_REPORTS_HEAD_COMPANY');?></th>
    <th><?php echo JText::sprintf('COM_REPORTS_HEAD_MANAGER');?></th>
    <th><?php echo JText::sprintf('COM_REPORTS_HEAD_STANDS');?></th>
    <th><?php echo JText::sprintf('COM_REPORTS_HEAD_SITE');?></th>
    <th><?php echo JText::sprintf('COM_REPORTS_HEAD_WELCOME_CALCULATE');?></th>
    <th><?php echo JText::sprintf('COM_REPORTS_HEAD_WELCOME_SQUARE_PAVILION');?></th>
    <th><?php echo JText::sprintf('COM_REPORTS_HEAD_WELCOME_SQUARE_OPEN_BUILDING');?></th>
    <th><?php echo JText::sprintf('COM_REPORTS_HEAD_WELCOME_SQUARE_OPEN_DEMO');?></th>
    <?php foreach ($this->items['price'] as $itemID => $item_title) :?>
        <th><?php echo $item_title;?></th>
    <?php endforeach; ?>
</tr>

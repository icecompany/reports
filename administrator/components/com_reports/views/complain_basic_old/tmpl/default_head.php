<?php
defined('_JEXEC') or die;
$listOrder    = $this->escape($this->state->get('list.ordering'));
$listDirn    = $this->escape($this->state->get('list.direction'));
?>

<tr>
    <th><?php echo JText::sprintf('COM_REPORTS_HEAD_COMPLAIN_BASIC_OLD_PARAMETER');?></th>
    <?php foreach ($this->items['projects'] as $project) :?>
        <th><?php echo $project['title'];?></th>
    <?php endforeach; ?>
</tr>

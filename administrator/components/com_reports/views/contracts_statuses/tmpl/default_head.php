<?php
defined('_JEXEC') or die;
$listOrder    = $this->escape($this->state->get('list.ordering'));
$listDirn    = $this->escape($this->state->get('list.direction'));
?>

<tr>
    <th style="width: 1%;">№</th>
    <th><?php echo JText::sprintf('COM_MKV_HEAD_COMPANY');?></th>
    <?php foreach ($this->items['projects'] as $projectID => $project) :?>
        <th><?php echo $project;?></th>
    <?php endforeach; ?>
    <th style="width: 1%;">ID</th>
</tr>

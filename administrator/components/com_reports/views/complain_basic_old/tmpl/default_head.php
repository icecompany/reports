<?php
defined('_JEXEC') or die;
$listOrder    = $this->escape($this->state->get('list.ordering'));
$listDirn    = $this->escape($this->state->get('list.direction'));
?>

<tr>
    <?php foreach ($this->items['projects'] as $project) :?>
        <th><?php echo $project['title'];?></th>
    <?php endforeach; ?>
</tr>

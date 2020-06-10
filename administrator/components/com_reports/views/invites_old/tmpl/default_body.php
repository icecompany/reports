<?php
// Запрет прямого доступа.
defined('_JEXEC') or die;
$ii = $this->state->get('list.start', 0);
?>
<?php foreach ($this->items as $item) :?>
    <tr>
        <td><?php echo ++$ii;?></td>
        <td><?php echo $item['edit_link'];?></td>
        <td><?php echo $item['manager'];?></td>
    </tr>
<?php endforeach;?>




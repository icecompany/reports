<?php
// Запрет прямого доступа.
defined('_JEXEC') or die;
$ii = $this->state->get('list.start', 0);
?>
<?php foreach ($this->items['items'] as $itemID => $item) :?>
    <tr>
        <td><?php echo ++$ii;?></td>
        <td><?php echo $item['item'];?></td>
        <td><?php echo $item['count'];?></td>
        <td><?php echo $item['rub'];?></td>
        <td><?php echo $item['usd'];?></td>
        <td><?php echo $item['eur'];?></td>
    </tr>
<?php endforeach;?>




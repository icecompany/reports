<?php
// Запрет прямого доступа.
defined('_JEXEC') or die;
$ii = $this->state->get('list.start', 0);
?>
<?php foreach ($this->items['items'] as $item) :?>
    <tr>
        <td><?php echo ++$ii;?></td>
        <td><?php echo $item['pavilions'];?></td>
        <td><?php echo $item['stands'];?></td>
        <td><?php echo $item['in_pavilion'];?></td>
        <td><?php echo $item['in_street'];?></td>
        <td><?php echo $item['contract_link'];?></td>
        <td><?php echo $item['quotes_pavilion'];?></td>
        <td><?php echo $item['quotes_street'];?></td>
        <td><?php echo $item['quotes_reg'];?></td>
        <td><?php echo $item['total'];?></td>
        <td><?php echo $item['manager'];?></td>
    </tr>
<?php endforeach;?>




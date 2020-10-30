<?php
// Запрет прямого доступа.
defined('_JEXEC') or die;
$ii = $this->state->get('list.start', 0);
?>
<?php foreach ($this->items as $item) :?>
    <tr>
        <td><?php echo ++$ii;?></td>
        <td><?php echo $item['edit_link'];?></td>
        <td><?php echo $item['invite_date'];?></td>
        <td><?php echo $item['invite_outgoing_number'];?></td>
        <td><?php echo $item['invite_incoming_number'];?></td>
        <td><?php echo $item['manager'];?></td>
        <td><?php echo $item['email'];?></td>
        <td><?php echo $item['status'];?></td>
        <td><?php echo $item['director_name'];?></td>
        <td><?php echo $item['director_post'];?></td>
        <td><?php echo $item['phone_1'];?></td>
    </tr>
<?php endforeach;?>




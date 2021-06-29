<?php
// Запрет прямого доступа.
defined('_JEXEC') or die;
$ii = $this->state->get('list.start', 0);
?>
<?php foreach ($this->items['items'] as $item) :?>
    <tr>
        <td><?php echo ++$ii;?></td>
        <?php foreach ($this->tableHeads as $field => $params): ?>
            <td><?php echo $item[$field];?></td>
        <?php endforeach; ?>
    </tr>
<?php endforeach;?>




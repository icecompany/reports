<?php
// Запрет прямого доступа.
defined('_JEXEC') or die;
$ii = $this->state->get('list.start', 0);
?>
<?php foreach ($this->items['items'] as $companyID => $company) :?>
    <tr>
        <td><?php echo ++$ii;?></td>
        <td><?php echo $company['contract_number'];?></td>
        <td><?php echo $company['company'];?></td>
        <td><?php echo $company['manager'];?></td>
        <?php foreach ($this->items['price'] as $itemID => $item_title) :?>
            <td>
                <?php echo $this->items['items'][$companyID]['price'][$itemID] ?? 0;?>
            </td>
        <?php endforeach; ?>
    </tr>
<?php endforeach;?>




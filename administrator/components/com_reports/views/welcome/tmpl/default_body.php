<?php
// Запрет прямого доступа.
defined('_JEXEC') or die;
$ii = $this->state->get('list.start', 0);
?>
<?php foreach ($this->items['companies'] as $companyID => $company) :?>
    <tr>
        <td><?php echo ++$ii;?></td>
        <td><?php echo $company['company'];?></td>
        <td><?php echo $company['manager'];?></td>
        <td><?php echo $company['site'];?></td>
        <td><?php echo $this->items['calculate'][$companyID] ?? 0;?></td>
        <td><?php echo $this->items['welcome_automatic'][$companyID]['pavilion'] ?? 0;?></td>
        <td><?php echo $this->items['welcome_automatic'][$companyID]['open_building'] ?? 0;?></td>
        <td><?php echo $this->items['welcome_automatic'][$companyID]['open_demo'] ?? 0;?></td>
        <?php foreach ($this->items['price'] as $itemID => $item_title) :?>
            <td>
                <?php echo $this->items['items'][$companyID][$itemID] ?? 0;?>
            </td>
        <?php endforeach; ?>
    </tr>
<?php endforeach;?>




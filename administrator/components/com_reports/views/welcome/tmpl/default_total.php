<?php
// Запрет прямого доступа.
defined('_JEXEC') or die;
?>
<tr>
    <td colspan="5" style="font-weight: bold; text-align: right;"><?php echo JText::sprintf('COM_REPORTS_HEAD_TOTAL');?></td>
    <td><?php echo $this->items['total']['calculate'] ?? 0;?></td>
    <?php foreach ($this->items['price'] as $itemID => $item_title) :?>
        <td>
            <?php echo $this->items['total']['price'][$itemID] ?? 0;?>
        </td>
    <?php endforeach; ?>
</tr>



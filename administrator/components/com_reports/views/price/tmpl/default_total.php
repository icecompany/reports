<?php
// Запрет прямого доступа.
defined('_JEXEC') or die;
?>
<tr>
    <td colspan="4" style="font-weight: bold; text-align: right;"><?php echo JText::sprintf('COM_REPORTS_HEAD_TOTAL');?></td>
    <?php foreach ($this->items['price'] as $itemID => $item_title) :?>
        <td>
            <?php echo $this->items['total'][$itemID] ?? 0;?>
        </td>
    <?php endforeach; ?>
</tr>



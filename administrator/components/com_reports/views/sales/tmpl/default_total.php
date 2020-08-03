<?php
// Запрет прямого доступа.
defined('_JEXEC') or die;
?>
<tr>
    <td colspan="3" style="text-align: right; font-weight: bold; font-style: italic;">
        <?php echo JText::sprintf('COM_REPORTS_HEAD_TOTAL');?>
    </td>
    <td><?php echo $this->items['total']['rub'];?></td>
    <td><?php echo $this->items['total']['usd'];?></td>
    <td><?php echo $this->items['total']['eur'];?></td>
</tr>

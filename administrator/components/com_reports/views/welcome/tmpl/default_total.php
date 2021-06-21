<?php
// Запрет прямого доступа.
defined('_JEXEC') or die;
?>
<tr>
    <td colspan="6" style="font-weight: bold; text-align: right;"><?php echo JText::sprintf('COM_REPORTS_HEAD_TOTAL');?></td>
    <td><?php echo $this->items['total']['calculate'] ?? 0;?></td>
    <td><?php echo $this->items['total']['print'] ?? 0;?></td>
    <td><?php echo $this->items['total']['electron'] ?? 0;?></td>
</tr>



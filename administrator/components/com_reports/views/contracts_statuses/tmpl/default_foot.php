<?php
// Запрет прямого доступа.
defined('_JEXEC') or die;
$colspan = count($this->items['projects']) + 3;
?>
<tr>
    <td colspan="<?php echo $colspan;?>"><?php echo $this->pagination->getListFooter(); ?></td>
</tr>

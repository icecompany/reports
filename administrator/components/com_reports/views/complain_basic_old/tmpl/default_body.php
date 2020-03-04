<?php
// Запрет прямого доступа.
defined('_JEXEC') or die;
$ii = $this->state->get('list.start', 0);
?>

<tr>
    <td><?php echo JText::sprintf('COM_REPORTS_HEAD_COMPLAIN_BASIC_OLD_CONTRACTS');?></td>
    <?php foreach ($this->items['projects'] as $project) :?>
        <td><?php echo $this->items['items'][$project['id']]['contracts'];?></td>
    <?php endforeach; ?>
</tr>
<tr>
    <td><?php echo JText::sprintf('COM_REPORTS_HEAD_COMPLAIN_BASIC_OLD_DOGOVORS');?></td>
    <?php foreach ($this->items['projects'] as $project) :?>
        <td><?php echo $this->items['items'][$project['id']]['dogovors'];?></td>
    <?php endforeach; ?>
</tr>
<tr>
    <td><?php echo JText::sprintf('COM_REPORTS_HEAD_COMPLAIN_BASIC_OLD_AMOUNT_RUB');?></td>
    <?php foreach ($this->items['projects'] as $project) :?>
        <td><?php echo $this->items['items'][$project['id']]['amounts']['rub'] ?? '';?></td>
    <?php endforeach; ?>
</tr>
<tr>
    <td><?php echo JText::sprintf('COM_REPORTS_HEAD_COMPLAIN_BASIC_OLD_AMOUNT_USD');?></td>
    <?php foreach ($this->items['projects'] as $project) :?>
        <td><?php echo $this->items['items'][$project['id']]['amounts']['usd'] ?? '';?></td>
    <?php endforeach; ?>
</tr>
<tr>
    <td><?php echo JText::sprintf('COM_REPORTS_HEAD_COMPLAIN_BASIC_OLD_AMOUNT_EUR');?></td>
    <?php foreach ($this->items['projects'] as $project) :?>
        <td><?php echo $this->items['items'][$project['id']]['amounts']['eur'] ?? '';?></td>
    <?php endforeach; ?>
</tr>

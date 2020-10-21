<?php
// Запрет прямого доступа.
defined('_JEXEC') or die;
$ii = $this->state->get('list.start', 0);
?>
<?php foreach ($this->items['items'] as $companyID => $company) :?>
    <tr>
        <td><?php echo ++$ii;?></td>
        <td><?php echo $company['company_link'];?></td>
        <?php foreach ($this->items['projects'] as $projectID => $project) :?>
            <td>
                <?php echo implode(', ', $this->items['items'][$companyID][$projectID]) ?? JHtml::link(JRoute::_("index.php?option=com_contracts&amp;task=contract.add&amp;companyID={$companyID}&amp;projectID={$projectID}&amp;return={$this->return}"), JText::sprintf('COM_REPORTS_ACTION_CREATE_CONTRACT'), ['style' => 'color:red']);?>
            </td>
        <?php endforeach; ?>
        <td><?php echo $company['id'];?></td>
    </tr>
<?php endforeach;?>




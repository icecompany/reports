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
                <?php echo implode(', ', $this->items['items'][$companyID][$projectID]);?>
            </td>
        <?php endforeach; ?>
        <td><?php echo $company['id'];?></td>
    </tr>
<?php endforeach;?>




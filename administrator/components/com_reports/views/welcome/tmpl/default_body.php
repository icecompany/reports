<?php
// Запрет прямого доступа.
defined('_JEXEC') or die;
$ii = $this->state->get('list.start', 0);
?>
<?php foreach ($this->items['items'] as $companyID => $company) :?>
    <tr>
        <td><?php echo ++$ii;?></td>
        <td><?php echo $company['company'];?></td>
        <td><?php echo $company['manager'];?></td>
        <td><?php echo $this->items['stands'][$companyID];?></td>
        <td><?php echo $company['site_link'];?></td>
        <td><?php echo $this->items['contacts'][$companyID];?></td>
        <td><?php echo $company['calculate'] ?? 0;?></td>
        <td><?php echo $company['print'] ?? 0;?></td>
        <td><?php echo $company['electron'] ?? 0;?></td>
    </tr>
<?php endforeach;?>




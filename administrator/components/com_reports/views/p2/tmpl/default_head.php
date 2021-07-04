<?php
defined('_JEXEC') or die;
$listOrder    = $this->escape($this->state->get('list.ordering'));
$listDirn    = $this->escape($this->state->get('list.direction'));
?>

<tr>
    <th>№</th>
    <?php foreach ($this->tableHeads as $field => $params): ?>
        <th>
            <?php if (isset($params['column']) && !empty($params['column'])) {
                echo JHtml::_('searchtools.sort', $params['text'], $params['column'], $listDirn, $listOrder);
            }
            else {
                echo JText::sprintf($params['text']);
            }
            ?>
        </th>
    <?php endforeach; ?>
</tr>

<?php
use Joomla\CMS\MVC\Controller\AdminController;

defined('_JEXEC') or die;

class ReportsControllerClose_day_quotes extends AdminController
{
    public function download(): void
    {
        echo "<script>window.open('index.php?option=com_reports&task=close_day_quotes.execute&format=xls');</script>";
        echo "<script>location.href='{$_SERVER['HTTP_REFERER']}'</script>";
        jexit();
    }
}

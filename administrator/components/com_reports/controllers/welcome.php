<?php
use Joomla\CMS\MVC\Controller\AdminController;

defined('_JEXEC') or die;

class ReportsControllerWelcome extends AdminController
{
    public function download(): void
    {
        echo "<script>window.open('index.php?option=com_reports&task=welcome.execute&format=xls');</script>";
        echo "<script>location.href='{$_SERVER['HTTP_REFERER']}'</script>";
        jexit();
    }
}

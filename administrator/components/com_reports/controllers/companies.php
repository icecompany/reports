<?php
use Joomla\CMS\MVC\Controller\AdminController;

defined('_JEXEC') or die;

class ReportsControllerCompanies extends AdminController
{
    public function download(): void
    {
        echo "Export...";
        echo "<script>";
        echo "var t = setTimeout(\"location.href='{$_SERVER['HTTP_REFERER']}'\", 3000);";
        echo "location.href = 'index.php?option=com_reports&task=companies.execute&format=xls';";
        echo "</script>";
        jexit();
    }
}

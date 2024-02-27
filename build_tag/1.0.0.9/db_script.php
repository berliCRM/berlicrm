<?php
ini_set('include_path',ini_get('include_path').':../..');
require_once("includes/main/WebUI.php");
require_once 'include/utils/utils.php';

global $adb;

//Scheduled Import Cron activation
echo "Activate Scheduled Import Cron (if it exists)... ";
$query = "UPDATE vtiger_cron_task SET status = ? WHERE module = ?;";
$res = $adb->pquery($query, array(1, 'Import'));
if ($res) {
	echo "done<br>";
} else {
	echo "failed<br>";
}
//end
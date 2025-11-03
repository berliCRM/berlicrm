
<meta http-equiv="cache-control" content="no-cache, must-revalidate, post-check=0, pre-check=0" />
<meta http-equiv="cache-control" content="max-age=0" />
<meta http-equiv="expires" content="0" />
<meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
<meta http-equiv="pragma" content="no-cache" />
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
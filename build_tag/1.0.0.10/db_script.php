
<meta http-equiv="cache-control" content="no-cache, must-revalidate, post-check=0, pre-check=0" />
<meta http-equiv="cache-control" content="max-age=0" />
<meta http-equiv="expires" content="0" />
<meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
<meta http-equiv="pragma" content="no-cache" />
<?php
ini_set('include_path',ini_get('include_path').':../..');
require_once("includes/main/WebUI.php");
require_once("includes/main/WebUI.php");
require_once 'include/Webservices/Utils.php';

global $adb;

//  Import Cron correction
echo "Proper text for Import Cron (if it exists)... ";
$query = "UPDATE vtiger_cron_task SET description = ? WHERE module = ?;";
$res = $adb->pquery($query, array('The recommended frequency for Imports is 15 minutes.', 'Import'));
if ($res) {
	echo "done<br>";
} else {
	echo "failed<br>";
}

// add new Webservices
echo "add new web services ";
// check existance (for migrated clients)
$query = "SELECT * FROM `vtiger_ws_operation` WHERE `name` = 'retrievedocattachment'";
$res = $adb->pquery($query, array());
if ($adb->num_rows($res) > 0) {
	// add web service: retrievedocattachment
	$operationId = vtws_addWebserviceOperation('retrievedocattachment','include/Webservices/RetrieveDocAttachment.php','berli_retrievedocattachment','Get','0');
	vtws_addWebserviceOperationParam($operationId,'id','string','1');
	vtws_addWebserviceOperationParam($operationId,'returnfile','string','2');
	echo "retrievedocattachment added";
}
else {
	echo "retrievedocattachment already exists";
}
// check existance (for migrated clients)
$query = "SELECT * FROM `vtiger_ws_operation` WHERE `name` = 'update_product_relations'";
$res = $adb->pquery($query, array());
if ($adb->num_rows($res) > 0) {
	// add web service: update_product_relations
	$operationId = vtws_addWebserviceOperation('update_product_relations','include/Webservices/Custom/ProductRelation.php','vtws_update_product_relations','POST','0');
	vtws_addWebserviceOperationParam($operationId,'productid','string','1');
	vtws_addWebserviceOperationParam($operationId,'relids','string','2');
	vtws_addWebserviceOperationParam($operationId,'preserve','string','3');
	echo "update_product_relations added";
}
else {
	echo "update_product_relations already exists";
}
// check existance (for migrated clients)
$query = "SELECT * FROM `vtiger_ws_operation` WHERE `name` = 'get_multi_relations'";
$res = $adb->pquery($query, array());
if ($adb->num_rows($res) > 0) {
	// add web service: get_multi_relations
	$operationId = vtws_addWebserviceOperation('get_multi_relations','include/Webservices/Custom/getMultiRelations.php','berli_get_multi_relations','Get','0');
	vtws_addWebserviceOperationParam($operationId,'id','string','1');
	echo "get_multi_relations added";
}
else {
	echo "get_multi_relations already exists";
}

// update vtiger_smsnotifier_servers structure (if required)
echo "<br>Update vtiger_smsnotifier_servers structure (if required)<br>Table ";
$query = "SHOW COLUMNS FROM vtiger_smsnotifier_servers LIKE 'countryprefix';";
$res = $adb->pquery($query, array());
if ($res) {
	echo "found";
	if ($adb->num_rows($res) < 1) {
		echo " and update required";
		$query = "ALTER TABLE vtiger_smsnotifier_servers ADD `countryprefix` VARCHAR (5) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '';";
		$res = $adb->pquery($query, array());
		if ($res) {
			echo "<br>Update successful";
		} else {
			echo "<br>Update NOT successful";
		}
	} else {
		echo " and update NOT required";
	}
} else {
	echo "not found";
}
echo 'module update crmtogo done <br>';

echo "<br>update Tag version to berlicrm-1.0.0.10";
$query = "UPDATE `vtiger_version` SET `tag_version` = 'berlicrm-1.0.0.10'";
$adb->pquery($query, array());
echo " Tag version done.<br>";

//end
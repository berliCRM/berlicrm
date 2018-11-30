<!DOCTYPE html><html><?php
require_once("includes/main/WebUI.php");
require_once('include/utils/utils.php');
include_once('vtlib/Vtiger/Module.php');
include_once('include/Webservices/Utils.php');
require_once('vtlib/Vtiger/Package.php');
global $adb;

echo "<br>Remove special UI types of salutation and name fields in leads and contacts...";
$query = "UPDATE vtiger_field SET uitype = '15', displaytype = 1, summaryfield = 1 WHERE columnname = 'salutation'";
$adb->pquery($query, array());
$query = "UPDATE vtiger_field SET uitype = '1' WHERE columnname = 'firstname'";
$adb->pquery($query, array());
$query = "UPDATE vtiger_field SET uitype = '2' WHERE columnname = 'lastname'";
$adb->pquery($query, array());
echo " done";


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
		$query = "ALTER TABLE vtiger_smsnotifier_servers ADD `countryprefix` VARCHAR (5) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '';"
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

//// adding fields and index missing from modcomments manifest before v1.0.11

echo 'Alter missing modcomments fields... ';

$moduleInstance = Vtiger_Module::getInstance('ModComments');
if($moduleInstance) {
	$customer = Vtiger_Field::getInstance('customer', $moduleInstance);
	if (!$customer) {
		$customer = new Vtiger_Field();
		$customer->name = 'customer';
		$customer->label = 'Customer';
		$customer->uitype = '10';
		$customer->displaytype = '3';
		$blockInstance = Vtiger_Block::getInstance('LBL_MODCOMMENTS_INFORMATION', $moduleInstance);
		$blockInstance->addField($customer);
		$customer->setRelatedModules(array('Contacts'));
	}

	$modCommentsUserId = Vtiger_Field::getInstance("userid", $moduleInstance);
	if(!$modCommentsUserId){
		$blockInstance = Vtiger_Block::getInstance('LBL_MODCOMMENTS_INFORMATION', $moduleInstance);
		$userId = new Vtiger_Field();
		$userId->name = 'userid';
		$userId->label = 'UserId';
		$userId->uitype = '10';
		$userId->displaytype = '3';
		$blockInstance->addField($userId);
	}

	$modCommentsReasonToEdit = Vtiger_Field::getInstance("reasontoedit", $moduleInstance);
	if(!$modCommentsReasonToEdit){
		$blockInstance = Vtiger_Block::getInstance('LBL_MODCOMMENTS_INFORMATION', $moduleInstance);
		$reasonToEdit = new Vtiger_Field();
		$reasonToEdit->name = 'reasontoedit';
		$reasonToEdit->label = 'ReasonToEdit';
		$reasonToEdit->uitype = '19';
		$reasonToEdit->displaytype = '1';
		$blockInstance->addField($reasonToEdit);
	}
    $adb->query("ALTER TABLE `vtiger_modcomments` ADD PRIMARY KEY ( `modcommentsid` )");
}

echo 'done<br>';

echo 'Creating ws_fieldtype entry for new uitype...';
$adb->query("INSERT INTO vtiger_ws_fieldtype (`uitype` ,`fieldtype`) VALUES ('cr16', 'autocompletedtext')");
echo 'done<br>';

echo "<br>update Tag version to 11.. ";
$query = "UPDATE `vtiger_version` SET `tag_version` = 'berlicrm-1.0.0.11'";
$adb->pquery($query, array());
echo " Tag version done.<br>";
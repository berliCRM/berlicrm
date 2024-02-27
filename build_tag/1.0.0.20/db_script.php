<!DOCTYPE html><html><?php
ini_set('include_path',ini_get('include_path').':../..');
require_once('include/utils/utils.php');
include_once('vtlib/Vtiger/Module.php');
include_once('include/Webservices/Utils.php');
require_once('vtlib/Vtiger/Package.php');
global $adb;


echo "<br>Add Documents related list to Vendor";

include_once('vtlib/Vtiger/Module.php');
$module = Vtiger_Module::getInstance('Vendors');
// avoid duplicates
$module->unsetRelatedList(Vtiger_Module::getInstance('Documents'), 'Documents','get_attachments');
$module->setRelatedList(Vtiger_Module::getInstance('Documents'), 'Documents',Array('ADD','SELECT'),'get_attachments');

echo "Related List added<br>";


echo "<br>Add Webservice for User Deletion";
// make sure we do not generate duplicates
$adb->pquery("DELETE FROM vtiger_ws_operation_parameters WHERE operationid = (SELECT operationid from vtiger_ws_operation WHERE name = 'deleteUser')", array());
$adb->pquery("DELETE FROM vtiger_ws_operation WHERE name = 'deleteUser'", array());

$operationId = vtws_addWebserviceOperation('deleteUser','include/Webservices/DeleteUser.php','vtws_deleteUser','POST','0');
vtws_addWebserviceOperationParam($operationId,'id','string','1');
vtws_addWebserviceOperationParam($operationId,'newOwnerId','string','2');
echo "Webservice added<br>";



echo "<br>update Tag version to 20 ";
$query = "UPDATE `vtiger_version` SET `tag_version` = 'berlicrm-1.0.0.20'";
$adb->pquery($query, array());
echo " Tag version done.<br>";
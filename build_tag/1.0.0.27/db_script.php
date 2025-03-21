<!DOCTYPE html><html>
<meta http-equiv="cache-control" content="no-cache, must-revalidate, post-check=0, pre-check=0" />
<meta http-equiv="cache-control" content="max-age=0" />
<meta http-equiv="expires" content="0" />
<meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
<meta http-equiv="pragma" content="no-cache" />
<?php
ini_set('include_path',ini_get('include_path').':../..');
require_once('include/utils/utils.php');
include_once('vtlib/Vtiger/Module.php');
include_once('include/Webservices/Utils.php');
require_once('vtlib/Vtiger/Package.php');
global $adb;


ini_set('include_path',ini_get('include_path').':../..');
require_once('include/utils/utils.php');
include_once('vtlib/Vtiger/Module.php');
include_once('include/Webservices/Utils.php');
require_once('vtlib/Vtiger/Package.php');
global $adb;

$moduleName = 'Users';
$moduleInstance = Vtiger_Module::getInstance($moduleName);

if($moduleInstance) {
	// use vtlib to add new block
	$blockInstance = Vtiger_Block::getInstance('LBL_USERSIGNATUR', $moduleInstance);
	if (!$blockInstance) {
		echo 'adding LBL_USERSIGNATUR Block ... <br>';
		$blockcf = new Vtiger_Block();
		$blockcf->label = 'LBL_USERSIGNATUR';
		$moduleInstance->addBlock($blockcf);
		echo 'adding LBL_USERSIGNATUR Block done<br>';
		
		$blockInstance = Vtiger_Block::getInstance('LBL_USERSIGNATUR', $moduleInstance);
		$newblockid = $blockInstance->id;
		
		if ($newblockid) {
			// move signature field
			$db = PearDatabase::getInstance(); 
			$updateQuery = "UPDATE `vtiger_field` SET 	block = ? WHERE tablename = 'vtiger_users' AND columnname = 'signature' AND fieldname = 'signature'";
			$db->pquery($updateQuery, array($newblockid));
		}
	}
}

echo "<br>update Tag version to 27.. ";
$query = "UPDATE `vtiger_version` SET `tag_version` = 'berlicrm-1.0.0.27'";
$adb->pquery($query, array());
echo " Tag version done.<br>";
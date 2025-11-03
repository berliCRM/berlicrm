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


echo "<br>Alter com_vtiger_workflowtasks.task to MEDIUMTEXT if applicable..";
$adb->pquery("ALTER TABLE `com_vtiger_workflowtasks` CHANGE `task` `task` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL", array());

echo "<br>Alter vtiger_berlicleverreach_settings.accesstoken to VARCHAR(600) if applicable..";
$adb->pquery("ALTER TABLE `vtiger_berlicleverreach_settings` CHANGE `accesstoken` `accesstoken` VARCHAR( 600 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL", array());

echo "<br>update crmtogo settings if applicable..";
$result = $adb->pquery("Select crmtogo_user  from berli_crmtogo_modules group by crmtogo_user", array());
$num_rows=$adb->num_rows($result);
for($i=0;$i<$num_rows;$i++) {
	$userid=$adb->query_result($result,$i,'crmtogo_user');
	$checkresult = $adb->pquery("Select * from berli_crmtogo_modules where crmtogo_module = 'Events' and crmtogo_user =?", array($userid));
	$checknum_rows=$adb->num_rows($checkresult);
	if ($checknum_rows==0) {
		if(vtlib_isModuleActive('Calendar') === true) {
			$seq_result = $adb->pquery("SELECT `order_num` FROM `berli_crmtogo_modules` WHERE `crmtogo_user` = ? ORDER BY order_num DESC LIMIT 1", array($userid));
			$seq=$adb->query_result($seq_result,0,'order_num');		
			$adb->pquery("INSERT INTO `berli_crmtogo_modules` (`crmtogo_user`, `crmtogo_module`, `crmtogo_active`, `order_num`) VALUES (?, ?, ?, ?)", array($userid,'Events', '1', $seq+1));
		}
		
	}
}

$moduleInstance = Vtiger_Module::getInstance("berliCleverReach");
if($moduleInstance) {
	updateVtlibModule("berliCleverReach", "packages/vtiger/optional/berliCleverReach.zip");
} 

echo "<br>update Tag version to 22 ";
$query = "UPDATE `vtiger_version` SET `tag_version` = 'berlicrm-1.0.0.22'";
$adb->pquery($query, array());
echo " Tag version done.<br>";
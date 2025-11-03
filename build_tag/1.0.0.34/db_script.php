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

echo "<br>increase password field length for Mail Manager and MailScanner<br>";
$query = "ALTER TABLE `vtiger_mailscanner` CHANGE `password` `password` VARCHAR( 4000 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ;";
$adb->pquery($query, array());
$query = "ALTER TABLE `vtiger_mail_accounts` CHANGE `mail_password` `mail_password` VARCHAR( 4000 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;";
$adb->pquery($query, array());
echo "increase done.<br>";



echo '<br>module Pdfsettings update start<br>';
//update Pdfsettings module
$moduleFolders = array('packages/vtiger/mandatory', 'packages/vtiger/optional');
foreach($moduleFolders as $moduleFolder) {
	if ($handle = opendir($moduleFolder)) {
		while (false !== ($file = readdir($handle))) {
			$packageNameParts = explode(".",$file);
			if($packageNameParts[count($packageNameParts)-1] != 'zip'){
				continue;
			}
			array_pop($packageNameParts);
			$packageName = implode("",$packageNameParts);
			if ($packageName =='Pdfsettings') {
				$packagepath = "$moduleFolder/$file";
				$package = new Vtiger_Package();
				$module = $package->getModuleNameFromZip($packagepath);
				if($module != null) {
					$moduleInstance = Vtiger_Module::getInstance($module);
					if($moduleInstance) {
						updateVtlibModule($module, $packagepath);
					} 
					else {
						installVtlibModule($module, $packagepath);
					}
				}
			}
		}
		closedir($handle);
	}
}
echo '<br>module update Pdfsettings done <br>';


echo "<br>update Tag version to 34.. ";
$query = "UPDATE `vtiger_version` SET `tag_version` = 'berlicrm-1.0.0.34'";
$adb->pquery($query, array());
echo " Tag version done.<br>";
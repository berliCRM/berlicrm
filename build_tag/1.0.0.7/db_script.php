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

echo "<br>Remove special UI types of salutation and name fields in leads and contacts...";
$query = "UPDATE vtiger_field SET uitype = '15', displaytype = 1, summaryfield = 1 WHERE columnname = 'salutation'";
$adb->pquery($query, array());
$query = "UPDATE vtiger_field SET uitype = '1' WHERE columnname = 'firstname'";
$adb->pquery($query, array());
$query = "UPDATE vtiger_field SET uitype = '2' WHERE columnname = 'lastname'";
$adb->pquery($query, array());
echo " done";


echo "<br>update Tag version to 7.. ";
$query = "UPDATE `vtiger_version` SET `tag_version` = 'berlicrm-1.0.0.7'";
$adb->pquery($query, array());
echo " Tag version done.<br>";

echo 'module update crmtogo start<br>';
//update crmtogo module
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
			if ($packageName =='crmtogo') {
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
echo 'module update crmtogo done <br>';

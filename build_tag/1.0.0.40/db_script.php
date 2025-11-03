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

echo "add recurring frequency of 4 month<br>";
$query = 'update `vtiger_recurring_frequency` set recurring_frequency_id = 7, sortorderid = 7 where recurring_frequency_id = 6';
$res = $adb->pquery($query, array());
if(!$res) {
    echo "Error: ".$adb->database->errorMsg();
}
echo "sortoder done<br>";

$query = "INSERT INTO `vtiger_recurring_frequency` (`recurring_frequency_id`, `recurring_frequency`, `sortorderid`, `presence`) VALUES (6, 'every 4 months', 6, 1)";
$res = $adb->pquery($query, array());
if(!$res) {
    echo "Error: ".$adb->database->errorMsg();
}

$query = "UPDATE `vtiger_recurring_frequency_seq` SET id = 7";
$res = $adb->pquery($query, array());
if(!$res) {
    echo "Error: ".$adb->database->errorMsg();
}

echo "recurring frequency done<br>";


echo "add 14 days payment interval<br>";
$query = 'UPDATE vtiger_payment_duration SET sortorderid = sortorderid + 1 WHERE sortorderid >= 1';
$res = $adb->pquery($query, array());
if(!$res) {
    echo "Error: ".$adb->database->errorMsg();
}
echo "sortoder done<br>";

$query = "INSERT INTO vtiger_payment_duration (payment_duration_id, payment_duration, sortorderid, presence) VALUES (1, 'Net 14 days', 1, 1)";
$res = $adb->pquery($query, array());
if(!$res) {
    echo "Error: ".$adb->database->errorMsg();
}

$query = "UPDATE `vtiger_payment_duration_seq` SET id = 4";
$res = $adb->pquery($query, array());
if(!$res) {
    echo "Error: ".$adb->database->errorMsg();
}

echo "14 days payment interval done<br>";


echo '<br>module Projects update start<br>';
//update Projects module
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
			if ($packageName =='Projects') {
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
echo '<br>module update Projects done <br>';


echo '<br>module berlimap update start<br>';
//update berlimap module
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
			if ($packageName =='berlimap') {
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
echo '<br>module update berlimap done <br>';

echo "<br>update Tag version to 40.. ";
$query = "UPDATE `vtiger_version` SET `tag_version` = 'berlicrm-1.0.0.40'";
$adb->pquery($query, array());
echo " Tag version done.<br>";

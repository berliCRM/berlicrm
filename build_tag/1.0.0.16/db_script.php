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

echo 'Creating ws_fieldtype entry for new uitype...';
$adb->query("INSERT INTO vtiger_ws_fieldtype (`uitype` ,`fieldtype`) VALUES ('crs16', 'autocompletedsingleuse')");
echo 'done<br>';

echo "<br>delete not needed files for new SMS Notifier version ";
require_once('config.inc.php');
$filePath = "modules/SMSNotifier/views/CheckStatus.php";
$file = $root_directory.''.$filePath;
unlink($file);
echo "file deletion done.<br>";

echo "<br>set constraint for SMS Notifier tables";
$query = "delete vtiger_smsnotifier FROM vtiger_smsnotifier
LEFT JOIN vtiger_crmentity ON ( vtiger_crmentity.crmid = vtiger_smsnotifier.smsnotifierid)
WHERE vtiger_smsnotifier.smsnotifierid IS NOT NULL
AND vtiger_crmentity.crmid IS NULL;";
$adb->pquery($query, array());
$query = "ALTER TABLE `vtiger_smsnotifier` ADD CONSTRAINT `fk_crmid_vtiger_smsnotifier` FOREIGN KEY (`smsnotifierid`) REFERENCES `vtiger_crmentity` (`crmid`) ON DELETE CASCADE;";
$adb->pquery($query, array());
echo " set constraint done for vtiger_smsnotifier.<br>";


$query = "delete vtiger_smsnotifiercf FROM vtiger_smsnotifiercf
LEFT JOIN vtiger_crmentity ON ( vtiger_crmentity.crmid = vtiger_smsnotifiercf.smsnotifierid)
WHERE vtiger_smsnotifiercf.smsnotifierid IS NOT NULL
AND vtiger_crmentity.crmid IS NULL;";
$adb->pquery($query, array());
$query = "ALTER TABLE `vtiger_smsnotifiercf` ADD CONSTRAINT `fk_crmid_vtiger_smsnotifiercf` FOREIGN KEY (`smsnotifierid`) REFERENCES `vtiger_smsnotifier` (`smsnotifierid`) ON DELETE CASCADE;";
$adb->pquery($query, array());
echo " set constraint done for vtiger_smsnotifiercf.<br>";

echo "add primary key to vtiger_smsnotifier<br>";
$query = "ALTER TABLE vtiger_smsnotifier ADD PRIMARY KEY(smsnotifierid)";
$adb->pquery($query, array());
echo "add primary key done.<br>";

echo "<br>delete not needed files for new CKeditor version ";
require_once('config.inc.php');
$filePath = "libraries/jquery/ckeditor/ckeditor_php4.php";
$file = $root_directory.''.$filePath;
unlink($file);
$filePath = "libraries/jquery/ckeditor/ckeditor_php5.php";
$file = $root_directory.''.$filePath;
unlink($file);
echo "file deletion done.<br>";


echo "<br>delete old theme<br>";
$dirPath = "libraries/jquery/ckeditor/skins/icy_orange/";
$dir = $root_directory.''.$dirPath;

if (is_dir($dir)) {
  foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $filename) {
    if ($filename->isDir()) continue;
    unlink($filename);
  }
}
$dirPath = "libraries/jquery/ckeditor/skins/icy_orange/images/hidpi/";
$dir = $root_directory.''.$dirPath;
rmdir($dir);
$dirPath = "libraries/jquery/ckeditor/skins/icy_orange/images/";
$dir = $root_directory.''.$dirPath;
rmdir($dir);
$dirPath = "libraries/jquery/ckeditor/skins/icy_orange/";
$dir = $root_directory.''.$dirPath;
rmdir($dir);
echo "dir deletion done.<br>";


echo "<br>delete not needed lang files<br>";
$dirPath = "libraries/jquery/ckeditor/plugins/a11yhelp/lang/";
$dir = $root_directory.''.$dirPath;

if (is_dir($dir)) {
  foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $filename) {
    if ($filename->isDir()) continue;
    unlink($filename);
  }
}
rmdir($dir);
echo "lang files deletion done.<br>";

echo "change uitype of certain numberfields that were text until now<br>";
$query = "UPDATE `vtiger_field` SET uitype = 7 WHERE uitype = 1 AND typeofdata LIKE 'N%';";
$adb->pquery($query, array());
echo "uitype change done.<br>";

echo "remove berliSoftphone as entity <br>";
$query = "DELETE FROM `vtiger_ws_entity` WHERE `vtiger_ws_entity`.`name` = 'berliSoftphones';";
$adb->pquery($query, array());
echo "uitype change done.<br>";

echo 'module install Verteiler start<br>';
//install Verteiler module
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
			if ($packageName =='Verteiler') {
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
echo 'module install Verteiler done <br>';

 
echo "<br>update Tag version to 16. ";
$query = "UPDATE `vtiger_version` SET `tag_version` = 'berlicrm-1.0.0.16'";
$adb->pquery($query, array());
echo " Tag version done.<br>";


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

echo "<br>Altering pdf fonts tables...";
$adb->pquery("UPDATE `crmnow_pdffonts` SET `tcpdfname` = 'vera',`namedisplay` = 'Vera' WHERE `crmnow_pdffonts`.`fontid` =22", array());
$adb->pquery("UPDATE `crmnow_pdffonts` SET `tcpdfname` = 'verab',`namedisplay` = 'Vera Bold' WHERE `crmnow_pdffonts`.`fontid` =23", array());
$adb->pquery("UPDATE `crmnow_pdffonts` SET `tcpdfname` = 'verai',`namedisplay` = 'Vera Italic' WHERE `crmnow_pdffonts`.`fontid` =24", array());
$adb->pquery("UPDATE `crmnow_pdffonts` SET `tcpdfname` = 'verabi',`namedisplay` = 'Vera Italic BOLD' WHERE `crmnow_pdffonts`.`fontid` =25", array());
echo " Fonts done.<br>";

echo "<br>Altering collations for berliCleverReach tables...";
$adb->pquery("ALTER TABLE `berli_listview_colors` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci", array());
echo " done.<br>";

// ------ Fix missing primary index on vtiger_modcomments

echo "<br>Checking index on vtiger_modcomments...";
$res = $adb->pquery('SHOW INDEX FROM `vtiger_modcomments` WHERE Column_name = "modcommentsid"');
$row = $adb->fetchByAssoc($res);
if (empty($row)) {
    $res = $adb->pquery('ALTER TABLE `vtiger_modcomments` ADD PRIMARY KEY (`modcommentsid`)');
    if ($res) echo " Created missing primary index, ";
}
else echo " Index present, ";
echo " done.<br>";


//update google module
echo 'Start update Google module <br>';
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
			if ($packageName =='Google') {
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
 
echo 'module update Google done <br>';

//install berlimap module
echo 'module install berlimap start<br>';
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
echo 'module install berlimap done <br>';

echo "<br>update Tag version";
$query = "UPDATE `vtiger_version` SET `tag_version` = 'berlicrm-1.0.0.3'";
$adb->pquery($query, array());
echo " Tag version done.<br>";


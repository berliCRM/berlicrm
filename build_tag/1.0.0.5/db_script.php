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

// De-duplicate crmentityrel entries, drop indices and create proper ones
$q = "SELECT *,COUNT(*) as duplicates FROM `vtiger_crmentityrel` GROUP BY crmid,relcrmid HAVING duplicates > 1";
$res = $adb->pquery($q);
$delrows =0;
echo "<hr>Deleted duplicate entries in vtiger_crmentityrel... ";
while ($row = $adb->fetchByAssoc($res,-1,false)) {
    $q = "DELETE FROM vtiger_crmentityrel WHERE crmid=? AND relcrmid=? LIMIT ?";
    $res2 = $adb->pquery($q,array($row["crmid"], $row["relcrmid"], $row["duplicates"]-1));
    $delrows += $adb->getAffectedRowCount($res2);
}
echo "deleted $delrows duplicate entries.";

echo "<br>Deleting indices... ";
$q = "SHOW INDEX FROM `vtiger_crmentityrel`";
$res = $adb->pquery($q);
while ($row = $adb->fetchByAssoc($res,-1,false)) {
    echo "<br>Found index ",$row["key_name"], ", dropping...";
    $q = "DROP INDEX `{$row["key_name"]}` ON `vtiger_crmentityrel`";
    $adb->pquery($q);
}
echo "dropped all indices.";

echo "<br>Creating new indices...";
$q = "ALTER TABLE `vtiger_crmentityrel` ADD PRIMARY KEY ( `crmid` , `relcrmid` )";
$adb->pquery($q);
$q = "ALTER TABLE `vtiger_crmentityrel` ADD INDEX `relcrmid` ( `relcrmid` ) ";
$adb->pquery($q);
echo "Done.";

echo "<br>Creating table vtiger_webforms_field...";
$q = "CREATE TABLE IF NOT EXISTS `vtiger_webforms_field` (
  `id` int(19) NOT NULL AUTO_INCREMENT,
  `webformid` int(19) NOT NULL,
  `fieldname` varchar(50) NOT NULL,
  `neutralizedfield` varchar(50) NOT NULL,
  `defaultvalue` varchar(200) DEFAULT NULL,
  `required` int(10) NOT NULL DEFAULT '0',
  `sequence` int(10) DEFAULT NULL,
  `hidden` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_1_vtiger_webforms_field` (`webformid`),
  KEY `fk_2_vtiger_webforms_field` (`fieldname`),
  CONSTRAINT `fk_1_vtiger_webforms_field` FOREIGN KEY (`webformid`) REFERENCES `vtiger_webforms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_3_vtiger_webforms_field` FOREIGN KEY (`fieldname`) REFERENCES `vtiger_field` (`fieldname`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
$adb->pquery($q);
echo "Done.";

//install gdpr module
echo 'module install gdpr start<br>';
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
			if ($packageName =='gdpr') {
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
echo 'module install gdpr done <br>';

echo "<br>update Tag version";
$query = "UPDATE `vtiger_version` SET `tag_version` = 'berlicrm-1.0.0.5'";
$adb->pquery($query, array());
echo " Tag version done.<br>";

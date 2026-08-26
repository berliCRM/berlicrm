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

// Update entries for projects in vtiger_relatedlists
$query = "UPDATE vtiger_relatedlists SET actions = 'ADD'
WHERE relation_id IN (
    SELECT vtiger_relatedlists.relation_id
    FROM vtiger_relatedlists
    INNER JOIN vtiger_tab ON vtiger_relatedlists.tabid = vtiger_tab.tabid
    WHERE vtiger_relatedlists.name = 'get_dependents_list'
    AND vtiger_relatedlists.label = 'Projects'
    AND vtiger_tab.name IN ('Contacts', 'Accounts')";
$result = $adb->pquery($query, array());
echo "<br>Updated vtiger_relatedlists successfully<br>";

$query = "ALTER TABLE `vtiger_schedulereports` 
ADD COLUMN `attfolderid` INT(19) DEFAULT NULL,
ADD COLUMN `savetype` VARCHAR(30) DEFAULT NULL;";
$result = $adb->pquery($query, array());
echo "<br>vtiger_schedulereports table updated successfully<br>";

$moduleFolders = array('packages/vtiger/mandatory', 'packages/vtiger/optional');
foreach ($moduleFolders as $moduleFolder) {
    if ($handle = opendir($moduleFolder)) {
        while (false !== ($file = readdir($handle))) {
            $packageNameParts = explode(".", $file);
            if ($packageNameParts[count($packageNameParts) - 1] != 'zip') {
                continue;
            }
            array_pop($packageNameParts);
            $packageName = implode("", $packageNameParts);
            if ($packageName == 'crmtogo' || $packageName == 'Project' || $packageName == 'Import') {
				echo "Update package $packageName START<br>";
                $packagepath = "$moduleFolder/$file";
                $package = new Vtiger_Package();
                $module = $package->getModuleNameFromZip($packagepath);
                if ($module != null) {
                    $moduleInstance = Vtiger_Module::getInstance($module);
                    if (false) {
                        updateVtlibModule($module, $packagepath);
                    } else {
                        installVtlibModule($module, $packagepath);
                    }
                }
				echo "Update package $packageName DONE<br>";
            }
        }
        closedir($handle);
    }
}

echo "<br>update Tag version to 43.. ";
$query = "UPDATE `vtiger_version` SET `tag_version` = 'berlicrm-1.0.0.43'";
$adb->pquery($query, array());
echo " Tag version done.<br>";

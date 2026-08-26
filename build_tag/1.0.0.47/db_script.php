<!DOCTYPE html><html>
<meta http-equiv="cache-control" content="no-cache, must-revalidate, post-check=0, pre-check=0" />
<meta http-equiv="cache-control" content="max-age=0" />
<meta http-equiv="expires" content="0" />
<meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
<meta http-equiv="pragma" content="no-cache" />
<?php
ini_set('include_path',ini_get('include_path').':../..');
require_once('includes/main/WebUI.php');
require_once('include/utils/utils.php');
include_once('vtlib/Vtiger/Module.php');
include_once('include/Webservices/Utils.php');
require_once('vtlib/Vtiger/Package.php');
global $adb;

echo "Add new RelatedLists START...<br>";

require_once('modules/Install/models/InitSchema.php');
Install_InitSchema_Model::addNewRelatedLists();

echo "Add new RelatedLists DONE...<br>";

echo "<br>Add new column to vtiger_modcommentsscope START...";
//first check if the column already exists
$query = "SELECT COUNT(*) AS cnt
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = ?
	AND COLUMN_NAME = ?;";
$result = $adb->pquery($query, array("vtiger_modcommentsscope", "timeneeded"));
try {
    $cnt = (int)$adb->query_result($result, 0, 'cnt');
    if ($cnt > 0) {
        echo "<br>column already exists";
    } else {
        $query = "ALTER TABLE `vtiger_modcommentsscope`
                    ADD COLUMN `timeneeded` time DEFAULT NULL";
        $adb->pquery($query, array());
    }
} catch (Exception $e) {
    echo "<br>Failed adding new column to vtiger_modcommentsscope";
}
echo "<br>Add new column to vtiger_modcommentsscope DONE...";

echo "<br>update Tag version to 47.. ";
$query = "UPDATE `vtiger_version` SET `tag_version` = 'berlicrm-1.0.0.47'";
$adb->pquery($query, array());
echo " Tag version done.<br>";

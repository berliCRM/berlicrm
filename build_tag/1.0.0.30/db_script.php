<!DOCTYPE html><html><?php
ini_set('include_path',ini_get('include_path').':../..');
require_once('include/utils/utils.php');
include_once('vtlib/Vtiger/Module.php');
include_once('include/Webservices/Utils.php');
require_once('vtlib/Vtiger/Package.php');
global $adb;



//create berlicrm_recurringreferences table for new calendar
echo "<br>Create berlicrm_recurringreferences table for calendar recurring events<br>";

$query = "
CREATE TABLE IF NOT EXISTS `berlicrm_recurringreferences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parentactivityid` int(11) NOT NULL,
  `activityid` int(11) NOT NULL,
  `startdate` date DEFAULT NULL,
  `enddate` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
";
$adb->pquery($query, array());
echo "create berlicrm_recurringreferences table done.<br>";



echo "<br>update Tag version to 30.. ";
$query = "UPDATE `vtiger_version` SET `tag_version` = 'berlicrm-1.0.0.30'";
$adb->pquery($query, array());
echo " Tag version done.<br>";
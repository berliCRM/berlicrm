<!DOCTYPE html><html>
<meta http-equiv="cache-control" content="no-cache, must-revalidate, post-check=0, pre-check=0" />
<meta http-equiv="cache-control" content="max-age=0" />
<meta http-equiv="expires" content="0" />
<meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
<meta http-equiv="pragma" content="no-cache" />
<?php
ini_set('include_path',ini_get('include_path').':../..');
include_once('vtlib/Vtiger/Module.php');
include_once('include/Webservices/Utils.php');
global $adb;

$module = Vtiger_Module::getInstance('berliCleverReach');

if ($module) {
echo "<br>Altering collations for berliCleverReach tables...";
$adb->pquery("ALTER TABLE `vtiger_berlicleverreach_settings` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci", array());
$adb->pquery("ALTER TABLE `vtiger_berlicleverreach_settings` CHANGE `customerid` `customerid` VARCHAR( 16 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL", array());
$adb->pquery("ALTER TABLE `vtiger_berlicleverreach_settings` CHANGE `customername` `customername` VARCHAR( 128 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL", array());
$adb->pquery("ALTER TABLE `vtiger_berlicleverreach_settings` CHANGE `accesstoken` `accesstoken` VARCHAR( 400 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL", array());
$adb->pquery("ALTER TABLE `vtiger_berlicleverreach_settings` CHANGE `newsubscribertype` `newsubscribertype` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL", array());
$adb->pquery("ALTER TABLE `vtiger_berlicleverreach` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci", array());
$adb->pquery("ALTER TABLE `vtiger_berlicleverreach` CHANGE `cleverreachname` `cleverreachname` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL", array());
$adb->pquery("ALTER TABLE `vtiger_berlicleverreach` CHANGE `bcr_campaign_no` `bcr_campaign_no` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL", array());
$adb->pquery("ALTER TABLE `vtiger_berlicleverreach` CHANGE `bcr_campaign_type` `bcr_campaign_type` VARCHAR( 200 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL", array());
$adb->pquery("ALTER TABLE `vtiger_berlicleverreach` CHANGE `bcr_campaign_status` `bcr_campaign_status` VARCHAR( 200 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL", array());
$adb->pquery("ALTER TABLE `vtiger_bcr_campaign_status` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci", array());
$adb->pquery("ALTER TABLE `vtiger_bcr_campaign_status` CHANGE `bcr_campaign_status` `bcr_campaign_status` VARCHAR( 200 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL", array());
$adb->pquery("ALTER TABLE `vtiger_bcr_campaign_type` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci", array());
$adb->pquery("ALTER TABLE `vtiger_bcr_campaign_type` CHANGE `bcr_campaign_type` `bcr_campaign_type` VARCHAR( 200 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL", array());
echo " done.<br>";
}
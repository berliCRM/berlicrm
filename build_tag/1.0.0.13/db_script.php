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


echo "<br>create table for new Mailchimp version ";
$query = "CREATE TABLE IF NOT EXISTS `vtiger_mailchimp_synced_entities` (
  `crmid` int(11) NOT NULL,
  `mcgroupid` int(11) NOT NULL,
  `recordid` int(11) NOT NULL,
  KEY `recordidx` (`recordid`),
  KEY `crmid` (`crmid`),
  CONSTRAINT `fk_1_vtiger_mailchimp_synced_entities` FOREIGN KEY (`crmid`) REFERENCES `vtiger_crmentity` (`crmid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
$adb->pquery($query, array());
echo "create table done.<br>";

echo "<br>delete not needed files for new Mailchimp version ";
require_once('config.inc.php');
$filePath = "modules/Mailchimp/actions/logfileWriter.php";
$file = $root_directory.''.$filePath;
unlink($file);
$filePath = "modules/Mailchimp/actions/MailchimpSyncStep1.php";
$file = $root_directory.''.$filePath;
unlink($file);
$filePath = "modules/Mailchimp/actions/MailchimpSyncStep2.php";
$file = $root_directory.''.$filePath;
unlink($file);
$filePath = "modules/Mailchimp/actions/MailchimpSyncStep3.php";
$file = $root_directory.''.$filePath;
unlink($file);
$filePath = "modules/Mailchimp/actions/MailchimpSyncStep4.php";
$file = $root_directory.''.$filePath;
unlink($file);
echo "file deletion done.<br>";

echo "<br>re-enabling send-reminder cron if stuck...";
$adb->pquery("UPDATE vtiger_cron_task SET status = 1 WHERE name = 'SendReminder' AND status = 2");

echo "<br>update Tag version to 13.. ";
$query = "UPDATE `vtiger_version` SET `tag_version` = 'berlicrm-1.0.0.13'";
$adb->pquery($query, array());
echo " Tag version done.<br>";


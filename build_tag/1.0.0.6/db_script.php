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

$contactInstance = Vtiger_Module::getInstance('Contacts');
$leadsInstance = Vtiger_Module::getInstance('Leads');
//add links for GDPR PDF
$contactInstance->addLink(
	'DETAILVIEWBASIC',
	'LBL_DSGVO_NAME',
	'index.php?module=gdpr&action=printgdpr&scr_module=Contacts&recordid=$RECORD$',
	'themes/images/Contacts.gif'
);
$leadsInstance->addLink(
	'DETAILVIEWBASIC',
	'LBL_DSGVO_NAME',
	'index.php?module=gdpr&action=printgdpr&scr_module=Leads&recordid=$RECORD$'
);

// create dsgvo settings tables
$adb->pquery("DROP TABLE IF EXISTS `berli_dsgvo_global`");
$adb->pquery("CREATE TABLE `berli_dsgvo_global` (
    `op_mode` varchar(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'm',`del_note_time_days` int(10) NOT NULL,`del_mode` varchar(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
$adb->pquery("INSERT INTO `berli_dsgvo_global` VALUES ('m',7,'0')");
$adb->pquery("CREATE TABLE IF NOT EXISTS `berli_dsgvo_module` (
    `setting_date` datetime NOT NULL,`tabid` int(11) NOT NULL,`deletion_mode` int(11) NOT NULL,`fieldids` text COLLATE utf8_unicode_ci NOT NULL,
    PRIMARY KEY (`setting_date`,`tabid`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

// make workflow's "condition" column ("test") larger 
$adb->pquery("ALTER TABLE `com_vtiger_workflows` CHANGE `test` `test` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL");

// insert missing settings links
$blockid = getSettingsBlockId('LBL_OTHER_SETTINGS');
$seq_res = $adb->pquery("SELECT max(sequence) AS max_seq FROM vtiger_settings_field WHERE blockid = ?", array($blockid));
if ($adb->num_rows($seq_res) > 0) {
    $cur_seq = $adb->query_result($seq_res, 0, 'max_seq');
    if ($cur_seq != null) $seq = $cur_seq + 1;
}

$res = $adb->pquery("SELECT * FROM vtiger_settings_field WHERE name = ?",array('Scheduler'));
if (!$adb->num_rows($res)) {
    $id = $adb->getUniqueID("vtiger_settings_field");
    $q = "INSERT INTO vtiger_settings_field (fieldid, blockid, name, iconpath, description, linkto, sequence, active, pinned) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $adb->pquery($q,array($id, $blockid, 'Scheduler', NULL, 'Allows you to Configure Cron Task', 'index.php?module=CronTasks&parent=Settings&view=List', $seq++, 0, 0));
    echo "<br>Scheduler settings link created";
}

$res = $adb->pquery("SELECT * FROM vtiger_settings_field WHERE name = ?",array('gdpr'));
if (!$adb->num_rows($res)) {
    $id = $adb->getUniqueID("vtiger_settings_field");
    $q = "INSERT INTO vtiger_settings_field (fieldid, blockid, name, iconpath, description, linkto, sequence, active, pinned) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $adb->pquery($q,array($id, $blockid, 'gdpr', NULL, 'LBL_GRDPR_SETUP_DESCRIPTION', 'index.php?module=gdpr&parent=Settings&view=Index', $seq++, 0, 0));
    echo "<br>GDPR settings link created";
}

// change fieldtype of vtiger_notes.notecontent and vtiger_emailtemplates.body to mediumtext
$adb->pquery("ALTER TABLE `vtiger_notes` CHANGE `notecontent` `notecontent` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL");
$adb->pquery("ALTER TABLE `vtiger_emailtemplates` CHANGE `body` `body` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL");
echo "<br>Field types changed to mediumtext";

 echo "<br>create links for Document Drag & Drop Widget";
// Documents Drag & Drop widget
$module = Vtiger_Module::getInstance('Leads');
$module->addLink('DETAILVIEWSIDEBARWIDGET', 'LBL_UPLOAD_DOCUMENTS', 'module=berliWidgets&view=dropDocument');	
	
$module = Vtiger_Module::getInstance('Contacts');
$module->addLink('DETAILVIEWSIDEBARWIDGET', 'LBL_UPLOAD_DOCUMENTS', 'module=berliWidgets&view=dropDocument');	
	
$module = Vtiger_Module::getInstance('Accounts');
$module->addLink('DETAILVIEWSIDEBARWIDGET', 'LBL_UPLOAD_DOCUMENTS', 'module=berliWidgets&view=dropDocument');	
	
$module = Vtiger_Module::getInstance('Potentials');
$module->addLink('DETAILVIEWSIDEBARWIDGET', 'LBL_UPLOAD_DOCUMENTS', 'module=berliWidgets&view=dropDocument');	
	
$module = Vtiger_Module::getInstance('Quotes');
$module->addLink('DETAILVIEWSIDEBARWIDGET', 'LBL_UPLOAD_DOCUMENTS', 'module=berliWidgets&view=dropDocument');	
	
$module = Vtiger_Module::getInstance('SalesOrder');
$module->addLink('DETAILVIEWSIDEBARWIDGET', 'LBL_UPLOAD_DOCUMENTS', 'module=berliWidgets&view=dropDocument');	

$module = Vtiger_Module::getInstance('Invoice');
$module->addLink('DETAILVIEWSIDEBARWIDGET', 'LBL_UPLOAD_DOCUMENTS', 'module=berliWidgets&view=dropDocument');	
	
$module = Vtiger_Module::getInstance('PurchaseOrder');
$module->addLink('DETAILVIEWSIDEBARWIDGET', 'LBL_UPLOAD_DOCUMENTS', 'module=berliWidgets&view=dropDocument');	
	
$module = Vtiger_Module::getInstance('Vendors');
$module->addLink('DETAILVIEWSIDEBARWIDGET', 'LBL_UPLOAD_DOCUMENTS', 'module=berliWidgets&view=dropDocument');	
 echo "<br>Drag & Drop Widget links for Documents created";

echo "<br>update Tag version";
$query = "UPDATE `vtiger_version` SET `tag_version` = 'berlicrm-1.0.0.6'";
$adb->pquery($query, array());
echo " Tag version done.<br>";
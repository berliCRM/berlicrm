<?php
/*+********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *********************************************************************************/
vimport('~~include/Webservices/Utils.php');

if(defined('VTIGER_UPGRADE')) {

//Start add new currency - 'CFP Franc or Pacific Franc' 
global $adb;

Vtiger_Utils::AddColumn('vtiger_portalinfo', 'cryptmode', 'varchar(20)');
$adb->pquery("ALTER TABLE vtiger_portalinfo MODIFY COLUMN user_password varchar(255)", array());

//Updating existing users password to thier md5 hash
$portalinfo_hasmore = true;
do {
	$result = $adb->pquery('SELECT id, user_password FROM vtiger_portalinfo WHERE cryptmode is null limit 1000', array());
	
	$portalinfo_hasmore = false; // assume we are done.
	while ($row = $adb->fetch_array($result)) {
		$portalinfo_hasmore = true; // we found at-least one so there could be more.
		
		$enc_password = Vtiger_Functions::generateEncryptedPassword(decode_html($row['user_password']));
		$adb->pquery('UPDATE vtiger_portalinfo SET user_password=?, cryptmode = ? WHERE id=?', array($enc_password, 'CRYPT', $row['id']));
	}
	
} while ($portalinfo_hasmore);

//Change column type of inventory line-item comment.
$adb->pquery("ALTER TABLE vtiger_inventoryproductrel MODIFY COLUMN comment TEXT", array());


// Initlize mailer_queue tables.
include_once 'vtlib/Vtiger/Mailer.php';
$mailer = new Vtiger_Mailer();
$mailer->__initializeQueue();

//set settings links, fixes translation issue on migrations from 5.x
$adb->pquery("Update vtiger_settings_field set linkto='index.php?module=Users&parent=Settings&view=List' where name='LBL_USERS'", array());
$adb->pquery("Update vtiger_settings_field set linkto='index.php?module=Roles&parent=Settings&view=Index' where name='LBL_ROLES'", array());
$adb->pquery("Update vtiger_settings_field set linkto='index.php?module=Profiles&parent=Settings&view=List' where name='LBL_PROFILES'", array());
$adb->pquery("Update vtiger_settings_field set linkto='index.php?module=Groups&parent=Settings&view=List' where name='USERGROUPLIST'", array());
$adb->pquery("Update vtiger_settings_field set linkto='index.php?module=SharingAccess&parent=Settings&view=Index' where name='LBL_SHARING_ACCESS'", array());
$adb->pquery("Update vtiger_settings_field set linkto='index.php?module=FieldAccess&parent=Settings&view=Index' where name='LBL_FIELDS_ACCESS'", array());
$adb->pquery("Update vtiger_settings_field set linkto='index.php?module=LoginHistory&parent=Settings&view=List' where name='LBL_LOGIN_HISTORY_DETAILS'", array());
$adb->pquery("Update vtiger_settings_field set linkto='index.php?module=ModuleManager&parent=Settings&view=List' where name='VTLIB_LBL_MODULE_MANAGER'", array());
$adb->pquery("Update vtiger_settings_field set linkto='index.php?parent=Settings&module=Picklist&view=Index' where name='LBL_PICKLIST_EDITOR'", array());
$adb->pquery("Update vtiger_settings_field set linkto='index.php?parent=Settings&module=PickListDependency&view=List' where name='LBL_PICKLIST_DEPENDENCY_SETUP'", array());
$adb->pquery("Update vtiger_settings_field set linkto='index.php?module=MenuEditor&parent=Settings&view=Index' where name='LBL_MENU_EDITOR'", array());
$adb->pquery("Update vtiger_settings_field set linkto='index.php?module=Settings&view=listnotificationschedulers&parenttab=Settings' where name='NOTIFICATIONSCHEDULERS'", array());
$adb->pquery("Update vtiger_settings_field set linkto='index.php?module=Settings&view=listinventorynotifications&parenttab=Settings' where name='INVENTORYNOTIFICATION'", array());
$adb->pquery("Update vtiger_settings_field set linkto='index.php?parent=Settings&module=Vtiger&view=CompanyDetails' where name='LBL_COMPANY_DETAILS'", array());
$adb->pquery("Update vtiger_settings_field set linkto='index.php?parent=Settings&module=Vtiger&view=OutgoingServerDetail' where name='LBL_MAIL_SERVER_SETTINGS'", array());
$adb->pquery("Update vtiger_settings_field set linkto='index.php?parent=Settings&module=Currency&view=List' where name='LBL_CURRENCY_SETTINGS'", array());
$adb->pquery("Update vtiger_settings_field set linkto='index.php?module=Vtiger&parent=Settings&view=TaxIndex' where name='LBL_TAX_SETTINGS'", array());
$adb->pquery("Update vtiger_settings_field set linkto='index.php?module=Settings&submodule=Server&view=ProxyConfig' where name='LBL_SYSTEM_INFO'", array());
$adb->pquery("Update vtiger_settings_field set linkto='index.php?parent=Settings&module=Vtiger&view=AnnouncementEdit' where name='LBL_ANNOUNCEMENT'", array());
$adb->pquery("Update vtiger_settings_field set linkto='index.php?module=Settings&action=DefModuleView&parenttab=Settings' where name='LBL_DEFAULT_MODULE_VIEW'", array());
$adb->pquery("Update vtiger_settings_field set linkto='index.php?parent=Settings&module=Vtiger&view=TermsAndConditionsEdit' where name='INVENTORYTERMSANDCONDITIONS'", array());
$adb->pquery("Update vtiger_settings_field set linkto='index.php?module=Vtiger&parent=Settings&view=CustomRecordNumbering' where name='LBL_CUSTOMIZE_MODENT_NUMBER'", array());
$adb->pquery("Update vtiger_settings_field set linkto='index.php?parent=Settings&module=MailConverter&view=List' where name='LBL_MAIL_SCANNER'", array());
$adb->pquery("Update vtiger_settings_field set linkto='index.php?module=Workflows&parent=Settings&view=List' where name='LBL_LIST_WORKFLOWS'", array());
$adb->pquery("Update vtiger_settings_field set linkto='index.php?module=Vtiger&parent=Settings&view=ConfigEditorDetail' where name='LBL_CONFIG_EDITOR'", array());
// Extend description data-type (eg. allow large emails to be stored)
$adb->pquery("ALTER TABLE vtiger_crmentity MODIFY COLUMN description MEDIUMTEXT", array());

//crm-now: missing DB operations
//remove existing
$adb->pquery("DELETE FROM `vtiger_profile2standardpermissions` WHERE `operation`=7", array());
//add all new
$adb->pquery("INSERT INTO vtiger_actionmapping (actionid, actionname, securitycheck) VALUES ('7', 'CreateView', '0')", array());

$createActionResult = $adb->pquery('SELECT * FROM vtiger_profile2standardpermissions WHERE operation=?', array(1));
$query = 'INSERT INTO vtiger_profile2standardpermissions VALUES';
while($rowData = $adb->fetch_array($createActionResult)) {
	$tabId			= $rowData['tabid'];
	$profileId		= $rowData['profileid'];
	$permissions	= $rowData['permissions'];
	$query .= "('$profileId', '$tabId', '7', '$permissions'),";
}
$adb->pquery(trim($query, ','), array());

require_once './modules/Users/CreateUserPrivilegeFile.php';
$usersResult = $adb->pquery('SELECT id FROM vtiger_users', array());
$numOfRows = $adb->num_rows($usersResult);
$userIdsList = array();
for($i=0; $i<$numOfRows; $i++) {
	$userId = $adb->query_result($usersResult, $i, 'id');
	createUserPrivilegesfile($userId);
}

$operationId = vtws_addWebserviceOperation('retrievedocattachment','include/Webservices/RetrieveDocAttachment.php','berli_retrievedocattachment','Get','0');
vtws_addWebserviceOperationParam($operationId,'id','string','1');
vtws_addWebserviceOperationParam($operationId,'returnfile','string','2');

//fix for convertlead webservices
$adb->pquery("DELETE FROM vtiger_ws_operation_parameters WHERE operationid = (SELECT operationid from vtiger_ws_operation WHERE name = 'convertlead')", array());
$adb->pquery("INSERT INTO vtiger_ws_operation_parameters (operationid, name, type, sequence) VALUES ('13', 'element', 'encoded', '1')", array());
// fix for changePassword web service
$adb->pquery("update vtiger_ws_operation set handler_path ='include/Webservices/Custom/ChangePassword.php' where name = 'changePassword'", array());
// fix for Quote, Order and Invoice Web Services
$adb->pquery("UPDATE `vtiger_field` SET `typeofdata` = 'V~O' WHERE `vtiger_field`.`columnname` = 'productid' AND `vtiger_field`.`tablename` = 'vtiger_inventoryproductrel'", array());

// enable ModTracker for recycle bin if present
$recycleBinModule  = Vtiger_Module::getInstance('RecycleBin');
if ($recycleBinModule != false) {
    require_once('modules/ModTracker/ModTracker.php'); 
    ModTracker::enableTrackingForModule($recycleBinModule->id);
}

// add constraints to modtracker tables with neccessary cleanup
$adb->pquery('ALTER TABLE `vtiger_modtracker_detail` ADD CONSTRAINT `vtiger_modtracker_detail_ibfk_1` FOREIGN KEY (`id`) REFERENCES `vtiger_modtracker_basic` (`id`) ON DELETE CASCADE', array());
$adb->pquery('DELETE vtiger_modtracker_basic FROM vtiger_modtracker_basic LEFT JOIN vtiger_crmentity ON (vtiger_crmentity.crmid= vtiger_modtracker_basic.crmid) WHERE vtiger_modtracker_basic.crmid IS NOT NULL AND vtiger_crmentity.crmid IS NULL', array());
$adb->pquery('ALTER TABLE `vtiger_modtracker_basic` ADD CONSTRAINT `vtiger_modtracker_basic_ibfk_1` FOREIGN KEY (`crmid`) REFERENCES `vtiger_crmentity` (`crmid`) ON DELETE CASCADE', array());
}

// ------ Fix missing primary index on vtiger_modcomments

$res = $adb->pquery('SHOW INDEX FROM `vtiger_modcomments` WHERE Column_name = "modcommentsid"');
$row = $adb->fetchByAssoc($res);
if (empty($row)) {
    $res = $adb->pquery('ALTER TABLE `vtiger_modcomments` ADD PRIMARY KEY (`modcommentsid`)');
}

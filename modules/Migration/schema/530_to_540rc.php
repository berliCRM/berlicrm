<?php
/*+********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *********************************************************************************/
ini_set('display_errors',1);
error_reporting(E_ERROR | E_WARNING | E_PARSE );
require_once 'include/utils/utils.php';
require_once 'modules/com_vtiger_workflow/include.inc';
require_once 'modules/com_vtiger_workflow/tasks/VTEntityMethodTask.inc';
require_once 'modules/com_vtiger_workflow/VTEntityMethodManager.inc';
require_once 'include/events/include.inc';
include_once 'vtlib/Vtiger/Cron.php';

//5.0.3 to 5.0.4 database changes - added on 05-09-07
//we have to use the current object (stored in PatchApply.php) to execute the queries
//$adb = $_SESSION['adodb_current_object'];
$conn = $_SESSION['adodb_current_object'];

global $adb ;
//crm-now: remove or modify crm-now specific table entries
//language
$result=$adb->query("update `vtiger_users` set `language` = 'de_de' WHERE `language`='ge_de'");
$result=$adb->query("update `vtiger_language` set `name` = 'Deutsch',`label` = 'DE Deutsch', `prefix`='de_de'  WHERE `prefix`='ge_de'");
$result=$adb->query("UPDATE `vtiger_language` SET `label` = 'US English' WHERE `vtiger_language`.`name` ='English'");
$result=$adb->query("update `berli_pdfconfiguration` set `pdflang`= 'de_de' WHERE `pdflang`= 'ge_de'");

//settings field
$result=$adb->query("DELETE FROM `vtiger_settings_field` WHERE `vtiger_settings_field`.`name` = 'LBL_ASSIGN_MODULE_OWNERS'");

//webservices for quotes, orders and invoices
//remove our own modifications
//return to vtiger table coulumn name
$result=$adb->query("alter table vtiger_inventoryproductrel change column parent_id id integer(19) default null");

//remove link for old menu configurator
$result=$adb->query("DELETE FROM `vtiger_settings_field`  WHERE `vtiger_settings_field`.`name` = 'Menu Configurator' AND `vtiger_settings_field`.`description` = 'LBL_MENUCONFIGURATOR_DESCRIPTION'");

// remove special vtiger_ws_entity entries from old version which were designed for line items webservice
$result=$adb->query("update `vtiger_ws_entity` set `vtiger_ws_entity`.`handler_path` = 'include/Webservices/VtigerModuleOperation.php',  `vtiger_ws_entity`.`handler_class` = 'VtigerModuleOperation'  WHERE `vtiger_ws_entity`.`name` = 'Quotes' and `vtiger_ws_entity`.`handler_path` = 'include/Webservices/Custom/VtigerInventoryOperation.php'");
$result=$adb->query("update `vtiger_ws_entity` set `vtiger_ws_entity`.`handler_path` = 'include/Webservices/VtigerModuleOperation.php',  `vtiger_ws_entity`.`handler_class` = 'VtigerModuleOperation' WHERE `vtiger_ws_entity`.`name` = 'PurchaseOrder' and `vtiger_ws_entity`.`handler_path` = 'include/Webservices/Custom/VtigerInventoryOperation.php'");
$result=$adb->query("update `vtiger_ws_entity` set `vtiger_ws_entity`.`handler_path` = 'include/Webservices/VtigerModuleOperation.php',  `vtiger_ws_entity`.`handler_class` = 'VtigerModuleOperation' WHERE `vtiger_ws_entity`.`name` = 'SalesOrder' and `vtiger_ws_entity`.`handler_path` = 'include/Webservices/Custom/VtigerInventoryOperation.php'");
$result=$adb->query("update `vtiger_ws_entity` set `vtiger_ws_entity`.`handler_path` = 'include/Webservices/VtigerModuleOperation.php',  `vtiger_ws_entity`.`handler_class` = 'VtigerModuleOperation' WHERE `vtiger_ws_entity`.`name` = 'Invoice' and `vtiger_ws_entity`.`handler_path` = 'include/Webservices/Custom/VtigerInventoryOperation.php'");

$result=$adb->query("DELETE FROM `vtiger_ws_entity` WHERE `vtiger_ws_entity`.`name` = 'LineItem' and `vtiger_ws_entity`.`handler_path` = 'include/Webservices/Custom/VtigerLineItemOperation.php'");
$result=$adb->query("DELETE FROM `vtiger_ws_entity` WHERE `vtiger_ws_entity`.`name` = 'Tax' and `vtiger_ws_entity`.`handler_path` = 'include/Webservices/Custom/VtigerTaxOperation.php'");
$result=$adb->query("DELETE FROM `vtiger_ws_entity` WHERE `vtiger_ws_entity`.`name` = 'ProductTaxes' and `vtiger_ws_entity`.`handler_path` = 'include/Webservices/Custom/VtigerProductTaxesOperation.php'");

//SMS
$result=$adb->query("UPDATE `vtiger_tab` SET `isentitytype` = '0' WHERE `vtiger_tab`.`name` ='SMSNotifier'");

//related list
$result=$adb->query("DELETE FROM `vtiger_relatedlists` WHERE `vtiger_relatedlists`.`name` = 'get_relatedaccounts' and `vtiger_relatedlists`.`label` = 'Accounts'");

//buttons
$result=$adb->query("DELETE FROM `vtiger_links` WHERE `linklabel`='LBL_REMOVE_DUPLICATES'");

//phpList
$result=$adb->query("DELETE FROM `vtiger_tab` WHERE `vtiger_tab`.`name` = 'PHPListSync' and `vtiger_tab`.`tablabel` = 'PHPListSync'");
$result=$adb->query("DELETE FROM `vtiger_field` WHERE `vtiger_field`.`columnname` = 'listuser' and `vtiger_field`.`tablename` = 'crmnow_phplistsetup'");
$result=$adb->query("DROP TABLE IF EXISTS `crmnow_phplistsetup`");
$result=$adb->query("DROP TABLE IF EXISTS `eventlog`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_admin`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_admin_attribute`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_admin_task`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_adminattribute`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_attachment`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_bounce`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_bounceregex`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_bounceregex_bounce`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_config`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_eventlog`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_linktrack`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_linktrack_userclick`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_list`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_listattr_bpleaseche`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_listattr_bwheredoyo`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_listattr_cbgroup`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_listattr_comments`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_listattr_countries`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_listattr_hiddenfiel`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_listattr_iagreewith`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_listattr_most`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_listattr_othercomme`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_listattr_somemoreco`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_listmessage`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_listrss`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_listuser`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_message`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_message_attachment`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_messagedata`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_rssitem`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_rssitem_data`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_rssitem_user`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_sendprocess`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_subscribepage`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_subscribepage_data`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_task`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_template`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_templateimage`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_urlcache`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_user_attribute`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_user_blacklist`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_user_blacklist_data`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_user_message_bounce`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_user_message_forward`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_user_rss`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_user_user`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_user_user_attribute`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_user_user_history`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_usermessage`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_userstats`");

$result=$adb->query("DROP TABLE IF EXISTS `phplist_admintoken`");
$result=$adb->query("DROP TABLE IF EXISTS `phplist_formfield`");

// remove other customization
$result=$adb->query("DELETE FROM `vtiger_tab` WHERE `name`='Calendar4You'");
$result=$adb->query("DROP TABLE IF EXISTS `its4you_calendar4you_colors`");
$result=$adb->query("DROP TABLE IF EXISTS `its4you_calendar4you_event_fields`");
$result=$adb->query("DROP TABLE IF EXISTS `its4you_calendar4you_profilespermissions`");
$result=$adb->query("DROP TABLE IF EXISTS `its4you_calendar4you_settings`");
$result=$adb->query("DROP TABLE IF EXISTS `its4you_calendar4you_view`");
$result=$adb->query("DROP TABLE IF EXISTS `its4you_googlesync4you_access`");
$result=$adb->query("DROP TABLE IF EXISTS `its4you_googlesync4you_calendar`");
$result=$adb->query("DROP TABLE IF EXISTS `its4you_googlesync4you_dis`");
$result=$adb->query("DROP TABLE IF EXISTS `its4you_googlesync4you_events`");

//remove certain crm-now modules
$tabIdsResult = $adb->pquery('SELECT tabid, name FROM vtiger_tab', array());
$noOfTabs = $adb->num_rows($tabIdsResult);
$tabIdsList = array();

for ($i = 0; $i < $noOfTabs; ++$i) {
	$tabIdsList[$adb->query_result($tabIdsResult, $i, 'name')] = $adb->query_result($tabIdsResult, $i, 'tabid');
}
//530 modules to be removed
$remove_modules = array ('Map','Calendar4You','ExtensionsInstaller','PHPListSync','Mailchimp');
foreach ($remove_modules as $modulename) {
	// Deleting sharing access
	$adb->pquery("DELETE FROM vtiger_org_share_action2tab WHERE tabid=?", Array($tabIdsList[$modulename]));
	// Deleting tools
	$adb->pquery("DELETE FROM vtiger_profile2utility WHERE tabid=?", Array($tabIdsList[$modulename]));
	// Deleting fields of the module
	$adb->pquery("DELETE FROM vtiger_field WHERE tabid=?", Array($tabIdsList[$modulename]));
	// Deleting blocks for module
	$adb->pquery("DELETE FROM vtiger_blocks WHERE tabid=?", Array($tabIdsList[$modulename]));
	// De-Initializing webservices support
	$adb->pquery('DELETE FROM vtiger_ws_entity WHERE name=?',array($modulename));
	// Unsetting entity identifier ... DONE
	$adb->pquery("DELETE FROM vtiger_entityname WHERE tabid=?", Array($tabIdsList[$modulename]));
	// Deleting related lists
	$adb->pquery("DELETE FROM vtiger_relatedlists WHERE tabid=?", Array($tabIdsList[$modulename]));
	// Deleting Module as Tab
	$adb->pquery("DELETE FROM vtiger_tab WHERE tabid=?", Array($tabIdsList[$modulename]));
	// Deleting Links
	$adb->pquery('DELETE FROM vtiger_links WHERE tabid=?', Array($tabIdsList[$modulename]));
	// Detaching from menu
	$adb->pquery("DELETE FROM vtiger_parenttabrel WHERE tabid=?", Array($tabIdsList[$modulename]));

	// !!!! Invoking vtlib_handler for module.preuninstall 

}
// Updating parent_tabdata file
Vtiger_Menu::syncfile();
// Updating tabdata file
Vtiger_ModuleBasic::syncfile();

//remove crm-now modifications from ws entity entry
$adb->pquery("DELETE FROM `vtiger_ws_entity_tables` WHERE `vtiger_ws_entity_tables`.`table_name` = ?", Array('vtiger_inventoryproductrel'));
$adb->pquery("DELETE FROM `vtiger_ws_entity_fieldtype` WHERE `vtiger_ws_entity_fieldtype`.`field_name` = ? and `vtiger_ws_entity_fieldtype`.`table_name` = ? ", Array('parent_id','vtiger_inventoryproductrel'));
$adb->pquery("DELETE FROM `vtiger_ws_entity_fieldtype` WHERE `vtiger_ws_entity_fieldtype`.`field_name` = ? and `vtiger_ws_entity_fieldtype`.`table_name` = ? ", Array('productid','vtiger_inventoryproductrel'));
$adb->pquery("DELETE FROM `vtiger_ws_entity_fieldtype` WHERE `vtiger_ws_entity_fieldtype`.`field_name` = ? and `vtiger_ws_entity_fieldtype`.`table_name` = ? ", Array('incrementondel','vtiger_inventoryproductrel'));
$adb->pquery("DELETE FROM `vtiger_ws_entity_fieldtype` WHERE `vtiger_ws_entity_fieldtype`.`field_name` = ? and `vtiger_ws_entity_fieldtype`.`table_name` = ? ", Array('productid','vtiger_producttaxrel'));
$adb->pquery("DELETE FROM `vtiger_ws_entity_fieldtype` WHERE `vtiger_ws_entity_fieldtype`.`field_name` = ? and `vtiger_ws_entity_fieldtype`.`table_name` = ? ", Array('taxid','vtiger_producttaxrel'));
$adb->pquery("DELETE FROM `vtiger_ws_entity_referencetype` WHERE `vtiger_ws_entity_referencetype`.`type` = ? ", Array('Tax'));
$adb->pquery("DELETE FROM `vtiger_ws_entity_referencetype` WHERE `vtiger_ws_entity_referencetype`.`type` = ? ", Array('Products'));
$adb->pquery("DELETE FROM `vtiger_ws_entity_referencetype` WHERE `vtiger_ws_entity_referencetype`.`type` = ? ", Array('Invoice'));
$adb->pquery("DELETE FROM `vtiger_ws_entity_referencetype` WHERE `vtiger_ws_entity_referencetype`.`type` = ? ", Array('SalesOrder'));
$adb->pquery("DELETE FROM `vtiger_ws_entity_referencetype` WHERE `vtiger_ws_entity_referencetype`.`type` = ? ", Array('PurchaseOrder'));
$adb->pquery("DELETE FROM `vtiger_ws_entity_referencetype` WHERE `vtiger_ws_entity_referencetype`.`type` = ? ", Array('Quotes'));
$adb->pquery("DELETE FROM `vtiger_ws_entity_name` WHERE `vtiger_ws_entity_name`.`name_fields` = ? ", Array('taxlabel'));
$adb->pquery("DELETE FROM `vtiger_ws_entity_tables` WHERE `vtiger_ws_entity_tables`.`table_name` = ? ", Array('vtiger_inventorytaxinfo'));
$adb->pquery("DELETE FROM `vtiger_ws_entity_tables` WHERE `vtiger_ws_entity_tables`.`table_name` = ? ", Array('vtiger_producttaxrel'));

echo "crm-now customizations removed";



/*+********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *********************************************************************************/


//5.2.1 to 5.3.0RC database changes


$tabIdsResult = $adb->pquery('SELECT tabid, name FROM vtiger_tab', array());
$noOfTabs = $adb->num_rows($tabIdsResult);
$tabIdsList = array();

for ($i = 0; $i < $noOfTabs; ++$i) {
	$tabIdsList[$adb->query_result($tabIdsResult, $i, 'name')] = $adb->query_result($tabIdsResult, $i, 'tabid');
}
$leadTab = $tabIdsList['Leads'];
$accountTab = $tabIdsList['Accounts'];
$contactTab = $tabIdsList['Contacts'];
$potentialTab = $tabIdsList['Potentials'];
$usersTab = $tabIdsList['Users'];

$productsTabId = $tabIdsList['Products'];
$servicesTabId = $tabIdsList['Services'];
$documentsTabId = $tabIdsList['Documents'];

$skipForModules = array('ModComments');

$result = $adb->pquery("SELECT presence,quickcreate,masseditable,tabid,block FROM vtiger_field WHERE fieldname=?", array('createdtime'));
$rows = $adb->num_rows($result);
for($i=0; $i<$rows; $i++){
	$tabId = $adb->query_result($result,$i,'tabid');
	$blockId = $adb->query_result($result,$i,'block');
	$presence = $adb->query_result($result,$i,'presence');
	$quickcreate = $adb->query_result($result,$i,'quickcreate');
	$massedit = $adb->query_result($result,$i,'massedit');
	$moduleName = getTabModuleName($tabId);
	if(in_array($moduleName, $skipForModules)) continue;

	$moduleInstance = Vtiger_Module::getInstance($moduleName);
	$blockInstance = Vtiger_Block::getInstance($blockId,$moduleInstance);

	$field = new Vtiger_Field();
	$field->name = 'modifiedby';
	$field->label= 'Last Modified By';
	$field->table = 'vtiger_crmentity';
	$field->column = 'modifiedby';
	$field->uitype = 52;
	$field->displaytype = 3;
	$field->presence = $presence;
	$field->quickcreate = $quickcreate;
	$field->masseditable = $massedit;
	$blockInstance->addField($field);
}

$moduleInstance = Vtiger_Module::getInstance('Home');
$moduleInstance->addLink(
		'HEADERSCRIPT', 'Help Me', 'modules/Home/js/HelpMeNow.js'
);

$adb->pquery("UPDATE vtiger_blocks SET sequence = ? WHERE blocklabel = ? AND tabid = ? ", array(2, 'LBL_FILE_INFORMATION', $documentsTabId));
$adb->pquery("UPDATE vtiger_blocks SET sequence = ? WHERE blocklabel = ? AND tabid = ?", array(3, 'LBL_DESCRIPTION', $documentsTabId));

// Adding 'from_portal' field to Trouble tickets module, to track the tickets created from customer portal
$moduleInstance = Vtiger_Module::getInstance('HelpDesk');
$block = Vtiger_Block::getInstance('LBL_TICKET_INFORMATION', $moduleInstance);

$field = new Vtiger_Field();
$field->name = 'from_portal';
$field->label = 'From Portal';
$field->table = 'vtiger_troubletickets';
$field->column = 'from_portal';
$field->columntype = 'varchar(3)';
$field->typeofdata = 'C~O';
$field->uitype = 56;
$field->displaytype = 3;
$field->presence = 0;
$block->addField($field);

// Register Entity Methods
$emm = new VTEntityMethodManager($adb);

// Register Entity Method for Customer Portal Login details email notification task
$emm->addEntityMethod("Contacts", "SendPortalLoginDetails", "modules/Contacts/ContactsHandler.php", "Contacts_sendCustomerPortalLoginDetails");

// Register Entity Method for Email notification on ticket creation from Customer portal
$emm->addEntityMethod("HelpDesk", "NotifyOnPortalTicketCreation", "modules/HelpDesk/HelpDeskHandler.php", "HelpDesk_nofifyOnPortalTicketCreation");

// Register Entity Method for Email notification on ticket comment from Customer portal
$emm->addEntityMethod("HelpDesk", "NotifyOnPortalTicketComment", "modules/HelpDesk/HelpDeskHandler.php", "HelpDesk_notifyOnPortalTicketComment");

// Register Entity Method for Email notification to Record Owner on ticket change, which is not from Customer portal
$emm->addEntityMethod("HelpDesk", "NotifyOwnerOnTicketChange", "modules/HelpDesk/HelpDeskHandler.php", "HelpDesk_notifyOwnerOnTicketChange");

// Register Entity Method for Email notification to Related Customer on ticket change, which is not from Customer portal
$emm->addEntityMethod("HelpDesk", "NotifyParentOnTicketChange", "modules/HelpDesk/HelpDeskHandler.php", "HelpDesk_notifyParentOnTicketChange");

 
echo "Creating Default workflows";
//crm-now: since we run 5.4 scripts under 6.x make sure that all fields are created for workflow object
$result = $adb->pquery("show columns from com_vtiger_workflows like ?", array('filtersavedinnew'));
if (!($adb->num_rows($result))) {
    $adb->pquery("ALTER TABLE com_vtiger_workflows ADD filtersavedinnew int(1)", array());
}
if (!($adb->num_rows($result))) {
    $adb->pquery("ALTER TABLE com_vtiger_workflows ADD schtypeid INT(10)", array());
}
$result = $adb->pquery("show columns from com_vtiger_workflows like ?", array('schtime'));
if (!($adb->num_rows($result))) {
    $adb->pquery("ALTER TABLE com_vtiger_workflows ADD schtime TIME", array());
}
$result = $adb->pquery("show columns from com_vtiger_workflows like ?", array('schdayofmonth'));
if (!($adb->num_rows($result))) {
    $adb->pquery("ALTER TABLE com_vtiger_workflows ADD schdayofmonth VARCHAR(100)", array());
}
$result = $adb->pquery("show columns from com_vtiger_workflows like ?", array('schdayofweek'));
if (!($adb->num_rows($result))) {
    $adb->pquery("ALTER TABLE com_vtiger_workflows ADD schdayofweek VARCHAR(100)", array());
}
$result = $adb->pquery("show columns from com_vtiger_workflows like ?", array('schannualdates'));
if (!($adb->num_rows($result))) {
    $adb->pquery("ALTER TABLE com_vtiger_workflows ADD schannualdates VARCHAR(100)", array());
}
$result = $adb->pquery("show columns from com_vtiger_workflows like ?", array('nexttrigger_time'));
if (!($adb->num_rows($result))) {
    $adb->pquery("ALTER TABLE com_vtiger_workflows ADD nexttrigger_time DATETIME", array());
}



$workflowManager = new VTWorkflowManager($adb);
$taskManager = new VTTaskManager($adb);

// Contact workflow on creation/modification
$contactWorkFlow = $workflowManager->newWorkFlow("Contacts");
$contactWorkFlow->test = '';
$contactWorkFlow->description = "Workflow for Contact Creation or Modification";
$contactWorkFlow->executionCondition = VTWorkflowManager::$ON_EVERY_SAVE;
$contactWorkFlow->defaultworkflow = 1;
$workflowManager->save($contactWorkFlow);

//crm-now: introduce table 
 $adb->pquery("CREATE TABLE IF NOT EXISTS com_vtiger_workflow_tasktypes (
					id int(11) NOT NULL,
					tasktypename varchar(255) NOT NULL,
					label varchar(255),
					classname varchar(255),
					classpath varchar(255),
					templatepath varchar(255),
					modules text(500),
					sourcemodule varchar(255)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8", array());
			
// $task = $taskManager->createTask('VTEntityMethodTask', $contactWorkFlow->id);
// $task->active = true;
// $task->summary = 'Email Customer Portal Login Details';
// $task->methodName = "SendPortalLoginDetails";
// $taskManager->saveTask($task);

// Trouble Tickets workflow on creation from Customer Portal
$helpDeskWorkflow = $workflowManager->newWorkFlow("HelpDesk");
$helpDeskWorkflow->test = '[{"fieldname":"from_portal","operation":"is","value":"true:boolean"}]';
$helpDeskWorkflow->description = "Workflow for Ticket Created from Portal";
$helpDeskWorkflow->executionCondition = VTWorkflowManager::$ON_FIRST_SAVE;
$helpDeskWorkflow->defaultworkflow = 1;
$workflowManager->save($helpDeskWorkflow);

// $task = $taskManager->createTask('VTEntityMethodTask', $helpDeskWorkflow->id);
// $task->active = true;
// $task->summary = 'Notify Record Owner and the Related Contact when Ticket is created from Portal';
// $task->methodName = "NotifyOnPortalTicketCreation";
// $taskManager->saveTask($task);

// Trouble Tickets workflow on ticket update from Customer Portal
$helpDeskWorkflow = $workflowManager->newWorkFlow("HelpDesk");
$helpDeskWorkflow->test = '[{"fieldname":"from_portal","operation":"is","value":"true:boolean"}]';
$helpDeskWorkflow->description = "Workflow for Ticket Updated from Portal";
$helpDeskWorkflow->executionCondition = VTWorkflowManager::$ON_MODIFY;
$helpDeskWorkflow->defaultworkflow = 1;
$workflowManager->save($helpDeskWorkflow);

// $task = $taskManager->createTask('VTEntityMethodTask', $helpDeskWorkflow->id);
// $task->active = true;
// $task->summary = 'Notify Record Owner when Comment is added to a Ticket from Customer Portal';
// $task->methodName = "NotifyOnPortalTicketComment";
// $taskManager->saveTask($task);

// Trouble Tickets workflow on ticket change, which is not from Customer Portal - Both Record Owner and Related Customer
$helpDeskWorkflow = $workflowManager->newWorkFlow("HelpDesk");
$helpDeskWorkflow->test = '[{"fieldname":"from_portal","operation":"is","value":"false:boolean"}]';
$helpDeskWorkflow->description = "Workflow for Ticket Change, not from the Portal";
$helpDeskWorkflow->executionCondition = VTWorkflowManager::$ON_EVERY_SAVE;
$helpDeskWorkflow->defaultworkflow = 1;
$workflowManager->save($helpDeskWorkflow);

// $task = $taskManager->createTask('VTEntityMethodTask', $helpDeskWorkflow->id);
// $task->active = true;
// $task->summary = 'Notify Record Owner on Ticket Change, which is not done from Portal';
// $task->methodName = "NotifyOwnerOnTicketChange";
// $taskManager->saveTask($task);

// $task = $taskManager->createTask('VTEntityMethodTask', $helpDeskWorkflow->id);
// $task->active = true;
// $task->summary = 'Notify Related Customer on Ticket Change, which is not done from Portal';
// $task->methodName = "NotifyParentOnTicketChange";
// $taskManager->saveTask($task);

// Events workflow when Send Notification is checked
$eventsWorkflow = $workflowManager->newWorkFlow("Events");
$eventsWorkflow->test = '[{"fieldname":"sendnotification","operation":"is","value":"true:boolean"}]';
$eventsWorkflow->description = "Workflow for Events when Send Notification is True";
$eventsWorkflow->executionCondition = VTWorkflowManager::$ON_EVERY_SAVE;
$eventsWorkflow->defaultworkflow = 1;
$workflowManager->save($eventsWorkflow);

// $task = $taskManager->createTask('VTEmailTask', $eventsWorkflow->id);
// $task->active = true;
// $task->summary = 'Send Notification Email to Record Owner';
// $task->recepient = "\$(assigned_user_id : (Users) email1)";
// $task->subject = "Event :  \$subject";
// $task->content = '$(assigned_user_id : (Users) last_name) $(assigned_user_id : (Users) first_name) ,<br/>'
		// . '<b>Activity Notification Details:</b><br/>'
		// . 'Subject             : $subject<br/>'
		// . 'Start date and time : $date_start  $time_start ( $(general : (__VtigerMeta__) dbtimezone) ) <br/>'
		// . 'End date and time   : $due_date  $time_end ( $(general : (__VtigerMeta__) dbtimezone) ) <br/>'
		// . 'Status              : $eventstatus <br/>'
		// . 'Priority            : $taskpriority <br/>'
		// . 'Related To          : $(parent_id : (Leads) lastname) $(parent_id : (Leads) firstname) $(parent_id : (Accounts) accountname) '
								// . '$(parent_id : (Potentials) potentialname) $(parent_id : (HelpDesk) ticket_title) <br/>'
		// . 'Contacts List       : $(contact_id : (Contacts) lastname) $(contact_id : (Contacts) firstname) <br/>'
		// . 'Location            : $location <br/>'
		// . 'Description         : $description';
// $taskManager->saveTask($task);

// Calendar workflow when Send Notification is checked
$calendarWorkflow = $workflowManager->newWorkFlow("Calendar");
$calendarWorkflow->test = '[{"fieldname":"sendnotification","operation":"is","value":"true:boolean"}]';
$calendarWorkflow->description = "Workflow for Calendar Todos when Send Notification is True";
$calendarWorkflow->executionCondition = VTWorkflowManager::$ON_EVERY_SAVE;
$calendarWorkflow->defaultworkflow = 1;
$workflowManager->save($calendarWorkflow);

// $task = $taskManager->createTask('VTEmailTask', $calendarWorkflow->id);
// $task->active = true;
// $task->summary = 'Send Notification Email to Record Owner';
// $task->recepient = "\$(assigned_user_id : (Users) email1)";
// $task->subject = "Task :  \$subject";
// $task->content = '$(assigned_user_id : (Users) last_name) $(assigned_user_id : (Users) first_name) ,<br/>'
		// . '<b>Task Notification Details:</b><br/>'
		// . 'Subject : $subject<br/>'
		// . 'Start date and time : $date_start  $time_start ( $(general : (__VtigerMeta__) dbtimezone) ) <br/>'
		// . 'End date and time   : $due_date ( $(general : (__VtigerMeta__) dbtimezone) ) <br/>'
		// . 'Status              : $taskstatus <br/>'
		// . 'Priority            : $taskpriority <br/>'
		// . 'Related To          : $(parent_id : (Leads) lastname) $(parent_id : (Leads) firstname) $(parent_id : (Accounts) accountname) '
		// . '$(parent_id         : (Potentials) potentialname) $(parent_id : (HelpDesk) ticket_title) <br/>'
		// . 'Contacts List       : $(contact_id : (Contacts) lastname) $(contact_id : (Contacts) firstname) <br/>'
		// . 'Location            : $location <br/>'
		// . 'Description         : $description';
// $taskManager->saveTask($task);

$adb->pquery("UPDATE com_vtiger_workflows SET defaultworkflow=1 WHERE
			module_name='Invoice' and summary='UpdateInventoryProducts On Every Save'", array());

$em = new VTEventsManager($adb);
// Registering event for HelpDesk - To reset from_portal value
$em->registerHandler('vtiger.entity.aftersave.final', 'modules/HelpDesk/HelpDeskHandler.php', 'HelpDeskHandler');

Vtiger_Cron::register('Workflow', 'cron/modules/com_vtiger_workflow/com_vtiger_workflow.service', 900, 'com_vtiger_workflow', '', '', 'Recommended frequency for Workflow is 15 mins');
Vtiger_Cron::register('RecurringInvoice', 'cron/modules/SalesOrder/RecurringInvoice.service', 43200, 'SalesOrder', '', '', 'Recommended frequency for RecurringInvoice is 12 hours');
Vtiger_Cron::register('SendReminder', 'cron/SendReminder.service', 900, 'Calendar', '', '', 'Recommended frequency for SendReminder is 15 mins');
Vtiger_Cron::register('ScheduleReports', 'cron/modules/Reports/ScheduleReports.service', 900, 'Reports', '', '', 'Recommended frequency for ScheduleReports is 15 mins');
Vtiger_Cron::register('MailScanner', 'cron/MailScanner.service', 900, 'Settings', '', '', 'Recommended frequency for MailScanner is 15 mins');

$adb->pquery("DELETE FROM vtiger_settings_field WHERE name='LBL_ASSIGN_MODULE_OWNERS'", array());

Vtiger_Utils::AddColumn('vtiger_tab', 'parent','VARCHAR(30)');

$adb->query("update vtiger_tab set parent = 'Sales' where name = 'Accounts'");
$adb->query("update vtiger_tab set parent = 'Tools' where name = 'Calendar'");
$adb->query("update vtiger_tab set parent = 'Sales' where name = 'Contacts'");
$adb->query("update vtiger_tab set parent = 'Analytics' where name = 'Dashboard'");
$adb->query("update vtiger_tab set parent = 'Sales' where name = 'Leads'");
$adb->query("update vtiger_tab set parent = 'Sales' where name = 'Potentials'");
$adb->query("update vtiger_tab set parent = 'Inventory' where name = 'Vendors'");
$adb->query("update vtiger_tab set parent = 'Inventory' where name = 'Products'");
$adb->query("update vtiger_tab set parent = 'Tools' where name = 'Documents'");
$adb->query("update vtiger_tab set parent = 'Tools' where name = 'Emails'");
$adb->query("update vtiger_tab set parent = 'Support' where name = 'HelpDesk'");
$adb->query("update vtiger_tab set parent = 'Support' where name = 'Faq'");
$adb->query("update vtiger_tab set tabsequence = -1 where name = 'Faq'");
$adb->query("update vtiger_tab set parent = 'Inventory' where name = 'PriceBooks'");
$adb->query("update vtiger_tab set tabsequence = -1 where name = 'PriceBooks'");
$adb->query("update vtiger_tab set parent = 'Sales' where name = 'SalesOrder'");
$adb->query("update vtiger_tab set tabsequence = -1 where name = 'SalesOrder'");
$adb->query("update vtiger_tab set parent = 'Sales' where name = 'Quotes'");
$adb->query("update vtiger_tab set tabsequence = -1 where name = 'Quotes'");
$adb->query("update vtiger_tab set parent = 'Inventory' where name = 'PurchaseOrder'");
$adb->query("update vtiger_tab set tabsequence = -1 where name = 'PurchaseOrder'");
$adb->query("update vtiger_tab set parent = 'Sales' where name = 'Invoice'");
$adb->query("update vtiger_tab set tabsequence = -1 where name = 'Invoice'");
$adb->query("update vtiger_tab set parent = 'Tools' where name = 'RSS'");
$adb->query("update vtiger_tab set tabsequence = -1 where name = 'RSS'");
$adb->query("update vtiger_tab set parent = 'Analytics' where name = 'Reports'");
$adb->query("update vtiger_tab set tabsequence = -1 where name = 'Reports'");
$adb->query("update vtiger_tab set parent = 'Marketing' where name = 'Campaigns'");
$adb->query("update vtiger_tab set tabsequence = -1 where name = 'Campaigns'");
$adb->query("update vtiger_tab set parent = 'Tools' where name = 'Portal'");
$adb->query("update vtiger_tab set tabsequence = -1 where name = 'Portal'");
$adb->query("update vtiger_tab set parent = 'Support' where name = 'ServiceContracts'");
$adb->query("update vtiger_tab set tabsequence = -1 where name = 'ServiceContracts'");
$adb->query("update vtiger_tab set parent = 'Tools' where name = 'PBX Manager'");
$adb->query("update vtiger_tab set tabsequence = -1 where name = 'PBX Manager'");
$adb->query("update vtiger_tab set parent = 'Inventory' where name = 'Services'");
$adb->query("update vtiger_tab set tabsequence = -1 where name = 'Services'");
$adb->query("update vtiger_tab set parent = 'Tools' where name = 'RecycleBin'");
$adb->query("update vtiger_tab set tabsequence = -1 where name = 'RecycleBin'");
$adb->query("update vtiger_tab set parent = 'Support' where name = 'Assets'");
$adb->query("update vtiger_tab set tabsequence = -1 where name = 'Assets'");
$adb->query("update vtiger_tab set parent = 'Tools' where name = 'ModComments'");
$adb->query("update vtiger_tab set tabsequence = -1 where name = 'ModComments'");
$adb->query("update vtiger_tab set parent = 'Support' where name = 'ProjectMilestone'");
$adb->query("update vtiger_tab set tabsequence = -1 where name = 'ProjectMilestone'");
$adb->query("update vtiger_tab set parent = 'Support' where name = 'ProjectTask'");
$adb->query("update vtiger_tab set tabsequence = -1 where name = 'ProjectTask'");
$adb->query("update vtiger_tab set parent = 'Support' where name = 'Project'");
$adb->query("update vtiger_tab set tabsequence = -1 where name = 'Project'");
$adb->query("update vtiger_tab set parent = 'Tools' where name = 'SMSNotifier'");
$adb->query("update vtiger_tab set tabsequence = -1 where name = 'SMSNotifier'");
$adb->query("update vtiger_tab set parent = 'Tools' where name = 'MailManager'");
$adb->query("update vtiger_tab set tabsequence = -1 where name = 'MailManager'");
$adb->query("update vtiger_tab set tabsequence = -1 where name = 'Events'");

$fieldId = $adb->getUniqueId("vtiger_settings_field");
$adb->query("insert into vtiger_settings_field (fieldid,blockid,name,iconpath,description,linkto,sequence,active)
					values ($fieldId," . getSettingsBlockId('LBL_STUDIO') . ",'LBL_MENU_EDITOR','menueditor.png','LBL_MENU_DESC',
					'index.php?module=Settings&action=MenuEditor&parenttab=Settings',4,0)");

$present_module = array();
$result = $adb->query('select tabid,name,tablabel,tabsequence,parent from vtiger_tab where parent is not null and parent!=" "');
for ($i = 0; $i < $adb->num_rows($result); $i++) {
	$modulename = $adb->query_result($result, $i, 'name');
	$modulelabel = $adb->query_result($result, $i, 'tablabel');
	array_push($present_module, $modulelabel);
}
$result = $adb->query("select name,tablabel,parenttab_label,vtiger_tab.tabid
							from vtiger_parenttabrel
							inner join vtiger_tab on vtiger_parenttabrel.tabid = vtiger_tab.tabid
							inner join vtiger_parenttab on vtiger_parenttabrel.parenttabid = vtiger_parenttab.parenttabid
									and vtiger_parenttab.parenttab_label is not null
									and vtiger_parenttab.parenttab_label != ' '");

$skipModules = array("Webmails", "Home");
for ($i = 0; $i < $adb->num_rows($result); $i++) {
	$modulename = $adb->query_result($result, $i, 'name');
	$modulelabel = $adb->query_result($result, $i, 'tablabel');
	$parent = $adb->query_result($result, $i, 'parenttab_label');
	if ((!(in_array($modulelabel, $present_module))) && (!(in_array($modulelabel, $skipModules)))) {
		if ($modulelabel == "MailManager") {
			$adb->pquery("update vtiger_tab set parent = ? where tablabel = ?", array("Tools", $modulelabel));
			$adb->pquery("update vtiger_tab set tabsequence = -1 where tablabel = ?", array($modulelabel));
		} else {
			$adb->pquery("update vtiger_tab set parent = ? where tablabel = ?", array($parent, $modulelabel));
		}
	}
}

$adb->query("ALTER TABLE `vtiger_customerportal_prefs` DROP PRIMARY KEY");
$adb->query("ALTER TABLE `vtiger_customerportal_prefs` ALTER COLUMN prefkey DROP DEFAULT");
$adb->query("ALTER TABLE `vtiger_customerportal_prefs` ADD PRIMARY KEY(tabid,prefkey)");

$query = "INSERT INTO vtiger_customerportal_prefs (
			SELECT tabid, 'defaultassignee', prefvalue FROM vtiger_customerportal_prefs WHERE prefkey='userid'
		)";
$adb->pquery($query, array());

$fieldMap = array(
	array('industry', 'industry', null, null),
	array('phone', 'phone', 'phone', null),
	array('fax', 'fax', 'fax', null),
	array('rating', 'rating', null, null),
	array('email', 'email1', 'email', null),
	array('website', 'website', null, null),
	array('city', 'bill_city', 'mailingcity', null),
	array('code', 'bill_code', 'mailingcode', null),
	array('country', 'bill_country', 'mailingcountry', null),
	array('state', 'bill_state', 'mailingstate', null),
	array('lane', 'bill_street', 'mailingstreet', null),
	array('pobox', 'bill_pobox', 'mailingpobox', null),
	array('city', 'ship_city', null, null),
	array('code', 'ship_code', null, null),
	array('country', 'ship_country', null, null),
	array('state', 'ship_state', null, null),
	array('lane', 'ship_street', null, null),
	array('pobox', 'ship_pobox', null, null),
	array('description', 'description', 'description', 'description'),
	array('salutationtype', null, 'salutationtype', null),
	array('firstname', null, 'firstname', null),
	array('lastname', null, 'lastname', null),
	array('mobile', null, 'mobile', null),
	array('designation', null, 'title', null),
	array('secondaryemail', null, 'secondaryemail', null),
	array('leadsource', null, 'leadsource', 'leadsource'),
	array('leadstatus', null, null, null),
	array('noofemployees', 'employees', null, null),
	array('annualrevenue', 'annual_revenue', null, null)
);

$mapSql = "INSERT INTO vtiger_convertleadmapping(leadfid,accountfid,contactfid,potentialfid) values(?,?,?,?)";

foreach ($fieldMap as $values) {
	$leadfid = getFieldid($leadTab, $values[0]);
	$accountfid = getFieldid($accountTab, $values[1]);
	$contactfid = getFieldid($contactTab, $values[2]);
	$potentialfid = getFieldid($potentialTab, $values[3]);
	$adb->pquery($mapSql, array($leadfid, $accountfid, $contactfid, $potentialfid));
}

$delete_empty_mapping = "DELETE FROM vtiger_convertleadmapping WHERE accountfid=0 AND contactfid=0 AND potentialfid=0";
$adb->pquery($delete_empty_mapping, array());
$alter_vtiger_convertleadmapping = "ALTER TABLE vtiger_convertleadmapping ADD COLUMN editable int default 1";
$adb->pquery($alter_vtiger_convertleadmapping, array());

$check_mapping = "SELECT 1 FROM vtiger_convertleadmapping WHERE leadfid=? AND accountfid=? AND contactfid=? AND  potentialfid=?";
$insert_mapping = "INSERT INTO vtiger_convertleadmapping(leadfid,accountfid,contactfid,potentialfid,editable) VALUES(?,?,?,?,?)";
$update_mapping = "UPDATE vtiger_convertleadmapping SET editable=0 WHERE leadfid=? AND accountfid=? AND contactfid=? AND potentialfid=?";
$check_res = $adb->pquery($check_mapping, array(getFieldid($leadTab, 'company'), getFieldid($accountTab, 'accountname'), 0, getFieldid($potentialTab, 'potentialname')));
if ($adb->num_rows($check_res) > 0) {
	$adb->pquery($update_mapping, array(getFieldid($leadTab, 'company'), getFieldid($accountTab, 'accountname'), 0, getFieldid($potentialTab, 'potentialname')));
} else {
	$adb->pquery($insert_mapping, array(getFieldid($leadTab, 'company'), getFieldid($accountTab, 'accountname'), null, getFieldid($potentialTab, 'potentialname'), 0));
}

$check_res = $adb->pquery($check_mapping, array(getFieldid($leadTab, 'email'), getFieldid($accountTab, 'email1'), getFieldid($contactTab, 'email'), 0));
if ($adb->num_rows($check_res) > 0) {
	$adb->pquery($update_mapping, array(getFieldid($leadTab, 'email'), getFieldid($accountTab, 'email1'), getFieldid($contactTab, 'email'), 0));
} else {
	$adb->pquery($insert_mapping, array(getFieldid($leadTab, 'email'), getFieldid($accountTab, 'email1'), getFieldid($contactTab, 'email'), null, 0));
}

$check_res = $adb->pquery($check_mapping, array(getFieldid($leadTab, 'firstname'), 0, getFieldid($contactTab, 'firstname'), 0));
if ($adb->num_rows($check_res) > 0) {
	$adb->pquery($update_mapping, array(getFieldid($leadTab, 'firstname'), 0, getFieldid($contactTab, 'firstname'), 0));
} else {
	$adb->pquery($insert_mapping, array(getFieldid($leadTab, 'firstname'), null, getFieldid($contactTab, 'firstname'), null, 0));
}

$check_res = $adb->pquery($check_mapping, array(getFieldid($leadTab, 'lastname'), 0, getFieldid($contactTab, 'lastname'), 0));
if ($adb->num_rows($check_res) > 0) {
	$adb->pquery($update_mapping, array(getFieldid($leadTab, 'lastname'), 0, getFieldid($contactTab, 'lastname'), 0));
} else {
	$adb->pquery($insert_mapping, array(getFieldid($leadTab, 'lastname'), null, getFieldid($contactTab, 'lastname'), null, 0));
}

$productInstance = Vtiger_Module::getInstance('Products');
$serviceInstance = Vtiger_Module::getInstance('Services');

/* Replace 'Handler' field with 'Assigned to' field for Products and Services - starts */
$adb->query("UPDATE vtiger_crmentity, vtiger_products SET vtiger_crmentity.smownerid = vtiger_products.handler WHERE vtiger_crmentity.crmid = vtiger_products.productid");
$adb->query("ALTER TABLE vtiger_products DROP COLUMN handler");
$adb->pquery("UPDATE vtiger_field SET columnname = 'smownerid', tablename = 'vtiger_crmentity', uitype = '53', typeofdata = 'V~M', info_type = 'BAS', quickcreate = 0, quickcreatesequence = 5
				WHERE columnname = 'handler' AND tablename = 'vtiger_products' AND tabid = ?", array($productsTabId));
$oldProductHandlerColumnName = 'vtiger_products:handler:assigned_user_id:Products_Handler:V';
$newProductHandlerColumnName = 'vtiger_crmentity:smownerid:assigned_user_id:Products_Handler:V';
$adb->pquery("UPDATE vtiger_cvcolumnlist SET columnname=? WHERE columnname=?", array($newProductHandlerColumnName, $oldProductHandlerColumnName));
$adb->pquery("UPDATE vtiger_cvadvfilter SET columnname=? WHERE columnname=?", array($newProductHandlerColumnName, $oldProductHandlerColumnName));

$adb->query("UPDATE vtiger_crmentity, vtiger_service SET vtiger_crmentity.smownerid = vtiger_service.handler WHERE vtiger_crmentity.crmid = vtiger_service.serviceid");
$adb->query("ALTER TABLE vtiger_service DROP COLUMN handler");
$adb->pquery("UPDATE vtiger_field SET columnname = 'smownerid', tablename = 'vtiger_crmentity', uitype = '53', typeofdata = 'V~M', info_type = 'BAS', quickcreate = 0, quickcreatesequence = 4
				WHERE columnname = 'handler' AND tablename = 'vtiger_service' AND tabid = ?", array($servicesTabId));
$oldServiceOwnerColumnName = 'vtiger_service:handler:assigned_user_id:Services_Owner:V';
$newServiceOwnerColumnName = 'vtiger_crmentity:smownerid:assigned_user_id:Services_Owner:V';
$adb->pquery("UPDATE vtiger_cvcolumnlist SET columnname=? WHERE columnname=?", array($newServiceOwnerColumnName, $oldServiceOwnerColumnName));
$adb->pquery("UPDATE vtiger_cvadvfilter SET columnname=? WHERE columnname=?", array($newServiceOwnerColumnName, $oldServiceOwnerColumnName));

// Allow Sharing access and role-based security for Products and Services
Vtiger_Access::deleteSharing($productInstance);
Vtiger_Access::initSharing($productInstance);
Vtiger_Access::allowSharing($productInstance);
Vtiger_Access::setDefaultSharing($productInstance);

Vtiger_Access::deleteSharing($serviceInstance);
Vtiger_Access::initSharing($serviceInstance);
Vtiger_Access::allowSharing($serviceInstance);
Vtiger_Access::setDefaultSharing($serviceInstance);

Vtiger_Module::syncfile();
/* Replace 'Handler' field with 'Assigned to' field for Products and Services - ends */

$adb->pquery("UPDATE vtiger_entityname SET fieldname = 'firstname,lastname' WHERE tabid= ? ", array($contactTab));
$adb->pquery("UPDATE vtiger_entityname SET fieldname = 'firstname,lastname' WHERE tabid= ? ", array($leadTab));
$adb->pquery("UPDATE vtiger_entityname SET fieldname = 'first_name,last_name' WHERE tabid= ? ", array($usersTab));

require_once 'include/utils/utils.php';

$usersQuery = "SELECT * FROM vtiger_users";
$usersResult = $adb->query($usersQuery);
$usersCount = $adb->num_rows($usersResult);
for($i=0;$i<$usersCount;++$i){
	$userId = $adb->query_result($usersResult,$i,'id');
	$userName = $adb->query_result($usersResult,$i,'user_name');
	$firstName = $adb->query_result($usersResult,$i,'first_name');
	$lastName = $adb->query_result($usersResult,$i,'last_name');
	$fullName = getFullNameFromQResult($usersResult, $i, 'Users');
	$oldFullName = $lastName.' '.$firstName;

	$adb->pquery("UPDATE vtiger_cvadvfilter SET value=? WHERE columnname LIKE '%:assigned_user_id:%' AND value=?", array($fullName, $oldFullName));
	$adb->pquery("UPDATE vtiger_cvadvfilter SET value=? WHERE columnname LIKE '%:modifiedby:%' AND value=?", array($fullName, $oldFullName));
	$adb->pquery("UPDATE vtiger_cvadvfilter SET value=? WHERE columnname LIKE '%:assigned_user_id1:%' AND value=?", array($fullName, $oldFullName));
	$adb->pquery("UPDATE vtiger_relcriteria SET value=? WHERE columnname LIKE 'vtiger_users%:user_name%' AND value=?", array($fullName, $oldFullName));
	$adb->pquery("UPDATE vtiger_relcriteria SET value=? WHERE columnname LIKE '%:modifiedby:%' AND value=?", array($fullName, $oldFullName));

	$adb->pquery("UPDATE vtiger_cvadvfilter SET comparator='c'
						WHERE (columnname LIKE '%:assigned_user_id%:' OR columnname LIKE '%:assigned_user_id1%:' OR columnname LIKE '%:modifiedby%:')
								AND (comparator='s' OR comparator='ew')", array());
	$adb->pquery("UPDATE vtiger_relcriteria SET comparator='c'
						WHERE (columnname LIKE 'vtiger_users%:user_name%' OR columnname LIKE '%:modifiedby%:')
								AND (comparator='s' OR comparator='ew')", array());
}

$replaceReportColumnsList = array(
	'vtiger_accountAccounts:accountname:Accounts_Member_Of:account_id:V' =>
	'vtiger_account:parentid:Accounts_Member_Of:account_id:V',
	'vtiger_accountContacts:accountname:Contacts_Account_Name:account_id:V' =>
	'vtiger_contactdetails:accountid:Contacts_Account_Name:account_id:V',
	'vtiger_contactdetailsContacts:lastname:Contacts_Reports_To:contact_id:V' =>
	'vtiger_contactdetails:reportsto:Contacts_Reports_To:contact_id:V',
	'vtiger_productsCampaigns:productname:Campaigns_Product:product_id:V' =>
	'vtiger_campaign:product_id:Campaigns_Product:product_id:V',
	'vtiger_productsFaq:productname:Faq_Product_Name:product_id:V' =>
	'vtiger_faq:product_id:Faq_Product_Name:product_id:V',
	'vtiger_contactdetailsInvoice:lastname:Invoice_Contact_Name:contact_id:V' =>
	'vtiger_invoice:contactid:Invoice_Contact_Name:contact_id:V',
	'vtiger_accountInvoice:accountname:Invoice_Account_Name:account_id:V' =>
	'vtiger_invoice:accountid:Invoice_Account_Name:account_id:V',
	'vtiger_campaignPotentials:campaignname:Potentials_Campaign_Source:campaignid:V' =>
	'vtiger_potential:campaignid:Potentials_Campaign_Source:campaignid:V',
	'vtiger_vendorRelProducts:vendorname:Products_Vendor_Name:vendor_id:V' =>
	'vtiger_products:vendor_id:Products_Vendor_Name:vendor_id:V',
	'vtiger_vendorRelPurchaseOrder:vendorname:PurchaseOrder_Vendor_Name:vendor_id:V' =>
	'vtiger_purchaseorder:vendorid:PurchaseOrder_Vendor_Name:vendor_id:V',
	'vtiger_contactdetailsPurchaseOrder:lastname:PurchaseOrder_Contact_Name:contact_id:V' =>
	'vtiger_purchaseorder:contactid:PurchaseOrder_Contact_Name:contact_id:V',
	'vtiger_potentialRelQuotes:potentialname:Quotes_Potential_Name:potential_id:V' =>
	'vtiger_quotes:potentialid:Quotes_Potential_Name:potential_id:V',
	'vtiger_contactdetailsQuotes:lastname:Quotes_Contact_Name:contact_id:V' =>
	'vtiger_quotes:contactid:Quotes_Contact_Name:contact_id:V',
	'vtiger_accountQuotes:accountname:Quotes_Account_Name:account_id:V' =>
	'vtiger_quotes:accountid:Quotes_Account_Name:account_id:V',
	'vtiger_quotesSalesOrder:subject:SalesOrder_Quote_Name:quote_id:V' =>
	'vtiger_salesorder:quoteid:SalesOrder_Quote_Name:quote_id:V',
	'vtiger_contactdetailsSalesOrder:lastname:SalesOrder_Contact_Name:contact_id:V' =>
	'vtiger_salesorder:contactid:SalesOrder_Contact_Name:contact_id:V',
	'vtiger_accountSalesOrder:accountname:SalesOrder_Account_Name:account_id:V' =>
	'vtiger_salesorder:accountid:SalesOrder_Account_Name:account_id:V',
	'vtiger_crmentityRelHelpDesk:setype:HelpDesk_Related_To:parent_id:V' =>
	'vtiger_troubletickets:parent_id:HelpDesk_Related_To:parent_id:V',
	'vtiger_productsRel:productname:HelpDesk_Product_Name:product_id:V' =>
	'vtiger_troubletickets:product_id:HelpDesk_Product_Name:product_id:V',
	'vtiger_crmentityRelCalendar:setype:Calendar_Related_To:parent_id:V' =>
	'vtiger_seactivityrel:crmid:Calendar_Related_To:parent_id:V',
	'vtiger_contactdetailsCalendar:lastname:Calendar_Contact_Name:contact_id:V' =>
	'vtiger_cntactivityrel:contactid:Calendar_Contact_Name:contact_id:V',
);

foreach ($replaceReportColumnsList as $oldName => $newName) {
	$adb->pquery('UPDATE vtiger_selectcolumn SET columnname=? WHERE columnname=?', array($newName, $oldName));
	$adb->pquery('UPDATE vtiger_relcriteria SET columnname=? WHERE columnname=?', array($newName, $oldName));
	$adb->pquery('UPDATE vtiger_reportsortcol SET columnname=? WHERE columnname=?', array($newName, $oldName));
}

// Report Charts - tables creation
$adb->pquery("CREATE TABLE if not exists vtiger_homereportchart (stuffid int(19) PRIMARY KEY, reportid int(19), reportcharttype varchar(100))", array());
$adb->pquery("CREATE TABLE vtiger_reportgroupbycolumn(reportid int(19),sortid int(19),sortcolname varchar(250),dategroupbycriteria varchar(250))", array());
$adb->pquery("ALTER TABLE vtiger_reportgroupbycolumn add constraint fk_1_vtiger_reportgroupbycolumn FOREIGN KEY (reportid) REFERENCES vtiger_report(reportid) ON DELETE CASCADE", array());

$adb->pquery("DELETE FROM vtiger_time_zone WHERE time_zone = 'Kwajalein'", array());
$adb->pquery("UPDATE vtiger_users SET time_zone='UTC' WHERE time_zone='Kwajalein'", array());

$serviceContractsInstance = Vtiger_Module::getInstance('ServiceContracts');
$helpDeskInstance = Vtiger_Module::getInstance("HelpDesk");
$helpDeskInstance->setRelatedList($serviceContractsInstance,"Service Contracts",Array('ADD','SELECT'));

$adb->pquery("UPDATE vtiger_field SET uitype=11 WHERE fieldname IN ('phone_work', 'phone_mobile', 'phone_fax', 'phone_home', 'phone_other')
							AND tabid IN (SELECT tabid FROM vtiger_tab WHERE name='Users')", array());

?>
<?php
/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */
vimport('~~modules/Users/DefaultDataPopulator.php');
vimport('~~include/PopulateComboValues.php');
require_once('includes/main/WebUI.php');
require_once('include/utils/utils.php');
require_once('modules/com_vtiger_workflow/VTTaskManager.inc');
require_once('modules/com_vtiger_workflow/tasks/VTEmailTask.inc');

class Install_InitSchema_Model {

	/**
	 * Function starts applying schema changes
	 */
	public static function initialize() {
		global $adb;
		$path = Install_Utils_Model::INSTALL_LOG;
		file_put_contents($path, "[".date('Y-m-d h:i:s')."] ".__FILE__." ".__LINE__." Init DB from Session vars\n", FILE_APPEND);
		$adb = PearDatabase::getInstance();
		$configParams = $_SESSION['config_file_info'];
		$adb->resetSettings($configParams['db_type'], $configParams['db_hostname'], $configParams['db_name'], $configParams['db_username'], $configParams['db_password']);
		$adb->query('SET NAMES utf8');
		
		file_put_contents($path, "[".date('Y-m-d h:i:s')."] ".__FILE__." ".__LINE__." Set MultiQuery Mode (requires mysqli) and import SQL dump\n", FILE_APPEND);
		$adb->database->multiQuery = true;
		$schema = file_get_contents('schema/DatabaseSchema.sql');
		$adb->pquery($schema);
		$adb->database->multiQuery = false;
		if ($adb->database->_failedQuery) {
			return $adb->database->_failedQuery;
		} else {
			require 'vtigerversion.php';
			$query = "UPDATE `vtiger_version` SET `tag_version` = ?";
			$adb->pquery($query, array($current_release_tag));
		}

		self::createCurrency();

		return true;
	}

	/**
	 * Function upgrades the schema with changes post 540 version
	 */
	public static function upgrade() {
		$migrateVersions = Migration_Module_Model::getInstance('')->getAllowedMigrationVersions();

		define('VTIGER_UPGRADE', true);
		$oldVersion = null;
		foreach($migrateVersions as $migrateVersion) {
			foreach($migrateVersion as $newVersion => $versionLabel) {
				// Not ready?	
				if ($oldVersion == null) {
					$oldVersion = $newVersion;
					break;
				}
				$oldVersion = str_replace(array('.', ' '), '', $oldVersion);
				$newVersion = str_replace(array('.', ' '), '', $newVersion);
				$filename =  "modules/Migration/schema/".$oldVersion."_to_".$newVersion.".php";
				if(is_file($filename)) {
					include($filename);
				}
				$oldVersion = $newVersion;
			}
		}
		
		//crm-now: modifications during install
		self::setCRMNOWmodifications();
	}

	//get currency from installation 
	public static function createCurrency() {
		global $adb;
		$query = 'SELECT * FROM `vtiger_currencies` WHERE `currency_name` = ?;';
		$res = $adb->pquery($query, array($_SESSION['config_file_info']['currency_name']));

		if ($res && $adb->num_rows($res) > 0) {
			$row = $adb->getNextRow($res, false);		
			$query = 'UPDATE vtiger_currency_info SET currency_name = ?, currency_code = ?, currency_symbol = ? WHERE id = ?';
			$params = array($row['currency_name'], $row['currency_code'], $row['currency_symbol'], '1');
			$adb->pquery($query, $params);
		} 
	}

	public static function createUser() {
		global $adb;
		$path = Install_Utils_Model::INSTALL_LOG;
		file_put_contents($path, "[".date('Y-m-d h:i:s')."] ".__FILE__." ".__LINE__." Get AdoDB instance and new Users object\n", FILE_APPEND);
		$adb = PearDatabase::getInstance();
		
		$adminPassword = $_SESSION['config_file_info']['password'];
		$userDateFormat = $_SESSION['config_file_info']['dateformat'];
		$userTimeZone = $_SESSION['config_file_info']['timezone'];
		//Fix for http://trac.vtiger.com/cgi-bin/trac.cgi/ticket/7974
        $userFirstName = $_SESSION['config_file_info']['firstname']; 
        $userLastName = $_SESSION['config_file_info']['lastname']; 
        $userLanguage = $_SESSION['config_file_info']['default_language'];
        // create default admin user
    	$user = CRMEntity::getInstance('Users');
		//Fix for http://trac.vtiger.com/cgi-bin/trac.cgi/ticket/7974
        $user->column_fields["first_name"] = $userFirstName; 
		$user->column_fields["last_name"] = $userLastName; 
        //Ends
        $user->column_fields["user_name"] = 'admin';
        $user->column_fields["status"] = 'Active';
        $user->column_fields["is_admin"] = 'on';
        $user->column_fields["user_password"] = $adminPassword;
        $user->column_fields["time_zone"] = $userTimeZone;
        $user->column_fields["language"] = $userLanguage;
        $user->column_fields["holidays"] = 'de,en_uk,fr,it,us,';
        $user->column_fields["workdays"] = '0,1,2,3,4,5,6,';
        $user->column_fields["weekstart"] = '1';
        $user->column_fields["namedays"] = '';
        $user->column_fields["currency_id"] = 1;
        $user->column_fields["reminder_interval"] = '30 Minutes';
        $user->column_fields["reminder_next_time"] = date('Y-m-d H:i');
		$user->column_fields["date_format"] = $userDateFormat;
		$user->column_fields["hour_format"] = '24';
		$user->column_fields["start_hour"] = '08:00';
		$user->column_fields["end_hour"] = '23:00';
		$user->column_fields["imagename"] = '';
		$user->column_fields["internal_mailer"] = '1';
		$user->column_fields["activity_view"] = 'This Week';
		$user->column_fields["lead_view"] = 'Today';
		$user->column_fields["dayoftheweek"] = 'Monday';
		$user->column_fields["currency_decimal_separator"] = ',';
		$user->column_fields["currency_grouping_separator"] = '.';
		$user->column_fields["callduration"] = '30';
		$user->column_fields["othereventduration"] = '30';
		$user->column_fields["currency_symbol_placement"] = '1.0$';
		$user->column_fields["currency_decimal_separator"] = ',';
		$user->column_fields["currency_grouping_pattern"] = '123456789';
		$user->column_fields["no_of_currency_decimals"] = '2';
		$user->column_fields["lead_view"] = 'Last Week';
		$user->column_fields["defaulteventstatus"] = 'Planned';
		$user->column_fields["defaultactivitytype"] = 'Call';

		$adminEmail = (!empty($_SESSION['config_file_info']['admin_email'])) ? $_SESSION['config_file_info']['admin_email'] : 'admin@berlicrm.de';
		$user->column_fields["email1"] = $adminEmail;
		$user->column_fields["roleid"] = 'H2';
		
		file_put_contents($path, "[".date('Y-m-d h:i:s')."] ".__FILE__." ".__LINE__." Save User\n", FILE_APPEND);
        $user->save("Users");
        $adminUserId = 1;

		//create new profile
		file_put_contents($path, "[".date('Y-m-d h:i:s')."] ".__FILE__." ".__LINE__." starting to add new profile\n", FILE_APPEND);
	
		try {
			self::createProfileAndRole();
			$user->column_fields["roleid"] = 'H6';
			$user->mode = 'edit';
			$user->save("Users");
		}
		catch (Exception $e) {
			file_put_contents($path, "[".date('Y-m-d h:i:s')."] ".__FILE__." ".__LINE__." Fehler: ".$e->getMessage(), FILE_APPEND);
		}
		
		file_put_contents($path, "[".date('Y-m-d h:i:s')."] ".__FILE__." ".__LINE__." Update User ID\n", FILE_APPEND);
		//due to late user entry the groups already exist, so cheat admin to id 1
		$adb->pquery("UPDATE vtiger_users SET id = ? WHERE id = ?;", array($adminUserId, $user->id));
		
		//Creating the flat files for admin user
		file_put_contents($path, "[".date('Y-m-d h:i:s')."] ".__FILE__." ".__LINE__." Create Privilege file\n", FILE_APPEND);
		createUserPrivilegesfile($adminUserId);
		file_put_contents($path, "[".date('Y-m-d h:i:s')."] ".__FILE__." ".__LINE__." Create Sharing file\n", FILE_APPEND);
		createUserSharingPrivilegesfile($adminUserId);
		return true;
	}

	public static function createNonAdminUser() {
		global $adb;
		$path = Install_Utils_Model::INSTALL_LOG;
		file_put_contents($path, "[".date('Y-m-d h:i:s')."] ".__FILE__." ".__LINE__." Get AdoDB instance and new Users object\n", FILE_APPEND);
		$adb = PearDatabase::getInstance();
		
		$adminPassword = $_SESSION['config_file_info']['password'];
		$userDateFormat = $_SESSION['config_file_info']['dateformat'];
		$userTimeZone = $_SESSION['config_file_info']['timezone'];
		$manangerlastname = $_SESSION['config_file_info']['managerfirstname'];
		//$selectCurrency = $_SESSION['selectedCurrency'];
		//Fix for http://trac.vtiger.com/cgi-bin/trac.cgi/ticket/7974
        $userFirstName = $_SESSION['config_file_info']['managerfirstname']; 
        $userLastName = $_SESSION['config_file_info']['managerlastname']; 
        $userLanguage = $_SESSION['config_file_info']['default_language'];
        // create default admin user
    	$user = CRMEntity::getInstance('Users');
		//Fix for http://trac.vtiger.com/cgi-bin/trac.cgi/ticket/7974
        $user->column_fields["first_name"] = $userFirstName; 
		$user->column_fields["last_name"] = $userLastName;
        //Ends
        $user->column_fields["user_name"] = $manangerlastname;
        $user->column_fields["status"] = 'Active';
        $user->column_fields["is_admin"] = 'off';
        $user->column_fields["user_password"] = $adminPassword;
        $user->column_fields["time_zone"] = $userTimeZone;
        $user->column_fields["language"] = $userLanguage;
        $user->column_fields["holidays"] = 'de,en_uk,fr,it,us,';
        $user->column_fields["workdays"] = '0,1,2,3,4,5,6,';
        $user->column_fields["weekstart"] = '1';
        $user->column_fields["namedays"] = '';
        $user->column_fields["currency_id"] = 1;
        $user->column_fields["reminder_interval"] = '30 Minutes';
        $user->column_fields["reminder_next_time"] = date('Y-m-d H:i');
		$user->column_fields["date_format"] = $userDateFormat;
		$user->column_fields["hour_format"] = '24';
		$user->column_fields["start_hour"] = '08:00';
		$user->column_fields["end_hour"] = '23:00';
		$user->column_fields["imagename"] = '';
		$user->column_fields["internal_mailer"] = '1';
		$user->column_fields["activity_view"] = 'This Week';
		$user->column_fields["lead_view"] = 'Today';
		$user->column_fields["dayoftheweek"] = 'Monday';
		$user->column_fields["currency_decimal_separator"] = ',';
		$user->column_fields["currency_grouping_separator"] = '.';
		$user->column_fields["callduration"] = '30';
		$user->column_fields["othereventduration"] = '30';
		$user->column_fields["currency_symbol_placement"] = '1.0$';
		$user->column_fields["currency_decimal_separator"] = ',';
		$user->column_fields["currency_grouping_pattern"] = '123456789';
		$user->column_fields["no_of_currency_decimals"] = '2';
		$user->column_fields["lead_view"] = 'Last Week';
		$user->column_fields["defaulteventstatus"] = 'Planned';
		$user->column_fields["defaultactivitytype"] = 'Call';


		$adminEmail = (!empty($_SESSION['config_file_info']['admin_email'])) ? $_SESSION['config_file_info']['admin_email'] : 'admin@berlicrm.de';
		$user->column_fields["email1"] = $adminEmail;
		$user->column_fields["roleid"] = 'H2';
		
		file_put_contents($path, "[".date('Y-m-d h:i:s')."] ".__FILE__." ".__LINE__." Save User\n", FILE_APPEND);
        $user->save("Users");
        $adminUserId = 1;
		
		file_put_contents($path, "[".date('Y-m-d h:i:s')."] ".__FILE__." ".__LINE__." Update User ID\n", FILE_APPEND);
		
		//Creating the flat files for admin user
		file_put_contents($path, "[".date('Y-m-d h:i:s')."] ".__FILE__." ".__LINE__." Create Privilege file\n", FILE_APPEND);
		createUserPrivilegesfile($adminUserId);
		file_put_contents($path, "[".date('Y-m-d h:i:s')."] ".__FILE__." ".__LINE__." Create Sharing file\n", FILE_APPEND);
		createUserSharingPrivilegesfile($adminUserId);

		return true;
	}

	/**
	 * Function add necessary schema for event handlers and workflows, also add defaul workflows
	 */
	public static function installDefaultEventsAndWorkflows() {
		global $adb;
		$adb = PearDatabase::getInstance();

		// Register All the Events
		self::registerEvents($adb);

		// Register All the Entity Methods
		self::registerEntityMethods($adb);

		// Populate Default Workflows
		self::populateDefaultWorkflows($adb);

		// Populate Links
		self::populateLinks();

		// Set Help Information for Fields
		self::setFieldHelpInfo();

		// Register Cron Jobs
		self::registerCronTasks();
	}

	/**
	 *  Register all the Cron Tasks
	 */
	public static function registerCronTasks() {
		vimport('~~vtlib/Vtiger/Cron.php');
		Vtiger_Cron::register( 'Workflow', 'cron/modules/com_vtiger_workflow/com_vtiger_workflow.service', 900, 'com_vtiger_workflow', 1, 1, 'LBL_WORKFLOW_DES');
		Vtiger_Cron::register( 'RecurringInvoice', 'cron/modules/SalesOrder/RecurringInvoice.service', 86400, 'SalesOrder', 1, 2, 'LBL_REC_INVOICE_DES');
		Vtiger_Cron::register( 'SendReminder', 'cron/SendReminder.service', 900, 'Calendar', 1, 3, 'LBL_SENDREMINDER_DES');
		Vtiger_Cron::register( 'ScheduleReports', 'cron/modules/Reports/ScheduleReports.service', 900, 'Reports', 1, 4, 'LBL_REPORT_DES');
		Vtiger_Cron::register( 'MailScanner', 'cron/MailScanner.service', 900, 'Settings', 1, 5, 'LBL_MAILSCANNER_DES');
	}

	/**
	 * Function registers all the event handlers
	 */
	static function registerEvents($adb) {
		vimport('~~include/events/include.inc');
		$em = new VTEventsManager($adb);

		// Registering event for Recurring Invoices
		$em->registerHandler('vtiger.entity.aftersave', 'modules/SalesOrder/RecurringInvoiceHandler.php', 'RecurringInvoiceHandler');

		//Registering Entity Delta handler for before save and after save events of the record to track the field value changes
		$em->registerHandler('vtiger.entity.beforesave', 'data/VTEntityDelta.php', 'VTEntityDelta');
		$em->registerHandler('vtiger.entity.aftersave', 'data/VTEntityDelta.php', 'VTEntityDelta');

		// Workflow manager
		$dependentEventHandlers = array('VTEntityDelta');
		$dependentEventHandlersJson = Zend_Json::encode($dependentEventHandlers);
		$em->registerHandler('vtiger.entity.aftersave', 'modules/com_vtiger_workflow/VTEventHandler.inc', 'VTWorkflowEventHandler',
									'',$dependentEventHandlersJson);

		//Registering events for On modify
		$em->registerHandler('vtiger.entity.afterrestore', 'modules/com_vtiger_workflow/VTEventHandler.inc', 'VTWorkflowEventHandler');

		// Registering event for HelpDesk - To reset from_portal value
		$em->registerHandler('vtiger.entity.aftersave.final', 'modules/HelpDesk/HelpDeskHandler.php', 'HelpDeskHandler');
	}

	/**
	 * Function registers all the work flow custom entity methods
	 * @param <PearDatabase> $adb
	 */
	static function registerEntityMethods($adb) {
		vimport("~~modules/com_vtiger_workflow/include.inc");
		vimport("~~modules/com_vtiger_workflow/tasks/VTEntityMethodTask.inc");
		vimport("~~modules/com_vtiger_workflow/VTEntityMethodManager.inc");
		$emm = new VTEntityMethodManager($adb);

		// Registering method for Updating Inventory Stock
		$emm->addEntityMethod("SalesOrder","UpdateInventory","include/InventoryHandler.php","handleInventoryProductRel");//Adding EntityMethod for Updating Products data after creating SalesOrder
		$emm->addEntityMethod("Invoice","UpdateInventory","include/InventoryHandler.php","handleInventoryProductRel");//Adding EntityMethod for Updating Products data after creating Invoice

		// Register Entity Method for Customer Portal Login details email notification task
		$emm->addEntityMethod("Contacts","SendPortalLoginDetails","modules/Contacts/ContactsHandler.php","Contacts_sendCustomerPortalLoginDetails");

		// Register Entity Method for Email notification on ticket creation from Customer portal
		$emm->addEntityMethod("HelpDesk","NotifyOnPortalTicketCreation","modules/HelpDesk/HelpDeskHandler.php","HelpDesk_nofifyOnPortalTicketCreation");

		// Register Entity Method for Email notification on ticket comment from Customer portal
		$emm->addEntityMethod("HelpDesk","NotifyOnPortalTicketComment","modules/HelpDesk/HelpDeskHandler.php","HelpDesk_notifyOnPortalTicketComment");

		// Register Entity Method for Email notification to Record Owner on ticket change, which is not from Customer portal
		$emm->addEntityMethod("HelpDesk","NotifyOwnerOnTicketChange","modules/HelpDesk/HelpDeskHandler.php","HelpDesk_notifyOwnerOnTicketChange");

		// Register Entity Method for Email notification to Related Customer on ticket change, which is not from Customer portal
		$emm->addEntityMethod("HelpDesk","NotifyParentOnTicketChange","modules/HelpDesk/HelpDeskHandler.php","HelpDesk_notifyParentOnTicketChange");
	}

	/**
	 * Function adds default system workflows
	 * @param <PearDatabase> $adb
	 */
	static function populateDefaultWorkflows($adb) {
		vimport("~~modules/com_vtiger_workflow/include.inc");
		vimport("~~modules/com_vtiger_workflow/tasks/VTEntityMethodTask.inc");
		vimport("~~modules/com_vtiger_workflow/VTEntityMethodManager.inc");
		vimport("~~modules/com_vtiger_workflow/VTTaskManager.inc");

		// register the workflow tasks
		$taskTypes = array();
		$defaultModules = array('include' => array(), 'exclude'=>array());
		$createToDoModules = array('include' => array("Leads","Accounts","Potentials","Contacts","HelpDesk","Campaigns","Quotes","PurchaseOrder","SalesOrder","Invoice"), 'exclude'=>array("Calendar", "FAQ", "Events"));
		$createEventModules = array('include' => array("Leads","Accounts","Potentials","Contacts","HelpDesk","Campaigns"), 'exclude'=>array("Calendar", "FAQ", "Events"));

		$taskTypes[] = array("name"=>"VTEmailTask", "label"=>"Send Mail", "classname"=>"VTEmailTask", "classpath"=>"modules/com_vtiger_workflow/tasks/VTEmailTask.inc", "templatepath"=>"com_vtiger_workflow/taskforms/VTEmailTask.tpl", "modules"=>$defaultModules, "sourcemodule"=>'');
		$taskTypes[] = array("name"=>"VTEntityMethodTask", "label"=>"Invoke Custom Function", "classname"=>"VTEntityMethodTask", "classpath"=>"modules/com_vtiger_workflow/tasks/VTEntityMethodTask.inc", "templatepath"=>"com_vtiger_workflow/taskforms/VTEntityMethodTask.tpl", "modules"=>$defaultModules, "sourcemodule"=>'');
		$taskTypes[] = array("name"=>"VTCreateTodoTask", "label"=>"Create Todo", "classname"=>"VTCreateTodoTask", "classpath"=>"modules/com_vtiger_workflow/tasks/VTCreateTodoTask.inc", "templatepath"=>"com_vtiger_workflow/taskforms/VTCreateTodoTask.tpl", "modules"=>$createToDoModules, "sourcemodule"=>'');
		$taskTypes[] = array("name"=>"VTCreateEventTask", "label"=>"Create Event", "classname"=>"VTCreateEventTask", "classpath"=>"modules/com_vtiger_workflow/tasks/VTCreateEventTask.inc", "templatepath"=>"com_vtiger_workflow/taskforms/VTCreateEventTask.tpl", "modules"=>$createEventModules, "sourcemodule"=>'');
		$taskTypes[] = array("name"=>"VTUpdateFieldsTask", "label"=>"Update Fields", "classname"=>"VTUpdateFieldsTask", "classpath"=>"modules/com_vtiger_workflow/tasks/VTUpdateFieldsTask.inc", "templatepath"=>"com_vtiger_workflow/taskforms/VTUpdateFieldsTask.tpl", "modules"=>$defaultModules, "sourcemodule"=>'');
		$taskTypes[] = array("name"=>"VTCreateEntityTask", "label"=>"Create Entity", "classname"=>"VTCreateEntityTask", "classpath"=>"modules/com_vtiger_workflow/tasks/VTCreateEntityTask.inc", "templatepath"=>"com_vtiger_workflow/taskforms/VTCreateEntityTask.tpl", "modules"=>$defaultModules, "sourcemodule"=>'');
		$taskTypes[] = array("name"=>"VTSMSTask", "label"=>"SMS Task", "classname"=>"VTSMSTask", "classpath"=>"modules/com_vtiger_workflow/tasks/VTSMSTask.inc", "templatepath"=>"com_vtiger_workflow/taskforms/VTSMSTask.tpl", "modules"=>$defaultModules, "sourcemodule"=>'SMSNotifier');

		foreach ($taskTypes as $taskType) {
			VTTaskType::registerTaskType($taskType);
		}

		// Creating Workflow for Updating Inventory Stock for Invoice
		$vtWorkFlow = new VTWorkflowManager($adb);
		$invWorkFlow = $vtWorkFlow->newWorkFlow("Invoice");
		$invWorkFlow->test = '[{"fieldname":"subject","operation":"does not contain","value":"`!`"}]';
		$invWorkFlow->description = "LBL_INVENTORY_UPDATE";
		$invWorkFlow->defaultworkflow = 1;
		$vtWorkFlow->save($invWorkFlow);

		$tm = new VTTaskManager($adb);
		$task = $tm->createTask('VTEntityMethodTask', $invWorkFlow->id);
		$task->active=true;
		$task->methodName = "UpdateInventory";
		$tm->saveTask($task);

		// Creating Workflow for Accounts when Notifyowner is true
		$vtaWorkFlow = new VTWorkflowManager($adb);
		$accWorkFlow = $vtaWorkFlow->newWorkFlow("Accounts");
		$accWorkFlow->test = '[{"fieldname":"notify_owner","operation":"is","value":"true:boolean"}]';
		$accWorkFlow->description = "LBL_SEND_OWNER_EMAIL";
		$accWorkFlow->executionCondition=2;
		$accWorkFlow->defaultworkflow = 1;
		$vtaWorkFlow->save($accWorkFlow);
		$id1=$accWorkFlow->id;

		$tm = new VTTaskManager($adb);
		$task = $tm->createTask('VTEmailTask',$accWorkFlow->id);
		$task->active=true;
		$task->methodName = "NotifyOwner";
		$task->recepient = "\$(assigned_user_id : (Users) email1)";
		$task->subject = "Regarding Account Creation";
		$task->content = "An Account has been assigned to you on the CRM<br>Details of account are:<br><br>".
				"Account Id: ".'<b>$account_no</b><br>'."Account Name: ".'<b>$accountname</b><br>'."Rating: ".'<b>$rating</b><br>'.
				"Industry: ".'<b>$industry</b><br>'."Account Type: ".'<b>$accounttype</b><br>'.
				"Description:".'<b>$description</b><br><br><br>'."Thank You";
		$task->summary="An account has been created ";
		$tm->saveTask($task);
		$adb->pquery("update com_vtiger_workflows set defaultworkflow=? where workflow_id=?",array(1,$id1));

		// Creating Workflow for Contacts when Notifyowner is true

		$vtcWorkFlow = new VTWorkflowManager($adb);
		$conWorkFlow = 	$vtcWorkFlow->newWorkFlow("Contacts");
		$conWorkFlow->summary="A contact has been created ";
		$conWorkFlow->executionCondition=2;
		$conWorkFlow->test = '[{"fieldname":"notify_owner","operation":"is","value":"true:boolean"}]';
		$conWorkFlow->description = "LBL_SEND_OWNER_EMAIL";
		$conWorkFlow->defaultworkflow = 1;
		$vtcWorkFlow->save($conWorkFlow);
		$id1=$conWorkFlow->id;
		$tm = new VTTaskManager($adb);
		$task = $tm->createTask('VTEmailTask',$conWorkFlow->id);
		$task->active=true;
		$task->methodName = "NotifyOwner";
		$task->recepient = "\$(assigned_user_id : (Users) email1)";
		$task->subject = "Regarding Contact Creation";
		$task->content = "A Contact has been assigned to you on the CRM<br>Details of Contact are :<br><br>".
				"Contact Id:".'<b>$contact_no</b><br>'."LastName:".'<b>$lastname</b><br>'."FirstName:".'<b>$firstname</b><br>'.
				"Lead Source:".'<b>$leadsource</b><br>'.
				"Department:".'<b>$department</b><br>'.
				"Description:".'<b>$description</b><br><br><br>'."Thank You<br>Admin";
		$task->summary="A contact has been created ";
		$tm->saveTask($task);
		$adb->pquery("update com_vtiger_workflows set defaultworkflow=? where workflow_id=?",array(1,$id1));


		// Creating Workflow for Contacts when PortalUser is true

		$vtcWorkFlow = new VTWorkflowManager($adb);
		$conpuWorkFlow = $vtcWorkFlow->newWorkFlow("Contacts");
		$conpuWorkFlow->test = '[{"fieldname":"portal","operation":"is","value":"true:boolean"}]';
		$conpuWorkFlow->description = "LBL_SEND_PORTAL_EMAIL";
		$conpuWorkFlow->executionCondition=2;
		$conpuWorkFlow->defaultworkflow = 1;
		$vtcWorkFlow->save($conpuWorkFlow);
		$id1=$conpuWorkFlow->id;

                $taskManager = new VTTaskManager($adb);
                $task = $taskManager->createTask('VTEntityMethodTask', $id1);
		$task->active = true;
		$task->summary = 'Email Customer Portal Login Details';
		$task->methodName = "SendPortalLoginDetails";
		$taskManager->saveTask($task);
		// Creating Workflow for Potentials

		$vtcWorkFlow = new VTWorkflowManager($adb);
		$potentialWorkFlow = $vtcWorkFlow->newWorkFlow("Potentials");
		$potentialWorkFlow->description = "LBL_SEND_POTENTIAL_EMAIL";
		$potentialWorkFlow->executionCondition=1;
		$potentialWorkFlow->defaultworkflow = 1;
		$vtcWorkFlow->save($potentialWorkFlow);
		$id1=$potentialWorkFlow->id;

		$tm = new VTTaskManager($adb);
		$task = $tm->createTask('VTEmailTask',$potentialWorkFlow->id);

		$task->active=true;
		$task->recepient = "\$(assigned_user_id : (Users) email1)";
		$task->subject = "Regarding Potential Assignment";
		$task->content = "An Potential has been assigned to you on the CRM<br>Details of Potential are :<br><br>".
				"Potential No:".'<b>$potential_no</b><br>'."Potential Name:".'<b>$potentialname</b><br>'.
				"Amount:".'<b>$amount</b><br>'.
				"Expected Close Date:".'<b>$closingdate</b><br>'.
				"Type:".'<b>$opportunity_type</b><br><br><br>'.
				"Description :".'$description<br>'."<br>Thank You<br>Admin";

		$task->summary="A Potential has been created ";
		$tm->saveTask($task);

		$workflowManager = new VTWorkflowManager($adb);
		$taskManager = new VTTaskManager($adb);

		// Contact workflow on creation/modification
		$contactWorkFlow = $workflowManager->newWorkFlow("Contacts");
		$contactWorkFlow->test = '';
		$contactWorkFlow->description = "LBL_CONT_CRE_OR_MOD";
		$contactWorkFlow->executionCondition = VTWorkflowManager::$ON_EVERY_SAVE;
		$contactWorkFlow->defaultworkflow = 1;
		$workflowManager->save($contactWorkFlow);

		$tm = new VTTaskManager($adb);
		$task = $tm->createTask('VTEmailTask',$contactWorkFlow->id);

		$task->active=true;
		$task->recepient = "\$(assigned_user_id : (Users) email1)";
		$task->subject = "Regarding Contact Assignment";
		$task->content = "A Contact has been assigned to you on the CRM<br>The Details of the Contact are:<br><br>".
				"Contact Id:".'<b>$contact_no</b><br>'."LastName:".'<b>$lastname</b><br>'."FirstName:".'<b>$firstname</b><br>'.
				"Lead Source:".'<b>$leadsource</b><br>'.
				"Department:".'<b>$department</b><br>'.
				"<br>Thank You<br>";

		$task->summary="A contact has been created ";
		$tm->saveTask($task);
		$adb->pquery("update com_vtiger_workflows set defaultworkflow=? where workflow_id=?",array(1,$id1));
                
		// Trouble Tickets workflow on creation from Customer Portal
		$helpDeskWorkflow = $workflowManager->newWorkFlow("HelpDesk");
		$helpDeskWorkflow->test = '[{"fieldname":"from_portal","operation":"is","value":"true:boolean"}]';
		$helpDeskWorkflow->description = "LBL_PORTAL_TICKET_CR_EMAIL";
		$helpDeskWorkflow->executionCondition = VTWorkflowManager::$ON_FIRST_SAVE;
		$helpDeskWorkflow->defaultworkflow = 1;
		$workflowManager->save($helpDeskWorkflow);

		$task = $taskManager->createTask('VTEntityMethodTask', $helpDeskWorkflow->id);
		$task->active = true;
		$task->summary = 'Notify Record Owner and the Related Contact when Ticket is created from Portal';
		$task->methodName = "NotifyOnPortalTicketCreation";
		$taskManager->saveTask($task);

		// Trouble Tickets workflow on ticket update from Customer Portal
		$helpDeskWorkflow = $workflowManager->newWorkFlow("HelpDesk");
		$helpDeskWorkflow->test = '[{"fieldname":"from_portal","operation":"is","value":"true:boolean"}]';
		$helpDeskWorkflow->description = "LBL_PORTAL_TICKET_UP_EMAIL";
		$helpDeskWorkflow->executionCondition = VTWorkflowManager::$ON_MODIFY;
		$helpDeskWorkflow->defaultworkflow = 1;
		$workflowManager->save($helpDeskWorkflow);

		$task = $taskManager->createTask('VTEntityMethodTask', $helpDeskWorkflow->id);
		$task->active = true;
		$task->summary = 'Notify Record Owner when Comment is added to a Ticket from Customer Portal';
		$task->methodName = "NotifyOnPortalTicketComment";
		$taskManager->saveTask($task);

		// Trouble Tickets workflow on ticket change, which is not from Customer Portal - Both Record Owner and Related Customer
		$helpDeskWorkflow = $workflowManager->newWorkFlow("HelpDesk");
		$helpDeskWorkflow->test = '[{"fieldname":"from_portal","operation":"is","value":"false:boolean"}]';
		$helpDeskWorkflow->description = "Workflow for Ticket Change, not from the Portal";
		$helpDeskWorkflow->executionCondition = VTWorkflowManager::$ON_EVERY_SAVE;
		$helpDeskWorkflow->defaultworkflow = 1;
		$workflowManager->save($helpDeskWorkflow);

		$task = $taskManager->createTask('VTEntityMethodTask', $helpDeskWorkflow->id);
		$task->active = true;
		$task->summary = 'Notify Record Owner on Ticket Change, which is not done from Portal';
		$task->methodName = "NotifyOwnerOnTicketChange";
		$taskManager->saveTask($task);

		$task = $taskManager->createTask('VTEntityMethodTask', $helpDeskWorkflow->id);
		$task->active = true;
		$task->summary = 'Notify Related Customer on Ticket Change, which is not done from Portal';
		$task->methodName = "NotifyParentOnTicketChange";
		$taskManager->saveTask($task);

		// Events workflow when Send Notification is checked
		$eventsWorkflow = $workflowManager->newWorkFlow("Events");
		$eventsWorkflow->test = '[{"fieldname":"sendnotification","operation":"is","value":"true:boolean"}]';
		$eventsWorkflow->description = "LBL_EVENT_NOTIFY_EMAIL";
		$eventsWorkflow->executionCondition = VTWorkflowManager::$ON_EVERY_SAVE;
		$eventsWorkflow->defaultworkflow = 1;
		$workflowManager->save($eventsWorkflow);

		$task = $taskManager->createTask('VTEmailTask', $eventsWorkflow->id);
		$task->active = true;
		$task->summary = 'Send Notification Email to Record Owner';
		$task->recepient = "\$(assigned_user_id : (Users) email1)";
		$task->subject = "Event :  \$subject";
		$task->content = '$(assigned_user_id : (Users) first_name) $(assigned_user_id : (Users) last_name) ,<br/>'
						.'<b>Activity Notification Details:</b><br/>'
						.'Subject             : $subject<br/>'
						.'Start date and time : $date_start  $time_start ( $(general : (__VtigerMeta__) dbtimezone) ) <br/>'
						.'End date and time   : $due_date  $time_end ( $(general : (__VtigerMeta__) dbtimezone) ) <br/>'
						.'Status              : $eventstatus <br/>'
						.'Priority            : $taskpriority <br/>'
						.'Related To          : $(parent_id : (Leads) lastname) $(parent_id : (Leads) firstname) $(parent_id : (Accounts) accountname) '
												.'$(parent_id : (Potentials) potentialname) $(parent_id : (HelpDesk) ticket_title) <br/>'
						.'Contacts List       : $(contact_id : (Contacts) lastname) $(contact_id : (Contacts) firstname) <br/>'
						.'Location            : $location <br/>'
						.'Description         : $description';
		$taskManager->saveTask($task);

		// Calendar workflow when Send Notification is checked
		$calendarWorkflow = $workflowManager->newWorkFlow("Calendar");
		$calendarWorkflow->test = '[{"fieldname":"sendnotification","operation":"is","value":"true:boolean"}]';
		$calendarWorkflow->description = "LBL_TASK_NOTIFY_EMAIL";
		$calendarWorkflow->executionCondition = VTWorkflowManager::$ON_EVERY_SAVE;
		$calendarWorkflow->defaultworkflow = 1;
		$workflowManager->save($calendarWorkflow);

		$task = $taskManager->createTask('VTEmailTask', $calendarWorkflow->id);
		$task->active = true;
		$task->summary = 'Send Notification Email to Record Owner';
		$task->recepient = "\$(assigned_user_id : (Users) email1)";
		$task->subject = "Task :  \$subject";
		$task->content = '$(assigned_user_id : (Users) first_name) $(assigned_user_id : (Users) last_name) ,<br/>'
						.'<b>Task Notification Details:</b><br/>'
						.'Subject : $subject<br/>'
						.'Start date and time : $date_start  $time_start ( $(general : (__VtigerMeta__) dbtimezone) ) <br/>'
						.'End date and time   : $due_date ( $(general : (__VtigerMeta__) dbtimezone) ) <br/>'
						.'Status              : $taskstatus <br/>'
						.'Priority            : $taskpriority <br/>'
						.'Related To          : $(parent_id : (Leads) lastname) $(parent_id : (Leads) firstname) $(parent_id : (Accounts) accountname) '
						.'$(parent_id         : (Potentials) potentialname) $(parent_id : (HelpDesk) ticket_title) <br/>'
						.'Contacts List       : $(contact_id : (Contacts) lastname) $(contact_id : (Contacts) firstname) <br/>'
						.'Location            : $location <br/>'
						.'Description         : $description';
		$taskManager->saveTask($task);
	}

	/**
	 * Function adds default details view links
	 */
	public static function populateLinks() {
		vimport('~~vtlib/Vtiger/Module.php');

		// Links for Accounts module
		$accountInstance = Vtiger_Module::getInstance('Accounts');
		// Detail View Custom link
		$accountInstance->addLink(
			'DETAILVIEWBASIC', 'LBL_ADD_NOTE',
			'index.php?module=Documents&action=EditView&return_module=$MODULE$&return_action=DetailView&return_id=$RECORD$&parent_id=$RECORD$',
			'themes/images/bookMark.gif'
		);
		$accountInstance->addLink('DETAILVIEWBASIC', 'LBL_SHOW_ACCOUNT_HIERARCHY', 'index.php?module=Accounts&action=AccountHierarchy&accountid=$RECORD$');

		$leadInstance = Vtiger_Module::getInstance('Leads');
		$leadInstance->addLink(
			'DETAILVIEWBASIC', 'LBL_ADD_NOTE',
			'index.php?module=Documents&action=EditView&return_module=$MODULE$&return_action=DetailView&return_id=$RECORD$&parent_id=$RECORD$',
			'themes/images/bookMark.gif'
		);
		$leadInstance->addLink(
			'DETAILVIEWBASIC', 'Export vCard',
			'index.php?module=Leads&action=getvCard&src_module=Leads&src_record=$RECORD$',
			''
		);

		$contactInstance = Vtiger_Module::getInstance('Contacts');
		$contactInstance->addLink(
			'DETAILVIEWBASIC', 'LBL_ADD_NOTE',
			'index.php?module=Documents&action=EditView&return_module=$MODULE$&return_action=DetailView&return_id=$RECORD$&parent_id=$RECORD$',
			'themes/images/bookMark.gif'
		);
		$contactInstance->addLink(
			'DETAILVIEWBASIC', 'Export vCard',
			'index.php?module=Contacts&action=getvCard&src_module=Contacts&src_record=$RECORD$',
			''
		);
	}

	/**
	 * Function add help information on special fields
	 */
	public static function setFieldHelpInfo() {
		// Added Help Info for Hours and Days fields of HelpDesk module.
		vimport('~~vtlib/Vtiger/Module.php');
		$helpDeskModule = Vtiger_Module::getInstance('HelpDesk');
		$field1 = Vtiger_Field::getInstance('hours',$helpDeskModule);
		$field2 = Vtiger_Field::getInstance('days',$helpDeskModule);

		$field1->setHelpInfo('This gives the estimated hours for the Ticket.'.
					'<br>When the same ticket is added to a Service Contract,'.
					'based on the Tracking Unit of the Service Contract,'.
					'Used units is updated whenever a ticket is Closed.');

		$field2->setHelpInfo('This gives the estimated days for the Ticket.'.
					'<br>When the same ticket is added to a Service Contract,'.
					'based on the Tracking Unit of the Service Contract,'.
					'Used units is updated whenever a ticket is Closed.');

		$usersModuleInstance = Vtiger_Module::getInstance('Users');
		$field1 = Vtiger_Field::getInstance('currency_grouping_pattern', $usersModuleInstance);
		$field2 = Vtiger_Field::getInstance('currency_decimal_separator', $usersModuleInstance);
		$field3 = Vtiger_Field::getInstance('currency_grouping_separator', $usersModuleInstance);
		$field4 = Vtiger_Field::getInstance('currency_symbol_placement', $usersModuleInstance);

		$field1->setHelpInfo("<b>Currency - Digit Grouping Pattern</b> <br/><br/>".
									"This pattern specifies the format in which the currency separator will be placed.");
		$field2->setHelpInfo("<b>Currency - Decimal Separator</b> <br/><br/>".
											"Decimal separator specifies the separator to be used to separate ".
											"the fractional values from the whole number part. <br/>".
											"<b>Eg:</b> <br/>".
											". => 123.45 <br/>".
											", => 123,45 <br/>".
											"' => 123'45 <br/>".
											"  => 123 45 <br/>".
											"$ => 123$45 <br/>");
		$field3->setHelpInfo("<b>Currency - Grouping Separator</b> <br/><br/>".
											"Grouping separator specifies the separator to be used to group ".
											"the whole number part into hundreds, thousands etc. <br/>".
											"<b>Eg:</b> <br/>".
											". => 123.456.789 <br/>".
											", => 123,456,789 <br/>".
											"' => 123'456'789 <br/>".
											"  => 123 456 789 <br/>".
											"$ => 123$456$789 <br/>");
		$field4->setHelpInfo("<b>Currency - Symbol Placement</b> <br/><br/>".
											"Symbol Placement allows you to configure the position of the ".
											"currency symbol with respect to the currency value.<br/>".
											"<b>Eg:</b> <br/>".
											"$1.0 => $123,456,789.50 <br/>".
											"1.0$ => 123,456,789.50$ <br/>");
	}
	
	//crm-now: modifications to DB during install
	public static function setCRMNOWmodifications() {
		global $adb;
		$path = Install_Utils_Model::INSTALL_LOG;
		file_put_contents($path, "[".date('Y-m-d h:i:s')."] Include Webservice Utils, get AdoDB instance\n", FILE_APPEND);
		vimport('~~include/Webservices/Utils.php');
		$adb = PearDatabase::getInstance();
		
		//set tag
		file_put_contents($path, "[".date('Y-m-d h:i:s')."] Update version table\n", FILE_APPEND);
		$adb->pquery("UPDATE vtiger_version SET tag_version = ? WHERE id = ?;", array($_SESSION['installer_info']['svn_tag'], 1));
		
		//crm-now: new settings menu for PDF templates
		file_put_contents($path, "[".date('Y-m-d h:i:s')."] Update vtiger_field to add PDF Templates entry\n", FILE_APPEND);
		$ID = $adb->getUniqueID('vtiger_settings_field');
		$params = array($ID, '3', 'LBL_PDF_TEMPLATES', '', 'LBL_PDF_TEMPLATE_DESCRIPTION', 'index.php?parent=Settings&module=Vtiger&view=listpdftexttemplates', '3', '0', '0');
		$adb->pquery("INSERT INTO `vtiger_settings_field` (`fieldid` ,`blockid` ,`name` ,`iconpath` ,`description` ,`linkto` ,`sequence` ,`active`, `pinned`)
		VALUES (".generateQuestionMarks($params).")", $params);

		//crm-now: add all modules to tracking (this was done in migration script)
		file_put_contents($path, "[".date('Y-m-d h:i:s')."] Update Modtracker tracked modules\n", FILE_APPEND);
		if(file_exists('modules/ModTracker/ModTrackerUtils.php')) {
			require_once 'modules/ModTracker/ModTrackerUtils.php';
			$modules = $adb->pquery('SELECT * FROM vtiger_tab WHERE isentitytype = ?;', array(1));
			$rows = $adb->num_rows($modules);
			for($i=0; $i<$rows; $i++) {
				$tabid=$adb->query_result($modules, $i, 'tabid');
				$module=$adb->query_result($modules, $i, 'name');
				file_put_contents($path, "[".date('Y-m-d h:i:s')."] Track $module\n", FILE_APPEND);
				ModTrackerUtils::modTrac_changeModuleVisibility($tabid, 'module_enable');
			}
		}
		
		// set default language specifics
		if ($_SESSION['config_file_info']['default_language'] == "de_de") {
            
            // german defaults for new users
            $adb->query("UPDATE vtiger_field SET defaultvalue = 'This Week' WHERE tablename = 'vtiger_users' AND columnname = 'activity_view'");
            $adb->query("UPDATE vtiger_field SET defaultvalue = 'Last Week' WHERE tablename = 'vtiger_users' AND columnname = 'lead_view'");
            $adb->query("UPDATE vtiger_field SET defaultvalue = '24' WHERE tablename = 'vtiger_users' AND columnname = 'hour_format'");
            $adb->query("UPDATE vtiger_field SET defaultvalue = '08:00' WHERE tablename = 'vtiger_users' AND columnname = 'start_hour'");
            $adb->query("UPDATE vtiger_field SET defaultvalue = 'Europe/Amsterdam' WHERE tablename = 'vtiger_users' AND columnname = 'time_zone'");
            $adb->query("UPDATE vtiger_field SET defaultvalue = '123456789' WHERE tablename = 'vtiger_users' AND columnname = 'currency_grouping_pattern'");
            $adb->query("UPDATE vtiger_field SET defaultvalue = ',' WHERE tablename = 'vtiger_users' AND columnname = 'currency_decimal_separator'");
            $adb->query("UPDATE vtiger_field SET defaultvalue = '.' WHERE tablename = 'vtiger_users' AND columnname = 'currency_grouping_separator'");
            $adb->query("UPDATE vtiger_field SET defaultvalue = '1.0$' WHERE tablename = 'vtiger_users' AND columnname = 'currency_symbol_placement'");
            $adb->query("UPDATE vtiger_field SET defaultvalue = '30 Minutes' WHERE tablename = 'vtiger_users' AND columnname = 'reminder_interval'");
            $adb->query("UPDATE vtiger_field SET defaultvalue = 'Monday' WHERE tablename = 'vtiger_users' AND columnname = 'dayoftheweek'");
            $adb->query("UPDATE vtiger_field SET defaultvalue = '30' WHERE tablename = 'vtiger_users' AND columnname = 'callduration'");
            $adb->query("UPDATE vtiger_field SET defaultvalue = '30' WHERE tablename = 'vtiger_users' AND columnname = 'othereventduration'");
            $adb->query("UPDATE vtiger_field SET defaultvalue = 'Planned' WHERE tablename = 'vtiger_users' AND columnname = 'defaulteventstatus'");
            $adb->query("UPDATE vtiger_field SET defaultvalue = 'Call' WHERE tablename = 'vtiger_users' AND columnname = 'defaultactivitytype'");
        }
		
		//save language after package is installed
		$query = 'SELECT * FROM vtiger_users;';
		$res = $adb->pquery($query, array());
		global $default_language;
		while ($row = $adb->fetch_row($res, false)) {
			$userId = $row['id'];
			$userRecordModel = Users_Record_Model::getInstanceById($userId, 'Users');
			$userRecordModel->set('mode', 'edit'); 
			$userRecordModel->set('language', $default_language); 
			$userRecordModel->save();
		}

		self::startWidgetGui();
		self::translateSettings();
		self::translateWorkflowstasks();
		self::removeGroups();

		//last step, set info this system was installed
		$path = Install_Utils_Model::INSTALL_FINISHED;
		$fh = fopen($path, 'a+');
		fclose($fh);
		
		return true;
	}

	private static function createProfileAndRole() {
		global $current_user;
		$current_user = Users::getActiveAdminUser();
		$adb = PearDatabase::getInstance();
		$allModuleModules = Vtiger_Module_Model::getAll(array(0), Settings_Profiles_Module_Model::getNonVisibleModulesList());
		$eventModule = Vtiger_Module_Model::getInstance('Events');
		$allModuleModules[$eventModule->getId()] = $eventModule;
		$actionModels = Vtiger_Action_Model::getAll(true);
		$permissions = array();
		foreach($allModuleModules AS $tabId => $moduleModel) {
			$permissions[$tabId]['is_permitted'] = Settings_Profiles_Module_Model::IS_PERMITTED_VALUE;
			if($moduleModel->isEntityModule()) {
				$permissions[$tabId]['actions'] = array();
				foreach($actionModels AS $actionModel) {
					if($actionModel->isModuleEnabled($moduleModel)) {
						$permissions[$tabId]['actions'][$actionModel->getId()] = Settings_Profiles_Module_Model::IS_PERMITTED_VALUE;
					}
				}
				$permissions[$tabId]['fields'] = array();
				$moduleFields = $moduleModel->getFields();
				foreach($moduleFields AS $fieldModel) {
					if($fieldModel->isEditEnabled()) {
						$permissions[$tabId]['fields'][$fieldModel->getId()] = Settings_Profiles_Record_Model::PROFILE_FIELD_READWRITE;
					} elseif ($fieldModel->isViewEnabled()) {
						$permissions[$tabId]['fields'][$fieldModel->getId()] = Settings_Profiles_Record_Model::PROFILE_FIELD_READONLY;
					} else {
						$permissions[$tabId]['fields'][$fieldModel->getId()] = Settings_Profiles_Record_Model::PROFILE_FIELD_INACTIVE;
					}
				}
			}
		}
		$recordModel = new Settings_Profiles_Record_Model();
		$recordModel->set('profilename', 'Corporate Management');
		$recordModel->set('description', 'has all previleges');
		$recordModel->set('viewall', 'off');
		$recordModel->set('editall', 'off');
		$recordModel->set('profile_permissions', $permissions);
		$recordModel->save();

		$profileId = $recordModel->getId();

		$organizationRecordModel = Settings_Roles_Record_Model::getInstanceById('H2');
		$organizationRecordModel->set('mode', 'edit');
		$organizationRecordModel->set('profileIds', array($profileId));
		$roleName = $organizationRecordModel->get('rolename');
		$roleName = html_entity_decode($roleName);
		$organizationRecordModel->set('rolename', $roleName);
		$organizationRecordModel->save();

		$roleRecordModel = new Settings_Roles_Record_Model();
		$parentRole = Settings_Roles_Record_Model::getInstanceById('H1');

		$roleRecordModel->set('rolename', 'CRM Administrator');
		$roleRecordModel->set('profileIds', array('1'));
		$roleRecordModel->set('allowassignedrecordsto', 1);
		$parentRole->addChildRole($roleRecordModel);
	}

	private static function translateSettings() {
		//change language of profile description and name of profile in system
		global $adb;

		if (vglobal('default_language') == 'de_de') {
			$sqlProfil = 'UPDATE `vtiger_profile` SET `profilename` = ?, `description` = ? WHERE `profileid` = ?;';
			$profiles = array(1 => array('Administrator','Admin Profil'), 
							  2 => array('Vertriebsprofil','Alle Vertrieb'),
							  3 => array('Support Profil','Alle Support'),
							  4 => array('Gastprofil','Nur gucken'),
							  5 => array('Unternehmensleitung','Kann und darf alles'),
						);
			foreach ($profiles AS $profileId => $infos) {
				$adb->pquery($sqlProfil,array($infos[0], $infos[1], $profileId));
			}

			$sqlRoles = 'UPDATE `vtiger_role` SET `rolename` = ? WHERE `roleid` = ?;';
			$users = array('H1' => array('Organization'),
						   'H2' => array('Geschäftsführung'),
						   'H3' => array('VP Marketing und Vertrieb'),
						   'H4' => array('Vertiebsmanager'),
						   'H5' => array('Vertriebsbeauftragte'),
						   'H6' => array('CRM Administrator/in'),
					 );
			foreach ($users AS $roleId => $infos) {
				$adb->pquery($sqlRoles,array($infos[0], $roleId));
			}
			
			$sqlGroup = 'UPDATE `vtiger_groups` SET `groupname` = ?, `description` = ? WHERE `groupid` = ?;';
			$groups = array(2 => array('Vertriebsteam','Alle Vertrieb'));

			foreach ($groups AS $groupId => $infos) {
				$adb->pquery($sqlGroup,array($infos[0], $infos[1], $groupId));
			}
		}
	}
		
	private static function translateWorkflowstasks(){
		//translate all workflowtasks into german
		global $current_user;
		$current_user = Users::getActiveAdminUser();

		$adb = PearDatabase::getInstance();
		$query = "SELECT * FROM `com_vtiger_workflowtasks` WHERE task LIKE '%VTEmailTask%' AND summary NOT LIKE 'LBL_%'";
		$result = $adb->pquery($query, array());

		$translations = array(
			'An account has been created ' => array(
				'Eine Organisation wurde erstellt', 
				'Regarding Account Assignment' => array(
					'Zuweisung einer erstellten Organisation', 
					'Ihnen wurde eine Organisation im CRM zugewiesen<br>Details der Organisation sind:<br><br>Organisations ID: <b>$account_no</b><br>Organisationsname: <b>$accountname</b><br>Wertung: <b>$rating</b><br>Branche: <b>$industry</b><br>Typ: <b>$accounttype</b><br>Beschreibung:<b>$description</b><br><br><br>Mit freundlichen Gr&uuml;&szlig;en<br> Administrator'
				)
			),
			'A contact has been created ' => array(
				'Eine Person wurde erstellt', 
				'Regarding Contact Creation' => array(
					'Zuweisung einer erstellten Person', 
					'Ihnen wurde eine Person im CRM zugewiesen.<br>Die Details zur der Person sind:<br><br>Person ID:<b>$contact_no</b><br>Nachname:<b>$lastname</b><br>Vorname:<b>$firstname</b><br>Leadquelle:<b>$leadsource</b><br>Abteilung:<b>$department</b><br>Beschreibung:<b>$description</b><br><br><br>Mit freundlichen Gr&uuml;&szlig;en<br>Administrator'
				),
				'Regarding Contact Assignment' => array(
					'Erstellung oder Veränderung einer zugewiesenen Person', 
					'Ihnen wurde eine Person im CRM zugewiesen.<br>Die Details zur der Person sind:<br><br>Person ID:<b>$contact_no</b><br>Nachname:<b>$lastname</b><br>Vorname:<b>$firstname</b><br>Leadquelle:<b>$leadsource</b><br>Abteilung:<b>$department</b><br><br>Beschreibung:<b>$description</b><br><br><br>Mit freundlichen Gr&uuml;&szlig;en<br>Administrator'
				)
			),
			'A Potential has been created ' => array(
				'Eine Potential wurde erstellt', 
				'Regarding Potential Assignment' => array(
					'Zuweisung eines Potentials', 
					'Ihnen wurde ein Potential im CRM zugewiesen<br>Details des Potentials sind:<br><br>Potential Nr.:<b>$potential_no</b><br>Potentialname:<b>$potentialname</b><br>Betrag:<b>$amount</b><br>Erwartetes Abschlussdatum:<b>$closingdate ($_DATE_FORMAT_)</b><br>Typ:<b>$opportunity_type</b><br><br><br>Beschreibung:$description<br><br>Mit freundlichen Gr&uuml;&szlig;en<br>Administrator'
				)
			),
			'Notify Related Contact when Ticket is created from Portal' => array(
				'Benachrichtigung an betreffende Person, bei Ticket-Erstellung vom Portal', 
				'[From Portal] $ticket_no [ Ticket Id : $(general : (__VtigerMeta__) recordId) ] $ticket_title' => array(
					'[From Portal] $ticket_no [ Ticket Id : $(general : (__VtigerMeta__) recordId) ] $ticket_title', 'Ticket Nr.: $ticket_no<br>Ticket ID : $(general : (__VtigerMeta__) recordId)<br>Tickettitel: $ticket_title<br><br>Beschreibung:<br>$description'
				)
			),
			'Send Email to Contact on Ticket Update' => array(
				'Sendet E-Mail an Person bei einer Ticketaktualisierung', 
				'$ticket_no [ Ticket Id : $(general : (__VtigerMeta__) recordId) ] $ticket_title' => array(
					'$ticket_no [ Ticket Id: $(general : (__VtigerMeta__) recordId) ] $ticket_title', 
					'Ticket ID: $(general : (__VtigerMeta__) recordId)<br>Tickettitel: $ticket_title<br><br>Sehr geehrte:r $(contact_id : (Contacts) lastname) $(contact_id : (Contacts) firstname),<br><br>auf das Ticket wurde reagiert. Die Details dazu lauten wie folgt:<br><br>Ticket Nr.: $ticket_no<br>Status: $ticketstatus<br>Kategorie: $ticketcategories<br>Gewichtung: $ticketseverities<br>Priorit&auml;t: $ticketpriorities<br><br>Beschreibung: <br>$description<br><br>L&ouml;sung: <br>$solution<br>Die Kommentare dazu: <br> $allComments<br><br>Mit freundlichen Gr&uuml;&szlig;en<br>Support Administrator'
				)
			),
			'Send Notification Email to Record Owner' => array(
				'Sendet Benachrichtigung an den Zuständigen', 
				'Event :  $subject' => array(
					'Ereignis:  $subject', 
					'$(assigned_user_id : (Users) last_name) $(assigned_user_id : (Users) first_name) ,<br/><b>Details zur Aktivitätsbenachrichtigung:</b><br/>Betreff: $subject<br/>Startdatum &-zeit: $date_start ($(general : (__VtigerMeta__) usertimezone))<br/>Enddatum &-zeit: $due_date ($(general : (__VtigerMeta__) usertimezone)) <br/>Status: $eventstatus <br/>Priorit&auml;t: $taskpriority <br/>Bezogen auf: $(parent_id : (Leads) lastname) $(parent_id : (Leads) firstname) $(parent_id : (Accounts) accountname) $(parent_id : (Potentials) potentialname) $(parent_id : (HelpDesk) ticket_title) $(parent_id : (Campaigns) campaignname) <br/>Personenliste: $contact_id <br/>Ort: $location <br/>Beschreibung: $description'
				),
				'Task :  $subject' => array(
					'Aufgabe:  $subject', 
					'$(assigned_user_id : (Users) last_name) $(assigned_user_id : (Users) first_name) ,<br/><b>Details zur Aufgabenbenachrichtigung:</b><br/>Betreff: $subject<br/>Startdatum &-zeit: $date_start ($(general : (__VtigerMeta__) usertimezone))<br/>Enddatum &-zeit: $due_date ($_DATE_FORMAT_) <br/>Status: $taskstatus <br/>Priorit&auml;t: $taskpriority <br/>Bezogen auf: $(parent_id : (Leads) lastname) $(parent_id : (Leads) firstname) $(parent_id : (Accounts) accountname) $(parent_id : (Potentials) potentialname) $(parent_id : (HelpDesk) ticket_title) $(parent_id : (Campaigns) campaignname) <br/>Personenliste: $contact_id <br/>Beschreibung: $description'
				)
			),
			'Comment Added From Portal : Send Email to Record Owner' => array(
				'Kommentar hinzugefügt von Portal: E-Mail an Zuständigen schicken', 
				'Respond to Ticket ID## $(general : (__VtigerMeta__) recordId) ## in Customer Portal - URGENT' => array(
					'Antwort auf Ticket ID## $(general : (__VtigerMeta__) recordId) ## im Kunden Portal - URGENT', 
					'Sehr geehrte:r $(assigned_user_id : (Users) last_name) $(assigned_user_id : (Users) first_name),<br><br>Der Kunde hat die folgenden zusätzlichen Informationen zu Ihrer Antwort bereitgestellt:<br><br><b>$lastComment</b><br><br>Bitte schnellstmöglich auf das Ticket reagieren.<br><br>Mit freundlichen Gr&uuml;&szlig;en<br>Support Administrator'
				)
			),
			'Comment Added From CRM : Send Email to Contact, where Contact is not a Portal User' => array(
				'Kommentar hinzugefügt von CRM: E-Mail an Person senden, wenn Person kein Portalnutzer ist', 
				'$ticket_no [ Ticket Id : $(general : (__VtigerMeta__) recordId) ] $ticket_title' => array(
					'$ticket_no [ Ticket ID: $(general : (__VtigerMeta__) recordId) ] $ticket_title', 
					'Sehr geehrte:r $(contact_id : (Contacts) lastname) $(contact_id : (Contacts) firstname),<br><br>auf das Ticket wurde reagiert. Die Details dazu lauten wie folgt:<br><br>Ticket Nr.: $ticket_no<br>Status: $ticketstatus<br>Kategorie: $ticketcategories<br>Gewichtung: $ticketseverities<br>Priorit&auml;t: $ticketpriorities<br><br>Beschreibung: <br>$description<br><br>L&ouml;sung: <br>$solution<br>Die Kommentare sind: <br>$allComments<br><br>Mit freundlichen Gr&uuml;&szlig;en<br>Support Administrator'
				)
			),
			'Comment Added From CRM : Send Email to Contact, where Contact is Portal User' => array(
				'Kommentar hinzugefügt von CRM: E-Mail an Person senden, wenn Person ein Portalnutzer ist', 
				'$ticket_no [ Ticket Id : $(general : (__VtigerMeta__) recordId) ] $ticket_title' => array(
					'$ticket_no [ Ticket ID: $(general : (__VtigerMeta__) recordId) ] $ticket_title', 
					'Ticket Nr.: $ticket_no<br>Ticket ID: $(general : (__VtigerMeta__) recordId)<br>Thema: $ticket_title<br><br>Sehr geehrte:r $(contact_id : (Contacts) lastname) $(contact_id : (Contacts) firstname),<br><br>Es gibt eine Antwort auf <b>$ticket_title</b> im "Kundenportal". Sie können den folgenden Link verwenden, um die Antwort zu sehen:<br><a href="$(general : (__VtigerMeta__) portaldetailviewurl)">Ticket Details</a><br><br>Mit freundlichen Gr&uuml;&szlig;en<br>$(general : (__VtigerMeta__) supportName)'
				)
			),
			'Comment Added From CRM : Send Email to Organization' => array(
				'Kommentar hinzugefügt von CRM: E-Mail an Organisation senden', 
				'$ticket_no [ Ticket Id : $(general : (__VtigerMeta__) recordId) ] $ticket_title' => array(
					'$ticket_no [ Ticket ID : $(general : (__VtigerMeta__) recordId) ] $ticket_title', 
					'Ticket ID: $(general : (__VtigerMeta__) recordId)<br>Tickettitel : $ticket_title<br><br>Sehr geehrte:r $(parent_id : (Accounts) accountname),<br><br>auf das Ticket wurde reagiert. Die Details dazu lauten wie folgt:<br><br>Ticket Nr.: $ticket_no<br>Status: $ticketstatus<br>Kategorie: $ticketcategories<br>Gewichtung: $ticketseverities<br>Priorit&auml;t: $ticketpriorities<br><br>Beschreibung: <br>$description<br><br>L&ouml;sung: <br>$solution<br>Die Kommentare sind: <br>$allComments<br><br>Mit freundlichen Gr&uuml;&szlig;en<br>Support Administrator'
				)
			),
			'Notify Record Owner when Ticket is created from Portal' => array(
				'Zuständigen benachrichtigen, wenn ein Ticket vom Portal erstellt wurde', 
				'[From Portal] $ticket_no [ Ticket Id : $(general : (__VtigerMeta__) recordId) ] $ticket_title' => array(
					'[From Portal] $ticket_no [ Ticket ID: $(general : (__VtigerMeta__) recordId) ] $ticket_title', 
					'Ticket Nr.: $ticket_no<br>Ticket ID: $(general : (__VtigerMeta__) recordId)<br>Tickettitel: $ticket_title<br><br>$description'
				)
			),
			'Send Email to Record Owner on Ticket Update' => array(
				'Sendet Zuständigen eine E-Mail zur Ticketaktualisierung', 
				'Ticket Number : $ticket_no $ticket_title' => array(
					'Ticket Nummer: $ticket_no $ticket_title', 
					'Ticket ID : $(general : (__VtigerMeta__) recordId)<br>Tickettitel: $ticket_title<br><br>Sehr geehrte:r $(assigned_user_id : (Users) last_name) $(assigned_user_id : (Users) first_name),<br><br>auf das Ticket wurde reagiert. Die Details dazu lauten wie folgt:<br><br>Ticket Nr.: $ticket_no<br>Status: $ticketstatus<br>Kategorie: $ticketcategories<br>Gewichtung: $ticketseverities<br>Priorit&auml;t: $ticketpriorities<br><br>Beschreibung: <br>$description<br><br>L&ouml;sung: <br>$solution$allComments<br><br>Mit freundlichen Gr&uuml;&szlig;en<br>Support Administrator'
				)
			),
			'Ticket Creation From CRM : Send Email to Record Owner' => array(
				'Ticketerstellung von CRM: E-Mail an Zuständigen schicken', 
				'Ticket Number : $ticket_no $ticket_title' => array(
					'Ticket Nummer: $ticket_no $ticket_title', 
					'Ticket ID: $(general : (__VtigerMeta__) recordId)<br>Tickettitel: $ticket_title<br><br>Sehr geehrte:r $(assigned_user_id : (Users) last_name) $(assigned_user_id : (Users) first_name),<br><br>auf das Ticket wurde reagiert. Die Details dazu lauten wie folgt:<br><br>Ticket Nr.: $ticket_no<br>Status: $ticketstatus<br>Kategorie: $ticketcategories<br>Gewichtung: $ticketseverities<br>Priorit&auml;t: $ticketpriorities<br><br>Beschreibung: <br>$description<br><br>L&ouml;sung: <br>$solution$allComments<br><br>Mit freundlichen Gr&uuml;&szlig;en<br>Support Administrator'
				)
			),
			'Send Email to Organization on Ticket Update' =>  array(
				'Sendet E-Mail an Organisation zur Ticketaktualisierung', 
				'$ticket_no [ Ticket Id : $(general : (__VtigerMeta__) recordId) ] $ticket_title' => array(
					'$ticket_no [ Ticket ID: $(general : (__VtigerMeta__) recordId) ] $ticket_title', 
					'Ticket ID: $(general : (__VtigerMeta__) recordId)<br>Tickettitel : $ticket_title<br><br>Sehr geehrte Damen und Herren von $(parent_id : (Accounts) accountname),<br><br>auf das Ticket wurde reagiert. Die Details dazu lauten wie folgt:<br><br>Ticket Nr.: $ticket_no<br>Status: $ticketstatus<br>Kategorie: $ticketcategories<br>Gewichtung: $ticketseverities<br>Priorit&auml;t: $ticketpriorities<br><br>Beschreibung: <br>$description<br><br>L&ouml;sung: <br>$solution<br>Die Kommentare sind: <br>$allComments<br><br>Mit freundlichen Gr&uuml;&szlig;en<br>Support Administrator'
				)
			),
			'Ticket Creation From CRM : Send Email to Organization' => array(
				'Ticketerstellung von CRM: Sendet E-Mail an Organisation', 
				'$ticket_no [ Ticket Id : $(general : (__VtigerMeta__) recordId) ] $ticket_title' => array(
					'$ticket_no [ Ticket ID: $(general : (__VtigerMeta__) recordId) ] $ticket_title',
					'Ticket ID : $(general : (__VtigerMeta__) recordId)<br>Ticket Title : $ticket_title<br><br>Sehr geehrte:r $(parent_id : (Accounts) accountname),<br><br>auf das Ticket wurde reagiert. Die Details dazu lauten wie folgt:<br><br>Ticket Nr.: $ticket_no<br>Status: $ticketstatus<br>Kategorie: $ticketcategories<br>Gewichtung: $ticketseverities<br>Priorit&auml;t: $ticketpriorities<br><br>Beschreibung: <br>$description<br><br>L&ouml;sung: <br>$solution<br>Die Kommentare sind: <br>$allComments<br><br>Mit freundlichen Gr&uuml;&szlig;en<br>Support Administrator'
				)
			),
			'Ticket Creation From CRM : Send Email to Contact' => array(
				'Ticketerstellung von CRM: Sendet E-Mail an Person', 
				'$ticket_no [ Ticket Id : $(general : (__VtigerMeta__) recordId) ] $ticket_title' => array(
					'$ticket_no [ Ticket Id : $(general : (__VtigerMeta__) recordId) ] $ticket_title', 
					'Ticket ID : $(general : (__VtigerMeta__) recordId)<br>Tickettitle : $ticket_title<br><br>Sehr geehrte:r  $(contact_id : (Contacts) lastname) $(contact_id : (Contacts) firstname),<br><br>auf das Ticket wurde reagiert. Die Details dazu lauten wie folgt:<br><br>Ticket Nr.: $ticket_no<br>Status : $ticketstatus<br>Kategorie: $ticketcategories<br>Gewichtung: $ticketseverities<br>Priorit&auml;t: $ticketpriorities<br><br>Beschreibung: <br>$description<br><br>L&ouml;sung: <br>$solution<br>Die Kommentare sind: <br>$allComments<br><br>Mit freundlichen Gr&uuml;&szlig;en<br>Support Administrator'
				)
			),
		);

		while($row = $adb->getNextRow($result, false)){
			$task = $row['task'];
			$taskId = $row['task_id'];
			$taskObject = unserialize($task);
			$taskClass = get_class($taskObject);
			$summary = $taskObject->summary;
			$subject = $taskObject->subject;
			if(isset($translations[$summary][0])) {
				$taskObject->summary = $translations[$summary][0];
				if(isset($translations[$summary][$subject][0])) {
				$taskObject->subject = $translations[$summary][$subject][0];
				$taskObject->content = $translations[$summary][$subject][1];
				$task = serialize($taskObject);
				$adb->pquery("UPDATE `com_vtiger_workflowtasks` SET task = ? WHERE task_id = ?;", array($task, $taskId));
				}
			}
		}

		$query = "SELECT * FROM `com_vtiger_workflowtasks` WHERE task LIKE '%VTEntityMethodTask%' AND summary NOT LIKE 'LBL_%'";
		$result = $adb->pquery($query, array());

		$translations = array('Email Customer Portal Login Details' => array('E-Mail Kundenportal Login Details'),
							  'Update Inventory Products' => array('Aktualisierung vom Lagerbestand')
						);

		
		while($row = $adb->getNextRow($result, false)){
			$task = $row['task'];
			$taskId = $row['task_id'];
			$taskObject = unserialize($task, ['allowed_classes' => ['VTEntityMethodTask']]);
			$taskClass = get_class($taskObject);
			$summary = $taskObject->summary;
			if(isset($translations[$summary][0])) {
				$taskObject->summary = $translations[$summary][0];
				$task = serialize($taskObject);
				$adb->pquery("UPDATE `com_vtiger_workflowtasks` SET task = ? WHERE task_id = ?;", array($task, $taskId));
			}
		}

		$query = "SELECT * FROM `com_vtiger_workflowtasks` WHERE task LIKE '%VTUpdateFieldsTask%' AND summary NOT LIKE 'LBL_%'";
		$result = $adb->pquery($query, array());

		$translations = array('update forecast amount' => array('Aktualisierung vom Forecast Betrag'),
						);

		
		while($row = $adb->getNextRow($result, false)){
			$task = $row['task'];
			$taskId = $row['task_id'];
			$taskObject = unserialize($task, ['allowed_classes' => ['VTUpdateFieldsTask']]);
			$taskClass = get_class($taskObject);
			$summary = $taskObject->summary;
			if(isset($translations[$summary][0])) {
				$taskObject->summary = $translations[$summary][0];
				$task = serialize($taskObject);
				$adb->pquery("UPDATE `com_vtiger_workflowtasks` SET task = ? WHERE task_id = ?;", array($task, $taskId));
			}
		}

	}

	private static function removeGroups() {
		global $adb;

		$query = 'SELECT * FROM vtiger_groups WHERE groupid > 2;';
		$res = $adb->pquery($query, array());
		$groupRecordModel = Settings_Groups_Record_Model::getInstance('2');
		while ($row = $adb->fetch_row($res, false)) {
			$groupId = $row['groupid'];
			$recordModel = Settings_Groups_Record_Model::getInstance($groupId);
			$recordModel->delete($groupRecordModel);
		}

	}

	private static function startWidgetGui() {
		global $adb;

		$queryWidget = 'INSERT INTO `vtiger_module_dashboard_widgets` (id, linkid, userid, filterid, title, data, position) VALUES ( ?,  ?,  ?,  ?,  ?,  ?,  ?)';
		$standardWidgets = array('1' => array('66', '1', '0', 'CRM Administrator/in Notizen', '{"contents":"'.date('d.m.y').' CRM bereitgestellt","lastSavedOn":"'.date('Y-m-d h:i:s').'"}', '{"row":"1","col":"5"}'),
								   '2' => array('29', '1', '0', '0', 'false', '{"row":"1","col":"1"}'),
								   '3' => array('30', '5', '0', '0', false, NULL),
								   '4' => array('39', '5', '0', '0', false, NULL),
								   '5' => array('29',  '5', '0', '0', false, NULL),
								);
		foreach ($standardWidgets AS $widgetId => $infos) {
			$adb->pquery($queryWidget,array($widgetId, $infos[0], $infos[1], $infos[2], $infos[3], $infos[4], $infos[5]));
		}
	}

}

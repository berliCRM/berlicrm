<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
$languageStrings = array(
	'ModuleName' => 'Module Name',
	'LBL_CUSTOM_INFORMATION' => 'Custom Information',
	'LBL_MODULEBLOCK_INFORMATION' => 'Module Block Information',
	'ModuleFieldLabel' => 'Module Field Label Text',
	'SINGLE_berliCleverReach' => 'CleverReach Group',
    'LBL_LOAD_LIST' => 'load list',
	'LBL_SUCCESS'=>'Success:',
	'LBL_BCR_CAMPAIGN_NAME'=> 'Group Name',
	'LBL_BCR_CAMPAIGN_NO'=>'Group Id',
	'LBL_BCR_CAMPAIGN_TYPE'=>'Group Type',
	'LBL_BCR_CAMPAIGN_STATUS' => 'Group Status',
	'LBL_NEVER' => 'none yet',
	
	'LBL_SETUP_MODULE'=>'Set up your module to enable the synchronization with CleverReach',
	'LBL_CLEVERREACH_MODULE_NAME'=>'CleverReach Information', 
	'LBL_CR_LOGIN_FAILED'=> 'Login failed!',
	'LBL_CHECK_CREDS' => 'Please check your CleverReach credentials.',
	'LBL_SAVE_SUCCESS'=>'Settings saved sucessfully.',
	'LBL_SAVE_ERROR'=>'Error saving settings.',
	'LBL_API_DISCONNECTED'=>'CleverReach API-access removed.',
	
	'LBL_GOT_ALL_MEMBERS_VTIGER_CLEVERREACH'=>'Fetched all entries of CRM group <b>%s</b> (ID %s).',
	'LBL_GOT_ALL_MEMBERS_CLEVERREACH_API'=>'Fetched all entries of CleverReach group <b>%s</b> (ID %s).',
	'LBL_CLEVERREACH_ATTRIB_CREATED'=>'All neccessary attributes created on CleverReach.',
	'LBL_CLEVERREACH_ATTRIB_OK'=>'All neccessary attributes present on CleverReach.',	
	'LBL_NO_CHANGES_TO_SYNC'=>'No changes since last synchronization.',		
		
	'LBL_REMOVE_ENTITYS_FROM_VTIGER'=>'Entries removed from CRM group:',
	'LBL_REMOVE_FROM_CLEVERREACH'=>'Entries deleted from CleverReach:',
	'LBL_NEW_LOCAL_ENTRIES_TO_EXPORT'=>'New CRM entries exported:',
	'LBL_FINISHED_AFTER'=>'Synchronization complete after %s seconds.',	

	'LBL_ENTER_CLEVERREACH_ID'=>'CleverReach customer ID:',
	'LBL_ENTER_CLEVERREACH_USERNAME'=>'CleverReach user name:',
	'LBL_ENTER_CLEVERREACH_PASSWORD'=>'CleverReach password:',
	'LBL_ENTER_CREDENTIALS_FOR_NEW_TOKEN'=>'Please enter your CleverReach credentials to request API token:',
	'LBL_API_TIMEOUT'=>'API Timeout',
	'LBL_API_ERROR'=>'CleverReach API error. Please try again.',
	'LBL_API_TIMEOUT_ACT'=>'Please try again.',
	'LBL_API_AUTH_ERROR'=>'API not authorized!',
	'LBL_API_AUTH_ERROR_ACT'=>'Please check your CleverReach settings.',
	'LBL_API_CONNECTED_TO'=>'Successfully connected to CleverReach API Account #%s of %s %s.',
	'LBL_CREATE_AS'=>'Select whether CleverReach subscribers are<br />created as Contacts or Leads in the CRM',
		
	'LBL_EXISTING_CONTACTS_ADDED'=>'Existing contacts added to CRM sync group:',
	'LBL_EXISTING_LEADS_ADDED'=>'Existing leads added to CRM sync group:',
	'LBL_UPDATED_ENTRIES'=>'Updated entries with changed attributes:',
	'LBL_NEW_CONTACTS_IMPORTED'=>'New CleverReach entries imported as contacts:',
	'LBL_NEW_LEADS_IMPORTED'=>'New CleverReach entries imported as leads:',
	'LBL_BROKEN_CONTACTS'=>'<br><b>Selected entries missing email-address:</b> ',
	'LBL_BROKEN_LEADS'=>'<br><b>Selected entries missing email-address:</b> ',
			
	'LBL_VERBOSE'=>'verbose output',
	'LBL_VERBOSELOG_DELETE'=>'CRM entry &raquo;%s&laquo; deleted, <b>DELETE FROM CLEVERREACH</b>',
	'LBL_VERBOSELOG_NOEXPORTONOPTOUT'=>'CRM entry %s not on CleverReach, but has EmailOptOut set! <b>NO EXPORT</b>',
	'LBL_VERBOSELOG_DELETEDREMOTELY'=>'CRM entry %s deleted remotely. <b>REMOVE FROM CRM GROUP</b>',
	'LBL_VERBOSELOG_EXPORT'=>'CRM-entry %s not yet on CleverReach. <b>EXPORT</b>',
	'LBL_VERBOSELOG_ADDTOCRMGROUP'=>'CleverReach entry %s present in CRM but not yet in sync group. <b>ADD TO SYNC GROUP</b>',
	'LBL_VERBOSELOG_TEST4IMPORT'=>'CleverReach entry %s not yet in CRM.',
	'LBL_VERBOSELOG_INACTIVE'=>' CleverReach entry is inactive.',
    'LBL_VERBOSELOG_TYPEBLOCKED'=>'CleverReach entry &raquo;%s&laquo; is blocked in the CRM for type &raquo;%s&laquo;. <b>SKIP</b>',
	'LBL_VERBOSELOG_INCOMPLETE'=>' Is incomplete!',
	'LBL_VERBOSELOG_DOIMPORT'=>' <b>IMPORT</b>',
	'LBL_VERBOSELOG_DONTIMPORT'=>' <b>NO IMPORT</b>',
	'LBL_VERBOSELOG_HAVEENTRY'=>'CleverReach entry %s present in CRM and sync group.',
	'LBL_VERBOSELOG_UNSUBSCRIBED'=>' <b>UNSUBSCRIBED on %s</b>',
	'LBL_VERBOSELOG_BOUNCED'=>' <b>BOUNCED, blocked until %s</b>',
	'LBL_VERBOSELOG_ATTRIBCHANGED'=>' Attributes changed!',
	'LBL_VERBOSELOG_OPTOUT'=>' Active despite EmailOptOut set!',
	'LBL_VERBOSELOG_DOUPDATE'=>' <b>UPDATE CLEVERREACH</b>',
	'LBL_VERBOSELOG_DONTUPDATE'=>' <b>NO CHANGES</b>',
	
	'LBL_UPDATEPROGRESS'=>'<b>Please wait</b>: Updated %s of %s entries...',
	'LBL_DELETEPROGRESS'=>'<b>Please wait</b>: Deleted %s of %s entries...',
	'LBL_IMPORTPROGRESS'=>'<b>Please wait</b>: Imported %s of %s entries...',
	'LBL_EXPORTPROGRESS'=>'<b>Please wait</b>: Exported %s of %s entries...',

	'LBL_CLEVERREACH_SETTINGS'=>'CleverReach settings',
		
	'LBL_CONTACTS'=>'Contacts',
	'LBL_LEADS'=>'Leads',
	'LBL_STEP'=>'Step',
	'LBL_SAVE'=>'Save',

	'LBL_START_SYNC'=> 'Starting Synchronization',
	'LBL_LAST_SYNCHRONIZATION'=>'Last synchronization',
	
	'Description Information' => 'Description Information',
	'Select One'=>'please select a list',
	'RESPONSE_TIME_OUT'=>'Could not read response (timed out)',
	'LBL_SYNC_HISTORY'=>'CleverReach Synchronization Log',
	'LBL_START_SYNC_BTN'=>'Start Synchronization',
	'LBL_NONE'=>'--None--',
	'LBL_LISTE'=>'CleverReach lists ',
	'GROUPS_ADD'=>'Add groups list ',
	'GROUPS_NOT_ADD'=>'can not add groups list, the groups list already exists or an error occurred by creating groups list',
	'LBL_GROUPS'=>'Groups',
	'LBL_GROUPE'=>'CRM Group Name',
	'LBL_NEW_GROUP'=>'New Group Name',
	'LBL_SYNC'=>'Synchronization',

	'type'=>'Type',
	'status'=>'Status',
	'description'=>'Description',

	'LBL_EMPTY_LOG'=>'Empty Log Display',
	
	'LBL_SELECT_TO_LOAD_LIST'=>'select list',
	'LBL_FIELD_EXISTS'=>'Column exists in CleverReach',
	'Planning'=>'planned',
	'Inactive'=>'inactive',
	'Completed'=>'completed',
	'LBL_NO_CRM_CHANGES'=>'There are no changes since the last synchronization date.',
	'LBL_UPDATED'=>'updated',
	'LBL_NEW_CREATED'=>'created',
	'LBL_FIRST_SYNC'=>'This is your very first synchronization of this list.',
	'LBL_CRM_LIST_EMPTY'=>'Your related contacts and leads lists are empty.',
	'LBL_NO_REMOVED_MEMBER_LAST_SYNC'=>'Since the last synchronization nothing was removed from the CRM list.',
	'LBL_NO_NOTSUBSCRIBED'=>'Number of entries on CleverReach list which are not subscribed:',
	'LBL_TOKENREJECTED' => 'Your access token has been rejected. Please try to login again.',
	);
	
		
	$jsLanguageStrings = array(
		'JS_LBL_ARE_YOU_SURE_YOU_WANT_TO_ADD_THIS_FILTER' => 'Are you sure that you want to add this list?',
		'JSLBL_GOTO_DETAIL_VIEW'=>'Please go to the CleverReach Group Detail view before starting a synchronization.',
		'LBL_MASS_DELETE_REL_CONFIR'=>'Are you sure that you want to remove these entries? If applicable these records will get removed from the CleverReach list during the next synchronization.',
		'of' => 'of',
		'to' => 'to',
		'MC_WAIT' => 'Synchronization started, please wait. Depending on your list size the next operation can take up to 15 minutes.',
		'RESPONSE_TIME_OUT'=>'Unexpected API error (time out)',
		'LBL_GOTO_DETAIL_VIEW'=>'This view does not has a display of log data.',
		'LBL_RECORDS_ADDED' => 'The list was added without creating duplicates.',
		'LBL_ERROR_RECORDS_ADD' => 'The list could not be added.',
	);
?>
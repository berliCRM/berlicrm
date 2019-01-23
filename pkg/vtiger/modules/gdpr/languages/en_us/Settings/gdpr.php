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
	// General Data Protection Regulation (GDPR)
	'LBL_GDPR' => 'GDPR',
	'LBL_GDPR_DESCRIPTION' => 'General Data Protection Regulation (GDPR) Settings',
	'LBL_GDPR_SETTINGS' => 'Settings related to the General Data Protection Regulation (GDPR)',
	'LBL_GDPR_INSTRUCTIONS' => "This menu allows configuration processes about the General Data Protection Regulation (GDPR) which protects all personal data you might have stored in relation to contacts or leads. Such data may only be kept or processed if there is a legal basis to do so. If a person has given explicit consent to process their data the terminability will be considered. Here you can configure the processing of personal data in a lawful manner.",
	'LBL_CONTACT_RELATED' => 'Personal data',
	'LBL_OPERATION_MODE' => 'Deletion of personal data',
	'LBL_SELECT_MODE' => 'mode of operation',
	'LBL_OPERATION_AUTO' => 'automatically',
	'LBL_OPERATION_DEACTIVATED' => 'deactivated',
	'LBL_OPERATION_MANUAL' => 'manually',
	'LBL_NOTIFICATION_TIME' => 'How long in advance would you like to be informed about required deletions?',
	'LBL_SELECT_NOTIFICATION_TIME' => 'wähle Benachrichtigungszeit',
	'LBL_ONE_DAY' => '1 day in advance',
	'LBL_ONE_WEEK' => '1 week in advance',
	'LBL_TWO_WEEKS' => '2 week in advance',
	'LBL_THREE_WEEKS' => '3 week in advance',
	'LBL_FOUR_WEEKS' => '4 week in advance',
	'LBL_DELETE_WHAT' => 'was soll gelöscht werden',
	
	'LBL_MODULES_RELATED' => 'List of active CRM modules',
	'LBL_GDPR_RELEVANT' => 'Does contain personal data?',
	'LBL_ACTIVATED_DELETE_ACTION' => 'Automatic deletion of',
	'LBL_GDPR_RELEVANT_FIELDS' => 'Fields containing personal data',

	'LBL_DELETE_OPERATION' => 'Mode of automatic deletion',
	'LBL_DELETE_TRAY' => 'move to recycle bin',
	'LBL_DELETE_FOREVER' => 'delete irreversibly',
    
	'LBL_NO_AUTO_DELETE' => 'no automatic deletion',
	'LBL_AUTO_DELETE_FIELD' => 'complete record',
	'LBL_AUTO_DELETE_MODULE' => 'personal data of record',

	'LBL_GDPR_HINT' => 'Note:',
	'LBL_GDPR_HINT1' => 'Your data will be scanned once a day. If you select automatic deletion GDPR-affected data will be deleted according to your settings below. If you select manual deletion will receive an email with a list of records to delete.',
	'LBL_GDPR_HINT2' => 'You can chose to be informed about pending deletions in advance. Please note that conditions for deletions could change during this period.',
	'LBL_GDPR_HINT3' => 'Please select the CRM modules containing personal data. You can specifiy a restricted set of fields per record, otherwise a whole record will be considered personal data. If automatic deletion is enabled above you can control this setting per module, too. Press the save button to the right after configuring.',
	'LBL_GDPR_HINT4' => "If you chosse to have data moved to the recycle bin it is your resposibility to empty the bin in time. A list of IDs of deleted records will be kept to be used in case data has to be restored from an older backup to ensure deleted records won't be restored accidentally.",
	'LBL_GDPR_IMPORTANT' => 'Please note:',
	'LBL_GDPR_IMPORTANT1' => "According to the GDPR all personal data must be deleted after six months unless there's expicit reason or consent to store them. The CRM will use the \"created\" date-field for this calculation.<br><b>Automatic deletion has not yet been implemented!</b>",	
	
);
$jsLanguageStrings = array(
	'LBL_GDPR_CONFIGURATION' => 'Modulauswahl',
	'LBL_CONFIG_SAVED'=>'Your preference has been saved.',
	'LBL_CONFIG_CANCEL'=>'Die automatische Datenverarbeitung wurde deaktiviert.',
	'LBL_BIG_PROBLEM' => 'An error occured saving your preferences.',
    'LBL_CUSTOM_REQUIRED_ERROR' => 'This field must not be empty, if you chose "personal data of record" to the right',
);


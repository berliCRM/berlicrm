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
    'LBL_SOFTPHONE_SETTINGS' => 'Softphone Settings',
    'LBL_SOFTPHONE_SELECTION' => 'Softphone Selection',
	'LBL_SOFTPHONE_SERVER_SETTINGS_DESCRIPTION'=>'wählen Sie hier die Parameter für Ihr Softphone',
	'LBL_PHONE_CONFIGURATION' => 'Softphone Configuration',
	'LBL_PHONE_INSTRUCTIONS' => 'A softphone is a software program for making telephone calls over the Internet using a general purpose computer and a headset.Your computer must be able to make IP calls which may generate additional costs. If you want to use a softphone you may make your selection here. The CRM supports only softphones which provide an API for the browser. Depending on your API you may use this for Inbound and Outbound calls.',
	'LBL_PHONE_ID' => 'No.',
	'LBL_PHONE_NAME' => 'Softphone Name',
	'LBL_PHONE_PREFIX' => 'Prefix',
	'LBL_PHONE_ACTIVE' => 'Active',
	'LBL_PHONE_DESCRIPTION' => 'Source Description',
	'LBL_PHONE_INACTIVE_ALL' => 'Deactivate softphone funktion for the CRM: ',
	'LBL_PHONE_SIP' => 'generic - used by most softphones',
	'LBL_PHONE_STARTCALL' => 'generic - used by some softphones</a>',
	'LBL_PHONE_XLIGTH' => '<a href="http://www.counterpath.com/x-lite" target=_blank >www.counterpath.com/x-lite</a>',
	'LBL_PHONE_PHONER' => '<a href="http://www.phoner.de" target=_blank >www.phoner.de</a>',
	'LBL_PHONE_EFFTEL' => '<a href="http://www.efftel.com" target=_blank >www.efftel.com</a>',
	'LBL_PHONE_ZOIPER' => '<a href="www.zoiper.com" target=_blank >www.zoiper.com</a> for Inbound and Outbound calls',
	'LBL_PHONE_NFON' => '<a href="http://www.nfon.com" target=_blank >www.nfon.com</a> (Nsoftphone premium for Windows or iSoftPhone for Mac)',
	'LBL_PHONE_IMPORTANT' => 'Important:',
	'LBL_PHONE_IMPORTANT1' => 'Do not activate a softphone option unless you have a softphone installed on your computer.',
	'LBL_PHONE_HINT' => 'Hints for inbound calls:',
	'LBL_PHONE_HINT1' => 'In order to initiate a contact display by an incoming call at the CRM you have to transmit the phone number of the caller to the CRM. If your softphone has such an option you may set it up that each incoming call initiates an URL call to the following URL:',
	'LBL_PHONE_INBOUND_URL' => '---CRM Client URL---/index.php?module=berliSoftphones&view=List&phonenumber=---Callers Phone#---',
	'LBL_PHONE_HINT2' => 'Please consult the manual for a proper softphone setup. For the softphone Phoner (not Phoner light) you may enter %CALLERID%  at the Options->external application menu as a phone number substitute.',
	'LBL_PHONE_HINT3' => 'The CRM identifies phone numbers which are entered in the same format as the format used by your softphone. They must have at least 4 digits. You may consult the CRM Journal for general hints: <a href="http://blog.crm-now.de/2009/07/22/format-von-telefonnummern/?lang=en" target=_blank>phone number formats</a>',
	'LBL_CONFIG_SAVED'=>'Your settings has been saved.',

);

$jsLanguageStrings = array(
	'LBL_PHONE_CONFIGURATION' => 'Softphone Selection',
	'LBL_CONFIG_SAVED'=>'Your Softphone selection has been activated.',
	'LBL_CONFIG_CANCEL'=>'The CRM Softphone function is deactivated.',
    
);
<?php
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Modified and improved by crm-now.de
 ********************************************************************************/

require_once('include/database/PearDatabase.php');
require_once('include/utils/UserInfoUtil.php');
require_once('include/utils/utils.php');
global $adb,$log;
$log->debug("Entering into Mailchimp Settings Saving");
// all modules available
$apikey=vtlib_purify($_REQUEST['apikey']);
$createtype=vtlib_purify($_REQUEST['newsubscriber']);
// key entry exists?
$check_query="select apikey from vtiger_mailchimp_settings where id =1";
$check_result = $adb->pquery($check_query, array());
if ($adb->num_rows($check_result)>0) {
	//we update key,type for all settings entries
	$update_query="update vtiger_mailchimp_settings set apikey =?, newsubscribertype =?";
	$result = $adb->pquery($update_query, array($apikey, $createtype));
	if ($result) {
		echo "OK";
		exit;
	}
	else {
		echo "SAVING ERROR";
		exit;
	}
}
else {
	//create entry
	$create_query="INSERT INTO `vtiger_mailchimp_settings` (`id`, `apikey`, `listid`, `newsubscribertype`, `lastsyncdate`) VALUES ('1', ?, '', ?, '');";
	$result = $adb->pquery($create_query, array($apikey, $createtype));
	if ($result) {
		echo "OK";
		exit;
	}
	else {
		echo "SAVING ERROR";
		exit;
	}

}


?>
<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Modified and improved by crm-now.de
 *************************************************************************************/

class Settings_berliCleverReach_Module_Model extends Vtiger_Module_Model {
	
	/**
	 * Function to get the Subscriber type (lead or contact)
	 */
	public static function getSubscriberType() {
		$SubscriberType = 'lead';
		$db = PearDatabase::getInstance();
		$query = 'SELECT newsubscribertype FROM vtiger_berlicleverreach_settings WHERE id=1';
		$result = $db->pquery($query, array());
		if ($db->num_rows($result) > 0) {
			$SubscriberType = $db->query_result($result, 0, 'newsubscribertype');
		}
		return $SubscriberType;
	}
	/**
	 * Function to get the CleverReach api credentials
	 */
	public static function getApiCredentials() {

	$db = PearDatabase::getInstance();
	$query = 'SELECT customerid,customername,accesstoken FROM vtiger_berlicleverreach_settings WHERE id=1';
	$result = $db->pquery($query, array());
	if ($db->num_rows($result) > 0) {
		$tmp = $db->fetch_array($result);
	}
	return array("client_id"=>$tmp["customerid"],"login"=>$tmp["customername"],"accesstoken"=>$tmp["accesstoken"]);
	}
}

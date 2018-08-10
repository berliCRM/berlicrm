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

class berliCleverReach_Module_Model extends Vtiger_Module_Model{

	/**
	 * Function to check whether the module is an entity type module or not
	 * @return <Boolean> true/false
	 */
	public function isQuickCreateSupported() {
		//berliCleverReach module is not enabled for quick create
		return false;
	}

	public function isWorkflowSupported() {
		return false;
	}

	/**
	 * Function to check whether the module is summary view supported
	 * @return <Boolean> - true/false
	 */
	public function isSummaryViewSupported() {
		return false;
	}
	
	/**
	 * Function to check whether the module comment supported
	 * @return <Boolean> - true/false
	 */
	public function isCommentEnabled() {
		return true;
	}
	
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
	 * Function to get full CleverReach credentials
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
	
	/**
	 * Function to update token
	 */
	public static function updateToken($token) {

		$db = PearDatabase::getInstance();
		$query = "UPDATE vtiger_berlicleverreach_settings SET `accesstoken`=? WHERE id=1";
		$db->pquery($query, array($token));
	}
}
?>

<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
include_once dirname(__FILE__) . '/SMSNotifierBase.php';
include_once dirname(__FILE__) . '/models/ISMSProvider.php';
include_once 'include/Zend/Json.php';

class SMSNotifier extends SMSNotifierBase {

	/**
	 * Check if there is active server configured.
	 *
	 * @return true if activer server is found, false otherwise.
	 */
	static function checkServer() {
		$provider = SMSNotifierManager::getActiveProviderInstance();
		return ($provider !== false);
	}

	/**
	 * Send SMS (Creates SMS Entity record, links it with related CRM record and triggers provider to send sms)
	 *
	 * @param String $message
	 * @param Array $tonumbers
	 * @param Integer $ownerid User id to assign the SMS record
	 * @param mixed $linktoids List of CRM record id to link SMS record
	 * @param String $linktoModule Modulename of CRM record to link with (if not provided lookup it will be calculated)
	 */
	static function sendsms($message, $tonumbers, $ownerid = false, $linktoids = false, $linktoModule = '') {
		global $current_user, $adb, $log;
		$log->debug("Entering sendsms (Message: ".$message."|"." To Numbers: ".implode(",", $tonumbers)."|"." Owner ID: ".$ownerid."|"." Link to Ids: ".implode(",", $linktoids)."|"." Link to Module: ".$linktoModule.") method  of SMSNotifier.php ...");

		if($ownerid === false) {
			if(isset($current_user) && !empty($current_user)) {
				$ownerid = $current_user->id;
			} 
			else {
				$ownerid = 1;
			}
		}

		$moduleName = 'SMSNotifier';
		$focus = CRMEntity::getInstance($moduleName);

		$focus->column_fields['message'] = utf8_encode($message);
		$focus->column_fields['assigned_user_id'] = $ownerid;
		$focus->save($moduleName);

		if($linktoids !== false) {

			if(!empty($linktoModule)) {
				relateEntities($focus, $moduleName, $focus->id, $linktoModule, $linktoids);
			} 
			else {
				// Link modulename not provided (linktoids can belong to mix of module so determine proper modulename)
				$linkidsetypes = $adb->pquery( "SELECT setype,crmid FROM vtiger_crmentity WHERE crmid IN (".generateQuestionMarks($linktoids) . ")", array($linktoids) );
				if($linkidsetypes && $adb->num_rows($linkidsetypes)) {
					while($linkidsetypesrow = $adb->fetch_array($linkidsetypes)) {
						relateEntities($focus, $moduleName, $focus->id, $linkidsetypesrow['setype'], $linkidsetypesrow['crmid']);
					}
				}
			}
		}
		$responses = self::fireSendSMS($message, $tonumbers);
		$focus->processFireSendSMSResponse($responses);
		$log->debug("Exiting sendsms method of SMSNotifier.php ...");
 
	}

	/**
	 * Detect the related modules based on the entity relation information for this instance.
	 */
	function detectRelatedModules() {
		global $current_user, $adb, $log;
		$log->debug("Entering detectRelatedModules method of SMSNotifier.php ...");

		// Pick the distinct modulenames based on related records.
		$result = $adb->pquery("SELECT distinct setype FROM vtiger_crmentity WHERE crmid in (
			SELECT relcrmid FROM vtiger_crmentityrel INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid=vtiger_crmentityrel.crmid
			WHERE vtiger_crmentity.crmid = ? AND vtiger_crmentity.deleted=0)", array($this->id));

		$relatedModules = array();

		// Calculate the related module access (similar to getRelatedList API in DetailViewUtils.php)
		if($result && $adb->num_rows($result)) {
			require('user_privileges/user_privileges_'.$current_user->id.'.php');
			while($resultrow = $adb->fetch_array($result)) {
				$accessCheck = false;
				$relatedTabId = getTabid($resultrow['setype']);
				if($relatedTabId == 0) {
					$accessCheck = true;
				} 
				else {
					if($profileTabsPermission[$relatedTabId] == 0) {
						if($profileActionPermission[$relatedTabId][3] == 0) {
							$accessCheck = true;
						}
					}
				}

				if($accessCheck) {
					$relatedModules[$relatedTabId] = $resultrow['setype'];
				}
			}
		}
		$log->debug("Exiting detectRelatedModules method of SMSNotifier.php ...");

		return $relatedModules;

	}

	protected function isUserOrGroup($id) {
		global $adb;
		$result = $adb->pquery("SELECT 1 FROM vtiger_users WHERE id=?", array($id));
		if($result && $adb->num_rows($result)) {
			return 'U';
		} 
		else {
			return 'T';
		}
	}

	protected function smsAssignedTo() {
		global $adb;

		// Determine the number based on Assign To
		$assignedtoid = $this->column_fields['assigned_user_id'];
		$type = $this->isUserOrGroup($assignedtoid);

		if($type == 'U'){
			$userIds = array($assignedtoid);
		}else {
			require_once('include/utils/GetGroupUsers.php');
			$getGroupObj=new GetGroupUsers();
			$getGroupObj->getAllUsersInGroup($assignedtoid);
      		$userIds = $getGroupObj->group_users;
		}

		$tonumbers = array();

		if(count($userIds) > 0) {
	       	$phoneSqlQuery = "select phone_mobile, id from vtiger_users WHERE status='Active' AND id in(". generateQuestionMarks($userIds) .")";
	       	$phoneSqlResult = $adb->pquery($phoneSqlQuery, array($userIds));
	       	while($phoneSqlResultRow = $adb->fetch_array($phoneSqlResult)) {
	       		$number = $phoneSqlResultRow['phone_mobile'];
	       		if(!empty($number)) {
					$tonumbers[] = self::formatPhoneNumber($number);
	       		}
	       	}
      	}

      	if(!empty($tonumbers)) {
			$responses = self::fireSendSMS($this->column_fields['message'], $tonumbers);
			$this->processFireSendSMSResponse($responses);
      	}
	}

	private function processFireSendSMSResponse($responses) {
		if(empty($responses)) return;

		global $adb;

		foreach($responses as $response) {
			$responseID = '';
			$responseStatus = '';
			$responseStatusMessage = '';

			$needlookup = 1;
			if($response['error']) {
				$responseStatus = SMSNotifier_ISMSProvider_Model::MSG_STATUS_FAILED;
				$needlookup = 0;
				$responseID ='';
			} 
			else {
				$responseID = $response['id'];
				$responseStatus = $response['status'];
			}

			if(isset($response['statusmessage'])) {
				$responseStatusMessage = $response['statusmessage'];
			}
			$adb->pquery("INSERT INTO vtiger_smsnotifier_status(smsnotifierid,tonumber,status,statusmessage,smsmessageid,needlookup) VALUES(?,?,?,?,?,?)",
				array($this->id,$response['to'],$responseStatus,$responseStatusMessage,$responseID,$needlookup) );
			
		}
	}

	static function smsquery($record) {
		global $adb;
		$result = $adb->pquery("SELECT * FROM vtiger_smsnotifier_status WHERE smsnotifierid = ? AND needlookup = 1", array($record));
		if($result && $adb->num_rows($result)) {
			$provider = SMSNotifierManager::getActiveProviderInstance();

			while($resultrow = $adb->fetch_array($result)) {
				$messageid = $resultrow['smsmessageid'];

				$response = $provider->query($messageid);

				if($response['error']) {
					$responseStatus = $response['status'];
					$needlookup = $response['needlookup'];
				} else {
					$responseStatus = $response['status'];
					$needlookup = $response['needlookup'];
				}

				$responseStatusMessage = '';
				if(isset($response['statusmessage'])) {
					$responseStatusMessage = $response['statusmessage'];
				}

				$adb->pquery("UPDATE vtiger_smsnotifier_status SET status=?, statusmessage=?, needlookup=? WHERE smsmessageid = ?",
					array($responseStatus, $responseStatusMessage, $needlookup, $messageid));
			}
		}
	}

	static function fireSendSMS($message, $tonumbers) {
		global $log;
		$log->debug("Entering fireSendSMS (".$message.",".implode(",", $tonumbers).") method ...");
		$provider = SMSNotifierManager::getActiveProviderInstance();
		if($provider) {
			return $provider->send($message, $tonumbers);
		}
	}

	static function getSMSStatusInfo($record) {
		global $adb;
		$results = array();
		$qresult = $adb->pquery("SELECT * FROM vtiger_smsnotifier_status WHERE smsnotifierid=?", array($record));

		if($qresult && $adb->num_rows($qresult)) {
			while($resultrow = $adb->fetch_array($qresult)) {
				 $results[] = $resultrow;
			}
		}
		return $results;
	}
	
	//crm-now: added for proper phone number formating
	static function formatPhoneNumber($ph_number) {
		global $adb, $log;
		$log->debug("Entering formatPhoneNumber (".$ph_number.") method ...");
		//crm-now: check whether a country prefix from settings must get added
		$resultprefix = $adb->pquery("SELECT countryprefix FROM vtiger_smsnotifier_servers WHERE isactive = ? LIMIT 1", array(1));
		if (!$resultprefix || $adb->num_rows($resultprefix) < 1) {
			return false;
		}
		$prefix = trim($adb->query_result($resultprefix,0,"countryprefix"));
		//remove all char which are not numbers, except + sign if any
		$smsGoesTo = preg_replace('/[^\d+]/i', '', trim($ph_number));
		if ($smsGoesTo =='') {
			return $smsGoesTo;
		}
		$prefix_long = str_replace('+','00',$prefix) ;
		$prefix_long_length = strlen($prefix_long);
		//do not add country prefix if phone number starts with + char
		if (substr($smsGoesTo, 0, 1) !='+') {
			if (substr($smsGoesTo, 0, $prefix_long_length) ==$prefix_long) {
				//replace 00 country prefix with + char
				$smsGoesTo = substr($smsGoesTo, $prefix_long_length);
				$smsGoesTo = $prefix.$smsGoesTo;
			}
			elseif (substr($smsGoesTo, 0, 2) =='00') {
				//do nothing, probably another country prefix
			}
			elseif (substr($smsGoesTo, 0, 1) =='0') {
				//replace leading 0 by prefix
				$smsGoesTo = substr($smsGoesTo, 1);
				$smsGoesTo = $prefix.$smsGoesTo;
			}
			else {
				//add prefix
				$smsGoesTo = $prefix.$smsGoesTo;
			}
		}	
		$log->debug("Exiting formatPhoneNumber method, formatted phone number: ".$smsGoesTo);
		return $smsGoesTo;
	}
	
	static function setSMSStatusInfo($messageId, $status ,$errcode, $timestamp=''){
		global $adb;
		$adb->pquery("update vtiger_smsnotifier_status set status=?, statusmessage =?, timestamp =?  WHERE smsmessageid=?", array($status,$errcode,$timestamp, $messageId));
		return;
	}
	
}

class SMSNotifierManager extends SMSNotifierBase {

	/** Server configuration management */
	static function listAvailableProviders() {
		return SMSNotifier_Provider_Model::listAll();
	}

	public static function getActiveProviderInstance() {
		global $adb;
		$result = $adb->pquery("SELECT * FROM vtiger_smsnotifier_servers WHERE isactive = 1 LIMIT 1", array());
		if($result && $adb->num_rows($result)) {
			$resultrow = $adb->fetch_array($result);
			$provider = SMSNotifier_Provider_Model::getInstance($resultrow['providertype']);
			$parameters = array();
			if(!empty($resultrow['parameters'])) {
				$parameters = Zend_Json::decode(decode_html($resultrow['parameters']));
			}
			foreach($parameters as $k=>$v) {
				$provider->setParameter($k, $v);
			}
			$provider->setAuthParameters($resultrow['username'], $resultrow['password']);

			return $provider;
		}
		return false;
	}

}
?>

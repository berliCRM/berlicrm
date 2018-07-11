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

class Mailchimp_MailchimpSyncStep2_Action extends Mailchimp_MailChimpStepController_Action{

    function __construct() {
        parent::__construct();
	}
	
	public function process(Vtiger_Request $request) {
		self::syncUnsubscribedWithMailChimp();
		// print headline for next step
		parent::writeLogEventText('<p></p>');
		parent::writeLogEventText(getTranslatedString('LBL_WORK_MAILCHIMP', 'Mailchimp'),'','1','B');
		$response = new Vtiger_Response();
		$response->setResult('step2');
		return $response;
	}
	
	/**
	* Function to delete from MailChimp contacts and leads that have been deleted from the CRM related lists since the last synchronization
	*/
	function syncUnsubscribedWithMailChimp(){
		$db = PearDatabase::getInstance();
		$lastGroupSyncDate = parent::getLastGroupSyncDate();
		if (!empty($lastGroupSyncDate)) {
			// get CRM entries that have been deleted since the last synchronization
			// we do that by comparing the content of vtiger_syncdiff with the content of vtiger_crmentityrel
			// entries to delete are marked by an entry in existingMailChimpEntries and a missing entry in vtiger_crmentiyrel
			// everything which is in vtiger_syncdiff but not in vtiger_crmentityrel was removed since last syncronization
			parent::writeLogEventText(getTranslatedString('LBL_GET_REMOVE_MEMBER_LAST_SYNC', 'Mailchimp'));
			
			//1st do it for contacts
			$Contactquery = 'SELECT DISTINCT 
							vtiger_contactdetails.email
							FROM vtiger_contactdetails
							INNER JOIN vtiger_crmentity on vtiger_crmentity.crmid = vtiger_contactdetails.contactid
							INNER JOIN vtiger_mailchimpsyncdiff on vtiger_mailchimpsyncdiff.relcrmid = vtiger_contactdetails.contactid
							WHERE vtiger_mailchimpsyncdiff.crmid  = ? AND vtiger_crmentity.deleted = "0"
							AND vtiger_mailchimpsyncdiff.relcrmid NOT IN (SELECT crmid FROM vtiger_crmentityrel WHERE vtiger_crmentityrel.relcrmid  = ? )
							';
			//2nd do it for leads
			$Leadquery = 'SELECT DISTINCT 
								vtiger_leaddetails.email
								FROM  vtiger_leaddetails
								INNER JOIN vtiger_crmentity on vtiger_crmentity.crmid = vtiger_leaddetails.leadid
								INNER JOIN vtiger_mailchimpsyncdiff on vtiger_mailchimpsyncdiff.relcrmid = vtiger_leaddetails.leadid
								WHERE vtiger_mailchimpsyncdiff.crmid = ? AND vtiger_crmentity.deleted = "0"
									AND vtiger_mailchimpsyncdiff.relcrmid NOT IN
										(SELECT crmid FROM vtiger_crmentityrel WHERE vtiger_crmentityrel.relcrmid  = ? )
							';
			
			$result = $db->pquery($Contactquery,array(self::$recordid,self::$recordid));
			//We only get emails because it is a primary id for MailChimp, all we need to delete members from the MailChimp List
			while($donnee = $db->fetch_row($result)) {
				$emails_to_delete[] = $donnee['email'];
			}

			$result = $db->pquery($Leadquery,array(self::$recordid,self::$recordid));
			//We only get emails because it is a primary id for MailChimp, this is all we need to delete members from the MailChimp List
			while($donnee = $db->fetch_row($result)) {
				$emails_to_delete[] = $donnee['email'];
			}
			if(sizeof($emails_to_delete) != 0){
				// yes, we want members to be deleted at Mailchimp, not unsubscribed
				parent::writeLogEventText(getTranslatedString('LBL_REMOVE_FROM_MAILCHIMP', 'Mailchimp'),'','','','20');
				foreach ($emails_to_delete as $arrkey => $emailaddress) {
					parent::writeLogEventText($emailaddress,'red','','','30');
				}
				//unsubscribe deleted contacts at Mailchimp
				foreach ($emails_to_delete as $key => $email_address) {
					$subscriber_hash = self::$mc_api->subscriberHash($email_address);
					self::$mc_api->delete("lists/".self::$list_id."/members/".$subscriber_hash);
					if (!self::$mc_api->success()) {
						parent::writeLogEventText(getTranslatedString('LBL_BATCH_FAILED', 'Mailchimp'),'red','','','20');
						if (empty($email_address)) {
							parent::writeLogEventText(getTranslatedString('LBL_EMPTY_MAIL', 'Mailchimp'),'red');
						}
						parent::writeLogEventText(getTranslatedString('LBL_ERROR_MSG', 'Mailchimp').' '.self::$mc_api->getLastError(),'red','','','20');
					}
					// we also have to remove these entries from the var $existingMailChimpEntries for the next step3
					foreach (parent::$existingMailChimpEntries as $key => $member) {
						if($member['email_address'] == $email_address){
						  unset(parent::$existingMailChimpEntries[$key]);
					   }
					}
				}
			}
			else {
				parent::writeLogEventText(getTranslatedString('LBL_NO_REMOVED_MEMBER_LAST_SYNC', 'Mailchimp'),'gray');
			}
			//reindex and make modified list available for next step
			$_SESSION['mailchimpdata'] = array_values(parent::$existingMailChimpEntries);
			
		}
		else {
			parent::writeLogEventText(getTranslatedString('LBL_CRM_NONE_DELETED', 'Mailchimp'),'','','','20');
		}
		return;
	}
}
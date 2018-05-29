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

class Mailchimp_MailchimpSyncStep3_Action extends Mailchimp_MailChimpStepController_Action{

	// $show_unsubscribed = true: show a list of all Mailchimp entries (email + name) which are unsubscribed
	// $show_unsubscribed = false: show the number of all Mailchimp entries  which are unsubscribed
	static $show_unsubscribed = false;
	
	// $setEmailOptOut = true: sets the Email Opt Out field of related contact (not lead)
	// $setEmailOptOut = false: no settings
	static $setEmailOptOut = false;
	
    function __construct() {
        parent::__construct();
	}
	
	public function process(Vtiger_Request $request) {
		self::getMailchimpListMembers();
		$response = new Vtiger_Response();
		$response->setResult('step3');
		return $response;
	}

	function getMailchimpListMembers(){
		$db = PearDatabase::getInstance();
		// date stored in vtiger_mailchimp_settings (shown in detail view), if empty = this is the very first sync
		$lastGroupSyncDate = parent::getLastGroupSyncDate();
		if (empty($lastGroupSyncDate)) {
			// very first synchronization
			// - transfer all Mailchimp data to CRM
			parent::writeLogEventText(getTranslatedString('LBL_FIRST_SYNC', 'Mailchimp'));
			parent::writeLogEventText(getTranslatedString('LBL_MOVE_ALL_FROM_MAILCHIMP', 'Mailchimp'));
		}
		else {
			// transfer only new entries
			parent::writeLogEventText(getTranslatedString('LBL_MOVE_PARTIAL_FROM_MAILCHIMP', 'Mailchimp'));
		}
		//transfer data to Mailchimp
		if(count(parent::$existingMailChimpEntries)>0){
			parent::writeLogEventText(getTranslatedString('LBL_START_ADD_CONTACTS', 'Mailchimp'));
			self::addMailchimpDataToCRM();
		}
		else {
			parent::writeLogEventText(getTranslatedString('LBL_NO_MS_ADD', 'Mailchimp'),'','','','20');
		}
	}

	
	protected function addMailchimpDataToCRM(){
		// consider:
		// in Step 1 we had
		//  - added contacts or leads to Mailchimp which had been added since last synchronization
		// in Step 2 we had
		//  - deleted in Mailchimp all entries, which had been removed since last sync from the related list in CRM 
		// Step 3.1 add contacts and leads that have been added to Mailchimp and which have a contact entry already in the CRM (set related list)
		// Step 3.2 create and add contacts and leads that have been added to Mailchimp and which do not have a contact entry in the CRM 
		// Step 3.3 list unsubscribed contacts 
		$db = PearDatabase::getInstance();
		// Step 3.1:
		// Check whether for each email address in Mailchimp a CRM entry exists and set related list
		$emails_in_Mailchimp = array();
		foreach(parent::$existingMailChimpEntries as $member){
			$string_email_in_Mailchimp .= '"'.strtolower($member['email_address']).'",';
			$emails_in_Mailchimp[] = strtolower($member['email_address']);
			// make list of members which are not subscribed
			if ($member['status'] != 'subscribed') {
				$unsubscribed_data[] = array('SALUTATION'=>$member['merge_fields']['SALUTATION'], 'EMAIL'=>$member['email_address'], 'FNAME'=>$member['merge_fields']['FNAME'], 'LNAME'=>$member['merge_fields']['LNAME']?$member['merge_fields']['LNAME']:$l_name[0], 'COMPANY'=>$member['merge_fields']['COMPANY']);
			}
		}
		//remove trailing comma
		$string_email_in_Mailchimp = rtrim($string_email_in_Mailchimp, ",");
		
		parent::writeLogEventText(getTranslatedString('LBL_CHECK_EMAIL_EXIST', 'Mailchimp'),'','','','20');
		//check emails in Contacts
		$contact_query = 'SELECT vtiger_crmentity.setype as type, vtiger_crmentity.crmid as id, vtiger_contactdetails.email as cemail
					FROM vtiger_crmentity 
					left JOIN vtiger_contactdetails on vtiger_crmentity.crmid = vtiger_contactdetails.contactid
					WHERE vtiger_crmentity.deleted = "0"
					AND (LOWER(vtiger_contactdetails.email) IN ('.$string_email_in_Mailchimp.'))';
		$contact_result = $db->pquery($contact_query,array());
		// make a list of contact email addresses in CRM which are in the Mailchimp data and set related list for these entries
		$subcribe_to_mailcampaign = array();
		$emails_in_CRM = array();
		while($donnee = $db->fetch_row($contact_result)){
			$emails_in_CRM[] = strtolower($donnee['cemail']);
			$subcribe_to_mailcampaign[] = array('type' => $donnee['type'], 'id' => $donnee['id'], 'email' => $donnee['cemail']);
		}
		// get email adresses which are in Mailchimp but not in CRM
		$notfound_emails_arr = array();
		$notfound_emails_arr = array_diff($emails_in_Mailchimp,$emails_in_CRM);
		
		if (!empty ($notfound_emails_arr)) {
			//there are emails in Mailchimp which are not part of any contact in CRM
			//check mails in Leads
			$notfound_emails_string = "'".implode("','", $notfound_emails_arr)."'";
			$query = 'SELECT vtiger_crmentity.setype as type, vtiger_crmentity.crmid as id, vtiger_leaddetails.email as lemail
						FROM vtiger_crmentity 
						left JOIN vtiger_leaddetails on vtiger_crmentity.crmid = vtiger_leaddetails.leadid
						WHERE vtiger_crmentity.deleted = 0
						AND vtiger_leaddetails.converted = 0
						AND (vtiger_leaddetails.email  IN ('.$notfound_emails_string.'))';
			$result = $db->pquery($query,array());
			// add lead email addresses to the emails_in_CRM list
			while($donnee = $db->fetch_row($result)){
				$emails_in_CRM[] = strtolower($donnee['lemail']);
				$subcribe_to_mailcampaign[] = array('type' => $donnee['type'], 'id' => $donnee['id'], 'email' => $donnee['lemail']);
			}
		}
		// remove duplicates from CRM emails
		$emails_in_CRM = array_unique($emails_in_CRM);
		
		// We add the existing leads/contact to the Mailchimp Group at the CRM by setting the the related list, if relation does not exist already
		self::setRelatedCRMList($subcribe_to_mailcampaign);
		
		// Step 3.2:
		// now we check whether contacts or leads must get created at the CRM
		$emails_to_add = array_diff($emails_in_Mailchimp, $emails_in_CRM);
		if(!empty($emails_to_add)){	
			/* We create a contact or lead (based on subscriber type) for each email that is not in the database, and we add this contact or lead to the Mail Campaign*/
			$query_string = '';
			parent::writeLogEventText(getTranslatedString('LBL_CHECK_EMAIL_LIST_AND_ADD', 'Mailchimp'),'','','','20');
			foreach(parent::$existingMailChimpEntries as $batchmember){
				if(in_array(strtolower($batchmember['email_address']), $emails_to_add)){
					$first_name = decode_html($batchmember['merge_fields']['FNAME']);
					$last_name = (is_array($batchmember['merge_fields']['LNAME'])) ? implode(' ', decode_html($batchmember['merge_fields']['LNAME'])) : decode_html($batchmember['merge_fields']['LNAME']);
					$email_address = $batchmember['email_address'];
					$company = decode_html($batchmember['merge_fields']['COMPANY']);
					$salutationtype = decode_html($batchmember['merge_fields']['SALUTATION']);

					parent::writeLogEventText($first_name." ".$last_name." ".$email_address,'green','','','30');

					// If the email is related to a company, either we create an account for this contact, or we assign the existing account to the new contact, using the account's id
					$account_id = '';
					if($company != ''){
						$account_id = self::retrieve_account_id($company, $user_id);
					}
					// add to data base
					// todo: check user permission to decide whether subscriber should be added as contact or lead
					if (parent::$subscribertype == 'contact') { 
						//get module fields in CRM
						$field_list_Contacts = self::getFieldList('Contacts');
						$contact = new Contacts();
						$contact->column_fields['salutationtype']=in_array('salutationtype',$field_list_Contacts) ? $salutationtype : "";
						$contact->column_fields['firstname']=in_array('firstname',$field_list_Contacts) ? $first_name : "";
						$contact->column_fields['lastname']=in_array('lastname',$field_list_Contacts) ? $last_name : "";	
						$contact->column_fields['email']=in_array('email',$field_list_Contacts) ? $email_address : "";
						$contact->column_fields['account_id']=in_array('account_id',$field_list_Contacts) ? $account_id : "";
						$contact->column_fields['assigned_user_id']=in_array('assigned_user_id',$field_list_Contacts) ? $user_id : "";
						$contact->save("Contacts");
						$id = $contact->id;
					}
					else {
						//get module fields in CRM
						$field_list_Leads = self::getFieldList('Leads');
						$lead = new Leads();
						$lead->column_fields['firstname']=in_array('firstname',$field_list_Leads) ? $first_name : "";
						$lead->column_fields['lastname']=in_array('lastname',$field_list_Leads) ? $last_name : "";	
						$lead->column_fields['email']=in_array('email',$field_list_Leads) ? $email_address : "";
						$lead->column_fields['company']=in_array('company',$field_list_Leads) ? $company : "";
						$lead->column_fields['assigned_user_id']=in_array('assigned_user_id',$field_list_Leads) ? $user_id : "";
						$lead->save("Leads");
						$id = $lead->id;
					}
					
					// make sure that the new entry isn't already in the relation table
					$tempsql = "SELECT crmid FROM vtiger_crmentityrel 
							INNER JOIN vtiger_crmentity on vtiger_crmentity.crmid = vtiger_crmentityrel.crmid
							WHERE crmid = ? AND relcrmid = ? AND vtiger_crmentity.deleted = '0' ";
					$tempresult = $db->pquery($tempsql,array($id,self::$recordid));
					$relation_exists = false;
					if ($tempresult) {
						while($mailcheck = $db->fetch_row($tempresult)){
							$relation_exists = $mailcheck;
						}
					}

					if (!$relation_exists) {
						//make a new related list entry
						if (parent::$subscribertype == 'contact') {
							$rel_query = "INSERT INTO vtiger_crmentityrel VALUES (?,'Contacts',?,'Mailchimp')";
						}
						else {
							$rel_query = "INSERT INTO vtiger_crmentityrel VALUES (?,'Leads',?,'Mailchimp')";
						}
						$db->pquery($rel_query,array($id,self::$recordid));
					}
				}
			}
		}
		// Step 3.3:
		// show unsubscribed
		parent::writeLogEventText(getTranslatedString('LBL_CHECK_UNSUBSCRIBED', 'Mailchimp'));
		if (count ($unsubscribed_data) == 0) {
			parent::writeLogEventText(getTranslatedString('LBL_NO_UNSUBSCRIBED', 'Mailchimp'),'','','','20');
		}
		else {
			if (self::$show_unsubscribed == true) {
				// list unsubscribed
				parent::writeLogEventText(getTranslatedString('LBL_LIST_UNSUBSCRIBED', 'Mailchimp'));
				foreach ($unsubscribed_data as $dataset) {
					parent::writeLogEventText($dataset['EMAIL']." ".$dataset['FNAME']." ".$dataset['LNAME'],'red','','','20');
					//to do: create option to remove unsubscribed from CRM list
					if (self::$setEmailOptOut == true) {
						// Contact Special: set Email Opt Out Checkbox 
						$sql = "update vtiger_contactdetails set emailoptout= 1  WHERE LOWER(vtiger_contactdetails.email)  = '".strtolower($dataset['EMAIL'])."'";
						$db->query($sql);
					}
				}
			}
			else {
				// show only the number of unsubscribed
				parent::writeLogEventText(getTranslatedString('LBL_NO_NOTSUBSCRIBED', 'Mailchimp').' '.count($unsubscribed_data).' ','red','','','20');
				if (self::$setEmailOptOut == true) {
					foreach ($unsubscribed_data as $dataset) {
						// Contact Special: set Email Opt Out Checkbox 
						$sql = "update vtiger_contactdetails set emailoptout= 1  WHERE LOWER(vtiger_contactdetails.email)  = '".strtolower($dataset['EMAIL'])."'";
						$db->query($sql);
					}
				}
			}
		}
		
	}

	protected function getFieldList($module){
		$db = PearDatabase::getInstance();
		$tabid = getTabid($module);
		$sql1 = "select fieldname,columnname from vtiger_field where tabid=? and vtiger_field.presence in (0,2)";
		$params1 = array($tabid);
		$result1 = $db->pquery($sql1, $params1);
		// get the contact fields
		for($i=0;$i < $db->num_rows($result1);$i++) {
			$field_list[] = $db->query_result($result1,$i,'fieldname');
		}
		return ($field_list);
	}
	
	protected function setRelatedCRMList($subcribe_to_mailcampaign){
		$db = PearDatabase::getInstance();
		$counter = 0;
		$LogWriter = false;
		$num_of_entries = count($subcribe_to_mailcampaign);
		parent::writeLogEventText(getTranslatedString('LBL_LATE_ADD_RELATION', 'Mailchimp'),'','','','20');
		foreach($subcribe_to_mailcampaign as $members){
			// first make sure that an entity with this email address does already exist on this list (in case two or more entities share the same email address)
			$verify_query = "SELECT min(vtiger_crmentityrel.crmid) as crmid FROM vtiger_crmentityrel
						LEFT JOIN vtiger_contactdetails ON vtiger_contactdetails.contactid = vtiger_crmentityrel.crmid
						LEFT JOIN vtiger_leaddetails ON vtiger_leaddetails.leadid = vtiger_crmentityrel.relcrmid
						inner join vtiger_crmentity on vtiger_crmentity.crmid = vtiger_crmentityrel.crmid
						WHERE vtiger_crmentity.deleted = 0 and vtiger_crmentityrel.relcrmid = ? and (vtiger_contactdetails.email = ? OR vtiger_leaddetails.email = ?)";
			$entityExistsResult = $db->pquery($verify_query, array(self::$recordid,$members['email'],$members['email']));
			$entityData ='';
			while($donnee = $db->fetch_row($entityExistsResult)) {
				$entityData = $donnee['crmid'];
			}
			if ($entityData) { 
				$counter = $counter +1;
				continue;
			}
			$query2 = "INSERT INTO vtiger_crmentityrel VALUES ( '".$members['id']."',  '".$members['type']."','".self::$recordid."','Mailchimp')";
			$db->query($query2);
			if ($LogWriter == false) {
				parent::writeLogEventText(getTranslatedString('LBL_MS_ADD', 'Mailchimp'),'','','','20');
				$LogWriter = true;
			}
			parent::writeLogEventText($members['email'],'green','','','30');
		}
	}
	
	
	function retrieve_account_id($account_name,$user_id) {
		if(empty($account_name)) {
			return null;
		}
		$db = PearDatabase::getInstance();
		$query = "select vtiger_account.accountname accountname,vtiger_account.accountid accountid from vtiger_account inner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_account.accountid where vtiger_crmentity.deleted=0 and vtiger_account.accountname=?";
		$result=  $db->pquery($query, array($account_name));
		$rows_count =  $db->getRowCount($result);
		if($rows_count==0) {
			require_once('modules/Accounts/Accounts.php');
			$account = new Accounts();
			$account->column_fields[accountname] = $account_name;
			$account->column_fields[assigned_user_id]=$user_id;
			$account->save("Accounts");
			return $account->id;
		}
		else if ($rows_count==1) {
			$row = $db->fetchByAssoc($result, 0);
			return $row["accountid"];
		}
		else {
			$row = $db->fetchByAssoc($result, 0);
			return $row["accountid"];
		}
	}
}
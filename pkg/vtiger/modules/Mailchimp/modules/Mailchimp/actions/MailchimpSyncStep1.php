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
require_once('modules/Mailchimp/providers/MailChimp.php');

class Mailchimp_MailchimpSyncStep1_Action extends Mailchimp_MailChimpStepController_Action{

    function __construct() {
        parent::__construct();
	}
	
	public function process(Vtiger_Request $request) {
		$list_id=$request->get('list_id');
		$group=$request->get('group');
		$groupslist= $request->get('groupslist');
		self::syncSubscribedWithMailChimp($list_id, $groupslist, $group);

		$response = new Vtiger_Response();
		$response->setResult(self::$log_text);

		return $response;
	}

	
	public function initiateCustomFields($list_id) {
		$apikey = Mailchimp_Module_Model::getApikey();
		$mc_api = new MailChimp($apikey);
		$mc_customfields = array ('Contacts'=>Array('salutation'=>'SALUTATION','account_id'=>'COMPANY'));
		foreach($mc_customfields as $crmmodule=>$field_array){
			foreach($field_array as $crm_field=>$mc_field){
				//start --- create additional list fields in MailChimp if they do not exist
				$fieldfound = false;
				$mcVars = parent::listMergeVars();
				foreach ($mcVars as $merge_name =>$merge_tag) {
					if ($merge_tag == $mc_field) {
						$fieldfound = true;
					}
				}
				//create key
				if ($fieldfound == false) {
					//add key
					$result = parent::addMergeVars($mc_field,$mc_field, 'text');
					parent::writeLogEventText($mc_field.' '.getTranslatedString('LBL_CREATE_FIELD', 'Mailchimp'));
				}
				else {
					parent::writeLogEventText($mc_field.' '.getTranslatedString('LBL_FIELD_EXISTS', 'Mailchimp'));
				}
				//stop --- create additional list fields
			}
		}
	}
	
	protected function initiateMcGroup($list_id,$group) {
		//start --- create additional CRM group in Mailchimp if it does not exist
		$apikey = Mailchimp_Module_Model::getApikey();
		$mc_api = new MailChimp($apikey);
		$groupinfo = parent::listInterestGroupings();
		$group_exists = false;
		if (is_array($groupinfo)) {
			foreach ($groupinfo as $groupis => $groupname) {
				if ($groupname==parent::getGroupName()) {
					$group_exists = true;
				}
			}
		}
		if ($group_exists == false) {
			//create new group at Mailchimp
			$new_group = parent::addInterestGroupings(parent::getGroupName(), 'checkboxes');
			if ($new_group != 'ERROR') {
				parent::writeLogEventText($mc_field.' '.getTranslatedString('GROUPS_ADD', 'Mailchimp'));
			}
			else {
				parent::writeLogEventText(getTranslatedString('GROUPS_NOT_ADD', 'Mailchimp'),'red','','',20);
				parent::writeLogEventText(getTranslatedString('LBL_ERROR_MSG', 'Mailchimp').' '.self::$mc_api->getLastError(),'red','','','20');
				return 'ERROR';
			}
		}
		//stop --- create additional CRM group in Mailchimp if it does not exist
	}
	
     public static function syncSubscribedWithMailChimp($listid, $groupslist, $group) {
		$db = PearDatabase::getInstance();
		// date stored in vtiger_mailchimp_settings (shown in detail view), if empty = this is the very first sync
		$lastGroupSyncDate = parent::getLastGroupSyncDate();
		$lastListSyncDate = parent::getLastListSyncDate();
		// get all data from Mailchimp (to do: get only modified/added entries from Mailchimp for partial synchronization)
		parent::getMailChimpEntries();
		
		if (empty($lastGroupSyncDate) OR count($_SESSION['mailchimpdata']) == 0) {
			// very first synchronization
			// a full synchronization for the CRM has to consider:
			// - transfer all CRM data to Mailchimp
			// - transfer all Mailchimp data to CRM (done in MailchimpSyncStep3.php)
			parent::writeLogEventText(getTranslatedString('LBL_FIRST_SYNC', 'Mailchimp'));
			parent::writeLogEventText(getTranslatedString('LBL_MOVE_TO_MAILCHIMP', 'Mailchimp'));
			//transfer all data to Mailchimp
			self::doFullDataTransfer_to_Mailchimp ();
		}
		else {
			// a partial synchronization for the CRM has to consider:
			// - add to Mailchimp what has been added to the CRM or what has been modified in the CRM since last sync
			// *** new CRM entries are marked by an entry in vtiger_crmentiyrel and a missing entry in vtiger_mailchimpsyncdiff 
			// *** already synchronized but modified CRM entries are marked by vtiger_crmentity.modifiedtime of the related contact or lead after last sync date & time
			// - delete in Mailchimp all entries, which have been removed since last sync from the related list in CRM (done in MailchimpSyncStep2.php)
			// *** entries to delete are marked by an entry in existingMailChimpEntries and a missing entry in vtiger_crmentiyrel
			// - add to CRM what has been added to the Mailchimp since last sync (done in MailchimpSyncStep3.php)
			// - remove from the related CRM list all entries which have been deleted since last sync in Mailchimp (done in MailchimpSyncStep3.php)
			// - set synchronization date (done in MailchimpSyncStep4.php)
			
			// display sync date
			parent::writeLogEventText(getTranslatedString('LBL_GET_LAST_SYNCDATE', 'Mailchimp'));
			parent::writeLogEventText(getTranslatedString('LBL_LAST_SYNC_DATE', 'Mailchimp').' '.$lastListSyncDate);
			
			// transfer new and modified data to Mailchimp
			self::doPartialDataTransfer_to_Mailchimp ();
		}
	}
	

	protected function doFullDataTransfer_to_Mailchimp(){
		// create required custom fields in Mailchimp
		self::initiateCustomFields(self::$list_id);
		// create required list in Mailchimp
		self::initiateMcGroup(self::$list_id,self::$group);
		$db = PearDatabase::getInstance();
		//very first time synchronisation - get all contacts and leads from the CRM related list
		//1st do it for contacts
		$Contactquery = 'SELECT DISTINCT
					vtiger_contactdetails.salutation, 
					vtiger_contactdetails.email, 
					vtiger_crmentityrel.relcrmid, 
					vtiger_contactdetails.firstname, 
					vtiger_contactdetails.lastname, 
					vtiger_account.accountname 
				FROM vtiger_contactdetails 
					INNER JOIN vtiger_contactscf on vtiger_contactscf.contactid = vtiger_contactdetails.contactid
					INNER JOIN vtiger_crmentity on vtiger_crmentity.crmid = vtiger_contactdetails.contactid
					INNER JOIN vtiger_crmentityrel on vtiger_crmentityrel.crmid = vtiger_contactdetails.contactid
					LEFT OUTER JOIN vtiger_account 
						ON vtiger_contactdetails.accountid = vtiger_account.accountid
				WHERE vtiger_crmentityrel.relcrmid =  '.self::$recordid.' 
					AND vtiger_crmentity.deleted = "0"
				';
			//2nd do it for leads
		$Leadquery = 'SELECT DISTINCT
					vtiger_leaddetails.salutation,
					vtiger_leaddetails.email AS email, 
					vtiger_crmentityrel.relcrmid, 
					vtiger_leaddetails.firstname, 
					vtiger_leaddetails.lastname
					FROM vtiger_leaddetails
					INNER JOIN vtiger_crmentityrel on vtiger_crmentityrel.relcrmid = vtiger_leaddetails.leadid
					INNER JOIN vtiger_crmentity on vtiger_crmentity.crmid = vtiger_leaddetails.leadid
					WHERE vtiger_crmentityrel.relcrmid = '.self::$recordid.' 
					AND converted <> 1
					AND vtiger_crmentity.deleted = "0"
				';
		$crm_data = array();

		$result = $db->query($Contactquery);
		while($donnee = $db->fetch_row($result)) {
			$crm_data[] = array('RELID'=>$donnee['crmid'], 'SALUTATION'=>decode_html($donnee['salutation']) ,'EMAIL'=>$donnee['email'], 'FNAME'=>decode_html($donnee['firstname']), 'LNAME'=>decode_html($donnee['lastname']), 'COMPANY'=>decode_html($donnee['accountname']), 'GROUPINGS' => array(array('name'=>self::$campaignName, 'groups'=>'default')));
		}
		$result = $db->query($Leadquery);
		while($donnee = $db->fetch_row($result)) {
			$crm_data[] = array('RELID'=>$donnee['crmid'], 'SALUTATION'=>decode_html($donnee['salutation']) ,'EMAIL'=>$donnee['email'], 'FNAME'=>decode_html($donnee['firstname']), 'LNAME'=>decode_html($donnee['lastname']), 'COMPANY'=>decode_html($donnee['accountname']), 'GROUPINGS' => array(array('name'=>self::$campaignName, 'groups'=>'default')));
		}
		// crm_data contains all contacts and leads from CRM
		if(!empty($crm_data)){
			// We delete duplicates entries for contacts and leads
			parent::writeLogEventText(getTranslatedString('LBL_REMOVE_DUPLICATES', 'Mailchimp'));
			$crm_data = parent::uniqueArray($crm_data);
			self::transferCRMdataToMailchimp($crm_data);
		}
		else {
			parent::writeLogEventText(getTranslatedString('LBL_CRM_LIST_EMPTY', 'Mailchimp'),'gray');
		}

	}
	
	static function doPartialDataTransfer_to_Mailchimp(){
		$db = PearDatabase::getInstance();
		// a partial synchronization has to consider modified and new CRM entries and entries which were deleted from Mailchimp
		// first we need to check whether entries had been deleted at Mail Chimp since the last sync
		// we do that by comparing the email addresses from entries linked by vtiger_mailchimpsyncdiff with the MailChimp entries
		// Important: all email addresses are compared as "lower string" to avoid duplicates
		self::operateSynchronizedCRMEntries('Contacts');
		self::operateSynchronizedCRMEntries('Leads');
		// static $existingMailChimpEntries contains all group entries from Mailchimp [email_address] => [emailaddress]
		//now we start to compare SynchronizedCRMEntries and existingMailChimpEntries
		// start --- Step 1.1 
		// what was synchronized but removed from Mailchimp
		if (count($_SESSION['mailchimpdata'])>0) {
			parent::writeLogEventText(getTranslatedString('LBL_CHECK_DATA_MC', 'Mailchimp'));
			// $keysinboth will contain keys which are at Mailchimp as well as vtiger_mailchimpsyncdiff
			$keysinboth = array ();
			//check for entries which had been deleted at Mailchimp since last synchronisation
			foreach ($_SESSION['mailchimpdata'] as $arr_key => $arr_value) {
				//$keys will contain RELID's which are subscribed in both (CRM as well as Mailchimp)
				// note that comparison is based on strtolower
				$findkeys = array_search(strtolower ($arr_value['email_address']),parent::getSynchronizedCRMEntries() );
				if ($findkeys !='') {
					//there are identical entries in MailChimp and CRM
					$keysinboth[] = $findkeys;
				}
			}
			if (count($keysinboth)>0)  {
				$keysinboth = array_flip ($keysinboth);
				$keep_entries = array_intersect_key(parent::getSynchronizedCRMEntries(), $keysinboth);
				//get the entries which shall be removed
				$remove_entries = array_diff (parent::getSynchronizedCRMEntries(), $keep_entries);
				if (count($remove_entries) >0) {
					parent::writeLogEventText(getTranslatedString('LBL_REMOVE_ID', 'Mailchimp'),'','','','20');
					foreach($remove_entries as $entryid=>$emailaddress){
						//echo email addresses
						parent::writeLogEventText($emailaddress,'red','','','30');
						$RELdel_sql = "DELETE FROM vtiger_crmentityrel WHERE crmid = ?  AND relcrmid = ?";
						$db->pquery($RELdel_sql,array($entryid,self::$recordid));
						$SYNCdel_sql = "DELETE FROM vtiger_mailchimpsyncdiff WHERE relcrmid = ?  AND crmid = ?";
						$db->pquery($SYNCdel_sql,array($entryid,self::$recordid));
					}
				}
				else {
					parent::writeLogEventText(getTranslatedString('LBL_NO_MS_CHANGE', 'Mailchimp'),'','','','20');
				}
			}
		}
		// stop --- Step 1.1 
		// start --- Step 1.2 
		// get all CRM contact and entries which were modified (e.g. email address, name spelling etc.) or added since the last sync
		// date stored in vtiger_mailchimp, used to control partial synchronization
		$currentDate = date('Y-m-d H:i:s');
		$lastListSyncDate = parent::getLastListSyncDate();
		// this query contains all CRM entries which were modified since the last sync
		// 1st do it for contacts
		$Contactquery = 'SELECT DISTINCT vtiger_contactdetails.salutation, vtiger_contactdetails.email, vtiger_crmentityrel.crmid, vtiger_contactdetails.firstname, vtiger_contactdetails.lastname, vtiger_account.accountname, vtiger_crmentity.modifiedtime 
				FROM vtiger_contactdetails 
					INNER JOIN vtiger_contactscf on vtiger_contactscf.contactid = vtiger_contactdetails.contactid
					INNER JOIN vtiger_crmentity on vtiger_crmentity.crmid = vtiger_contactdetails.contactid
					INNER JOIN vtiger_crmentityrel on vtiger_crmentityrel.crmid = vtiger_contactdetails.contactid
					LEFT OUTER JOIN vtiger_account 
						ON vtiger_contactdetails.accountid = vtiger_account.accountid
					WHERE vtiger_crmentityrel.relcrmid = '.self::$recordid.' 
					AND vtiger_crmentity.deleted = "0"
					AND vtiger_crmentity.modifiedtime BETWEEN "'.$lastListSyncDate.'" AND "'.$currentDate.'"
				';
		//2nd do it for leads
		$Leadquery = 'SELECT DISTINCT 
					vtiger_leaddetails.salutation,vtiger_leaddetails.email AS email, vtiger_crmentityrel.crmid, vtiger_leaddetails.firstname, vtiger_leaddetails.lastname, null, vtiger_crmentity.modifiedtime
					FROM vtiger_leaddetails
					INNER JOIN vtiger_crmentityrel on vtiger_crmentityrel.crmid = vtiger_leaddetails.leadid
					INNER JOIN vtiger_crmentity on vtiger_crmentity.crmid = vtiger_leaddetails.leadid
					WHERE vtiger_crmentityrel.relcrmid = '.self::$recordid.' 
					AND converted <> 1
					AND vtiger_crmentity.deleted = "0"
					AND vtiger_crmentity.modifiedtime BETWEEN "'.$lastListSyncDate.'" AND "'.$currentDate.'"
				';
		$crm_data_modfied = array();
		$result = $db->query($Contactquery);
		while($donnee = $db->fetch_row($result)) {
			$crm_data_modfied[] = array('RELID'=>$donnee['crmid'], 'SALUTATION'=>decode_html($donnee['salutation']) ,'EMAIL'=>$donnee['email'], 'FNAME'=>decode_html($donnee['firstname']), 'LNAME'=>decode_html($donnee['lastname']), 'COMPANY'=>decode_html($donnee['accountname']), 'GROUPINGS' => array(array('name'=>self::$campaignName, 'groups'=>'default')));
		}
		$result = $db->query($Leadquery);
		while($donnee = $db->fetch_row($result)) {
			$crm_data_modfied[] = array('RELID'=>$donnee['crmid'], 'SALUTATION'=>decode_html($donnee['salutation']) ,'EMAIL'=>$donnee['email'], 'FNAME'=>decode_html($donnee['firstname']), 'LNAME'=>decode_html($donnee['lastname']), 'COMPANY'=>decode_html($donnee['accountname']), 'GROUPINGS' => array(array('name'=>self::$campaignName, 'groups'=>'default')));
		}
		// crm_data_modfied contains all contacts and leads from CRM which had been modified or added
		if(!empty($crm_data_modfied)){
			parent::writeLogEventText(getTranslatedString('LBL_REMOVE_DUPLICATES', 'Mailchimp'));
			// remove duplicates entries for contacts and leads
			$crm_data_modfied = parent::uniqueArray($crm_data_modfied);
			self::transferCRMdataToMailchimp($crm_data_modfied);
		}
		else {
			parent::writeLogEventText(getTranslatedString('LBL_NO_CRM_CHANGES', 'Mailchimp'),'gray');
		}
		// stop --- Step 1.2 
	}
	

	static function transferCRMdataToMailchimp($crm_data_arr){
		parent::writeLogEventText(getTranslatedString('LBL_ADD_BATCH', 'Mailchimp'));
		// we need to set the default for the related group (interests) by using the proper id
		// to do: find a better way to get the groupid
		// get all interest-categories of this list
		$interest_cat = self::$mc_api->get('lists/'.self::$list_id.'/interest-categories/');
		$categories_arr = $interest_cat['categories'];
		$groupid = '';
		// get all interest of the interest-categories related to this list and group
		foreach ($categories_arr as $key => $category) {
			if ($category['title'] == self::$campaignName) {
				$interests_arr =self::$mc_api->get('lists/'.self::$list_id.'/interest-categories/'.$category['id'].'/interests');
				if (self::$list_id == $interests_arr['interests'][0]['list_id'] and $category['id'] == $interests_arr['interests'][0]['category_id'] ) {
					$groupid = $interests_arr['interests'][0]['id'];
				}
			}
		}
		foreach ($crm_data_arr as $key => $value_arr) {
			set_time_limit(0);
			//check whether it is already a member of the Mailchimp list
			$subscriber_hash = self::$mc_api->subscriberHash(strtolower($value_arr['EMAIL']));
			$result = self::$mc_api->get("lists/".self::$list_id."/members/".$subscriber_hash);
			if (self::$mc_api->success()) {
				//entry exists = update
				self::$mc_api->patch("lists/".self::$list_id."/members/".$subscriber_hash, [
							'merge_fields' => [
								'SALUTATION' => $value_arr['SALUTATION'],
								'FNAME' => $value_arr['FNAME'] ,
								'LNAME' => $value_arr['LNAME'],
								'COMPANY' => $value_arr['COMPANY'],
							],
						]);
				if (self::$mc_api->success()) {
					parent::writeLogEventText($value_arr['FNAME']." ".$value_arr['LNAME']." ".$value_arr['EMAIL']." ".getTranslatedString('LBL_UPDATED', 'Mailchimp'),'green','','','20');
				}
				else {
					// echo self::$mc_api->getLastError();
					parent::writeLogEventText(getTranslatedString('LBL_BATCH_FAILED', 'Mailchimp'),'red','','','20');
					parent::writeLogEventText(getTranslatedString('LBL_ERROR_MSG', 'Mailchimp').' '.self::$mc_api->getLastError(),'red','','','20');
				}
			}
			else {
				//create new entry
				self::$mc_api->post("lists/".self::$list_id."/members", [
					'email_address' => $value_arr['EMAIL'] ,
					'status'        => 'subscribed',
					'merge_fields'  => Array (
								'SALUTATION' => $value_arr['SALUTATION'],
								'FNAME' => $value_arr['FNAME'] ,
								'LNAME' => $value_arr['LNAME'],
								'COMPANY' => $value_arr['COMPANY'],
							) ,
					'interests' 	=> Array (
								$groupid => true
							),
				]);
				if (self::$mc_api->success()) {
					parent::writeLogEventText($value_arr['FNAME']." ".$value_arr['LNAME']." ".$value_arr['EMAIL']." ".getTranslatedString('LBL_NEW_CREATED', 'Mailchimp'),'green','','','20');
				}
				else {
					// echo self::$mc_api->getLastError();
					parent::writeLogEventText(getTranslatedString('LBL_BATCH_FAILED', 'Mailchimp'),'red','','','20');
					parent::writeLogEventText(getTranslatedString('LBL_ERROR_MSG', 'Mailchimp').' '.self::$mc_api->getLastError(),'red','','','20');
				}
			}
		}
	}
	
	static function operateSynchronizedCRMEntries($module){
		//provides crmid => email if in both tables (vtiger_crmentityrel, vtiger_mailchimpsyncdiff)
		$db = PearDatabase::getInstance();
		//1st do it for contacts
		if ($module=='Contacts') {
			$CRMRelquery = 'SELECT DISTINCT
						vtiger_contactdetails.email, 
						vtiger_mailchimpsyncdiff.relcrmid
					FROM vtiger_mailchimpsyncdiff
						INNER JOIN vtiger_contactdetails ON vtiger_contactdetails.contactid = vtiger_mailchimpsyncdiff.relcrmid
						INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_contactdetails.contactid
						LEFT OUTER JOIN vtiger_account 
							ON vtiger_contactdetails.accountid = vtiger_account.accountid
					WHERE vtiger_mailchimpsyncdiff.crmid = ? 
						AND vtiger_crmentity.deleted = "0"
						';
		}
		else {
		//2nd or do it for leads
			$CRMRelquery = 'SELECT DISTINCT
						vtiger_leaddetails.email AS email, 
						vtiger_mailchimpsyncdiff.relcrmid
					FROM vtiger_mailchimpsyncdiff
						INNER JOIN vtiger_leaddetails ON vtiger_leaddetails.leadid = vtiger_mailchimpsyncdiff.relcrmid
						INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_leaddetails.leadid
					WHERE vtiger_mailchimpsyncdiff.crmid = ? 
						AND vtiger_crmentity.deleted = "0"
					';
		}
		$Relqueryresult = $db->pquery($CRMRelquery,array(self::$recordid));
		$numOfRows = $db->num_rows($Relqueryresult);
		$RelBatch = array();
		for($i=0; $i<$numOfRows; ++$i) {
			$email = $db->query_result($Relqueryresult,$i,'email');
			$relcrmid = $db->query_result($Relqueryresult,$i,'relcrmid');
			//all entries from the last sync
			if (!empty($email)) {
				$data [$relcrmid] = strtolower ($email);
				parent::setSynchronizedCRMEntries($data);
			}
		}
		
	}
}

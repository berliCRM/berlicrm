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
require_once('include/database/PearDatabase.php');
require_once('modules/Mailchimp/providers/MailChimp.php');
require_once('modules/Mailchimp/providers/Webhook.php');
require_once('modules/Mailchimp/actions/MailchimpSyncStep1.php');

require_once('modules/Contacts/Contacts.php');
require_once('modules/Leads/Leads.php');

class Mailchimp_MailChimpStepController_Action extends Vtiger_Action_Controller{

	//holds the Mailchimp API key
	static $apikey;
	//holds Contacts or Leads
	public static $subscribertype;
	//CRM Mailchimp module record ID
	static $recordid;
	//CRM Mailchimp list record ID
	static $list_id;
	//synchronization steps
	static $todo;
	//list of groups in Mailchimp 
	static $groupslist;
	//group name used for synchronization from Mailchimp
	static $group;
	//group name of record in CRM Mailchimp module
	static $campaignName;
	//holds a list of entries which are already synchronized
	static $SynchronizedCRMEntries = array();
	// holds all entries from Mailchimp
	public static $existingMailChimpEntries = array();
	// holds the number of 'subcribed' entries from Mailchimp
	public static $MCsubscribedTotal = 0;
	//reserved for future use
	static $action;
	//reserved for future use
	static $mailchimplistempty;
	// text for logging
	static $log_text = array();
	// MC object
	static $mc_api;
	// for future use
	static $webhooks;
	
    public function __construct() {
	}

	static function setMCbatchinfoTotal($num) {
		self::$MCsubscribedTotal = $num;
	}
	static function getMCbatchinfoTotal() {
		return self::$MCsubscribedTotal;
	}
	static function setSynchronizedCRMEntries($data) {
		self::$SynchronizedCRMEntries = $data;
	}
	static function getSynchronizedCRMEntries() {
		return self::$SynchronizedCRMEntries;
	}
	static function getexistingMailChimpEntries() {
		return self::$existingMailChimpEntries;
	}
	
	public function initiateParam($request) {
		self::$apikey= Mailchimp_Module_Model::getApikey();
		self::$subscribertype= Mailchimp_Module_Model::getSubscriberType();
		self::$recordid=$request->get('record');
		self::$list_id=$request->get('list_id');
		self::$action=$request->get('action');
		self::$todo=$request->get('function');
		self::$groupslist=$request->get('groupslist');
		self::$group=$request->get('group');
		self::$campaignName = self::getGroupName();
		self::$mailchimplistempty = false;
		if ($request->get('function') !='MailchimpSyncStep1') {
			self::$existingMailChimpEntries = $_SESSION['mailchimpdata'];
		}
	}
	protected function initiateApi() {
			self::$mc_api = new MailChimp(self::$apikey);
			self::$webhooks = new Webhook(self::$mc_api);
	}
		
	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		$userPrivilegesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		$permission = $userPrivilegesModel->hasModulePermission($moduleModel->getId());

		if(!$permission) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
	}
 
	public function process(Vtiger_Request $request) {
		try {
			$result = array();
			$response = new Vtiger_Response();
			$existkey = self::checkApiKey();
			if ($existkey == false) {
				$response->setResult(json_encode('FAILURE'));
				$response->emit();
				return;
			};
			self::initiateParam($request);
			self::initiateApi();
			$module_nameurl = $request->get('module_nameurl');
			$module_name = strtolower($module_nameurl);
			if(self::$todo=='MailchimpSyncStep1') {
				$this->writeLogEventText('<p></p>');
				$this->writeLogEventText(getTranslatedString('LBL_WORK_CRM', 'Mailchimp'),'','1','B');
				$this->writeLogEventText('');
				$this->writeLogEventText(getTranslatedString('LBL_CAMPAIGN', 'Mailchimp').' <b>'.self::$campaignName.'</b>');
				set_time_limit(0);
				$MailchimpSyncStep1_Action = new Mailchimp_MailchimpSyncStep1_Action();
				$MailchimpSyncStep1_Action->process($request);
				$response->setResult(json_encode(self::$log_text));
				$response->emit();
				return;
			}
			if(self::$todo == 'MailchimpSyncStep2') {
				$MailchimpSyncStep2_Action = new Mailchimp_MailchimpSyncStep2_Action();
				$MailchimpSyncStep2_Action->process($request);
				$response->setResult(json_encode(self::$log_text));
				$response->emit();
				return;
			}
			if(self::$todo == 'MailchimpSyncStep3') {
				$MailchimpSyncStep3_Action = new Mailchimp_MailchimpSyncStep3_Action();
				$MailchimpSyncStep3_Action->process($request);
				$response->setResult(json_encode(self::$log_text));
				$response->emit();
				return;
			}
			if(self::$todo == 'MailchimpSyncStep4') {
				$MailchimpSyncStep4_Action = new Mailchimp_MailchimpSyncStep4_Action();
				$MailchimpSyncStep4_Action->process($request);
				$response->setResult(json_encode(self::$log_text));
				$response->emit();
				return;
			}
		} 
		catch (Exception $ex) {
			echo $ex->getMessage();
			$response->setResult( $ex->getMessage());
			$response->emit();
		}

   }
 	protected function checkApiKey() {
		$apikey = Mailchimp_Module_Model::getApikey();
		if (trim($apikey =='')){
			return false;
		}
		else {
			self::$apikey= Mailchimp_Module_Model::getApikey();
			return true;
		}
	}
  
	static function writeLogEventText($logstring,$color='',$size='',$bold='',$margin='') {
		$style ='';
		if (!empty($color)) {
			$style = 'color:'.$color.';';
		}
		if (!empty($size)) {
			$style .= 'font-size:'.$size.'rem;';
		}
		else {
			$style .= 'font-size:0.8rem;';
		}
		if (!empty($bold)) {
			$style .= 'font-weight:bold;';
		}
		if (!empty($margin)) {
			$style .= 'margin:'.$margin.'px; margin-top:0; margin-bottom:0;';
		}
		else {
			$style .= 'margin:0px; margin-top:1; margin-bottom:1;';
		}
		$logtext =  array (
		  'text' => $logstring,
		  'style' => $style
		);
		array_push(self::$log_text, $logtext);
	}
   
	/**
	* Get the Mail Campaign name because it is used to match the Mail Campaign to the MailChimp list 
	*/
	protected static function getGroupName(){
		$db = PearDatabase::getInstance();
		$result = $db->pquery("select mailchimpname from vtiger_mailchimp where vtiger_mailchimp.mailchimpid = ?", array(self::$recordid));
		$donnee = $db->fetch_row($result);
		return $donnee['mailchimpname'];
	}
	protected static function getLastGroupSyncDate(){

		$db = PearDatabase::getInstance();
		$query = 'SELECT * FROM vtiger_mailchimp WHERE vtiger_mailchimp.mailchimpid = ?';
		$result = $db->pquery($query,array(self::$recordid));
		while($donnee = $db->fetch_row($result)) {
			return $donnee['lastsynchronization'];
		}
	}
	protected static function getLastListSyncDate(){
		$db = PearDatabase::getInstance();
		$query = 'SELECT lastsyncdate FROM vtiger_mailchimp_settings WHERE listid= ?';
		$result = $db->pquery($query,array(self::$list_id));
		while($donnee = $db->fetch_row($result)) {
			return $donnee['lastsyncdate'];
		}
	}
	public static function getNumberOfMailchimpEntries($list_id){
		//provides the number of 'subscribed' entries and does not consider others like unsubscribed, cleaned, ....
		$MCbatchinfo = self::$mc_api->get("lists/{$list_id}");
		$member_count = $MCbatchinfo['stats']['member_count'];
		if (self::$mc_api->success()) {
			return	$member_count;
		}
		else {
			$this->writeLogEventText(getTranslatedString('LBL_BATCH_FAILED', 'Mailchimp'),'red');
			$this->writeLogEventText(getTranslatedString('LBL_ERROR_MSG', 'Mailchimp').' '.self::$mc_api->getLastError(),'red','','','20');
			return 'ERROR';
		}
	}
	/**
	* Remove duplicates from a multidimensional array
	*/
	static function uniqueArray($sync_array) {
		$rslt_array = array();
		$known_email = array();
		foreach ($sync_array as $entry) {
			$email = $entry["EMAIL"];
			$bool = in_array($email, $known_email);
			if(!$bool){
				$rslt_array[] = $entry;
				$known_email[] = $entry["EMAIL"];
			}
		}
		return $rslt_array;
	}
	/**
	* get all entries from Mailchimp
	*/
	protected static function getMailChimpEntries(){
		// there is a limit for large data sets, the number of results to return - defaults to 10(!), upper limit set at 15000 - therefore we have to loop through the data
		// first get the total (limit 1) to decide the batch size for speed optimization
		$MCtotal = self::getNumberOfMailchimpEntries(self::$list_id);
		self::setMCbatchinfoTotal($MCtotal);
		if ($MCtotal > 100) {
			$numberPerBatch = 100;
		}
		else if ($MCtotal > 50){
			$numberPerBatch = 50;
		}
		else {
			$numberPerBatch = 5;
		}
		$offset = 0;
		$actualMCdata = array ();
		// infinite loop interrupted using a break
        while (true) {
			$MCbatchinfoLoop = self::$mc_api->get('lists/'.self::$list_id.'/members?offset='.$offset.'&count='.$numberPerBatch.'');
			if (self::$mc_api->success()) {
				$actualMCdata =array_merge($actualMCdata, $MCbatchinfoLoop['members']);
			}
			else {
				$this->writeLogEventText(getTranslatedString('LBL_BATCH_FAILED', 'Mailchimp'),'red');
				$this->writeLogEventText(getTranslatedString('LBL_ERROR_MSG', 'Mailchimp').' '.self::$mc_api->getLastError(),'red','','','20');
			}
			if (count ($MCbatchinfoLoop['members']) < $numberPerBatch) {
                break;
            }
			$offset = $offset + $numberPerBatch;
		}
		self::$existingMailChimpEntries = $actualMCdata;
		$_SESSION['mailchimpdata'] = $actualMCdata;
	}
	/**
	* get all tags (internal MC field names) from merge information
	* returns array([name]=>[tag])
	*/
	protected static function listMergeVars(){
		$result = self::$mc_api->get("lists/".self::$list_id."/merge-fields");
		$merge_field_list = $result['merge_fields'] ;
		foreach ($merge_field_list as $key => $mergefield) {
			$fieldtag[$mergefield ['name']] =  $mergefield ['tag'];
		}
		return $fieldtag;
	}
	
	/**
	* add a tags (internal MC field names) to merge information
	* <name> as displayed in MC list
	* <type> supported: text, number, address, phone, email, date, url, imageurl, radio, dropdown, birthday, zip
	* <tag> one word in capital letters
	*/
	protected static function addMergeVars($new_merge_field, $tagname, $fieldtype){
		$RESULT= self::$mc_api->post("lists/".self::$list_id."/merge-fields", [
					'name' 	=> $new_merge_field ,
					'tag' 	=> $tagname ,
					'type'	=> $fieldtype,
											
		]);
		if (self::$mc_api->success()) {
			return;
		}
		else {
			$this->writeLogEventText(getTranslatedString('LBL_FIELD_EXISTS', 'Mailchimp'),'red');
			$this->writeLogEventText(getTranslatedString('LBL_ERROR_MSG', 'Mailchimp').' '.self::$mc_api->getLastError(),'red','','','20');
			return;
		}
	}
	/**
	* get all groups 
	* returns array([name]=>[tag])
	*/
	protected static function listInterestGroupings(){
		$interest_cat = self::$mc_api->get('lists/'.self::$list_id.'/interest-categories/');
		$categories_arr = $interest_cat['categories'];
		if (is_array($categories_arr)) {
			foreach ($categories_arr as $key => $category) {
				$groups[$category ['id']] =  $category ['title'];
			}
			return $groups;
		}
		else {
			return 'ERROR';
		}
	}
	
	/**
	* add a tags (internal MC field names) to merge information
	* <name> as displayed in MC list
	* <type> supported: text, number, address, phone, email, date, url, imageurl, radio, dropdown, birthday, zip
	* <tag> one word in capital letters
	*/
	protected static function addInterestGroupings($title, $type){
		$new_interests = self::$mc_api->post('lists/'.self::$list_id.'/interest-categories/', [
							 'title' 	=> $title ,
							 'type' 	=> $type ,
													
		]);
		if (self::$mc_api->success()) {
			//add interests group name
			$new_interests_id = $new_interests['id'];
			$new_groupincat = self::$mc_api->post('lists/'.self::$list_id.'/interest-categories/'.$new_interests_id.'/interests', array (
						 'name' 	=> 'default' ,
			 ));
			return;
		}
		else {
			$this->writeLogEventText(getTranslatedString('LBL_FIELD_EXISTS', 'Mailchimp'),'red');
			$this->writeLogEventText(getTranslatedString('LBL_ERROR_MSG', 'Mailchimp').' '.self::$mc_api->getLastError(),'red','','','20');
			return 'ERROR';
		}
	}
	
}

?>
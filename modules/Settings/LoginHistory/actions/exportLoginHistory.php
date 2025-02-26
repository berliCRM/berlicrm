<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
include_once 'include/Webservices/Retrieve.php';

class Settings_LoginHistory_exportLoginHistory_Action {
	
	public function validateRequest(Vtiger_Request $request) { 
        $request->validateReadAccess(); 
	}
	public function loginRequired() {
		return true;
	}
	public function checkPermission() { }
	
	function preProcess(Vtiger_Request $request) {
		return true;
	}

	function postProcess(Vtiger_Request $request) {
		return true;
	}
	
	function process(Vtiger_Request $request) {
		$mode = $request->get('mode');
		$offset = $request->get('offset');  
		$selecteduser = $request->get('selecteduser');
        $filetyp = $request->get('filetyp');

		if(!empty($mode)) {
			$this->export($mode, $offset ,$selecteduser, $filetyp);
		}
		return;
	}
	
	static function export($mode, $offset, $selecteduser, $filetyp) {
		global $current_user;
		$db = PearDatabase::getInstance();
		if ($mode == "exportUserByName") {
			$orderBy = " vtiger_loginhistory.user_name, vtiger_loginhistory.login_time DESC ";
		}
		else {
			$orderBy = " vtiger_loginhistory.login_time DESC ";
		}

		$whereuseris = "";
		if(!empty($selecteduser) && strlen($selecteduser) > 0 && $selecteduser != "undefined"){
			$whereuseris = " WHERE vtiger_loginhistory.user_name = '".$selecteduser."' ";
			$orderBy = " vtiger_loginhistory.login_time DESC ";
		}
		
		$numberPerBatch = 50000;
		$numRows = 1;

		$query = 
		"SELECT CONCAT(vtiger_users.first_name,' ',vtiger_users.last_name) AS user_name_full, vtiger_loginhistory.user_ip, vtiger_loginhistory.login_time, vtiger_loginhistory.logout_time, vtiger_loginhistory.status, vtiger_role.rolename 
		FROM vtiger_loginhistory 
		INNER JOIN vtiger_users ON vtiger_users.user_name = vtiger_loginhistory.user_name 
		INNER JOIN vtiger_user2role ON vtiger_user2role.userid = vtiger_users.id 
		INNER JOIN vtiger_role ON vtiger_user2role.roleid = vtiger_role.roleid "
		.$whereuseris.
		" ORDER BY ".$orderBy ; 
		
		$path = "storage/";
		$fileName = "LoginHistory".$mode.".".$filetyp;

		$wiederholen = 1;
        $startednow = 0;
		
		// add to query new dynamic params
		$query = $query." LIMIT $numberPerBatch OFFSET $offset ";

		$result = $db->pquery($query, array( ));
		$numRows = $db->num_rows($result);

		// Open FILE
		$path_file = fopen($path.$fileName, 'a+');

		if($offset == 0){
			// add headers to CSV 
			$startednow = 1;
			$qualified_module = "Settings:LoginHistory"; 
			$headerscsv = "".vtranslate('LBL_USER_NAME', $qualified_module).",".vtranslate('LBL_USER_IP_ADDRESS', $qualified_module).",".vtranslate('LBL_LOGIN_TIME', $qualified_module).",".vtranslate('LBL_LOGGED_OUT_TIME', $qualified_module).",".vtranslate('LBL_STATUS', $qualified_module).",".vtranslate('LBL_ROLE', $qualified_module)."\n";
			fwrite($path_file, print_r($headerscsv, TRUE));
		}
		else{
			$startednow = 0;
		}
		// then add the next step hits. 
		while($row = $db->fetch_array($result)) {
			set_time_limit(0);
			$zeile = "".decode_html($row['user_name_full']).",".$row['user_ip'].",".$row['login_time'].",".$row['logout_time'].",".($row['status']).",".decode_html($row['rolename'])."\n";
			fwrite($path_file, print_r($zeile, TRUE));
		}
		
		fclose ($path_file);
        // Close FILE
		
		if($numRows == $numberPerBatch){
			$wiederholen = 1;
		}
		else{
			// the end is reach, now need to stop writing.
			$wiederholen = 0;
		}
		$offset = $offset + $numRows;
		
		$response = new Vtiger_Response();
		$result = array(
			'recordnum'=>$offset, 
			'startednow'=>$startednow, 
			'wiederholen'=>$wiederholen
		);
		$response->setResult($result);
		$response->emit();
		
	}
}

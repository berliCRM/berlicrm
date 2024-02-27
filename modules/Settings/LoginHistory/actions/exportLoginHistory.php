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
		if(!empty($mode)) {
			$this->export( vtlib_purify( $mode) );
		}
		return;
	}
	
	static function export($mode) {
		global $current_user;
		$db = PearDatabase::getInstance();
		if ($mode == 'exportUserByName') {
			$orderBy = 'user_name, login_time DESC';
		}
		else {
			$orderBy = 'login_time DESC';
		}
		$query = 'SELECT * FROM vtiger_loginhistory ORDER BY '.$orderBy;
		$result = $db->pquery($query, array());
		
		while($row = $db->fetch_array($result)) {
		  $data []= $row['user_name'].",".$row['user_ip'].",".$row['login_time'].",".$row['logout_time'].",".$row['status']."\n";
		}
		$response = new Vtiger_Response();
		$response->setResult(array('data'=>$data));
		$response->emit();
	}	
}

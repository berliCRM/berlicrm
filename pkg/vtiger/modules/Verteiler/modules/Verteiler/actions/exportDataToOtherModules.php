<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
class Verteiler_exportDataToOtherModules_Action extends Vtiger_Action_Controller {

	function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$record = $request->get('record');

		$currentUserPrivilegesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		if(!$currentUserPrivilegesModel->isPermitted($moduleName, 'Edit', $record)) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
	}

	public function process(Vtiger_Request $request) {
		$db = PearDatabase::getInstance();
		$mode = $request->get('mode');

		if ($mode == 'export') {
			try {
				$exportResult= self::exportDateToModule($request);
				$result = array('success' => true);
			} 
			catch(Exception $e) {
				// error
				$result = array('success' => false, 'error' => $e->getMessage() );
			}
		}
		else {
			$result = array('success' => false, 'error' => 'ERROR: this function is not activated');
		}
		
		$response = new Vtiger_Response();
		$response->setResult($result);
		return $response;
		
	}
	
	function exportDateToModule($request) {
		$db = PearDatabase::getInstance();
		$current_user = Users_Record_Model::getCurrentUserModel();
		$currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		$target_modules = array ('Campaigns','Mailchimp','berliCleverReach');
		$recordId = $request->get('record');
		$moduleName = $request->getModule();
		
		// get related contacts
		$contactIds = Verteiler_Relation_Model::getContactIdsFromVerteiler($recordId);
		
		$target = $request->get('target');
		$target_arr = explode("_", $target);
		$target_module = $target_arr[0];
		$target_id = $target_arr[1];
		// check privileges
		if (!in_array($target_module, $target_modules)) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
	
		if(isRecordExists($target_id) && !$currentUserPriviligesModel->isPermitted($target_module, 'Edit', $target_id)) {
			throw new AppException(vtranslate('LBL_MODULE_EDIT_PERMISSION_DENIED',$moduleName));
		}

		// export data
		$focus = CRMEntity::getInstance($target_module);
		$focus->save_related_module($target_module, $target_id, 'Contacts', $contactIds);
		
		//make a history note
		$date_var = date("Y-m-d H:i:s");
		$date_var = $db->formatDate($date_var, true);
		$newmodtrackid = $db->getUniqueId('vtiger_modtracker_basic');
		$db->pquery('INSERT INTO vtiger_modtracker_basic(id, crmid, module, whodid, changedon, status) VALUES(?,?,?,?,?,?)', array($newmodtrackid, $recordId, $moduleName, $current_user->id, $date_var, 0));
		$db->pquery('INSERT INTO vtiger_modtracker_detail(id,fieldname,prevalue,postvalue) VALUES(?,?,?,?)',array($newmodtrackid, "description", "",count($contactIds).' '.vtranslate('LBL_TRACKERINFO',$moduleName).vtranslate($target_module)));  
		
		return $exportResult;
		
	}
}

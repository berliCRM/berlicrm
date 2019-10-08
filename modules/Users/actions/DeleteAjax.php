<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
vimport('~~/include/Webservices/DeleteUser.php');

class Users_DeleteAjax_Action extends Vtiger_Delete_Action {

	public function process(Vtiger_Request $request) {
		$moduleName = $request->getModule();
        $ownerId = $request->get('userid');
        $newOwnerId = $request->get('transfer_user_id');
		$success = true;
 
		$userModel = Users_Record_Model::getCurrentUserModel();
		if($request->get('mode') == 'permanent')  {
            try {
				$this -> set_user_active($ownerId);
				$userId = vtws_getWebserviceEntityId($moduleName, $ownerId);
				$transformUserId = vtws_getWebserviceEntityId($moduleName, $newOwnerId);
				vtws_deleteUser($userId, $transformUserId, $userModel);
				Users_Record_Model::deleteUserPermanently($ownerId, $newOwnerId);
				$message = vtranslate('LBL_USER_DELETED_SUCCESSFULLY', $moduleName);
			} 
			catch (WebServiceException $we) {
				$this -> set_user_inactive($ownerId);
				$message = 'Source userId:'.$userId.' Target userId:'.$transformUserId.' '.$we->getMessage();
				$success = false;
			}
        } 
		else {
            $userId = vtws_getWebserviceEntityId($moduleName, $ownerId);
            $transformUserId = vtws_getWebserviceEntityId($moduleName, $newOwnerId);
			try {
				$message = vtranslate('LBL_USER_DELETED_SUCCESSFULLY', $moduleName);
				vtws_deleteUser($userId, $transformUserId, $userModel);
				if($request->get('permanent') == '1') {
					Users_Record_Model::deleteUserPermanently($ownerId, $newOwnerId);
				}
				else {
					$this -> set_user_inactive($ownerId);
				}
			} 
			catch (WebServiceException $we) {
				$this -> set_user_inactive($ownerId);
				$message = 'Source userId:'.$userId.' Target userId:'.$transformUserId.' '.$we->getMessage();
				$success = false;
			}
        }
		
		$response = new Vtiger_Response();
		$response->setResult(array('success' => $success, 'message' => $message));
		$response->emit();
	}
	
    /**
     * This function sets the status to Active.
     * @param <type> $id
     */
    function set_user_active($id) {
		global $log, $current_user, $adb;
		$date_var = date('Y-m-d H:i:s');
		$query = "UPDATE vtiger_users set status=?,date_modified=?,modified_user_id=? where id=?";
		$adb->pquery($query, array('Active', $adb->formatDate($date_var, true),$current_user->id, $id), true,"Error user record change status ");
    }
    /**
     * This function sets the status to Inactive.
     * @param <type> $id
     */
    function set_user_inactive($id) {
		global $log, $current_user, $adb;
        $date_var = date('Y-m-d H:i:s');
		$query = "UPDATE vtiger_users set status=?,date_modified=?,modified_user_id=? where id=?";
		$adb->pquery($query, array('Inactive', $adb->formatDate($date_var, true),$current_user->id, $id), true,"Error user record change status");
    }
}

<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Users_Save_Action extends Vtiger_Save_Action {
	
	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$currentUserModel = Users_Record_Model::getCurrentUserModel();

		$recordRaw = $request->get('record');

		// Strictly decide: create vs edit
		$isEdit = (is_string($recordRaw) || is_int($recordRaw))
			&& preg_match('/^\d+$/', (string)$recordRaw)
			&& ((int)$recordRaw > 0);

		if (!$isEdit) {
			// CREATE user
			if (!$currentUserModel->isAdminUser()) {
				throw new AppException('LBL_PERMISSION_DENIED');
			}

			// Optional: also require explicit Create permission if your vtiger uses it
			if (!Users_Privileges_Model::isPermitted($moduleName, 'CreateView')) {
				throw new AppException('LBL_PERMISSION_DENIED');
			}
			return;
		}

		// EDIT user
		$recordId = (int)$recordRaw;

		// Require Save permission on that record
		if (!Users_Privileges_Model::isPermitted($moduleName, 'Save', $recordId)) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}

		// Non-admins may only edit themselves
		if (!$currentUserModel->isAdminUser() && ((int)$currentUserModel->get('id') !== $recordId)) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}

		// Only now load the record to avoid weirdness on invalid ids
		$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
		if (!$recordModel || !$recordModel->getId()) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
	}

	/**
	 * Function to get the record model based on the request parameters
	 * @param Vtiger_Request $request
	 * @return Vtiger_Record_Model or Module specific Record Model instance
	 */
	protected function getRecordModelFromRequest(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$recordId = $request->get('record');
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
		
		// none admin users are not allowed to create users for the case checkPermission{} was bypassed
		$isEdit = preg_match('/^\d+$/', (string)$recordId) && (int)$recordId > 0;

		if (!$isEdit && !$currentUserModel->isAdminUser()) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}

		if (preg_match('/^\d+$/', (string)$recordId) && (int)$recordId > 0) {
			$recordId = (int)$recordId;
			$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
			$modelData = $recordModel->getData();
			$recordModel->set('id', $recordId);
			$sharedType = $request->get('sharedtype');
			if(!empty($sharedType))
				$recordModel->set('calendarsharedtype', $request->get('sharedtype'));
			$recordModel->set('mode', 'edit');
		} 
		else {
			$recordId = null;
			$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
			$modelData = $recordModel->getData();
			$recordModel->set('mode', '');
		}

		foreach ($modelData as $fieldName => $value) {
			$requestFieldExists = $request->has($fieldName);
			if(!$requestFieldExists){
				continue;
			}
			$fieldValue = $request->get($fieldName, null);

			if ($fieldName === 'is_admin') {
				if (!$currentUserModel->isAdminUser()) {
					$fieldValue = 'off';
					$recordModel->set('is_owner', 0); // or keep existing
				} 
				else {
					$fieldValue = ($fieldValue === 'on') ? 'on' : 'off';
					$recordModel->set('is_owner', ($fieldValue === 'on') ? 1 : 0);
				}
			}
			if($fieldValue !== null) {
				if(!is_array($fieldValue)) {
					$fieldValue = trim($fieldValue);
				}
				$recordModel->set($fieldName, $fieldValue);
			}
		}
		$homePageComponents = $recordModel->getHomePageComponents();
		$selectedHomePageComponents = $request->get('homepage_components', array());
		foreach ($homePageComponents as $key => $value) {
			if(in_array($key, $selectedHomePageComponents)) {
				$request->setGlobal($key, $key);
			} else {
				$request->setGlobal($key, '');
			}
		}

		// Tag cloud save
		$tagCloud = $request->get('tagcloudview');
		if($tagCloud == "on") {
			$recordModel->set('tagcloud', 0);
		} else {
			$recordModel->set('tagcloud', 1);
		}
		return $recordModel;
	}

	public function process(Vtiger_Request $request) {
		$result = Vtiger_Util_Helper::transformUploadedFiles($_FILES, true);
		$_FILES = $result['imagename'];

		$recordModel = $this->saveRecord($request);

		if ($request->get('relationOperation')) {
			$parentRecordModel = Vtiger_Record_Model::getInstanceById($request->get('sourceRecord'), $request->get('sourceModule'));
			$loadUrl = $parentRecordModel->getDetailViewUrl();
		} else if ($request->get('isPreference')) {
			$loadUrl =  $recordModel->getPreferenceDetailViewUrl();
		} else {
			$loadUrl = $recordModel->getDetailViewUrl();
		}

		header("Location: $loadUrl");
	}
}

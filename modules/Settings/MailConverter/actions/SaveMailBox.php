<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class Settings_MailConverter_SaveMailBox_Action extends Settings_Vtiger_Index_Action {

	public function process(Vtiger_Request $request) {
		$recordId = $request->get('record');
		$qualifiedModuleName = $request->getModule(false);

		if ($recordId) {
			$recordModel = Settings_MailConverter_Record_Model::getInstanceById($recordId);
		} else {
			$recordModel = Settings_MailConverter_Record_Model::getCleanInstance();
		}

		$recordModel->set('scannerOldName', $request->get('scannerOldName'));
		$fieldsList = $recordModel->getModule()->getFields();
		foreach ($fieldsList as $fieldName=>$fieldModel) {
			$recordModel->set($fieldName, $request->get($fieldName));
		}

		$status = $recordModel->save();

		$response = new Vtiger_Response();
		if ($status == "connectedToMailBoxOK") { 
			$result = array('message' => vtranslate('LBL_SAVED_SUCCESSFULLY', $qualifiedModuleName));
			$result['id'] = $recordModel->getId();
			$result['listViewUrl'] = $recordModel->getListUrl();
			$response->setResult($result);
		} 
		else if($status == "scannerNameNotValide") {
			$response->setError(vtranslate('LBL_ERROR_SCANNER_NAME', $qualifiedModuleName));
		}
		else if($status == "serverNameNotValide") {
			$response->setError(vtranslate('LBL_ERROR_SERVER_NAME', $qualifiedModuleName));
		}
		else if($status == "notMailBoxConnect") {
			$response->setError(vtranslate('LBL_ERROR_CONNECTION_TO_MAILBOX', $qualifiedModuleName));
		}
		else if($status == "thisReturnValueShouldNotAppearHere") {
			$response->setError(vtranslate('LBL_ERROR_NOT_REACHEBLE_RETURN_TOUCHED', $qualifiedModuleName));
		}
		else {
			$response->setError(vtranslate('LBL_ERROR_NOT_EXPECTED_RETURN_VALUE', $qualifiedModuleName));
		}
		$response->emit();
	}
        
        public function validateRequest(Vtiger_Request $request) { 
            $request->validateWriteAccess(); 
        }
}
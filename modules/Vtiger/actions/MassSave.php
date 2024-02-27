<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Vtiger_MassSave_Action extends Vtiger_Mass_Action {

	function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		$currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();

		if(!$currentUserPriviligesModel->hasModuleActionPermission($moduleModel->getId(), 'Save')) {
			throw new AppException(vtranslate($moduleName).' '.vtranslate('LBL_NOT_ACCESSIBLE'));
		}
	}

	public function process(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		$recordModels = $this->getRecordModelsFromRequest($request);
        $allRecordSave= true;
		foreach($recordModels as $recordId => $recordModel) {
			if(Users_Privileges_Model::isPermitted($moduleName, 'Save', $recordId)) {
				$recordModel->save();
			}
            else {
                $allRecordSave= false;
            }
		}
        
        $response = new Vtiger_Response();
        if($allRecordSave) {
           $response->setResult(true);
        } 
		else {
           $response->setResult(false);
        }
   	$response->emit();
	}

	/**
	 * Function to get the record model based on the request parameters
	 * @param Vtiger_Request $request
	 * @return Vtiger_Record_Model or Module specific Record Model instance
	 */
	function getRecordModelsFromRequest(Vtiger_Request $request) {

		$moduleName = $request->getModule();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		$recordIds = $this->getRecordsListFromRequest($request);
		$recordModels = array();

		$fieldModelList = $moduleModel->getFields();
		foreach($recordIds as $recordId) {
			$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleModel);
			$recordModel->set('id', $recordId);
			$recordModel->set('mode', 'edit');

			foreach ($fieldModelList as $fieldName => $fieldModel) {
				$fieldValue = $request->get($fieldName, null);
				$massDeleteUpdate = $request->get('mass_delete_check_'.$fieldName, null);
				$fieldDataType = $fieldModel->getFieldDataType();
				if($fieldDataType == 'time'){
					$fieldValue = Vtiger_Time_UIType::getTimeValueWithSeconds($fieldValue);
				}
				if(isset($fieldValue) && $fieldValue != null) {
					if(!is_array($fieldValue)) {
						$fieldValue = trim($fieldValue);
					}
					$recordModel->set($fieldName, $fieldValue);
				}
				else if (isset($massDeleteUpdate) && $massDeleteUpdate == 'on') {
                    $uiType = $fieldModel->get('uitype');
                    if ($uiType == 5 OR $uiType == 6 OR $uiType == 23) {
						//date field
						$recordModel->set($fieldName, Null);
					}
					else if ($uiType == 56) {
						//checkbox field
						$recordModel->set($fieldName, '0');
					}
					else if ($uiType == 15 OR $uiType == 33 OR $uiType == 1 OR $uiType == 11 OR $uiType == 12 OR $uiType == 13 OR $uiType == 69 OR $uiType == 53 OR $uiType == 7 OR $uiType == 83 OR $uiType == 72 OR $uiType == 52 OR $uiType == 75 OR $uiType == 17 OR $uiType == 51 OR $uiType == 13 OR $uiType == 21 OR $uiType == 19 OR $uiType == 13 OR $uiType == 57 OR $uiType == 71 OR $uiType == 9 OR $uiType == 58 OR $uiType == 59 OR $uiType == 23 OR $uiType == 16) {
						//picklist field, multi picklist field and any other field types
						$recordModel->set($fieldName, '');
					}
				}				
				else {
                    $uiType = $fieldModel->get('uitype');
                    if($uiType == 70) {
                        $recordModel->set($fieldName, $recordModel->get($fieldName));
                    }  
					else {
                        $uiTypeModel = $fieldModel->getUITypeModel();
                        $recordModel->set($fieldName, $uiTypeModel->getUserRequestValue($recordModel->get($fieldName)));
                    }
				}
			}
			$recordModels[$recordId] = $recordModel;
		}
		return $recordModels;
	}
}

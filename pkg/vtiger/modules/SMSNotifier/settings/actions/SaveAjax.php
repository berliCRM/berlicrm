<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Settings_SMSNotifier_SaveAjax_Action extends Settings_Vtiger_Index_Action {

	public function process(Vtiger_Request $request) {

		$recordId = $request->get('record');
		$qualifiedModuleName = $request->getModule(false);

		if ($recordId) {
			$recordModel = Settings_SMSNotifier_Record_Model::getInstanceById($recordId, $qualifiedModuleName);
			$recordModel->set('mode', 'edit');
			$recordModel->set('id', $recordId);
		} else {
			$recordModel = Settings_SMSNotifier_Record_Model::getCleanInstance($qualifiedModuleName);
		}
  		$config_data = $request->get('configfields');
		$one=explode("&",$config_data);
		$config_data_array = array();
		foreach ($one as $item){
			$sub_array = explode("=",$item);
			$config_data_array[$sub_array[0]] =urldecode($sub_array[1]);
		}

		$editableFields = $recordModel->getEditableFields();
		foreach ($editableFields as $fieldName => $fieldModel) {
			$recordModel->set($fieldName, $config_data_array[$fieldName]);
		}

		$parameters = array(); 
		$selectedProvider = $config_data_array['providertype'];
		
		$allProviders = $recordModel->getModule()->getAllProviders();
		foreach ($allProviders as $provider) {
			if ($provider->getName() === $selectedProvider) {
				$fieldsInfo = Settings_SMSNotifier_ProviderField_Model::getInstanceByProvider($provider); 
				foreach ($fieldsInfo as $fieldInfo) {
					$fieldcontents = trim(decode_html($config_data_array[$fieldInfo['name']]));
		        	$recordModel->set($fieldInfo['name'], $fieldcontents); 
 		            $parameters[$fieldInfo['name']] = $fieldcontents; 
		        }
				$test = json_encode((object)$parameters);
		        $recordModel->set('parameters', json_encode((object)$parameters));
 		        break;
			}
		}

		$response = new Vtiger_Response();
		try {
			$recordModel->save();
			$response->setResult(array(vtranslate('LBL_SAVED_SUCCESSFULLY', $qualifiedModuleName)));
		} 
		catch (Exception $e) {
			$response->setError($e->getMessage());
		}
		$response->emit();
	}
        
        public function validateRequest(Vtiger_Request $request) { 
            $request->validateWriteAccess(); 
        }
}
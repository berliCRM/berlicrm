<?php

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

class Settings_Vtiger_CompanyDetailsSave_Action extends Settings_Vtiger_Basic_Action {

	public function process(Vtiger_Request $request) {
		$qualifiedModuleName = $request->getModule(false);
		$moduleModel = Settings_Vtiger_CompanyDetails_Model::getInstance();
		$status = false;

        if ($request->get('organizationname')) {
			$saveLogo = $status = true;
			$binFileName = false;
			if(!empty($_FILES['logo']['name'])) {
				$logoDetails = $_FILES['logo'];
				$saveLogo = Vtiger_Functions::validateImage($logoDetails);
				if (is_string($saveLogo)) $saveLogo = ($saveLogo == 'false')? false : true;

				global $upload_badext;
				$binFileName = sanitizeUploadFileName($logoDetails['name'], $upload_badext);
                if ($saveLogo) {
                    $moduleModel->saveLogo($binFileName);
                }
            }else{
                $saveLogo = true;
            }
			$fields = $moduleModel->getFields();
			foreach ($fields as $fieldName => $fieldType) {
				$fieldValue = $request->get($fieldName);
				if ($fieldName === 'logoname') {
					if (!empty($logoDetails['name']) && $binFileName) {
						$fieldValue = ltrim(basename(" " . $binFileName));
					} else {
						$fieldValue = $moduleModel->get($fieldName);
					}
				}
				$moduleModel->set($fieldName, $fieldValue);
			}
			$moduleModel->save();
		}

		$reloadUrl = $moduleModel->getIndexViewUrl();
		if ($saveLogo && $status) {

		} else if (!$saveLogo) {
			$reloadUrl .= '&error=LBL_INVALID_IMAGE';
		} else {
			$reloadUrl = $moduleModel->getEditViewUrl() . '&error=LBL_FIELDS_INFO_IS_EMPTY';
		}
		header('Location: ' . $reloadUrl);
	}

        public function validateRequest(Vtiger_Request $request) { 
            $request->validateWriteAccess(); 
        } 
}

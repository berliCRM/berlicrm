<?php

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

class Settings_Vtiger_CompanyDetailsSaveLogo_Action extends Settings_Vtiger_Basic_Action {

	public function process(Vtiger_Request $request) {
		$qualifiedModuleName = $request->getModule(false);
		$moduleModel = Settings_Vtiger_CompanyDetails_Model::getInstance();
		$status = false;
		$logoid= 1;
		foreach ($_FILES as $key =>$file) {
            $saveLogo = $status = true;
			if(!empty($file['name'])) {
                $fileType = explode('/', $file['type']);
                $fileType = $fileType[1];

                if (!$file['size'] || ($key != "p4" && $fileType !='jpeg') || ($key == "p4" && $fileType != "png")) {
                    $saveLogo = false;
                }
				// Check for php code injection
				$imageContents = file_get_contents($file["tmp_name"]);
				if (preg_match('/(<\?php?(.*?))/i', $imageContents) == 1) {
					$saveLogo = false;
				}
                if ($saveLogo) {
                   $moduleModel->saveLoginLogo($file,$key);
                }
            }else{
                $saveLogo = true;
            }
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
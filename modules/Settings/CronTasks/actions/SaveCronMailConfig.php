<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

require_once 'modules/Settings/CronTasks/models/Config.php';

class Settings_CronTasks_SaveCronMailConfig_Action extends Settings_Vtiger_Index_Action {

	public function process(Vtiger_Request $request) {
		$qualifiedModuleName = $request->getModule(false);
		$senderEmail = preg_replace('/\s+/', '', trim((string)$request->get('sender_email')));
		$senderName = trim((string)$request->get('sender_name'));

		if ($senderEmail !== '' && !filter_var($senderEmail, FILTER_VALIDATE_EMAIL)) {
			throw new AppException(vtranslate('LBL_INVALID_EMAILID', $qualifiedModuleName));
		}

		if ($senderName !== '' && preg_match('/[\'";?><]/', $senderName)) {
			throw new AppException(vtranslate('LBL_INVALID_CRON_MAIL_SENDER_NAME', $qualifiedModuleName));
		}

		$model = Settings_CronTasks_Config_Model::getInstance();
		$model->save($senderEmail, $senderName);

		$redirectUrl = 'index.php?module=CronTasks&parent=Settings&view=List&cronMailSaved=1';
		$block = trim((string)$request->get('block'));
		$fieldId = trim((string)$request->get('fieldid'));
		if ($block !== '') {
			$redirectUrl .= '&block=' . (int)$block;
		}
		if ($fieldId !== '') {
			$redirectUrl .= '&fieldid=' . (int)$fieldId;
		}

		header('Location: ' . $redirectUrl);
		exit;
	}

	public function validateRequest(Vtiger_Request $request) {
		$request->validateWriteAccess();
	}
}

<?php
/*+**********************************************************************************
 * berliCRM / vtiger style Settings Action
 ************************************************************************************/

require_once 'modules/Settings/Vtiger/models/ConfigTicketEmailAddress.php';
require_once 'modules/Settings/Vtiger/models/ConfigModule.php';

class Settings_Vtiger_SaveConfigTicketMail_Action extends Settings_Vtiger_Index_Action {

	public function process(Vtiger_Request $request) {

		// Optional enable flag (depending on your tpl)
		$enabled = (int)$request->get('enabled');

		// Read form fields (keep them as-is, trim basic whitespace)
		$senderName   = trim((string)$request->get('sender_name'));
		$senderEmail  = trim((string)$request->get('sender_email'));
		$replyToName  = trim((string)$request->get('reply_to_name'));
		$replyToEmail = trim((string)$request->get('reply_to_email'));

		// Basic normalization (avoid accidental spaces/newlines)
		$senderEmail  = preg_replace('/\s+/', '', $senderEmail);
		$replyToEmail = preg_replace('/\s+/', '', $replyToEmail);

		// Optional: if reply-to not set, default to sender
		if ($replyToEmail === '') {
			$replyToEmail = $senderEmail;
		}
		if ($replyToName === '') {
			$replyToName = $senderName;
		}

		// Optional: basic email validation (non-blocking; adjust if you want to hard-fail)
		// if ($senderEmail !== '' && !filter_var($senderEmail, FILTER_VALIDATE_EMAIL)) { ... }
		// if ($replyToEmail !== '' && !filter_var($replyToEmail, FILTER_VALIDATE_EMAIL)) { ... }

		$model = Settings_Vtiger_ConfigTicketEmailAddress::getInstance();
		$model->save($enabled, $senderEmail, $senderName, $replyToEmail, $replyToName);

		// Build proper return URL using ConfigModule model
		$configModuleModel = Settings_Vtiger_ConfigModule_Model::getInstance();
		$menuItem = $configModuleModel->getMenuItem();

		$redirectUrl = 'index.php?module=Vtiger&parent=Settings&view=ConfigEditorDetail'
			. '&block=' . $menuItem->get('blockid')
			. '&fieldid=' . $menuItem->get('fieldid');

		header('Location: ' . $redirectUrl);
		exit;
	}

	public function validateRequest(Vtiger_Request $request) {
		// If your berliCRM doesn’t have validateWriteAccess(), remove this method.
		$request->validateWriteAccess();
	}
}
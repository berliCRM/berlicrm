<?php
class MailManager_MailReminder_Action extends Vtiger_Action_Controller{

	function __construct() {
		$this->exposeMethod('checkForNewMails');
	}

	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

		$userPrivilegesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		$permission = $userPrivilegesModel->hasModulePermission($moduleModel->getId());

		if(!$permission) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
	}

	public function process(Vtiger_Request $request) {
		$mode = $request->getMode();
		if(!empty($mode) && $this->isMethodExposed($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}

	}

	function checkForNewMails(Vtiger_Request $request) {
		//disable session writes as IMAP connections could have a very long timeout and this would block ALL further request through index.php
		session_write_close();
		$mailBox = MailManager_Mailbox_Model::activeInstance();
		$connector = MailManager_Connector_Connector::connectorWithModel($mailBox);
		if (!$connector->isConnected() || $connector->hasError()) {
			throw new AppException('No Box');
		}
		
		$records = $connector->getNewMailsCount();
		if (!empty($records)) {
			$mail = ($records > 1) ? vtranslate('Emails') : vtranslate('SINGLE_Emails');
			$records = sprintf(vJSTranslate('JS_MM_TEXT'), $records, $mail);
		}

		$response = new Vtiger_Response();
		$response->setResult($records);
		$response->emit();
	}
}
<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Documents_DownloadFile_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();

		if(!Users_Privileges_Model::isPermitted($moduleName, 'DetailView', $request->get('record'))) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED', $moduleName));
		}
	}

	public function process(Vtiger_Request $request) {
		$moduleName = $request->getModule();

		$documentRecordModel = Vtiger_Record_Model::getInstanceById($request->get('record'), $moduleName);

		$mode = $request->get('mode');
		if ($documentRecordModel->get('filelocationtype') !== 'I'
				|| !$documentRecordModel->isDownloadAvailable()) {
			$this->emitFileNotAvailable($moduleName);
			return;
		}

		if ($mode === 'preview') {
			//Preview the File
			$fileServed = $documentRecordModel->previewFile();
			if (!$fileServed) {
				$this->emitFileNotAvailable($moduleName);
			}
			return;
		} else {
			//Download the file
			$fileServed = $documentRecordModel->downloadFile();
		}

		if ($fileServed) {
			//Update the Download Count only after the file was opened successfully.
			$documentRecordModel->updateDownloadCount();
		} else {
			$this->emitFileNotAvailable($moduleName);
		}
	}

	protected function emitFileNotAvailable($moduleName) {
		if (!headers_sent()) {
			http_response_code(404);
			header('Content-Type: text/plain; charset=UTF-8');
		}
		echo vtranslate('LBL_FILE_NOT_AVAILABLE', $moduleName);
	}
}
?>

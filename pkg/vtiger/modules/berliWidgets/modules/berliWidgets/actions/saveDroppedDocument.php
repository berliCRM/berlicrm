<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class berliWidgets_saveDroppedDocument_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();

		if(!Users_Privileges_Model::isPermitted($moduleName, 'DetailView', $request->get('record'))) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED', $moduleName));
		}
	}

	public function process(Vtiger_Request $request) {
		global $currentModule;
		$db = PearDatabase::getInstance();
		$current_user = Users_Record_Model::getCurrentUserModel();
		$parentid = $request->get('recordid');
		$action = $request->get('action');
		$type = $request->get('type');
		
		if(!$this->checkModuleWriteAccessForCurrentUser('Documents')) {
			$errorMessage = getTranslatedString('LBL_WRITE_ACCESS_FOR', $currentModule)." ".getTranslatedString('Documents')." ".getTranslatedString('LBL_MODULE_DENIED', $currentModule);
			$result = array('success'=>false, 'error'=>$errorMessage);
			$response = new Vtiger_Response();
			$response->setResult($result);
			$response->emit();
			exit;
		}
		if (empty($_FILES)) {
			$errorMessage = getTranslatedString('LBL_PROBLEM_UPLOAD', $currentModule);
			$result = array('success'=>false, 'error'=>$errorMessage);
			$response = new Vtiger_Response();
			$response->setResult($result);
			$response->emit();
			return;
		}
		
		foreach($_FILES as $fileindex => $files) {
			$fileerror = $files['error'];
			$upload_error = '';
			if($fileerror == 4) {
				$upload_error = getTranslatedString('LBL_GIVE_VALID_FILE');
			}
			elseif($fileerror == 2) {
				$upload_error = getTranslatedString('LBL_UPLOAD_FILE_LARGE');
			}
			elseif($fileerror == 3) {
				$upload_error = getTranslatedString('LBL_PROBLEM_UPLOAD');
			}
			if($files['name'] != '' && $files['size'] > 0 && $upload_error == '') {
				$files['original_name'] = vtlib_purify($files['name']);
				$filesize = $files['size'];
				$filetype = $files['type'];

				//sanitize the file name
				$binFile = sanitizeUploadFileName($files['name'], vglobal('upload_badext'));
				$fileName = ltrim(basename(" ".$binFile));
				$folderid = 1;
				
				$documentRecordModel = Vtiger_Record_Model::getCleanInstance('Documents');
				$documentRecordModel->set('label', $fileName);
				$documentRecordModel->set('notes_title', $fileName);
				$documentRecordModel->set('filename', $fileName);
				$documentRecordModel->set('filetype', $filetype);
				$documentRecordModel->set('filestatus', 1);
				$documentRecordModel->set('filelocationtype', 'I');
				$documentRecordModel->set('folderid', $folderid);
				$documentRecordModel->set('filesize', $filesize);
				$documentRecordModel->set('assigned_user_id', $current_user->getId());
				$documentRecordModel->save();
				// Link document to entity
				$db->pquery("INSERT INTO vtiger_senotesrel(crmid, notesid) VALUES(?,?)", array($parentid, $documentRecordModel->getId()));
				
				$result =  array('success'=>true, 'docid'=>$documentRecordModel->getId());

			}
			else {
				if ($upload_error !='') {
					$result = array('success'=>false, 'error'=>$upload_error);
				}
				else {
					$result = array('success'=>false, 'error'=>'unknown error');
				}
			}
			$response = new Vtiger_Response();
			$response->setResult($result);
			$response->emit();
		}
	}
	
	static function checkModuleWriteAccessForCurrentUser($module) {
		global $current_user;
		if (isPermitted($module, 'CreateView') == "yes" && vtlib_isModuleActive($module)) {
            return true;
		}
		return false;
	}

}
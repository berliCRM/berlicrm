<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

include_once 'modules/MailManager/third-party/AjaxUpload/ajaxUpload.php';
require_once 'modules/MailManager/MailManager.php';

class MailManager_UploadFileXHR extends qqUploadedFileXhr {

	/**
	 * Create a Document
	 * @global Users $current_user
	 * @global PearDataBase $db
	 * @return array
	 */
	public function createDocument() {
		$db = PearDatabase::getInstance();
		$currentUserModel = Users_Record_Model::getCurrentUserModel();

		if(!MailManager::checkModuleWriteAccessForCurrentUser('Documents')) {
			$errorMessage = getTranslatedString('LBL_WRITE_ACCESS_FOR', $currentModule)." ".getTranslatedString('Documents')." ".getTranslatedString('LBL_MODULE_DENIED', $currentModule);
			return array('success'=>true, 'error'=>$errorMessage);
		}
		require_once 'data/CRMEntity.php';
		$document = CRMEntity::getInstance('Documents');

		$attachid = $this->saveAttachment();

		if($attachid !== false) {
			// Create document record
			$document = new Documents();
			$document->column_fields['notes_title']      = $this->getName() ;
			$document->column_fields['filename']         = $this->getName();
			$document->column_fields['filestatus']       = 1;
			$document->column_fields['filelocationtype'] = 'I';
			$document->column_fields['folderid']         = 1;
			$document->column_fields['filesize']		 = $this->getSize();
			$document->column_fields['assigned_user_id'] = $currentUserModel->getId();
			$document->save('Documents');

			// Link file attached to document
			$db->pquery("INSERT INTO vtiger_seattachmentsrel(crmid, attachmentsid) VALUES(?,?)",
					Array($document->id, $attachid));

			return array('success'=>true, 'docid'=>$document->id, 'attachid'=>$attachid);
		}
		return false;
	}

	/**
	 * Save an attachment
	 * @global PearDataBase $db
	 * @global Array $upload_badext
	 * @global Users $current_user
	 * @return Integer
	 */
	public function saveAttachment() {
		$db = PearDatabase::getInstance();
		$currentUserModel = Users_Record_Model::getCurrentUserModel();

		$uploadPath = decideFilePath();
		$fileName = $this->getName();
		if(!empty($fileName)) {
			$attachid = $db->getUniqueId('vtiger_crmentity');

			//sanitize the filename
			$binFile = sanitizeUploadFileName($fileName, vglobal('upload_badext'));
			$fileName = ltrim(basename(" ".$binFile));

			$saveAttchment = $this->save($uploadPath.$attachid."_".$fileName);
			if($saveAttchment) {
				$description = $fileName;
				$date_var = $db->formatDate(date('YmdHis'), true);
				$usetime = $db->formatDate($date_var, true);

				$db->pquery("INSERT INTO vtiger_crmentity(crmid, smcreatorid, smownerid,
				modifiedby, setype, description, createdtime, modifiedtime, presence, deleted)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
						Array($attachid, $currentUserModel->getId(), $currentUserModel->getId(), $currentUserModel->getId(), "Documents Attachment", $description, $usetime, $usetime, 1, 0));

				$mimetype = MailAttachmentMIME::detect($uploadPath.$attachid."_".$fileName);
				$db->pquery("INSERT INTO vtiger_attachments SET attachmentsid=?, name=?, description=?, type=?, path=?",
						Array($attachid, $fileName, $description, $mimetype, $uploadPath));

				return $attachid;
			}
		}
		return false;
	}

	/**
	 * Function used to Create Document and Attachments
	 */
	public function process() {
		return $this->createDocument();
	}
}

/**
 * Class used to Upload file using Form, used to IE
 */
class MailManager_UploadFileForm extends qqUploadedFileForm {

	/**
	 * Saves the uploaded file
	 * @global String $root_directory
	 * @param String $path
	 * @return Boolean
	 */
	public function save($path) {
		global $root_directory;
		if(is_file($root_directory."/".$path)) {
			return true;
		} else if(move_uploaded_file($_FILES['qqfile']['tmp_name'], $path)) {
			return true;
		}
		return false;
	}

	/**
	 * Function used to Create Document and Attachments
	 */
	public function process() {
		return $this->createDocument();
	}

	/**
	 * Used to create Documents
	 * @global Users $current_user
	 * @global PearDataBase $db
	 * @global String $currentModule
	 */
	public function createDocument() {
		$db = PearDatabase::getInstance();
		$currentUserModel = Users_Record_Model::getCurrentUserModel();

		if(!MailManager::checkModuleWriteAccessForCurrentUser('Documents')) {
			$errorMessage = getTranslatedString('LBL_WRITE_ACCESS_FOR', 'MailManager')." ".getTranslatedString('Documents')." ".getTranslatedString('LBL_MODULE_DENIED', 'MailManager');
			return array('success'=>true, 'error'=>$errorMessage);
		}
		require_once 'data/CRMEntity.php';
		$document = CRMEntity::getInstance('Documents');

		$attachid = $this->saveAttachment();

		if($attachid !== false) {
			// Create document record
			$document = new Documents();
			$document->column_fields['notes_title']      = $this->getName() ;
			$document->column_fields['filename']         = $this->getName();
			$document->column_fields['filestatus']       = 1;
			$document->column_fields['filelocationtype'] = 'I';
			$document->column_fields['folderid']         = 1;
			$document->column_fields['filesize']		 = $this->getSize();
			$document->column_fields['assigned_user_id'] = $currentUserModel->getId();
			$document->save('Documents');

			// Link file attached to document
			$db->pquery("INSERT INTO vtiger_seattachmentsrel(crmid, attachmentsid) VALUES(?,?)",
					Array($document->id, $attachid));

			return array('success'=>true, 'docid'=>$document->id, 'attachid'=>$attachid);
		}
		return false;
	}

	/**
	 * Creates an Attachments
	 * @global PearDataBase $db
	 * @global Array $upload_badext
	 * @global Users $current_user
	 */
	public function saveAttachment() {
		$db = PearDatabase::getInstance();
		$currentUserModel = Users_Record_Model::getCurrentUserModel();

		$uploadPath = decideFilePath();
		$fileName = $this->getName();
		if(!empty($fileName)) {
			$attachid = $db->getUniqueId('vtiger_crmentity');

			//sanitize the filename
			$binFile = sanitizeUploadFileName($fileName, vglobal('upload_badext'));
			$fileName = ltrim(basename(" ".$binFile));

			$saveAttachment = $this->save($uploadPath.$attachid."_".$fileName);
			if($saveAttachment) {
				$description = $fileName;
				$date_var = $db->formatDate(date('YmdHis'), true);
				$usetime = $db->formatDate($date_var, true);

				$db->pquery("INSERT INTO vtiger_crmentity(crmid, smcreatorid, smownerid,
				modifiedby, setype, description, createdtime, modifiedtime, presence, deleted)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
						Array($attachid, $currentUserModel->getId(), $currentUserModel->getId(), $currentUserModel->getId(), "Documents Attachment", $description, $usetime, $usetime, 1, 0));

				$mimetype = MailAttachmentMIME::detect($uploadPath.$attachid."_".$fileName);

				$db->pquery("INSERT INTO vtiger_attachments SET attachmentsid=?, name=?, description=?, type=?, path=?",
						Array($attachid, $fileName, $description, $mimetype, $uploadPath));

				return $attachid;
			}
		}
		return false;
	}
}

/**
 * Class used to control Uploading files
 */
class MailManager_Upload_Action extends qqFileUploader {

	/**
	 * Constructor used to invoke the Uploading Handler
	 * @param Array $allowedExtensions
	 * @param Integer $sizeLimit
	 */
	public function __construct($allowedExtensions, $sizeLimit) {

		$this->setAllowedFileExtension($allowedExtensions);

		$this->setMaxUploadSize($sizeLimit);

		if (isset($_GET['qqfile'])) {
			$this->file = new MailManager_UploadFileXHR();
		} elseif (isset($_FILES['qqfile'])) {
			$this->file = new MailManager_UploadFileForm();
		} else {
			$this->file = false;
		}
	}

	/**
	 * Function used to handle the upload
	 * @param String $uploadDirectory
	 * @param Boolean $replaceOldFile
	 * @return Array
	 */
	public function handleUpload($uploadDirectory, $replaceOldFile = FALSE) {
		if(!isPermitted('Documents', 'EditView')) {
			return array('error' => "Permission not available");
		}

		if (!is_writable($uploadDirectory)) {
			return array('error' => "Server error. Upload directory isn't writable.");
		}

		if (!$this->file) {
			return array('error' => 'No files were uploaded.');
		}

		$size = $this->file->getSize();
		if ($size == 0) {
			return array('error' => 'File is empty');
		}

		if ($size > $this->sizeLimit) {
			return array('error' => 'File is too large');
		}

		$pathinfo = pathinfo($this->file->getName());
		$filename = $pathinfo['filename'];
		$ext = $pathinfo['extension'];

		if($this->allowedExtensions && !in_array(strtolower($ext), $this->allowedExtensions)) {
			$these = implode(', ', $this->allowedExtensions);
			return array('error' => 'File has an invalid extension, it should be one of '. $these . '.');
		}

		$response = $this->file->process();
		if ($response['success'] == true) {
			return $response;
		} else {
			return array('error'=> 'Could not save uploaded file. The upload was cancelled, or server error encountered');
		}

	}

	/*
	 * get the max file upload sizr
	*/
	public function getMaxUploadSize() {
		return $this->sizeLimit;
	}

	/*
	 * Sets the max file upload size
	*/
	public function setMaxUploadSize($value) {
		$this->sizeLimit = $value;
	}

	/*
	 * gets the allowed file extension
	*/
	public function getAllowedFileExtension() {
		return $this->allowedExtensions;
	}

	/*
	 * sets the allowed file extension
	*/
	public function setAllowedFileExtension($values) {
		if(!empty($values)) {
			$this->allowedExtensions = $values;
		}
	}
}
?>
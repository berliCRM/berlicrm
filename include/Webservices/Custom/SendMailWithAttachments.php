<?php
/************************************************************************************************************************************************************ 
Description: send mail, body from template, attachments from Documents
  Parameters:
    - encoded receiverIds: list of Webservices IDs that will receive the mail (JSON)
	- string templateId: internal (no Webservices) ID of EmailTemplates module that will provide email contents
	- encoded documentIds: list of Webservices IDs of Documents that will be attached to the mail (JSON)
	- string mailFieldToUse: fieldname that will be primarily used for receiving email address
	- string fromId: Webservices ID of User that sends the email (not used yet)
	- string fromName: name that will be displayed for sender (not used yet)
	
  Returns:
    - array($receiverId => status)
Copyright (C) crm-now GmbH
All Rights Reserved.
************************************************************************************************************************************************************ */

function berli_sendMailWithAttachments($receiverIds, $templateId, $documentIds = '', $mailFieldToUse = '', $fromId = '', $fromName = '', $user) {
	$adb = PearDatabase::getInstance();
	$types = vtws_listtypes(NULL, $user);
	$returnArr = array();
	// check template
	if (empty($templateId)){
		throw new WebServiceException(WebServiceErrorCode::$MANDFIELDSMISSING, "Template ID must not be empty");
	}
	
	$query = 'SELECT vtiger_emailtemplates.subject, vtiger_emailtemplates.body FROM vtiger_emailtemplates WHERE vtiger_emailtemplates.`templateid` = ?;';
	$result = $adb->pquery($query, array($templateId));
	$templateRow = $adb->getNextRow($result, false);
	if (empty($templateRow)) {
		throw new WebServiceException(WebServiceErrorCode::$MANDFIELDSMISSING, "Missing email template for ID '$templateId'");
	}
	// check attachments
	$attachmentIds = array();
	if (!empty($documentIds)) {
		$moduleHandler = vtws_getModuleHandlerFromName('Documents', $user);
		$meta = $moduleHandler->getMeta();
		if (!in_array('Documents', $types['types']) || $meta->hasReadAccess() !== true) {
			throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, "Module 'Documents' is not accessible");
		}
		foreach ($documentIds AS $documentId) {
			$idComponents = vtws_getIdComponents($documentId);
			if (!$meta->hasPermission(EntityMeta::$RETRIEVE, $documentId) || !$meta->exists($idComponents[1])) {
				throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, "'$documentId' cannot be read or found");
			}
			$attachmentIds[] = $idComponents[1];
		}
	}
	
	// check receivers
	if (empty($receiverIds)){
		throw new WebServiceException(WebServiceErrorCode::$MANDFIELDSMISSING, "Receiver IDs must not be empty");
	}
	
	foreach ($receiverIds AS $receiverId) {
		$moduleHandler = vtws_getModuleHandlerFromId($receiverId, $user);
		$meta = $moduleHandler->getMeta();
		$entityName = $meta->getEntityName();
		
		$idComponents = vtws_getIdComponents($receiverId);
		
		if (!in_array($entityName, $types['types'])) {
			$returnArr[$receiverId] = "Module '$entityName' is not accessible";
		} elseif ($meta->hasReadAccess() !== true) {
			$returnArr[$receiverId] = "Permission to read '$entityName' denied";
		} elseif (!$meta->hasPermission(EntityMeta::$RETRIEVE, $receiverId)) {
			$returnArr[$receiverId] = 'Permission to read denied for record';
		} elseif (!$meta->exists($idComponents[1])) {
			$returnArr[$receiverId] = 'Record not found';
		} else {
			// determine email field
			if (empty($mailFields[$entityName])) {
				require_once('include/Webservices/DescribeObject.php');
				$describe = vtws_describe($entityName, $user);
				foreach ($describe['fields'] AS $field) {
					if (isset($field['type']) && isset($field['type']['name']) && $field['type']['name'] == 'email') {
						$mailFields[$entityName][] = $field['name'];
					}
				}
			}
			$recordModel = Vtiger_Record_Model::getInstanceById($idComponents[1], $entityName);
			$eMail = $recordModel->get($mailFieldToUse);
			if (empty($eMail)) {
				foreach ($mailFields[$entityName] AS $mailField) {
					$value = $recordModel->get($mailField);
					if (!empty($value)) {
						$eMail = $value;
						break;
					}
				}
			}
			
			$body = Vtiger_Functions::getMergedDescription($templateRow['body'], $idComponents[1], $entityName);
			$body = Vtiger_Functions::getMergedDescription($body, $idComponents[1], 'Users');
			
			$emailsRecordModel = Vtiger_Record_Model::getCleanInstance('Emails');
			$emailsRecordModel->set('subject', $templateRow['subject']);
			$emailsRecordModel->set('description', $body);
			$emailsRecordModel->set('email_flag', 'SENT');
			$emailsRecordModel->set('parent_type', $entityName);
			$emailsRecordModel->set('assigned_user_id', $user->id);
			$emailsRecordModel->set('parent_id', $idComponents[1]."@-1|");
			$emailsRecordModel->set('documentids', $attachmentIds);
			$emailsRecordModel->set('saved_toid', $eMail);
			try {
				$emailsRecordModel->save();
				$status = $emailsRecordModel->send();
				if ($status == true) {
					$status = "SUCCESS: sent to $eMail";
				} else {
					$emailsRecordModel->delete();
				}
			} catch (Exception $e) {
				$status = $e->getMessage();
				if (empty($status)) {
					$status = "Unknown error while sending mail to '$eMail'";
				}
			}
			$returnArr[$receiverId] = $status;
		}
		set_time_limit(0);
	}
	return $returnArr;
}
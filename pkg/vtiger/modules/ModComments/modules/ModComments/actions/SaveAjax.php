<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class ModComments_SaveAjax_Action extends Vtiger_SaveAjax_Action
{

	public function checkPermission(Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$record = $request->get('record');
		//Do not allow ajax edit of existing comments
		if ($record) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
	}

	public function process(Vtiger_Request $request)
	{
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$request->set('assigned_user_id', $currentUserModel->getId());
		$request->set('userid', $currentUserModel->getId());
		$request->set('username', $currentUserModel->getName());
		$mailTo = '';
		try {
			$recordModel = $this->saveRecord($request);
			if ($request->get('sendMail')) {
				$mailTo = $this->sendMail($request, $recordModel);
			}
			$this->saveModcommentsScope($request, $recordModel, $mailTo);
		} catch (\Throwable $th) {
			file_put_contents('test/0debug.txt', "Error: " . var_export($th, true) . "\n\n", FILE_APPEND);
		}

		$fieldModelList = $recordModel->getModule()->getFields();
		$result = array();
		foreach ($fieldModelList as $fieldName => $fieldModel) {
			$fieldValue = $recordModel->get($fieldName);
			$result[$fieldName] = array('value' => $fieldValue, 'display_value' => $fieldModel->getDisplayValue($fieldValue));
		}
		$result['id'] = $recordModel->getId();

		$result['_recordLabel'] = $recordModel->getName();
		$result['_recordId'] = $recordModel->getId();

		$response = new Vtiger_Response();
		$response->setEmitType(Vtiger_Response::$EMIT_JSON);
		$response->setResult($result);
		$response->emit();
	}

	/**
	 * Function to save record
	 * @param <Vtiger_Request> $request - values of the record
	 * @return <RecordModel> - record Model of saved record
	 */
	public function saveRecord($request)
	{
		$recordModel = $this->getRecordModelFromRequest($request);

		$recordModel->save();
		if ($request->get('relationOperation')) {
			$parentModuleName = $request->get('sourceModule');
			$parentModuleModel = Vtiger_Module_Model::getInstance($parentModuleName);
			$parentRecordId = $request->get('sourceRecord');
			$relatedModule = $recordModel->getModule();
			$relatedRecordId = $recordModel->getId();

			$relationModel = Vtiger_Relation_Model::getInstance($parentModuleModel, $relatedModule);
			$relationModel->addRelation($parentRecordId, $relatedRecordId);
		}
		return $recordModel;
	}

	/**
	 * Function to get the record model based on the request parameters
	 * @param Vtiger_Request $request
	 * @return Vtiger_Record_Model or Module specific Record Model instance
	 */
	public function getRecordModelFromRequest(Vtiger_Request $request)
	{
		$recordModel = parent::getRecordModelFromRequest($request);

		$recordModel->set('commentcontent', $request->getRaw('commentcontent'));

		return $recordModel;
	}

	public function sendMail(Vtiger_Request $request, Vtiger_Record_Model $recordModel)
	{
		global $site_URL, $HELPDESK_SUPPORT_EMAIL_ID;
		$email = '';
		$relatedId = $recordModel->get('related_to');
		$relatedRecordModel = Vtiger_Record_Model::getInstanceById($relatedId);
		$parent_type = '';
		$parent_id = '';

		if (empty($email) && !empty($relatedRecordModel->get('contact_id')) && $relatedRecordModel->get('contact_id') != '0') {
			$contactModel = Vtiger_Record_Model::getInstanceById($relatedRecordModel->get('contact_id'));
			$email = $contactModel->get('email');
			$parent_type = 'Contacts';
			$parent_id = $relatedRecordModel->get('contact_id');
		}

		if (empty($email)) {
			$accountModel = Vtiger_Record_Model::getInstanceById($relatedRecordModel->get('parent_id'));
			$email = $accountModel->get('email1');
			$parent_type = 'Accounts';
			$parent_id = $relatedRecordModel->get('parent_id');
		}

		$subject = $relatedRecordModel->get('ticket_no') . ' [ Ticket Id : ' . $relatedRecordModel->getId() . ' ] ' . $relatedRecordModel->getName();

		$contents = '<h4>Ihr Vorgang hat einen neuen Kommentar / Your ticket has a new comment:</h4>';
		$contents .= nl2br($recordModel->get('commentcontent'));
		$contents .= '<br><br>----------------------------------------------------------------------------------------------------';

		$contents .= '<h4>Ticket Details</h4>';
		$contents .= '<b>Ticket ID:</b> ' . $recordModel->getId() . '<br>';
		$contents .= '<b>Betreff / Subject:</b> ' . $relatedRecordModel->getName() . '<br>';
		$contents .= '<b>Ticket Nr:</b> ' . $relatedRecordModel->get('ticket_no') . '<br>';
		$contents .= '<b>Status:</b> ' . $relatedRecordModel->get('ticketstatus') . '<br>';
		$contents .= '<b>Description / Beschreibung:</b><br>' . nl2br($relatedRecordModel->get('description')) . '<br>';

		$to = $email;
		if(is_array($to)) {
			$to = implode(',',$to);
		}

		$emailsRecordModel = Vtiger_Record_Model::getCleanInstance('Emails');
		$emailsRecordModel->set('subject', html_entity_decode($subject));
		$emailsRecordModel->set('description', $contents);
		$emailsRecordModel->set('email_flag', 'SENT');
		$emailsRecordModel->set('assigned_user_id', Users_Record_Model::getCurrentUserModel()->getId());
		$emailsRecordModel->set('parent_id', $relatedId . '@1|' . $parent_id . '@1|');
		$emailsRecordModel->set('toemailinfo', array($relatedId => array($email)));
		$emailsRecordModel->set('toMailNamesList', array($relatedId => array(array('label' => $name, 'value' => $email))));
		$emailsRecordModel->set('saved_toid', $to);
		$emailsRecordModel->set('from_email', $HELPDESK_SUPPORT_EMAIL_ID);
		$emailsRecordModel->fromAddress = $HELPDESK_SUPPORT_EMAIL_ID;
		$emailsRecordModel->save();

		$response = $emailsRecordModel->send();
		if ($response === true) {
			// This is needed to set vtiger_email_track table as it is used in email reporting
			$emailsRecordModel->setAccessCountValue();
		} else {
			$emailsRecordModel->set('email_flag', 'FAILED');
			$emailsRecordModel->set('mode', 'edit');
			$emailsRecordModel->save();
		}

		return $response ? $email : $response;
	}

	public function saveModcommentsScope(Vtiger_Request $request, Vtiger_Record_Model $recordModel, $mailTo)
	{
		$adb = PearDatabase::getInstance();
		$external = json_decode($request->get('external'));

		if ($mailTo !== '') {
			$external = 1;
		}

		$query = "INSERT INTO vtiger_modcommentsscope (modcommentsid, mailto, external) VALUES (?, ?, ?)";
		$result = $adb->pquery($query, array($recordModel->getId(), $mailTo, $external));
	}
}

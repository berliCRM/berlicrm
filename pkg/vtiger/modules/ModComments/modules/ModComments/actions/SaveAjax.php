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
		require_once 'vtlib/Vtiger/Mailer.php';
		global $site_URL;
		$email= '';
		$name = $request->get('username');
		$relatedId = $recordModel->get('related_to');
		$relatedRecordModel = Vtiger_Record_Model::getInstanceById($relatedId);
		$accountModel = Vtiger_Record_Model::getInstanceById($relatedRecordModel->get('parent_id'));
		$email = $accountModel->get('email1');

		if(empty($email)) {
			$contactModel = Vtiger_Record_Model::getInstanceById($relatedRecordModel->get('contact_id'));
			$email = $contactModel->get('email');
		}

		$vtigerMailer = new Vtiger_Mailer();

		$vtigerMailer->AddAddress($email, $name);

		$subject = $name . ' ' . vtranslate('LBL_COMMENTED', 'ModComments') . ' ' . $relatedRecordModel->get('ticket_no') . ' | ' . $relatedRecordModel->getName();

		$contents = '<a href="' . $site_URL . $relatedRecordModel->getDetailViewUrl() . '"><h3>' . $name . ' ' . vtranslate('LBL_COMMENTED', 'ModComments') . ' ' . $relatedRecordModel->get('ticket_no') . ':</h3></a>';
		$contents .= $recordModel->get('commentcontent');

		$vtigerMailer->Subject = $subject;
		$vtigerMailer->Body    = $contents;
		$vtigerMailer->ContentType = "text/html";

		return $vtigerMailer->Send(true) ? $email : $vtigerMailer->ErrorInfo;
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

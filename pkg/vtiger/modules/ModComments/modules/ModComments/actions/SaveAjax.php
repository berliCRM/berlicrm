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

    /**
     * @param Vtiger_Request $request
     * @return void
     * @throws AppException
     */
    public function checkPermission(Vtiger_Request $request): void
    {
        $moduleName = $request->getModule();
        $record = $request->get('record');
        //Do not allow ajax edit of existing comments
        if ($record) {
            throw new AppException('LBL_PERMISSION_DENIED');
        }
    }

    /**
     * @param Vtiger_Request $request
     * @return void
     */
    public function process(Vtiger_Request $request): void
    {
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        $request->set('assigned_user_id', $currentUserModel->getId());
        $request->set('userid', $currentUserModel->getId());
        $request->set('username', $currentUserModel->getName());
        $mailTo = '';

        $recordModel = $this->saveRecord($request);
        if ($request->get('sendMail')) {
            $mailTo = $this->sendMail($request, $recordModel);
        }
        $this->saveModcommentsScope($request, $recordModel, $mailTo);

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
    public function saveRecord($request): Vtiger_Record_Model
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
    public function getRecordModelFromRequest(Vtiger_Request $request): Vtiger_Record_Model
    {
        $recordModel = parent::getRecordModelFromRequest($request);

        $recordModel->set('commentcontent', $request->getRaw('commentcontent'));

        return $recordModel;
    }

    /**
     * @param Vtiger_Request $request
     * @param Vtiger_Record_Model $recordModel
     * @return bool|mixed|string
     * @throws Exception
     */
    public function sendMail(Vtiger_Request $request, Vtiger_Record_Model $recordModel): mixed
    {
        global $site_URL, $HELPDESK_SUPPORT_EMAIL_ID, $PORTAL_URL;
        $email = '';
        $name = '';
        $relatedId = $recordModel->get('related_to');
        $relatedRecordModel = Vtiger_Record_Model::getInstanceById($relatedId);
        $parent_type = '';
        $parent_id = '';
        $companyDetails = getCompanyDetails();
        $portalUrl = $PORTAL_URL . "/index.php?module=HelpDesk&action=index&ticketid=" . $relatedId . "&fun=detail";
        $logoUrl = $site_URL . 'test/logo/' . $companyDetails['logoname'];

        if (empty($email) && !empty($relatedRecordModel->get('contact_id')) && $relatedRecordModel->get('contact_id') != '0') {
            $contactModel = Vtiger_Record_Model::getInstanceById($relatedRecordModel->get('contact_id'));
            $email = $contactModel->get('email');
            $name = $contactModel->get('firstname') . " " . $contactModel->get('lastname');
            $parent_type = 'Contacts';
            $parent_id = $relatedRecordModel->get('contact_id');
        }

        if (empty($email)) {
            $accountModel = Vtiger_Record_Model::getInstanceById($relatedRecordModel->get('parent_id'));
            $email = $accountModel->get('email1');
            $name = $accountModel->get('accountname');
            $parent_type = 'Accounts';
            $parent_id = $relatedRecordModel->get('parent_id');
        }

        $subject = $relatedRecordModel->get('ticket_no') . ' | ' . $relatedRecordModel->getName();

        $contents = decode_html(self::getMailTemplate());
        $contents = str_replace('$NOW_DATE$', date("d.m.Y"), $contents);
        $contents = str_replace('$NAME$', $name, $contents);
        $contents = str_replace('$TICKET_NR$', $relatedRecordModel->get('ticket_no'), $contents);
        $contents = str_replace('$TICKET_BETREFF$', $relatedRecordModel->getName(), $contents);
        $contents = str_replace('$TICKET_STATUS$', $relatedRecordModel->get('ticketstatus'), $contents);
        $contents = str_replace('$DESCRIPTION$', nl2br($relatedRecordModel->get('description')), $contents);
        $contents = str_replace('$REPLY_BODY$', nl2br($recordModel->get('commentcontent')), $contents);

        // For Company Details:
        $contents = str_replace('$COMPANY_NAME$', $companyDetails['companyname'], $contents);
        $contents = str_replace('$SITE$', $companyDetails['website'], $contents);
        $contents = str_replace('$COMPANY_PHONE$', $companyDetails['phone'], $contents);
        $contents = str_replace('$COMPANY_EMAIL$', $HELPDESK_SUPPORT_EMAIL_ID, $contents);
        $contents = str_replace('$NOW_YEAR$', date("Y"), $contents);
        $contents = str_replace('$COMPANY_ADDRESS$', $companyDetails['address'], $contents);
        $contents = str_replace('$URL_ENG$', $portalUrl, $contents);
        $contents = str_replace('$COMPANY_LOGO$', $logoUrl, $contents);

        $to = $email;
        if(is_array($to)) {
            $to = implode(',',$to);
        }

        $emailsRecordModel = Vtiger_Record_Model::getCleanInstance('Emails');
        $emailsRecordModel->set('subject', $subject);
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

    /**
     * @param Vtiger_Request $request
     * @param Vtiger_Record_Model $recordModel
     * @param $mailTo
     * @return void
     */
    public function saveModcommentsScope(Vtiger_Request $request, Vtiger_Record_Model $recordModel, $mailTo): void
    {
        $adb = PearDatabase::getInstance();
        $external = json_decode($request->get('external'));
        $timeNeeded = $request->get('neededTime');

        if ($mailTo !== '') {
            $external = 1;
        }

        $query = "INSERT INTO vtiger_modcommentsscope (modcommentsid, mailto, external, timeneeded) VALUES (?, ?, ?, ?)";
        $result = $adb->pquery($query, array($recordModel->getId(), $mailTo, $external, $timeNeeded));
    }

    /**
     * @return string|null
     * @throws Exception
     */
    public function getMailTemplate(): ?string
    {
        global $HELPDESK_SUPPORT_EMAIL_TEMPLATE;

        $db = PearDatabase::getInstance();

        $query = "SELECT vtiger_emailtemplates.subject,vtiger_emailtemplates.body
					FROM vtiger_emailtemplates
					WHERE vtiger_emailtemplates.templateid=?";
        $result = $db->pquery($query, array($HELPDESK_SUPPORT_EMAIL_TEMPLATE));
        return $db->query_result($result,0,'body');
    }
}

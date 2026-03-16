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
     * @throws Exception
     */
    public function process(Vtiger_Request $request): void
    {
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        $request->set('assigned_user_id', $currentUserModel->getId());
        $request->set('userid', $currentUserModel->getId());
        $request->set('username', $currentUserModel->getName());

        $recordModel = $this->saveRecord($request);

        if ($request->get('sendMail')) {
            $attachmentDocumentIds = $this->saveUploadedDocuments();
            $this->sendMail($request, $recordModel, $attachmentDocumentIds);
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
        $parentComments = $request->get('parent_comments');
        if ($parentComments === 'undefined' || $parentComments === null) {
            $parentComments = '';
        }

        $recordModel->set('commentcontent', $request->getRaw('commentcontent'));
        $recordModel->set('parent_comments', $parentComments);
        $recordModel->set('carboncopy', trim((string) $request->get('carboncopy')));
        $recordModel->set('blindcarboncopy', trim((string) $request->get('blindcarboncopy')));

        return $recordModel;
    }

    /**
     * @param Vtiger_Request $request
     * @param Vtiger_Record_Model $recordModel
     * @return void
     * @throws Exception
     */
    public function sendMail(Vtiger_Request $request, Vtiger_Record_Model $recordModel, array $attachmentDocumentIds = array()): void
    {
        global $HELPDESK_SUPPORT_EMAIL_ID;
        $db = PearDatabase::getInstance();

        $email = '';
        $name = '';
        $relatedId = $recordModel->get('related_to');
        $relatedRecordModel = Vtiger_Record_Model::getInstanceById($relatedId);
        $parent_id = '';
        [$subject, $contents] = array_map('decode_html', self::getMailTemplate());

        $subject = getMergedDescription($subject, $relatedId, 'HelpDesk');
        $contents = getMergedDescription($contents, $relatedId, 'HelpDesk');
        $contents = getMergedDescription($contents, $recordModel->getId(), 'ModComments');

        if (!empty($relatedRecordModel->get('contact_id')) && $relatedRecordModel->get('contact_id') != '0') {
            $contactModel = Vtiger_Record_Model::getInstanceById($relatedRecordModel->get('contact_id'));
            $email = $contactModel->get('email');
            $name = $contactModel->get('firstname') . " " . $contactModel->get('lastname');
            $parent_id = $relatedRecordModel->get('contact_id');
            $contents = getMergedDescription($contents, $parent_id, 'Contacts');
            // delete placeholders
            $contents = getMergedDescription($contents, 0, 'Accounts');
        }

        if (empty($email)) {
            $accountModel = Vtiger_Record_Model::getInstanceById($relatedRecordModel->get('parent_id'));
            $email = $accountModel->get('email1');
            $name = $accountModel->get('accountname');
            $parent_id = $relatedRecordModel->get('parent_id');
            $contents = getMergedDescription($contents, $parent_id, 'Accounts');
            // delete placeholders
            $contents = getMergedDescription($contents, 0, 'Contacts');
        }

        require_once 'modules/Settings/Vtiger/models/ConfigTicketEmailAddress.php';
        $emailForTicketModel = Settings_Vtiger_ConfigTicketEmailAddress::getInstance();
        $data = $emailForTicketModel->getData();
        $from_email = $HELPDESK_SUPPORT_EMAIL_ID;
        $sender_name = '';
        // If the checkbox is activated, then the data from the configuration menu should be set as the "sender".
        if($data["enabled"] == 1){
            $sender_email = $data["sender_email"];
            $sender_name = $data["sender_name"];
            $reply_to_email = $data["reply_to_email"];
            //$reply_to_name = $data["reply_to_name"];
            if(empty($sender_email) && empty($reply_to_email)){
                // nothing to set
            }
            else if( empty($sender_email) ){
                $from_email = $reply_to_email;
            }
            else if( empty($reply_to_email) ){
                $from_email = $sender_email;
            }
            else{
                $from_email = $sender_email;
            }
        }

        $to = $email;
        if(is_array($to)) {
            $to = implode(',',$to);
        }

        $emailsRecordModel = Vtiger_Record_Model::getCleanInstance('Emails');
        $emailsRecordModel->set('subject', htmlspecialchars_decode($subject));
        $emailsRecordModel->set('description', $contents);
        $emailsRecordModel->set('email_flag', 'SENT');
        $emailsRecordModel->set('assigned_user_id', Users_Record_Model::getCurrentUserModel()->getId());
        $emailsRecordModel->set('parent_id', $relatedId . '@1|' . $parent_id . '@1|');
        $emailsRecordModel->set('toemailinfo', array($relatedId => array($email)));
        $emailsRecordModel->set('toMailNamesList', array($relatedId => array(array('label' => $name, 'value' => $email))));
        $emailsRecordModel->set('saved_toid', $to);
        $emailsRecordModel->set('ccmail', trim((string) $request->get('carboncopy')));
        $emailsRecordModel->set('bccmail', trim((string) $request->get('blindcarboncopy')));
        $emailsRecordModel->set('sender_name', $sender_name);
        $emailsRecordModel->set('from_email', $from_email);
        $emailsRecordModel->set('documentids', $attachmentDocumentIds);
        $emailsRecordModel->fromAddress = $from_email;
        $emailsRecordModel->save();
        $this->ensureEmailRelation($relatedId, $emailsRecordModel->getId());
        $this->ensureDocumentRelations($relatedId, $attachmentDocumentIds);

        $response = $emailsRecordModel->send();
        if ($response === true) {
            // This is needed to set vtiger_email_track table as it is used in email reporting
            $emailsRecordModel->setAccessCountValue();
        } else {
            $emailsRecordModel->set('email_flag', 'FAILED');
            $emailsRecordModel->set('mode', 'edit');
            $emailsRecordModel->save();
        }

        // Not using record model for this because mailto has to be set after record model got saved and this would trigger aftersave handler a second time.
        // This is not wanted because things like ModTracker would count this as two different edits/saves
        $query = "UPDATE vtiger_modcomments SET mailto = ?, carboncopy = ?, blindcarboncopy = ? WHERE modcommentsid = ?";
        $db->pquery($query, array(
            $email,
            trim((string) $request->get('carboncopy')),
            trim((string) $request->get('blindcarboncopy')),
            $recordModel->getId()
        ));
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getMailTemplate(): array
    {
        global $HELPDESK_SUPPORT_EMAIL_TEMPLATE;

        $db = PearDatabase::getInstance();

        $query = "SELECT vtiger_emailtemplates.subject,vtiger_emailtemplates.body
					FROM vtiger_emailtemplates
					WHERE vtiger_emailtemplates.templateid=?";
        $result = $db->pquery($query, array($HELPDESK_SUPPORT_EMAIL_TEMPLATE));
        return array($db->query_result($result,0,'subject'), $db->query_result($result,0,'body'));
    }

    protected function saveUploadedDocuments(): array
    {
        if (empty($_FILES['attachments']) || empty($_FILES['attachments']['name'])) {
            return array();
        }

        require_once 'data/CRMEntity.php';
        CRMEntity::getInstance('Documents');

        $documentIds = array();
        $files = $_FILES['attachments'];
        $count = is_array($files['name']) ? count($files['name']) : 0;

        for ($index = 0; $index < $count; $index++) {
            if ((int) $files['error'][$index] !== UPLOAD_ERR_OK || empty($files['name'][$index])) {
                continue;
            }

            $documentId = $this->saveUploadedDocument(array(
                'name' => $files['name'][$index],
                'tmp_name' => $files['tmp_name'][$index],
                'size' => $files['size'][$index],
            ));

            if ($documentId) {
                $documentIds[] = $documentId;
            }
        }

        return $documentIds;
    }

    protected function saveUploadedDocument(array $file)
    {
        require_once 'modules/Settings/MailConverter/handlers/MailAttachmentMIME.php';

        $db = PearDatabase::getInstance();
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        $uploadPath = decideFilePath();

        $attachId = $db->getUniqueId('vtiger_crmentity');
        $fileName = sanitizeUploadFileName($file['name'], vglobal('upload_badext'));
        $fileName = ltrim(basename(' ' . $fileName));
        $savedFilePath = $uploadPath . $attachId . '_' . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $savedFilePath)) {
            return false;
        }

        $description = $fileName;
        $dateVar = $db->formatDate(date('YmdHis'), true);
        $useTime = $db->formatDate($dateVar, true);

        $db->pquery(
            "INSERT INTO vtiger_crmentity(crmid, smcreatorid, smownerid, modifiedby, setype, description, createdtime, modifiedtime, presence, deleted)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            array(
                $attachId,
                $currentUserModel->getId(),
                $currentUserModel->getId(),
                $currentUserModel->getId(),
                'Documents Attachment',
                $description,
                $useTime,
                $useTime,
                1,
                0
            )
        );

        $mimeType = MailAttachmentMIME::detect($savedFilePath);
        $db->pquery(
            "INSERT INTO vtiger_attachments SET attachmentsid=?, name=?, description=?, type=?, path=?",
            array($attachId, $fileName, $description, $mimeType, $uploadPath)
        );

        $document = new Documents();
        $document->column_fields['notes_title'] = $fileName;
        $document->column_fields['filename'] = $fileName;
        $document->column_fields['filestatus'] = 1;
        $document->column_fields['filelocationtype'] = 'I';
        $document->column_fields['folderid'] = 1;
        $document->column_fields['filesize'] = $file['size'];
        $document->column_fields['assigned_user_id'] = $currentUserModel->getId();
        $existingFiles = $_FILES;
        $_FILES = array();
        $document->save('Documents');
        $_FILES = $existingFiles;

        $db->pquery(
            "INSERT INTO vtiger_seattachmentsrel(crmid, attachmentsid) VALUES(?,?)",
            array($document->id, $attachId)
        );

        return $document->id;
    }

    protected function ensureEmailRelation($relatedId, $emailId): void
    {
        if (empty($relatedId) || empty($emailId)) {
            return;
        }

        $db = PearDatabase::getInstance();
        $existingRelation = $db->pquery(
            'SELECT 1 FROM vtiger_seactivityrel WHERE crmid = ? AND activityid = ?',
            array($relatedId, $emailId)
        );

        if ((int) $db->num_rows($existingRelation) === 0) {
            $db->pquery(
                'INSERT INTO vtiger_seactivityrel(crmid, activityid) VALUES(?, ?)',
                array($relatedId, $emailId)
            );
        }
    }

    protected function ensureDocumentRelations($relatedId, array $documentIds): void
    {
        if (empty($relatedId) || empty($documentIds)) {
            return;
        }

        $db = PearDatabase::getInstance();

        foreach (array_unique($documentIds) as $documentId) {
            if (empty($documentId)) {
                continue;
            }

            $existingRelation = $db->pquery(
                'SELECT 1 FROM vtiger_senotesrel WHERE crmid = ? AND notesid = ?',
                array($relatedId, $documentId)
            );

            if ((int) $db->num_rows($existingRelation) === 0) {
                $db->pquery(
                    'INSERT INTO vtiger_senotesrel(crmid, notesid) VALUES(?, ?)',
                    array($relatedId, $documentId)
                );
            }
        }
    }
}

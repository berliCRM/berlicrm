<?php
/**
 * Outlook-specific CRM email sending with optional template and file attachments.
 *
 * @package berliCRM.OutlookAddin
 */
require_once dirname(__DIR__, 2) . '/utils/OfficeAddinI18n.php';

/************************************************************************************************************************************************************
Description: send mail, body from template, attachments from Documents or direct payload
Parameters:
  - encoded receiverIds: list of Webservices IDs that will receive the mail (JSON)
  - string templateId: internal ID of EmailTemplates module that will provide email contents
  - string subject: email subject (overrides the template subject when provided)
  - string body: email body (overrides the template body when provided)
  - encoded documentIds: list of Webservices IDs of Documents that will be attached to the mail (JSON)
  - string mailFieldToUse: fieldname that will be primarily used for receiving email address
  - string fromId: Webservices ID of User that sends the email (not used yet)
  - string fromName: name that will be displayed for sender (not used yet)
  - encoded attachments: list of direct attachments:
      [
        {"name":"file.pdf","type":"application/pdf","content":"BASE64..."}
      ]

Returns:
  - array($receiverId => status)
************************************************************************************************************************************************************ */

/**
 * Creates and sends CRM email records for the permitted recipient records.
 *
 * @param array|string $receiverIds Recipient webservice IDs or JSON.
 * @param string $templateId Optional email-template identifier.
 * @param string $subject Optional subject override.
 * @param string $body Optional message-body override.
 * @param array|string $documentIds Document webservice IDs or JSON.
 * @param string $mailFieldToUse Preferred recipient email field.
 * @param string $fromId Legacy sender identifier parameter.
 * @param string $fromName Legacy sender display-name parameter.
 * @param array|string $attachments Direct attachment payloads or JSON.
 * @param Users $user Authenticated CRM user.
 * @return array<string,mixed> Send status keyed by recipient identifier.
 * @throws WebServiceException On invalid input or missing CRM permissions.
 * @berli-openapi-begin
 * {"summary":"Send mail from the Outlook add-in","description":"Sends a templated or explicitly supplied email to CRM records or direct addresses and optionally attaches CRM Documents or uploaded files.","tags":["Outlook"],"parameters":{"receiverIds":{"required":true,"description":"JSON array of CRM webservice IDs or direct email addresses.","example":"[\"12x345\"]","decodedSchema":{"type":"array","items":{"type":"string"}}},"templateId":{"required":false,"description":"CRM email template ID. Subject and body may be supplied instead.","example":"1"},"subject":{"required":false,"description":"Explicit subject overriding the template subject.","example":"Meeting follow-up"},"body":{"required":false,"description":"Explicit message body overriding the template body.","example":"Thank you for the meeting."},"documentIds":{"required":false,"description":"JSON array of readable Document webservice IDs.","example":"[\"15x900\"]","decodedSchema":{"type":"array","items":{"type":"string"}}},"mailFieldToUse":{"required":false,"description":"Preferred CRM email field name.","example":"email"},"fromId":{"required":false,"description":"Configured sender identifier.","example":"1"},"fromName":{"required":false,"description":"Sender display name.","example":"CRM Team"},"attachments":{"required":false,"description":"JSON array of direct attachment payloads.","example":"[]","decodedSchema":{"type":"array","items":{"type":"object","additionalProperties":true}}}},"result":{"schema":{"type":"object","additionalProperties":{"type":"string"}},"example":{"12x345":"SUCCESS: sent to jane@example.org"}}}
 * @berli-openapi-end
 */
function berli_outlookSendMail($receiverIds, $templateId, $subject, $body, $documentIds, $mailFieldToUse, $fromId, $fromName, $attachments, $user) {
    $adb = PearDatabase::getInstance();
    $types = vtws_listtypes(NULL, $user);
    $returnArr = array();
    $mailFields = array();

    // decode encoded params if needed
    if (is_string($receiverIds) && $receiverIds !== '') {
        $tmp = json_decode($receiverIds, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $receiverIds = $tmp;
        }
    }
    if (is_string($documentIds) && $documentIds !== '') {
        $tmp = json_decode($documentIds, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $documentIds = $tmp;
        }
    }
    if (is_string($attachments) && $attachments !== '') {
        $tmp = json_decode($attachments, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $attachments = $tmp;
        }
    }

    if (!is_array($receiverIds)) {
        $receiverIds = array();
    }
    if (!is_array($documentIds)) {
        $documentIds = array();
    }
    if (!is_array($attachments)) {
        $attachments = array();
    }

    $templateRow = array('subject' => '', 'body' => '');
    if (!empty($templateId)) {
        $query = 'SELECT subject, body FROM vtiger_emailtemplates WHERE templateid = ?';
        $result = $adb->pquery($query, array($templateId));
        $templateRow = $adb->getNextRow($result, false);
        if (empty($templateRow)) {
            throw new WebServiceException(WebServiceErrorCode::$MANDFIELDSMISSING, "Missing email template for ID '$templateId'");
        }
    }

    if (!empty($subject)) {
        $templateRow['subject'] = $subject;
    }
    if (!empty($body)) {
        $templateRow['body'] = $body;
    }

    if (empty($templateRow['subject']) || empty($templateRow['body'])) {
        throw new WebServiceException(WebServiceErrorCode::$MANDFIELDSMISSING, berli_outlookT('A_TEMPLATE_ID_OR_SUBJECT_BODY_MUST_BE_PROVIDED'));
    }

    // only real Documents go here
    $documentRecordIds = array();

    if (!empty($documentIds)) {
        $moduleHandler = vtws_getModuleHandlerFromName('Documents', $user);
        $meta = $moduleHandler->getMeta();

        if (!in_array('Documents', $types['types']) || $meta->hasReadAccess() !== true) {
            throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, berli_outlookT('THE_DOCUMENTS_MODULE_IS_NOT_ACCESSIBLE'));
        }

        foreach ($documentIds as $documentId) {
            $idComponents = vtws_getIdComponents($documentId);
            if (!$meta->hasPermission(EntityMeta::$RETRIEVE, $documentId) || !$meta->exists($idComponents[1])) {
                throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, "'$documentId' cannot be read or found");
            }
            $documentRecordIds[] = $idComponents[1];
        }
    }

    if (empty($receiverIds)) {
        throw new WebServiceException(WebServiceErrorCode::$MANDFIELDSMISSING, berli_outlookT('RECIPIENT_IDS_MUST_NOT_BE_EMPTY'));
    }

    foreach ($receiverIds as $receiverId) {
        $isDirectEmail = is_string($receiverId) && filter_var($receiverId, FILTER_VALIDATE_EMAIL);
        $emailAddress = '';
        $entityName = '';
        $parentId = '';
        $parentType = '';

        if ($isDirectEmail) {
            $emailAddress = $receiverId;
        } else {
            $moduleHandler = vtws_getModuleHandlerFromId($receiverId, $user);
            $meta = $moduleHandler->getMeta();
            $entityName = $meta->getEntityName();
            $idComponents = vtws_getIdComponents($receiverId);

            if (!in_array($entityName, $types['types'])) {
                $returnArr[$receiverId] = "Module '$entityName' is not accessible";
                continue;
            }
            if ($meta->hasReadAccess() !== true) {
                $returnArr[$receiverId] = "Permission to read '$entityName' denied";
                continue;
            }
            if (!$meta->hasPermission(EntityMeta::$RETRIEVE, $receiverId)) {
                $returnArr[$receiverId] = berli_outlookT('PERMISSION_TO_READ_THE_RECORD_IS_DENIED');
                continue;
            }
            if (!$meta->exists($idComponents[1])) {
                $returnArr[$receiverId] = berli_outlookT('RECORD_NOT_FOUND');
                continue;
            }

            if (empty($mailFields[$entityName])) {
                require_once('include/Webservices/DescribeObject.php');
                $describe = vtws_describe($entityName, $user);
                foreach ($describe['fields'] as $field) {
                    if (isset($field['type']['name']) && $field['type']['name'] === 'email') {
                        $mailFields[$entityName][] = $field['name'];
                    }
                }
            }

            $recordModel = Vtiger_Record_Model::getInstanceById($idComponents[1], $entityName);
            $emailAddress = $recordModel->get($mailFieldToUse);

            if (empty($emailAddress) && !empty($mailFields[$entityName])) {
                foreach ($mailFields[$entityName] as $mailField) {
                    $value = $recordModel->get($mailField);
                    if (!empty($value)) {
                        $emailAddress = $value;
                        break;
                    }
                }
            }

            if (empty($emailAddress)) {
                $returnArr[$receiverId] = berli_outlookT('NO_EMAIL_ADDRESS_FOUND');
                continue;
            }

            $parentType = $entityName;
            $parentId = $idComponents[1] . '@-1|';
        }

        $emailBody = $templateRow['body'];
        if (!$isDirectEmail && !empty($parentId)) {
            $emailBody = Vtiger_Functions::getMergedDescription($emailBody, $idComponents[1], $entityName);
            $emailBody = Vtiger_Functions::getMergedDescription($emailBody, $user->id, 'Users');
        }

        $emailsRecordModel = Vtiger_Record_Model::getCleanInstance('Emails');
        $emailsRecordModel->set('subject', $templateRow['subject']);
        $emailsRecordModel->set('description', $emailBody);
        $emailsRecordModel->set('email_flag', 'SENT');
        if (!empty($parentType)) {
            $emailsRecordModel->set('parent_type', $parentType);
        }
        if (!empty($parentId)) {
            $emailsRecordModel->set('parent_id', $parentId);
        }
        $emailsRecordModel->set('assigned_user_id', $user->id);
        $emailsRecordModel->set('saved_toid', $emailAddress);

        // only real Documents here
        $emailsRecordModel->set('documentids', $documentRecordIds);

        try {
            $emailsRecordModel->save();
            $emailId = $emailsRecordModel->getId();

            // create direct uploaded attachments in GUI-compatible way
            if (!empty($attachments)) {
                foreach ($attachments as $attachment) {
                    berli_outlookCreateEmailAttachmentFromPayload($attachment, $emailId, $user);
                }
            }

            $status = $emailsRecordModel->send();

            if ($status === true) {
                $status = "SUCCESS: sent to $emailAddress";
            } else {
                $emailsRecordModel->delete();
            }
        } catch (Exception $e) {
            $status = $e->getMessage();
            if (empty($status)) {
                $status = "Unknown error while sending mail to '$emailAddress'";
            }
        }

        $returnArr[$receiverId] = $status;
        set_time_limit(0);
    }

    return $returnArr;
}

/**
 * Resolves an available CRM error-code property with a backwards-compatible fallback.
 *
 * @param string $codeName Preferred error-code property.
 * @param string $fallbackCodeName Fallback error-code property.
 * @return mixed
 */
function berli_outlookGetWebserviceErrorCode($codeName, $fallbackCodeName = 'MANDFIELDSMISSING') {
    if (property_exists('WebServiceErrorCode', $codeName)) {
        $property = 'WebServiceErrorCode::$' . $codeName;
        return eval('return ' . $property . ';');
    }

    if (property_exists('WebServiceErrorCode', $fallbackCodeName)) {
        $property = 'WebServiceErrorCode::$' . $fallbackCodeName;
        return eval('return ' . $property . ';');
    }

    return null;
}

/**
 * Creates a direct email attachment from payload, stores it like a GUI-created
 * email attachment, links it to the given Email record, and returns the
 * attachment CRM ID.
 *
 * @param array<string,mixed> $attachment Filename, MIME type and base64 bytes.
 * @param int|string $emailId Numeric CRM email identifier.
 * @param Users $user Authenticated CRM user and attachment owner.
 * @return int|string Created CRM attachment identifier.
 * @throws WebServiceException On invalid attachment data or storage failures.
 */
function berli_outlookCreateEmailAttachmentFromPayload($attachment, $emailId, $user) {
    global $root_directory;
    $adb = PearDatabase::getInstance();

    if (!is_array($attachment)) {
        throw new WebServiceException(berli_outlookGetWebserviceErrorCode('INVALIDDATA', 'MANDFIELDSMISSING'), berli_outlookT('THE_ATTACHMENT_PAYLOAD_MUST_BE_AN_ARRAY'));
    }

    $filename = isset($attachment['name']) ? trim($attachment['name']) : '';
    $filetype = isset($attachment['type']) ? trim($attachment['type']) : 'application/octet-stream';
    $content  = isset($attachment['content']) ? $attachment['content'] : '';

    if ($filename === '' || $content === '') {
        throw new WebServiceException(berli_outlookGetWebserviceErrorCode('MANDFIELDSMISSING', 'MANDFIELDSMISSING'), berli_outlookT('ATTACHMENT_NAME_AND_CONTENT_ARE_REQUIRED'));
    }

    if ($filetype === '') {
        $filetype = 'application/octet-stream';
    }

    $decoded = base64_decode($content, true);
    if ($decoded === false) {
        throw new WebServiceException(berli_outlookGetWebserviceErrorCode('INVALIDDATA', 'MANDFIELDSMISSING'), "Attachment '$filename' has invalid base64 content");
    }

    if (strlen($decoded) > 10 * 1024 * 1024) {
        throw new WebServiceException(berli_outlookGetWebserviceErrorCode('INVALIDDATA', 'MANDFIELDSMISSING'), "Attachment '$filename' exceeds maximum allowed size");
    }

    $attachmentId = $adb->getUniqueID('vtiger_crmentity');
    $currentTime  = $adb->formatDate(date('YmdHis'), true);

    $adb->pquery(
        "INSERT INTO vtiger_crmentity
            (crmid, smcreatorid, smownerid, setype, description, createdtime, modifiedtime, presence, deleted)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
        array(
            $attachmentId,
            $user->id,
            $user->id,
            'Emails Attachment',
            '',
            $currentTime,
            $currentTime,
            1,
            0
        )
    );

    $uploadDir = decideFilePath();
    $safeFilename = preg_replace('/[\\\\\\/]+/', '_', $filename);
    $storedName = $attachmentId . '_' . $safeFilename;
    $fullPath   = rtrim($root_directory, '/') . '/' . $uploadDir . $storedName;

    if (file_put_contents($fullPath, $decoded) === false) {
        $adb->pquery("DELETE FROM vtiger_crmentity WHERE crmid = ?", array($attachmentId));
        throw new WebServiceException(berli_outlookGetWebserviceErrorCode('UNKNOWNOPERATION', 'MANDFIELDSMISSING'), "Could not store attachment '$filename'");
    }

    $adb->pquery(
        "INSERT INTO vtiger_attachments
            (attachmentsid, name, description, type, path)
         VALUES (?, ?, ?, ?, ?)",
        array(
            $attachmentId,
            $filename,
            '',
            $filetype,
            $uploadDir
        )
    );

    $adb->pquery(
        "INSERT INTO vtiger_seattachmentsrel (crmid, attachmentsid) VALUES (?, ?)",
        array($emailId, $attachmentId)
    );

    return $attachmentId;
}

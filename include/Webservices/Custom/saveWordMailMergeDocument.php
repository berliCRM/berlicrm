<?php
/**
 * Stores generated Word mail-merge documents and links them to authorized records.
 *
 * Registered as the authenticated saveWordMailMergeDocument POST operation.
 * Validates record access, persists an internal attachment and removes partial
 * document data when a subsequent storage or relation step fails.
 *
 * @package berliCRM.WordAddin
 */
require_once dirname(__DIR__, 2) . '/utils/OfficeAddinI18n.php';


global $root_directory;

require_once $root_directory . '/include/Webservices/Query.php';
require_once $root_directory . '/include/Webservices/Create.php';
require_once $root_directory . '/include/Webservices/Retrieve.php';
if (!defined('BERLI_WORD_DOCX_SAVE_VERSION')) define('BERLI_WORD_DOCX_SAVE_VERSION', 1);
/**
 * Saves one Word mail-merge document in berliCRM and links it to its record.
 *
 * @param string $moduleName CRM web-service module name.
 * @param string $listId ID of the selected saved list view.
 * @param string $recordId Internal record ID.
 * @param string $notesTitle CRM document title.
 * @param string $fileContent Base64-encoded legacy UTF-8 HTML or native DOCX bytes.
 * @param Users $user Authenticated berliCRM web-service user.
 * @return array The created CRM document ID.
 * @throws WebServiceException If access is denied or input is invalid.
 */
function berli_saveWordMailMergeDocument($moduleName, $listId, $recordId, $notesTitle, $fileContent, $user) {
	global $adb;

	$allowedModules = array('Contacts', 'Accounts', 'Leads', 'HelpDesk');
	if (!in_array($moduleName, $allowedModules, true)) {
		throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, berli_wordT('THIS_MODULE_IS_NOT_ENABLED_FOR_WORD_MAIL_MERGE'));
	}

	$types = vtws_listtypes(null, $user);
	if (!in_array($moduleName, $types['types'], true) || !in_array('Documents', $types['types'], true)) {
		throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, berli_wordT('YOU_DO_NOT_HAVE_PERMISSION_TO_USE_THIS_CRM_MODULE_OR_DOCUMENTS'));
	}

	$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
	$documentsModuleModel = Vtiger_Module_Model::getInstance('Documents');
	if (!$moduleModel || !$moduleModel->isPermitted('DetailView')
		|| !$documentsModuleModel || !$documentsModuleModel->isPermitted('CreateView')) {
		throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, berli_wordT('YOU_DO_NOT_HAVE_PERMISSION_TO_USE_THIS_RECORD_OR_DOCUMENTS'));
	}

	if (!ctype_digit((string) $recordId) || (int) $recordId < 1 || empty($listId)) {
		throw new WebServiceException(WebServiceErrorCode::$INVALIDID, berli_wordT('INVALID_RECORD_OR_LIST_VIEW_ID'));
	}

	$view = null;
	foreach (CustomView_Record_Model::getAll($moduleName) as $availableView) {
		if ((string) $availableView->getId() === (string) $listId) {
			$view = $availableView;
			break;
		}
	}
	if (!$view || !in_array((int) $recordId, array_map('intval', $view->getRecordIds()), true)) {
		throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, berli_wordT('THE_RECORD_DOES_NOT_BELONG_TO_THE_AVAILABLE_LIST_VIEW'));
	}

	$webserviceObject = VtigerWebserviceObject::fromName($adb, $moduleName);
	vtws_retrieve(vtws_getId($webserviceObject->getEntityId(), $recordId), $user);

	$notesTitle = trim(vtlib_purify($notesTitle));
	$titleLength = function_exists('mb_strlen') ? mb_strlen($notesTitle, 'UTF-8') : strlen($notesTitle);
	$allowedTitle = false;
	foreach (array('de_de', 'en_us') as $language) {
		if (strpos($notesTitle, berli_officeT('word', 'DOCUMENT_TITLE_START', array(), $language)) === 0) $allowedTitle = true;
	}
	if ($notesTitle === '' || $titleLength > 50 || !$allowedTitle) {
		throw new WebServiceException(WebServiceErrorCode::$INVALIDID, berli_wordT('INVALID_DOCUMENT_TITLE_OR_TITLE_TOO_LONG'));
	}

	$maxFileSize = 5 * 1024 * 1024;
	if (!is_string($fileContent) || strlen($fileContent) > (int) ceil($maxFileSize * 4 / 3) + 4) {
		throw new WebServiceException(WebServiceErrorCode::$INVALIDID, berli_wordT('THE_DOCUMENT_CONTENT_IS_INVALID_OR_EXCEEDS_5_MB'));
	}
	$fileBytes = base64_decode($fileContent, true);
	if ($fileBytes === false || $fileBytes === '' || strlen($fileBytes) > $maxFileSize) {
		throw new WebServiceException(WebServiceErrorCode::$INVALIDID, berli_wordT('THE_DOCUMENT_CONTENT_IS_INVALID_OR_EXCEEDS_5_MB'));
	}
	$fileFormat = array('extension' => 'doc', 'mime' => 'application/msword');
	if (substr($fileBytes, 0, 2) === 'PK') {
		require_once __DIR__ . '/WordMailMergeDocx.php';
		try { $fileFormat = berli_wordMailMergeDocxFormat($fileBytes); }
		catch (Throwable $formatError) { throw new WebServiceException(WebServiceErrorCode::$INVALIDID, $formatError->getMessage()); }
	}

	$userIds = berli_wordMailMergeGetUserIds($user);
	if ($userIds === false) {
		throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, berli_wordT('COULD_NOT_DETERMINE_THE_SIGNED_IN_CRM_USER'));
	}
	$userId = $userIds['id'];
	$userWebserviceId = $userIds['webserviceId'];
	$documentFileName = berli_wordT('BERLICRM_MAIL_MERGE') . $recordId . '-' . date('Ymd-His') . '.' . $fileFormat['extension'];

	$documentFolders = array();

	$result = $adb->pquery(
		'SELECT folderid, foldername FROM vtiger_attachmentsfolder ORDER BY folderid ASC LIMIT 1',
		array()
	);

	if ($result && $adb->num_rows($result) > 0) {
		$documentFolders[] = array(
			'id' => $adb->query_result($result, 0, 'folderid'),
			'foldername' => $adb->query_result($result, 0, 'foldername')
		);
	}
	if (empty($documentFolders[0]['id'])) {
		throw new WebServiceException(WebServiceErrorCode::$INTERNALERROR, berli_wordT('COULD_NOT_FIND_THE_CRM_DOCUMENT_FOLDER'));
	}

	$documentFields = array(
		'notes_title' => $notesTitle,
		'filename' => $documentFileName,
		'filesize' => strlen($fileBytes),
		'filetype' => $fileFormat['mime'],
		'filelocationtype' => 'I',
		'filestatus' => 1,
		'filedownloadcount' => 0,
'folderid' => vtws_getId(
    VtigerWebserviceObject::fromName($adb, 'DocumentFolders')->getEntityId(),
    $documentFolders[0]['id']
),
		'assigned_user_id' => $userWebserviceId,
		'notecontent' => berli_wordT('CREATED_USING_THE_BERLICRM_WORD_ADD_IN'),
	);
	try {
		$document = vtws_create('Documents', $documentFields, $user);
	} catch (Throwable $createError) {
		error_log('Word mail-merge document creation failed: ' . $createError->getMessage());
		throw new WebServiceException(WebServiceErrorCode::$INTERNALERROR, berli_wordT('COULD_NOT_CREATE_THE_CRM_DOCUMENT_SEE_THE_CRM_SERVER_LOG_FOR_DETAILS'));
	}
	if (empty($document['id'])) {
		throw new WebServiceException(WebServiceErrorCode::$INTERNALERROR, berli_wordT('COULD_NOT_CREATE_THE_CRM_DOCUMENT'));
	}

	$documentIdParts = vtws_getIdComponents($document['id']);
	if (count($documentIdParts) !== 2 || !ctype_digit($documentIdParts[1])) {
		throw new WebServiceException(WebServiceErrorCode::$INTERNALERROR, berli_wordT('BERLICRM_RETURNED_AN_INVALID_DOCUMENT_ID'));
	}
	$documentId = (int) $documentIdParts[1];
	$tempFilePath = tempnam(sys_get_temp_dir(), 'berli-word-');
	if ($tempFilePath === false || file_put_contents($tempFilePath, $fileBytes, LOCK_EX) !== strlen($fileBytes)) {
		if ($tempFilePath !== false && is_file($tempFilePath) && !unlink($tempFilePath)) {
			error_log('Word mail-merge cleanup could not remove temporary file ' . $tempFilePath . '.');
		}
		berli_wordMailMergeCleanup($documentId, null);
		throw new WebServiceException(WebServiceErrorCode::$INTERNALERROR, berli_wordT('COULD_NOT_PREPARE_THE_DOCUMENT_FILE'));
	}

	$focus = CRMEntity::getInstance('Documents');
	$focus->column_fields['assigned_user_id'] = $userId;
	$focus->column_fields['description'] = $notesTitle;
	$fileDetails = array(
		'name' => $documentFileName,
		'original_name' => $documentFileName,
		'type' => $fileFormat['mime'],
		'size' => strlen($fileBytes),
		'tmp_name' => $tempFilePath,
		'error' => 0
	);
	global $root_directory;
	$crmRoot = realpath($root_directory);
	if ($crmRoot === false || !is_dir($crmRoot)) {
		if (!unlink($tempFilePath)) {
			error_log('Word mail-merge cleanup could not remove temporary file ' . $tempFilePath . '.');
		}
		berli_wordMailMergeCleanup($documentId, null);
		throw new WebServiceException(WebServiceErrorCode::$INTERNALERROR, berli_wordT('COULD_NOT_DETERMINE_THE_BERLICRM_WEBROOT_FOR_FILE_UPLOAD'));
	}
	$previousDirectory = getcwd();
	if ($previousDirectory === false || !chdir($crmRoot)) {
		if (!unlink($tempFilePath)) {
			error_log('Word mail-merge cleanup could not remove temporary file ' . $tempFilePath . '.');
		}
		berli_wordMailMergeCleanup($documentId, null);
		throw new WebServiceException(WebServiceErrorCode::$INTERNALERROR, berli_wordT('COULD_NOT_USE_THE_BERLICRM_WEBROOT_FOR_FILE_UPLOAD'));
	}

	vtws_preserveGlobal('current_user', $user);
	try {
		$attachmentId = berli_wordMailMergeSaveGeneratedFile($documentId, $fileDetails, $userId, $notesTitle);
	} catch (Throwable $uploadError) {
		error_log('Word mail-merge file upload failed for document ' . $documentId . ': ' . $uploadError->getMessage());
		berli_wordMailMergeCleanup($documentId, null);
		throw new WebServiceException(WebServiceErrorCode::$INTERNALERROR, berli_wordT('COULD_NOT_SAVE_THE_DOCUMENT_FILE_IN_CRM_STORAGE_SEE_THE_CRM_SERVER_LOG_F'));
	} finally {
		VTWS_PreserveGlobal::flush();
		if (!chdir($previousDirectory)) {
			error_log('Word mail-merge could not restore the request working directory ' . $previousDirectory . '.');
		}
		if (is_file($tempFilePath) && !unlink($tempFilePath)) {
			error_log('Word mail-merge cleanup could not remove temporary file ' . $tempFilePath . '.');
		}
	}
	if (empty($attachmentId)) {
		berli_wordMailMergeCleanup($documentId, null);
		throw new WebServiceException(WebServiceErrorCode::$INTERNALERROR, berli_wordT('COULD_NOT_SAVE_THE_DOCUMENT_FILE_IN_CRM_STORAGE'));
	}

	$documentObject = VtigerWebserviceObject::fromName($adb, 'Documents');
	$recordIdForRelation = vtws_getId($webserviceObject->getEntityId(), $recordId);
	$documentIdForRelation = vtws_getId($documentObject->getEntityId(), $documentId);

	try {
		berli_wordMailMergeRelateDocument($documentIdForRelation, $recordIdForRelation, $moduleName, $user);
	} catch (Throwable $relationError) {
		error_log('Word mail-merge relation creation failed for document ' . $documentId . ': ' . $relationError->getMessage());
		berli_wordMailMergeCleanup($documentId, $attachmentId);
		throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, berli_wordT('COULD_NOT_LINK_THE_DOCUMENT_TO_THE_RECORD_SEE_THE_CRM_SERVER_LOG_FOR_DET'));
	}

	$storedFile = berli_wordMailMergeVerifySavedDocument($documentId, $attachmentId, (int) $recordId);
	if ($storedFile === false) {
		berli_wordMailMergeCleanup($documentId, $attachmentId);
		throw new WebServiceException(WebServiceErrorCode::$INTERNALERROR, berli_wordT('BERLICRM_DID_NOT_SAVE_THE_COMPLETE_DOCUMENT_THE_DOCUMENT_FILE_OR_RECORD'));
	}

	return array(
		'documentId' => $document['id'],
		'fileName' => $storedFile['name']
	);
}

/**
 * Save generated bytes as an internal Documents attachment.
 * Unlike CRMEntity::uploadAndSaveFile(), this supports server-created temp files.
 * Returns the numeric attachment ID, not a boolean.
 *
 * @param int $documentId CRM document ID receiving the attachment.
 * @param array{name: string, tmp_name: string, type: string} $fileDetails Generated temporary upload metadata.
 * @param int $ownerId Authenticated user's numeric CRM ID.
 * @param string $description Attachment description.
 * @return int Created attachment entity ID.
 * @throws RuntimeException If the file is invalid or storage/metadata persistence fails.
 */
function berli_wordMailMergeSaveGeneratedFile($documentId, $fileDetails, $ownerId, $description) {
    global $adb, $root_directory, $upload_badext;

    $name = sanitizeUploadFileName($fileDetails['name'], $upload_badext);
    $name = ltrim(basename(' ' . $name));
    if ($name === '' || !is_file($fileDetails['tmp_name'])) {
        throw new RuntimeException(berli_wordT('THE_GENERATED_DOCUMENT_OR_FILENAME_IS_INVALID'));
    }
    $attachmentId = (int) $adb->getUniqueID('vtiger_crmentity');
    $path = decideFilePath();
    $destination = rtrim($path, '/\\') . DIRECTORY_SEPARATOR . $attachmentId . '_' . $name;
    if (!rename($fileDetails['tmp_name'], $destination)) {
        throw new RuntimeException(berli_wordT('CANNOT_MOVE_THE_GENERATED_DOCUMENT_INTO_CRM_STORAGE') . $destination);
    }
    $now = date('Y-m-d H:i:s');
    $queries = array(
        array('INSERT INTO vtiger_crmentity (crmid, smcreatorid, smownerid, setype, description, createdtime, modifiedtime) VALUES (?, ?, ?, ?, ?, ?, ?)',
              array($attachmentId, $ownerId, $ownerId, 'Documents Attachment', $description, $adb->formatDate($now, true), $adb->formatDate($now, true))),
        array('INSERT INTO vtiger_attachments (attachmentsid, name, description, type, path) VALUES (?, ?, ?, ?, ?)',
              array($attachmentId, $name, $description, $fileDetails['type'], $path)),
        array('INSERT INTO vtiger_seattachmentsrel (crmid, attachmentsid) VALUES (?, ?)',
              array($documentId, $attachmentId))
    );
    foreach ($queries as $query) {
        if ($adb->pquery($query[0], $query[1]) === false) {
            // Remove any partially created rows; the caller cleans up the document.
            $adb->pquery('DELETE FROM vtiger_seattachmentsrel WHERE crmid = ? AND attachmentsid = ?', array($documentId, $attachmentId));
            $adb->pquery('DELETE FROM vtiger_attachments WHERE attachmentsid = ?', array($attachmentId));
            $adb->pquery('DELETE FROM vtiger_crmentity WHERE crmid = ?', array($attachmentId));
            if (is_file($destination)) { unlink($destination); }
            throw new RuntimeException(berli_wordT('CANNOT_SAVE_GENERATED_DOCUMENT_ATTACHMENT_METADATA'));
        }
    }
    return $attachmentId;
}

/**
 * Confirms that the document, attachment file and related-record link exist.
 *
 * @param int $documentId Numeric CRM document ID.
 * @param int $attachmentId Numeric CRM attachment ID.
 * @param int $recordId Numeric related CRM record ID.
 * @return array|false Stored attachment metadata, or false if incomplete.
 */
function berli_wordMailMergeVerifySavedDocument($documentId, $attachmentId, $recordId) {
	global $adb;

	$documentResult = $adb->pquery(
		'SELECT crmid FROM vtiger_crmentity INNER JOIN vtiger_notes ON vtiger_notes.notesid = vtiger_crmentity.crmid WHERE crmid = ? AND setype = ? AND deleted = 0',
		array($documentId, 'Documents')
	);
	$attachmentResult = $adb->pquery(
		'SELECT path, name FROM vtiger_attachments WHERE attachmentsid = ?',
		array($attachmentId)
	);
	$fileRelationResult = $adb->pquery(
		'SELECT crmid FROM vtiger_seattachmentsrel WHERE crmid = ? AND attachmentsid = ?',
		array($documentId, $attachmentId)
	);
	$recordRelationResult = $adb->pquery(
		'SELECT crmid FROM vtiger_senotesrel WHERE crmid = ? AND notesid = ?',
		array($recordId, $documentId)
	);

	if (!$documentResult || !$attachmentResult || !$fileRelationResult || !$recordRelationResult
		|| !$adb->num_rows($documentResult) || !$adb->num_rows($attachmentResult)
		|| !$adb->num_rows($fileRelationResult) || !$adb->num_rows($recordRelationResult)) {
		return false;
	}

	$storedPath = $adb->query_result($attachmentResult, 0, 'path');
	$storedName = $adb->query_result($attachmentResult, 0, 'name');
	global $root_directory;
	$storageDirectory = $storedPath;
	if (!preg_match('/^(?:[A-Za-z]:[\/\\\\]|\/|\\\\\\\\)/', $storageDirectory)) {
		$storageDirectory = rtrim($root_directory, '/\\') . DIRECTORY_SEPARATOR . $storageDirectory;
	}
	$physicalPath = rtrim($storageDirectory, '/\\') . DIRECTORY_SEPARATOR . $attachmentId . '_' . $storedName;
	if (!is_file($physicalPath) || filesize($physicalPath) < 1) {
		return false;
	}

	return array(
		'name' => $adb->query_result($attachmentResult, 0, 'name')
	);
}

/**
 * Resolves the authenticated user ID formats used by berliCRM web services.
 *
 * The Users object stores its database ID as a number; reference fields require
 * the web-service ID format, for example "19x1".
 *
 * @param Users $user Authenticated berliCRM web-service user.
 * @return array|false Database and web-service user IDs.
 */
function berli_wordMailMergeGetUserIds($user) {
	global $adb, $current_user;

	$usersObject = VtigerWebserviceObject::fromName($adb, 'Users');
	$userEntityId = $usersObject->getEntityId();
	$candidates = array();
	if (is_object($user) && isset($user->id)) {
		$candidates[] = $user->id;
	}
	if (isset($current_user) && is_object($current_user) && isset($current_user->id)) {
		$candidates[] = $current_user->id;
	}

	foreach ($candidates as $candidate) {
		if (is_numeric($candidate) && ctype_digit((string) $candidate) && (int) $candidate > 0) {
			$userId = (int) $candidate;
			return array(
				'id' => $userId,
				'webserviceId' => vtws_getId($userEntityId, $userId)
			);
		}

		if (is_string($candidate)) {
			$parts = vtws_getIdComponents($candidate);
			if (count($parts) === 2 && (string) $parts[0] === (string) $userEntityId
				&& ctype_digit($parts[1]) && (int) $parts[1] > 0) {
				return array(
					'id' => (int) $parts[1],
					'webserviceId' => vtws_getId($userEntityId, (int) $parts[1])
				);
			}
		}
	}

	return false;
}

/**
 * Links a document to a CRM record after checking the user's web-service access.
 *
 * @param string $documentId Web-service ID of the document.
 * @param string $relatedRecordId Web-service ID of the CRM record.
 * @param string $expectedModule Expected CRM record module.
 * @param Users $user Authenticated berliCRM web-service user.
 * @return void
 * @throws WebServiceException If either record is inaccessible or invalid.
 */
function berli_wordMailMergeRelateDocument($documentId, $relatedRecordId, $expectedModule, $user) {
	global $adb;

	$documentParts = berli_wordMailMergeGetIdParts($documentId);
	$relatedParts = berli_wordMailMergeGetIdParts($relatedRecordId);
	$documentWebserviceObject = VtigerWebserviceObject::fromId($adb, $documentParts[0]);
	$documentHandler = berli_wordMailMergeCreateHandler($documentWebserviceObject, $user);
	$documentMeta = $documentHandler->getMeta();
	$documentModule = $documentMeta->getObjectEntityName($documentId);
	$types = vtws_listtypes(null, $user);

	if ($documentModule !== 'Documents' || !in_array('Documents', $types['types'], true)
		|| $documentModule !== $documentWebserviceObject->getEntityName()) {
		throw new WebServiceException(WebServiceErrorCode::$INVALIDID, berli_wordT('THE_SPECIFIED_DOCUMENT_ID_IS_INVALID'));
	}
	if ($documentMeta->hasReadAccess() !== true || $documentMeta->hasWriteAccess() !== true
		|| !$documentMeta->hasPermission(EntityMeta::$RETRIEVE, $documentId)) {
		throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, berli_wordT('YOU_DO_NOT_HAVE_PERMISSION_TO_LINK_THIS_CRM_DOCUMENT'));
	}

	if (!$documentMeta->exists($documentParts[1])) {
		throw new WebServiceException(WebServiceErrorCode::$RECORDNOTFOUND, berli_wordT('THE_CRM_DOCUMENT_WAS_NOT_FOUND'));
	}

	$relatedWebserviceObject = VtigerWebserviceObject::fromId($adb, $relatedParts[0]);
	$relatedHandler = berli_wordMailMergeCreateHandler($relatedWebserviceObject, $user);
	$relatedMeta = $relatedHandler->getMeta();
	$relatedModule = $relatedMeta->getObjectEntityName($relatedRecordId);
	if ($relatedModule !== $expectedModule || !in_array($relatedModule, $types['types'], true)) {
		throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, berli_wordT('THE_TARGET_RECORD_IS_NOT_AVAILABLE_FOR_THIS_LINK'));
	}
try {
    vtws_retrieve($relatedRecordId, $user);
} catch (Throwable $e) {
    error_log('Word mail-merge relation access denied for ' . $expectedModule . ' record ' . $relatedRecordId . ': ' . $e->getMessage());
    throw new WebServiceException(
        WebServiceErrorCode::$ACCESSDENIED,
        berli_wordT('YOU_DO_NOT_HAVE_PERMISSION_TO_ACCESS_THE_TARGET_RECORD')
    );
}
	if (!$relatedMeta->exists($relatedParts[1])) {
		throw new WebServiceException(WebServiceErrorCode::$RECORDNOTFOUND, berli_wordT('THE_TARGET_RECORD_WAS_NOT_FOUND'));
	}

	$documentObject = new VtigerCRMObject($documentMeta->getTabId(), true);
	$documentObject->setObjectId($documentParts[1]);
	$documentRecord = $documentObject->getInstance();
	$documentRecord->insertintonotesrel($relatedParts[1], $documentParts[1]);
	VTWS_PreserveGlobal::flush();
}

/**
 * Splits and validates a web-service record ID.
 *
 * @param string $webserviceId CRM web-service ID in the form "entityIdxrecordId".
 * @return array Entity type ID and numeric record ID.
 * @throws WebServiceException If the ID is malformed.
 */
function berli_wordMailMergeGetIdParts($webserviceId) {
	$parts = vtws_getIdComponents($webserviceId);
	if (count($parts) !== 2 || !ctype_digit((string) $parts[0]) || !ctype_digit((string) $parts[1])) {
		throw new WebServiceException(WebServiceErrorCode::$INVALIDID, berli_wordT('INVALID_CRM_RECORD_ID'));
	}
	return array((int) $parts[0], (int) $parts[1]);
}

/**
 * Creates a web-service handler for an entity type ID.
 *
 * The CRM helper vtws_getModuleHandlerFromId expects an entity type ID, not
 * the full "entityIdxrecordId" ID used by retrieve and permission checks.
 *
 * @param VtigerWebserviceObject $webserviceObject CRM entity type metadata.
 * @param Users $user Authenticated CRM user.
 * @return WebserviceEntityOperation
 */
function berli_wordMailMergeCreateHandler($webserviceObject, $user) {
	global $adb, $log;

	$handlerPath = $webserviceObject->getHandlerPath();
	$handlerClass = $webserviceObject->getHandlerClass();
	require_once $handlerPath;
	return new $handlerClass($webserviceObject, $user, $adb, $log);
}

/**
 * Removes the file and CRM records created before a failed save operation.
 *
 * @param int $documentId Numeric CRM document ID.
 * @param int|null $attachmentId Attachment entity ID, if one was created.
 * @return void
 */
function berli_wordMailMergeCleanup($documentId, $attachmentId) {
	global $adb;

	if ($attachmentId !== null) {
		$fileResult = $adb->pquery(
			'SELECT path, name FROM vtiger_attachments WHERE attachmentsid = ?',
			array($attachmentId)
		);
		if ($fileResult && $adb->num_rows($fileResult)) {
			$filePath = $adb->query_result($fileResult, 0, 'path');
			$storedName = $adb->query_result($fileResult, 0, 'name');
			global $root_directory;
            if (!preg_match('/^(?:[A-Za-z]:[\/\\\\]|\/|\\\\\\\\)/', $filePath)) {
                $filePath = rtrim($root_directory, '/\\') . DIRECTORY_SEPARATOR . $filePath;
            }
            $physicalPath = rtrim($filePath, '/\\') . DIRECTORY_SEPARATOR . $attachmentId . '_' . $storedName;
			if (is_file($physicalPath) && !unlink($physicalPath)) {
            	error_log('Word mail-merge cleanup could not remove file ' . $physicalPath . '.');
			}
		}
		$cleanupQueries = array(
			array('DELETE FROM vtiger_seattachmentsrel WHERE crmid = ? AND attachmentsid = ?', array($documentId, $attachmentId)),
			array('DELETE FROM vtiger_attachments WHERE attachmentsid = ?', array($attachmentId)),
			array('DELETE FROM vtiger_crmentity WHERE crmid = ?', array($attachmentId))
		);
		foreach ($cleanupQueries as $cleanupQuery) {
			if ($adb->pquery($cleanupQuery[0], $cleanupQuery[1]) === false) {
				error_log('Word mail-merge cleanup failed for attachment ' . $attachmentId . '.');
			}
		}
	}

	try {
		$documentRecord = Vtiger_Record_Model::getInstanceById($documentId, 'Documents');
		if ($documentRecord) {
			$documentRecord->delete();
		}
	} catch (Exception $cleanupError) {
		error_log('Word mail-merge cleanup failed for document ' . $documentId . ': ' . $cleanupError->getMessage());
	}
}

<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0.
 * The Original Code is: berliCRM Open Source.
 *************************************************************************************/

require_once 'include/Webservices/Utils.php';
require_once 'modules/Documents/Documents.php';

/**
 * Generate an Invoice PDF, ZUGFeRD PDF or XRechnung XML as an active internal Document.
 *
 * Handles the authenticated POST operation "createinvoicepdf" using the existing
 * Invoice PDF template. Each call creates a new Document linked to the Invoice
 * and its assigned Contact. Without a Contact, only the Invoice is linked.
 * Requires read access to the Invoice and Contact, Document creation permission,
 * and a populated invoice due date. Database changes are rolled back and the
 * generated file is removed on storage errors; tables must support transactions.
 *
 * @param string|int $id Invoice webservice ID (e.g. "7x144420") or numeric CRM ID.
 * @param string|null $output_format Optional pdf (default), zugferd or xrechnung.
 * @param Users|null $user Authenticated CRM user supplied by the webservice dispatcher.
 *
 * @return array{
 *     invoice_id: string,
 *     contact_id: string|null,
 *     document_id: string,
 *     document_crmid: int,
 *     filename: string,
 *     format: string,
 *     mime_type: string
 * } Result payload containing webservice IDs, the numeric Document ID, and the
 *   PDF filename. The dispatcher adds the enclosing webservice response.
 *
 * @throws WebServiceException If an ID or Contact reference is invalid, a record
 *                            is missing or deleted, access is denied, the due date
 *                            is missing, or PDF generation or persistence fails.
 *
 * @berli-openapi-begin
 * {"summary":"Create an invoice export document","description":"Generates a PDF, ZUGFeRD PDF or XRechnung XML and stores it as a Document linked to the Invoice.","tags":["Inventory"],"parameters":{"id":{"required":true,"description":"Invoice webservice ID or numeric CRM ID.","example":"7x144420"},"output_format":{"required":false,"description":"Export format.","example":"pdf","default":"pdf","schema":{"type":"string","enum":["pdf","zugferd","xrechnung"]}}},"result":{"schema":{"type":"object","required":["invoice_id","document_id","document_crmid","filename","format","mime_type"],"properties":{"invoice_id":{"type":"string"},"contact_id":{"type":"string","nullable":true},"document_id":{"type":"string"},"document_crmid":{"type":"integer"},"filename":{"type":"string"},"format":{"type":"string"},"mime_type":{"type":"string"}}},"example":{"invoice_id":"7x144420","contact_id":"12x345","document_id":"15x900","document_crmid":900,"filename":"INV-100.pdf","format":"pdf","mime_type":"application/pdf"}}}
 * @berli-openapi-end
 */
function berli_createInvoicePdf($id, $output_format = null, $user = null) {
    global $adb, $current_user, $log;

    // Preserve direct PHP callers of the original two-argument handler.
    if (is_object($output_format) && $user === null) {
        $user = $output_format;
        $output_format = null;
    }
    $format = $output_format === null || $output_format === '' ? 'pdf' : $output_format;
    if (!is_string($format) || !in_array($format, array('pdf', 'zugferd', 'xrechnung'), true)) {
        throw new WebServiceException(WebServiceErrorCode::$INVALIDID, 'output_format must be pdf, zugferd or xrechnung.');
    }
    if (!is_object($user) || empty($user->id)) {
        throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, 'An authenticated CRM user is required.');
    }
    $mimeType = $format === 'xrechnung' ? 'application/xml' : 'application/pdf';
    $id = trim((string)$id);
    if (!preg_match('/^(?:[1-9][0-9]*x)?[1-9][0-9]*$/D', $id)) {
        throw new WebServiceException(WebServiceErrorCode::$INVALIDID, 'Expected an Invoice ID.');
    }
    $parts = explode('x', $id);
    $invoiceId = (int)end($parts);
    $invoiceWsId = vtws_getWebserviceEntityId('Invoice', $invoiceId);
    if (count($parts) === 2 && $id !== $invoiceWsId) {
        throw new WebServiceException(WebServiceErrorCode::$INVALIDID, 'ID does not belong to the Invoice module.');
    }

    $invoiceMeta = vtws_getModuleHandlerFromName('Invoice', $user)->getMeta();
    if (!$invoiceMeta->exists($invoiceId)) {
        throw new WebServiceException(WebServiceErrorCode::$RECORDNOTFOUND, 'Invoice was not found or is deleted.');
    }
    if (!$invoiceMeta->hasReadAccess() || !$invoiceMeta->hasPermission(EntityMeta::$RETRIEVE, $invoiceWsId)) {
        throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, 'Permission to read this Invoice is denied.');
    }
    $documentsMeta = vtws_getModuleHandlerFromName('Documents', $user)->getMeta();
    if (!$documentsMeta->hasCreateAccess()) {
        throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, 'Permission to create Documents is denied.');
    }

    $result = berli_invoicePdfQuery(
        'SELECT invoice_no, contactid, duedate FROM vtiger_invoice WHERE invoiceid = ?', array($invoiceId)
    );
    if (!$adb->num_rows($result)) {
        throw new WebServiceException(WebServiceErrorCode::$RECORDNOTFOUND, 'Invoice was not found.');
    }
    $invoiceNo = $adb->query_result($result, 0, 'invoice_no');
    $contactId = (int)$adb->query_result($result, 0, 'contactid');
    $dueDate = $adb->query_result($result, 0, 'duedate');
    if (empty($dueDate) || $dueDate === '0000-00-00') {
        throw new WebServiceException(WebServiceErrorCode::$MANDFIELDSMISSING, 'Invoice due date is required to create the PDF.');
    }

    $contactWsId = null;
    if ($contactId > 0) {
        $contactWsId = vtws_getWebserviceEntityId('Contacts', $contactId);
        $contactMeta = vtws_getModuleHandlerFromName('Contacts', $user)->getMeta();
        if (!$contactMeta->exists($contactId)) {
            throw new WebServiceException(WebServiceErrorCode::$REFERENCEINVALID, 'The Invoice Contact was not found or is deleted.');
        }
        if (!$contactMeta->hasReadAccess() || !$contactMeta->hasPermission(EntityMeta::$RETRIEVE, $contactWsId)) {
            throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, 'Permission to access the Invoice Contact is denied.');
        }
    }

    // Documents::save() handles uploaded files automatically. This operation
    // stores only the generated PDF, even if a caller submitted multipart data.
    $previousFiles = $_FILES;
    $previousRequest = $_REQUEST;
    $previousPost = $_POST;
    $previousGet = $_GET;
    $previousUser = $current_user;
    $bufferLevel = ob_get_level();
    $transactionStarted = false;
    $completionAttempted = false;
    $completionResult = null;
    $entryDepth = (int)$adb->database->transOff;
    $attachmentId = null;
    $uploadPath = null;
    try {
        $_FILES = array();
        $_REQUEST = $_POST = $_GET = array();
        $current_user = $user;
        ob_start();
        require_once 'modules/Invoice/pdfcreator.php';
        $uploadPath = decideFilePath();
        if (!is_dir($uploadPath) || !is_writable($uploadPath)) {
            throw new RuntimeException('The PDF storage directory is not writable.');
        }
        $attachmentId = $adb->getUniqueID('vtiger_crmentity');
        $filename = createpdffile($invoiceId, 'save', $uploadPath, $attachmentId, $format);
        if (!is_string($filename) || $filename === '' || basename($filename) !== $filename) {
            throw new RuntimeException('PDF generation did not return a valid filename.');
        }
        $pdfPath = $uploadPath . $attachmentId . '_' . $filename;
        if (!is_file($pdfPath) || filesize($pdfPath) === 0 || ($format === 'xrechnung' ? strpos(file_get_contents($pdfPath), 'CrossIndustryInvoice') === false : file_get_contents($pdfPath, false, null, 0, 5) !== '%PDF-')) {
            throw new RuntimeException('PDF generation did not create a valid PDF file.');
        }
        $filesize = filesize($pdfPath);

        $adb->startTransaction();
        $transactionStarted = true;
        $folderId = berli_invoicePdfFolderId($user);
        $description = 'PDF zu Rechnung ' . $invoiceNo;
        $document = new Documents();
        // Relate the Contact before aftersave handlers copy its paper-invoice flag.
        $document->parentid = $contactId > 0 ? $contactId : $invoiceId;
        $document->column_fields['assigned_user_id'] = $user->id;
        $document->column_fields['notes_title'] = vtranslate('SINGLE_Invoice', 'Invoice') . ' ' . $invoiceNo . ' (' . date('Y-m-d H:i:s') . ')';
        $document->column_fields['filename'] = $filename;
        $document->column_fields['filesize'] = $filesize;
        $document->column_fields['filetype'] = $mimeType;
        $document->column_fields['filelocationtype'] = 'I';
        $document->column_fields['filestatus'] = 1;
        $document->column_fields['folderid'] = $folderId;
        $document->column_fields['description'] = $description;
        $document->save('Documents');
        if (empty($document->id)) {
            throw new RuntimeException('The PDF Document could not be saved.');
        }
        $documentId = (int)$document->id;
        $now = date('Y-m-d H:i:s');
        berli_invoicePdfQuery(
            'INSERT INTO vtiger_crmentity (crmid, smcreatorid, smownerid, setype, description, createdtime, modifiedtime, presence, deleted) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            array($attachmentId, $user->id, $user->id, 'Documents Attachment', $description, $now, $now, 1, 0)
        );
        berli_invoicePdfQuery(
            'INSERT INTO vtiger_attachments (attachmentsid, name, description, type, path) VALUES (?, ?, ?, ?, ?)',
            array($attachmentId, $filename, $description, $mimeType, $uploadPath)
        );
        berli_invoicePdfQuery(
            'INSERT INTO vtiger_seattachmentsrel (crmid, attachmentsid) VALUES (?, ?)', array($documentId, $attachmentId)
        );
        foreach (array_unique(array_filter(array($invoiceId, $contactId))) as $relatedId) {
            berli_invoicePdfQuery(
                'INSERT INTO vtiger_senotesrel (crmid, notesid) SELECT ?, ? WHERE NOT EXISTS (SELECT 1 FROM vtiger_senotesrel WHERE crmid = ? AND notesid = ?)',
                array($relatedId, $documentId, $relatedId, $documentId)
            );
        }
        berli_invoicePdfQuery(
            'UPDATE vtiger_notes SET filename = ?, filesize = ?, filetype = ?, filelocationtype = ?, filestatus = ?, notecontent = ? WHERE notesid = ?',
            array($filename, $filesize, $mimeType, 'I', 1, $description, $documentId)
        );
        $response = array(
            'invoice_id' => $invoiceWsId,
            'contact_id' => $contactWsId,
            'document_id' => vtws_getWebserviceEntityId('Documents', $documentId),
            'document_crmid' => $documentId,
            'filename' => $filename,
            'format' => $format,
            'mime_type' => $mimeType,
        );
        if ($adb->hasFailedTransaction()) {
            throw new RuntimeException('The PDF Document transaction failed.');
        }
        $completionAttempted = true;
        $completionResult = berli_invoicePdfCompleteTransaction($adb);
        if ($completionResult !== true) {
            throw new RuntimeException('The PDF Document transaction did not complete successfully.');
        }
        $transactionStarted = false;
        return $response;
    } catch (Throwable $e) {
        // A completion exception after the final smart level closed has an
        // unknown commit outcome. Keep the file rather than break a committed
        // attachment; reconciliation is required and no success is returned.
        $removeFile = !($completionAttempted && $completionResult === null && (int)$adb->database->transOff <= $entryDepth);
        if ($transactionStarted) {
            try { berli_invoicePdfAbortTransaction($adb, $entryDepth); }
            catch (Throwable $cleanupError) {
                $removeFile = false;
                if (isset($log)) $log->error('createinvoicepdf rollback: ' . $cleanupError->getMessage());
            }
        }
        // The PDF creator may have written a file before throwing an exception.
        if ($removeFile && $attachmentId !== null && $uploadPath !== null) {
            foreach (glob($uploadPath . $attachmentId . '_*') ?: array() as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
        if (isset($log)) {
            $log->error('createinvoicepdf: Invoice ' . $invoiceId . ': ' . $e->getMessage());
        }
        if ($e instanceof WebServiceException) {
            throw $e;
        }
        throw new WebServiceException(WebServiceErrorCode::$INTERNALERROR, 'The Invoice PDF could not be created or saved. See the CRM log for details.');
    } finally {
        while (ob_get_level() > $bufferLevel) {
            ob_end_clean();
        }
        $_FILES = $previousFiles;
        $_REQUEST = $previousRequest;
        $_POST = $previousPost;
        $_GET = $previousGet;
        $current_user = $previousUser;
    }
}

/** Preserve ADODB's completion result (PearDatabase discards it).
 * A nested true is not a commit. Upstream mysqli CommitTrans currently ignores
 * the COMMIT Execute result: this check cannot guarantee durability on that driver.
 */
function berli_invoicePdfCompleteTransaction($adb) {
    return $adb->database->CompleteTrans();
}

/** Unwind only smart-transaction levels acquired by this operation/save handlers.
 * ADODB nested CompleteTrans returns true even after FailTrans, so keep unwinding
 * to the captured entry depth. The caller's outer level remains open and failed.
 */
function berli_invoicePdfAbortTransaction($adb, $entryDepth) {
    $connection = $adb->database;
    if ((int)$connection->transOff === 0 && $entryDepth === 0) {
        // Failed/exceptional final completion may have closed smart bookkeeping
        // without closing the SQL transaction. Best-effort rollback, never caller-owned.
        if ($connection->RollbackTrans() !== true) throw new RuntimeException('Invoice PDF rollback failed.');
        return;
    }
    $connection->FailTrans();
    while ((int)$connection->transOff > $entryDepth) {
        $before = (int)$connection->transOff;
        try {
            $connection->CompleteTrans();
        } catch (Throwable $cleanupError) {
            // Do not spin forever or replace the original storage error.
            if ((int)$connection->transOff >= $before) throw $cleanupError;
        }
        if ((int)$connection->transOff >= $before) {
            throw new RuntimeException('Cannot unwind Invoice PDF transaction.');
        }
    }
}

/**
 * Execute a parameterized query and report database failures as webservice errors.
 *
 * @param string $sql SQL statement with placeholders for bound parameters.
 * @param array $params Parameter values passed to PearDatabase::pquery().
 *
 * @return mixed Successful query result returned by PearDatabase::pquery().
 *
 * @throws WebServiceException With DATABASE_QUERY_ERROR if the query returns false.
 */
function berli_invoicePdfQuery($sql, $params) {
    global $adb;
    $result = $adb->pquery($sql, $params);
    if ($result === false) {
        throw new WebServiceException(WebServiceErrorCode::$DATABASEQUERYERROR, 'Could not save or read Invoice PDF data.');
    }
    return $result;
}

/**
 * Find or create the translated folder used by the Invoice save-PDF action.
 *
 * Creates a missing folder on behalf of the supplied user. The caller manages
 * the surrounding transaction.
 *
 * @param Users $user Authenticated CRM user recorded as the folder creator.
 *
 * @return int Existing or newly created Document folder ID.
 *
 * @throws WebServiceException If reading or creating the folder fails.
 */
function berli_invoicePdfFolderId($user) {
    global $adb;
    $folderName = vtranslate('LBL_PDF_FOLDERNAME', 'Invoice');
    $result = berli_invoicePdfQuery('SELECT folderid FROM vtiger_attachmentsfolder WHERE foldername = ? ORDER BY folderid', array($folderName));
    if ($adb->num_rows($result)) {
        return (int)$adb->query_result($result, 0, 'folderid');
    }
    $result = berli_invoicePdfQuery('SELECT MAX(folderid) AS folderid, MAX(sequence) AS sequence FROM vtiger_attachmentsfolder', array());
    $folderId = (int)$adb->query_result($result, 0, 'folderid') + 1;
    $sequence = (int)$adb->query_result($result, 0, 'sequence') + 1;
    berli_invoicePdfQuery(
        'INSERT INTO vtiger_attachmentsfolder (folderid, foldername, description, createdby, sequence) VALUES (?, ?, ?, ?, ?)',
        array($folderId, $folderName, vtranslate('LBL_PDF_FOLDERDES_DETAIL', 'Invoice'), $user->id, $sequence)
    );
    return $folderId;
}

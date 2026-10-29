<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0.
 * The Original Code is: berliCRM Open Source.
 *************************************************************************************/

require_once 'modules/Documents/Documents.php';
require_once 'modules/Documents/models/Folder.php';
if (!class_exists('TCPDF')) {
    require_once 'vendor/autoload.php';
}

/**
 * Resolves the Account belonging to an email recipient and creates a stored PDF
 * snapshot which can safely be attached to an outgoing email.
 */
class Accounts_AccountDatasheet_Service {

    public const PLACEHOLDER = '$custom-accountdatasheet$';
    public const FOLDER_NAME = 'Account-Datenblätter';
    public const TITLE_PREFIX = 'Automatisches Account-Datenblatt';
    public const MIME_TYPE = 'application/pdf';

    /**
     * Resolve the Account for the record currently used as email recipient context.
     */
    public static function resolveAccountId($moduleName, $recordId) {
        $recordId = self::normalizeRecordId($recordId);
        if ($recordId <= 0 || empty($moduleName) || $moduleName === 'Users') {
            return null;
        }

        if ($moduleName === 'Accounts') {
            return self::isReadableAccount($recordId) ? $recordId : null;
        }

        try {
            $recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
        } catch (Throwable $e) {
            return null;
        }

        $candidateId = null;
        if ($moduleName === 'Contacts') {
            $candidateId = $recordModel->get('account_id');
        } elseif ($moduleName === 'Potentials') {
            $candidateId = $recordModel->get('related_to');
        } elseif ($moduleName === 'HelpDesk') {
            $parentId = self::normalizeRecordId($recordModel->get('parent_id'));
            $parentType = self::getActiveEntityType($parentId);
            if ($parentType === 'Accounts') {
                $candidateId = $parentId;
            } elseif ($parentType === 'Contacts') {
                try {
                    $contact = Vtiger_Record_Model::getInstanceById($parentId, 'Contacts');
                    $candidateId = $contact->get('account_id');
                } catch (Throwable $e) {
                    $candidateId = null;
                }
            }
        } else {
            // Inventory records and custom modules commonly expose account_id.
            $candidateId = $recordModel->get('account_id');
        }

        $candidateId = self::normalizeRecordId($candidateId);
        return self::isReadableAccount($candidateId) ? $candidateId : null;
    }

    /**
     * Create the PDF, persist it as a Document and relate it to Account and Email.
     *
     * @return array{account_id:int,document_id:int,attachment_id:int,filename:string,path:string,type:string}
     */
    public static function createDocument($accountId, $emailId) {
        global $adb, $current_user;

        $accountId = self::normalizeRecordId($accountId);
        $emailId = self::normalizeRecordId($emailId);
        if (!self::isReadableAccount($accountId)) {
            throw new RuntimeException('Account is missing or not readable.');
        }
        if (!Users_Privileges_Model::isPermitted('Documents', 'CreateView')) {
            throw new RuntimeException('Permission to create Documents is denied.');
        }
        if (empty($current_user) || empty($current_user->id)) {
            throw new RuntimeException('A current CRM user is required.');
        }

        $account = Vtiger_Record_Model::getInstanceById($accountId, 'Accounts');
        $accountName = trim(decode_html((string)$account->get('accountname')));
        if ($accountName === '') {
            $accountName = 'Account-' . $accountId;
        }

        $storagePath = decideFilePath();
        $rootDirectory = rtrim((string)vglobal('root_directory'), '/\\') . '/';
        $absoluteDirectory = $rootDirectory . $storagePath;
        if (!is_dir($absoluteDirectory) || !is_writable($absoluteDirectory)) {
            throw new RuntimeException('PDF storage directory is not writable.');
        }

        $attachmentId = (int)$adb->getUniqueID('vtiger_crmentity');
        $filename = self::buildFilename($accountName);
        $absolutePath = $absoluteDirectory . $attachmentId . '_' . $filename;
        $transactionStarted = false;
        $entryDepth = isset($adb->database->transOff) ? (int)$adb->database->transOff : 0;

        try {
            self::renderPdf($account, $absolutePath);
            if (!is_file($absolutePath) || filesize($absolutePath) <= 0 || file_get_contents($absolutePath, false, null, 0, 5) !== '%PDF-') {
                throw new RuntimeException('Generated Account datasheet is not a valid PDF.');
            }

            $fileSize = filesize($absolutePath);
            $adb->startTransaction();
            $transactionStarted = true;
            $folderId = self::getOrCreateFolderId();
            $createdAt = date('Y-m-d H:i:s');
            $title = self::TITLE_PREFIX . ': ' . $accountName . ' (' . $createdAt . ')';
            $description = 'Automatisch für den E-Mail-Versand erzeugter Snapshot für ' . $accountName . '.';

            $document = new Documents();
            $document->parentid = $accountId;
            $document->column_fields['assigned_user_id'] = $current_user->id;
            $document->column_fields['notes_title'] = $title;
            $document->column_fields['filename'] = $filename;
            $document->column_fields['filesize'] = $fileSize;
            $document->column_fields['filetype'] = self::MIME_TYPE;
            $document->column_fields['filelocationtype'] = 'I';
            $document->column_fields['filestatus'] = 1;
            $document->column_fields['folderid'] = $folderId;
            $document->column_fields['description'] = $description;
            $document->save('Documents');
            if (empty($document->id)) {
                throw new RuntimeException('Account datasheet Document could not be saved.');
            }
            $documentId = (int)$document->id;

            self::query(
                'INSERT INTO vtiger_crmentity (crmid, smcreatorid, smownerid, setype, description, createdtime, modifiedtime, presence, deleted) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                array($attachmentId, $current_user->id, $current_user->id, 'Documents Attachment', $description, $createdAt, $createdAt, 1, 0)
            );
            self::query(
                'INSERT INTO vtiger_attachments (attachmentsid, name, description, type, path) VALUES (?, ?, ?, ?, ?)',
                array($attachmentId, $filename, $description, self::MIME_TYPE, $storagePath)
            );
            self::query(
                'INSERT INTO vtiger_seattachmentsrel (crmid, attachmentsid) VALUES (?, ?)',
                array($documentId, $attachmentId)
            );

            foreach (array_unique(array_filter(array($accountId, $emailId))) as $relatedId) {
                self::query(
                    'INSERT INTO vtiger_senotesrel (crmid, notesid) SELECT ?, ? WHERE NOT EXISTS (SELECT 1 FROM vtiger_senotesrel WHERE crmid = ? AND notesid = ?)',
                    array($relatedId, $documentId, $relatedId, $documentId)
                );
            }
            self::query(
                'UPDATE vtiger_notes SET filename = ?, filesize = ?, filetype = ?, filelocationtype = ?, filestatus = ?, notecontent = ? WHERE notesid = ?',
                array($filename, $fileSize, self::MIME_TYPE, 'I', 1, $description, $documentId)
            );

            if ($adb->hasFailedTransaction()) {
                throw new RuntimeException('Account datasheet transaction failed.');
            }
            $adb->completeTransaction();
            $transactionStarted = false;

            return array(
                'account_id' => $accountId,
                'document_id' => $documentId,
                'attachment_id' => $attachmentId,
                'filename' => $filename,
                'path' => $absolutePath,
                'type' => self::MIME_TYPE,
            );
        } catch (Throwable $e) {
            if ($transactionStarted) {
                self::rollbackTransaction($entryDepth);
            }
            if (is_file($absolutePath)) {
                @unlink($absolutePath);
            }
            throw $e;
        }
    }

    /** Render an A4 Account overview using only fields visible to the current user. */
    public static function renderPdf($account, $absolutePath) {
        $accountName = self::displayValue($account, 'accountname');
        if ($accountName === '') {
            $accountName = 'Account-' . (int)$account->getId();
        }
        $sections = array(
            'Stammdaten' => array(
                'Account-Nr.' => self::displayValue($account, 'account_no'),
                'Typ' => self::displayValue($account, 'account_type'),
                'Branche' => self::displayValue($account, 'industry'),
                'Bewertung' => self::displayValue($account, 'rating'),
                'Eigentümer' => self::displayValue($account, 'ownership'),
                'Mitarbeiter' => self::displayValue($account, 'employees'),
                'Jahresumsatz' => self::displayValue($account, 'annual_revenue'),
                'Zuständig' => self::displayValue($account, 'assigned_user_id'),
            ),
            'Kontakt' => array(
                'Telefon' => self::displayValue($account, 'phone'),
                'Weiteres Telefon' => self::displayValue($account, 'otherphone'),
                'E-Mail' => self::displayValue($account, 'email1'),
                'Weitere E-Mail' => self::displayValue($account, 'email2'),
                'Fax' => self::displayValue($account, 'fax'),
                'Website' => self::displayValue($account, 'website'),
            ),
            'Rechnungsadresse' => array('Adresse' => self::formatAddress($account, 'bill_')),
            'Lieferadresse' => array('Adresse' => self::formatAddress($account, 'ship_')),
        );
        self::renderPdfData(
            $accountName,
            (int)$account->getId(),
            $sections,
            self::displayValue($account, 'description'),
            $absolutePath
        );
    }

    /** Render already prepared data; public to allow layout tests without database writes. */
    public static function renderPdfData($accountName, $accountId, $sections, $description, $absolutePath) {
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(16, 15, 16);
        $pdf->SetAutoPageBreak(true, 18);
        $pdf->SetFont('dejavusans', '', 9);
        $pdf->SetTitle('Account-Datenblatt - ' . $accountName);
        $pdf->SetAuthor('berliCRM');
        $pdf->AddPage();

        $pdf->SetFillColor(31, 78, 121);
        $pdf->Rect(0, 0, 210, 35, 'F');
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('dejavusans', 'B', 21);
        $pdf->SetXY(16, 11);
        $pdf->Cell(178, 9, 'Account-Datenblatt', 0, 1, 'L');
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->SetX(16);
        $pdf->Cell(178, 6, $accountName, 0, 1, 'L');
        $pdf->SetY(43);
        $pdf->SetTextColor(38, 50, 56);

        foreach ($sections as $heading => $rows) {
            self::writeSection($pdf, $heading, $rows);
        }

        if ($description !== '') {
            self::writeSection($pdf, 'Beschreibung', array('' => $description));
        }

        $pdf->SetAutoPageBreak(false, 0);
        $pdf->SetY(-14);
        $pdf->SetDrawColor(205, 211, 216);
        $pdf->Line(16, $pdf->GetY(), 194, $pdf->GetY());
        $pdf->Ln(2);
        $pdf->SetTextColor(100, 110, 116);
        $pdf->SetFont('dejavusans', '', 7.5);
        $pdf->Cell(120, 5, 'Erstellt am ' . date('d.m.Y H:i'), 0, 0, 'L');
        $pdf->Cell(58, 5, 'Account-ID ' . (int)$accountId, 0, 0, 'R');
        $pdf->Output($absolutePath, 'F');
    }

    private static function writeSection($pdf, $heading, $rows) {
        $filtered = array_filter($rows, static function ($value) {
            return trim((string)$value) !== '';
        });
        if (empty($filtered)) {
            return;
        }

        if ($pdf->GetY() > 250) {
            $pdf->AddPage();
        }
        $pdf->SetFillColor(231, 238, 245);
        $pdf->SetTextColor(31, 78, 121);
        $pdf->SetFont('dejavusans', 'B', 10.5);
        $pdf->Cell(178, 7, $heading, 0, 1, 'L', true);
        $pdf->Ln(1.5);

        foreach ($filtered as $label => $value) {
            $value = trim((string)$value);
            $pdf->SetTextColor(75, 85, 90);
            $pdf->SetFont('dejavusans', 'B', 8.5);
            if ($label !== '') {
                $pdf->MultiCell(45, 5.5, $label, 0, 'L', false, 0);
                $pdf->SetTextColor(35, 42, 46);
                $pdf->SetFont('dejavusans', '', 8.5);
                $pdf->MultiCell(133, 5.5, $value, 0, 'L', false, 1);
            } else {
                $pdf->SetTextColor(35, 42, 46);
                $pdf->SetFont('dejavusans', '', 8.5);
                $pdf->MultiCell(178, 5.5, $value, 0, 'L', false, 1);
            }
        }
        $pdf->Ln(3);
    }

    private static function displayValue($record, $fieldName) {
        $moduleModel = $record->getModule();
        $fieldModel = Vtiger_Field_Model::getInstance($fieldName, $moduleModel);
        if (!$fieldModel || !$fieldModel->isViewable()) {
            return '';
        }
        $rawValue = $record->get($fieldName);
        if ($rawValue === null || $rawValue === '') {
            return '';
        }
        $value = $fieldModel->getDisplayValue($rawValue, $record->getId(), $record);
        $value = preg_replace('/<br\s*\/?\s*>/i', "\n", (string)$value);
        return trim(html_entity_decode(strip_tags((string)$value), ENT_QUOTES, 'UTF-8'));
    }

    private static function formatAddress($account, $prefix) {
        $street = self::displayValue($account, $prefix . 'street');
        $pobox = self::displayValue($account, $prefix . 'pobox');
        $cityLine = trim(implode(' ', array_filter(array(
            self::displayValue($account, $prefix . 'code'),
            self::displayValue($account, $prefix . 'city'),
        ))));
        $regionLine = trim(implode(', ', array_filter(array(
            self::displayValue($account, $prefix . 'state'),
            self::displayValue($account, $prefix . 'country'),
        ))));
        return implode("\n", array_filter(array($street, $pobox, $cityLine, $regionLine)));
    }

    private static function getOrCreateFolderId() {
        global $adb;
        $result = self::query(
            'SELECT folderid FROM vtiger_attachmentsfolder WHERE foldername = ? ORDER BY folderid LIMIT 1',
            array(self::FOLDER_NAME)
        );
        if ($adb->num_rows($result)) {
            return (int)$adb->query_result($result, 0, 'folderid');
        }

        $folder = Documents_Folder_Model::getInstance();
        $folder->set('foldername', self::FOLDER_NAME);
        $folder->set('description', 'Automatisch erzeugte Account-Datenblätter aus E-Mail-Vorlagen');
        $folder->save();
        return (int)$folder->getId();
    }

    private static function buildFilename($accountName) {
        $safeName = preg_replace('/[^\pL\pN_-]+/u', '-', $accountName);
        $safeName = trim((string)$safeName, '-_');
        if ($safeName === '') {
            $safeName = 'Account';
        }
        return 'Account-Datenblatt_' . mb_substr($safeName, 0, 80, 'UTF-8') . '_' . date('Y-m-d_His') . '.pdf';
    }

    private static function normalizeRecordId($recordId) {
        if (is_string($recordId) && strpos($recordId, 'x') !== false) {
            $parts = vtws_getIdComponents($recordId);
            $recordId = $parts[1] ?? 0;
        }
        return ctype_digit((string)$recordId) ? (int)$recordId : 0;
    }

    private static function getActiveEntityType($recordId) {
        global $adb;
        if ($recordId <= 0) {
            return '';
        }
        $result = $adb->pquery('SELECT setype FROM vtiger_crmentity WHERE crmid = ? AND deleted = 0', array($recordId));
        return $adb->num_rows($result) ? (string)$adb->query_result($result, 0, 'setype') : '';
    }

    private static function isReadableAccount($accountId) {
        $accountId = self::normalizeRecordId($accountId);
        return $accountId > 0
            && self::getActiveEntityType($accountId) === 'Accounts'
            && Users_Privileges_Model::isPermitted('Accounts', 'DetailView', $accountId);
    }

    private static function query($sql, $params) {
        global $adb;
        $result = $adb->pquery($sql, $params);
        if ($result === false) {
            throw new RuntimeException('Could not persist Account datasheet data.');
        }
        return $result;
    }

    private static function rollbackTransaction($entryDepth) {
        global $adb;
        $connection = $adb->database;
        $connection->FailTrans();
        while (isset($connection->transOff) && (int)$connection->transOff > $entryDepth) {
            $before = (int)$connection->transOff;
            $connection->CompleteTrans();
            if ((int)$connection->transOff >= $before) {
                break;
            }
        }
    }
}


<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0.
 * The Original Code is: berliCRM Open Source.
 *************************************************************************************/

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Global, module-independent letterhead configuration for inventory PDFs.
 */
class InventoryPdfLetterheadSettings
{
    public const TABLE = 'berli_pdfletterhead';
    public const SINGLETON_ID = 1;

    /** @var bool|null */
    private static $tableAvailable;

    /**
     * Read the configured document IDs. Missing schema is treated as no configuration.
     *
     * @return array{first_document_id:int,continuation_document_id:int}
     */
    public static function getDocumentIds()
    {
        $empty = array('first_document_id' => 0, 'continuation_document_id' => 0);
        if (!self::tableExists()) {
            return $empty;
        }

        global $adb;
        try {
            $result = $adb->pquery(
                'SELECT first_document_id, continuation_document_id FROM ' . self::TABLE . ' WHERE id = ?',
                array(self::SINGLETON_ID)
            );
            if (!$result || $adb->num_rows($result) === 0) {
                return $empty;
            }

            return array(
                'first_document_id' => max(0, (int)$adb->query_result($result, 0, 'first_document_id')),
                'continuation_document_id' => max(0, (int)$adb->query_result($result, 0, 'continuation_document_id')),
            );
        } catch (Throwable $error) {
            self::log('Cannot read letterhead configuration', $error);
            return $empty;
        }
    }

    /**
     * Persist the singleton configuration. Callers must enforce administrator access.
     */
    public static function save($firstDocumentId, $continuationDocumentId, $userId)
    {
        if (!self::tableExists()) {
            throw new RuntimeException('The PDF letterhead configuration table is not installed.');
        }

        $firstDocumentId = self::normalizeId($firstDocumentId);
        $continuationDocumentId = self::normalizeId($continuationDocumentId);
        foreach (array($firstDocumentId, $continuationDocumentId) as $documentId) {
            if ($documentId > 0) {
                $resolved = InventoryPdfLetterheadResolver::resolveDocument($documentId);
                if (!$resolved['valid']) {
                    throw new InvalidArgumentException($resolved['message']);
                }
            }
        }

        global $adb;
        $adb->pquery(
            'INSERT INTO ' . self::TABLE . ' (id, first_document_id, continuation_document_id, updated_by, updated_at) '
            . 'VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE first_document_id = VALUES(first_document_id), '
            . 'continuation_document_id = VALUES(continuation_document_id), updated_by = VALUES(updated_by), updated_at = VALUES(updated_at)',
            array(self::SINGLETON_ID, $firstDocumentId ?: null, $continuationDocumentId ?: null, (int)$userId, date('Y-m-d H:i:s'))
        );
    }

    public static function tableExists()
    {
        if (self::$tableAvailable !== null) {
            return self::$tableAvailable;
        }

        global $adb;
        if (!isset($adb)) {
            return self::$tableAvailable = false;
        }
        try {
            $result = $adb->pquery(
                'SELECT COUNT(*) AS table_count FROM information_schema.tables '
                . 'WHERE table_schema = DATABASE() AND table_name = ?',
                array(self::TABLE)
            );
            return self::$tableAvailable = ($result && (int)$adb->query_result($result, 0, 'table_count') === 1);
        } catch (Throwable $error) {
            self::log('Cannot check letterhead configuration schema', $error);
            return self::$tableAvailable = false;
        }
    }

    public static function resetTableAvailabilityCache()
    {
        self::$tableAvailable = null;
    }

    private static function normalizeId($value)
    {
        if ($value === null || $value === '' || $value === false) {
            return 0;
        }
        if (!is_scalar($value) || !preg_match('/\A[1-9][0-9]*\z/', (string)$value)) {
            throw new InvalidArgumentException('Invalid letterhead Document ID.');
        }
        return (int)$value;
    }

    public static function log($message, ?Throwable $error = null)
    {
        $details = $error ? ': ' . $error->getMessage() : '';
        error_log('Inventory PDF letterhead: ' . $message . $details);
    }
}
/**
 * Resolves CRM Documents to safe, importable, single-page PDF files.
 */
class InventoryPdfLetterheadResolver
{
    /** @var array<int,array<string,mixed>> */
    private static $cache = array();

    /**
     * @return array{valid:bool,id:int,label:string,path:?string,message:string,width:?float,height:?float}
     */
    public static function resolveDocument($documentId)
    {
        $documentId = (int)$documentId;
        if ($documentId <= 0) {
            return self::result(0, '', null, true, '', null, null);
        }
        if (isset(self::$cache[$documentId])) {
            return self::$cache[$documentId];
        }

        global $adb;
        try {
            $result = $adb->pquery(
                'SELECT n.notesid, n.title AS notes_title, n.filename, n.filetype, n.filelocationtype, n.filestatus, '
                . 'a.attachmentsid, a.path, a.name '
                . 'FROM vtiger_notes n INNER JOIN vtiger_crmentity c ON c.crmid = n.notesid AND c.deleted = 0 '
                . 'INNER JOIN vtiger_seattachmentsrel r ON r.crmid = n.notesid '
                . 'INNER JOIN vtiger_attachments a ON a.attachmentsid = r.attachmentsid '
                . 'WHERE n.notesid = ? ORDER BY a.attachmentsid DESC LIMIT 1',
                array($documentId)
            );
            if (!$result || $adb->num_rows($result) === 0) {
                return self::$cache[$documentId] = self::invalid($documentId, '', 'The selected letterhead Document no longer exists.');
            }

            $label = decode_html((string)$adb->query_result($result, 0, 'notes_title'));
            $filename = decode_html((string)$adb->query_result($result, 0, 'name'));
            $filetype = strtolower(trim((string)$adb->query_result($result, 0, 'filetype')));
            if ($adb->query_result($result, 0, 'filelocationtype') !== 'I'
                || !(int)$adb->query_result($result, 0, 'filestatus')) {
                return self::$cache[$documentId] = self::invalid($documentId, $label, 'Letterhead Documents must be active, internally stored files.');
            }
            if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'pdf'
                || ($filetype !== '' && strpos($filetype, 'pdf') === false)) {
                return self::$cache[$documentId] = self::invalid($documentId, $label, 'The selected letterhead Document is not a PDF.');
            }

            $storedPath = rtrim((string)$adb->query_result($result, 0, 'path'), '/\\');
            $attachmentId = (int)$adb->query_result($result, 0, 'attachmentsid');
            $candidate = $storedPath . DIRECTORY_SEPARATOR . $attachmentId . '_' . $filename;
            if (!self::isAbsolutePath($candidate)) {
                $root = rtrim((string)vglobal('root_directory'), '/\\');
                if ($root === '') {
                    $root = dirname(__DIR__);
                }
                $candidate = $root . DIRECTORY_SEPARATOR . ltrim($candidate, '/\\');
            }
            $path = realpath($candidate);
            if ($path === false || is_link($candidate) || !is_file($path) || !is_readable($path)) {
                return self::$cache[$documentId] = self::invalid($documentId, $label, 'The selected letterhead PDF is missing or unreadable.');
            }
            $handle = fopen($path, 'rb');
            $signature = $handle ? fread($handle, 5) : false;
            if (is_resource($handle)) {
                fclose($handle);
            }
            if ($signature !== '%PDF-') {
                return self::$cache[$documentId] = self::invalid($documentId, $label, 'The selected file is not a valid PDF.');
            }

            $probe = new \setasign\Fpdi\Tcpdf\Fpdi('P', 'mm', 'A4', true, 'UTF-8');
            $pageCount = $probe->setSourceFile($path);
            if ($pageCount !== 1) {
                return self::$cache[$documentId] = self::invalid($documentId, $label, 'A letterhead PDF must contain exactly one page.');
            }
            $template = $probe->importPage(1, 'MediaBox');
            $size = $probe->getTemplateSize($template);

            return self::$cache[$documentId] = self::result(
                $documentId,
                $label !== '' ? $label : $filename,
                $path,
                true,
                '',
                isset($size['width']) ? (float)$size['width'] : null,
                isset($size['height']) ? (float)$size['height'] : null
            );
        } catch (Throwable $error) {
            InventoryPdfLetterheadSettings::log('Cannot resolve Document ' . $documentId, $error);
            return self::$cache[$documentId] = self::invalid($documentId, '', 'The selected letterhead PDF cannot be imported.');
        }
    }

    public static function resetCache()
    {
        self::$cache = array();
    }

    private static function invalid($id, $label, $message)
    {
        return self::result($id, $label, null, false, $message, null, null);
    }

    private static function result($id, $label, $path, $valid, $message, $width, $height)
    {
        return array(
            'valid' => (bool)$valid,
            'id' => (int)$id,
            'label' => (string)$label,
            'path' => $path,
            'message' => (string)$message,
            'width' => $width,
            'height' => $height,
        );
    }

    private static function isAbsolutePath($path)
    {
        return $path !== '' && ($path[0] === '/' || $path[0] === '\\' || preg_match('/\A[A-Za-z]:[\\\\\/]/', $path));
    }
}

/**
 * Shared TCPDF/FPDI base. Module-specific MYPDF classes retain their footers.
 */
class InventoryPdfDocument extends \setasign\Fpdi\Tcpdf\Fpdi
{
    /** @var array<string,array<string,mixed>> */
    private $letterheadTemplates = array();
    /** @var array<string,bool> */
    private $letterheadWarnings = array();

    public function configureLetterheads(array $letterheads)
    {
        foreach (array('first', 'continuation') as $slot) {
            if (empty($letterheads[$slot]['path'])) {
                continue;
            }
            try {
                $count = $this->setSourceFile($letterheads[$slot]['path']);
                if ($count !== 1) {
                    throw new RuntimeException('The source must contain exactly one page.');
                }
                $template = $this->importPage(1, 'MediaBox');
                $size = $this->getTemplateSize($template);
                $this->letterheadTemplates[$slot] = array('template' => $template, 'size' => $size);
            } catch (Throwable $error) {
                $this->warnOnce($slot, 'Cannot import ' . $slot . ' page letterhead', $error);
            }
        }
        return $this;
    }

    public function hasConfiguredLetterhead()
    {
        return !empty($this->letterheadTemplates);
    }

    public function AddPage($orientation = '', $format = '', $keepmargins = false, $tocpage = false)
    {
        parent::AddPage($orientation, $format, $keepmargins, $tocpage);
        $slot = $this->PageNo() === 1 ? 'first' : 'continuation';
        if (empty($this->letterheadTemplates[$slot])) {
            return;
        }

        $template = $this->letterheadTemplates[$slot];
        $width = $this->getPageWidth();
        $height = $this->getPageHeight();
        $sourceWidth = isset($template['size']['width']) ? (float)$template['size']['width'] : 0.0;
        $sourceHeight = isset($template['size']['height']) ? (float)$template['size']['height'] : 0.0;
        if (abs($sourceWidth - $width) > 2.0 || abs($sourceHeight - $height) > 2.0) {
            $this->warnOnce($slot, sprintf(
                '%s letterhead size %.1fx%.1f mm does not match output page %.1fx%.1f mm',
                ucfirst($slot), $sourceWidth, $sourceHeight, $width, $height
            ));
            unset($this->letterheadTemplates[$slot]);
            return;
        }

        try {
            $this->useTemplate($template['template'], 0, 0, $width, $height, false);
        } catch (Throwable $error) {
            $this->warnOnce($slot, 'Cannot apply ' . $slot . ' page letterhead', $error);
            unset($this->letterheadTemplates[$slot]);
        }
    }

    private function warnOnce($key, $message, ?Throwable $error = null)
    {
        if (isset($this->letterheadWarnings[$key])) {
            return;
        }
        $this->letterheadWarnings[$key] = true;
        InventoryPdfLetterheadSettings::log($message, $error);
    }
}

/**
 * Central entry point used by all inventory PDF creators.
 */
class InventoryPdfGenerator
{
    /** Return the single organization-details row used by every inventory module. */
    public static function organizationDetails($decodeValues = false)
    {
        global $adb;
        $result = $adb->pquery('SELECT * FROM vtiger_organizationdetails', array());
        if (!$result || $adb->num_rows($result) === 0) {
            return array();
        }
        $row = $adb->query_result_rowdata($result, 0);
        if ($decodeValues) {
            foreach ($row as $key => $value) {
                $row[$key] = decode_html((string)$value);
            }
        }
        return $row;
    }

    /** Populate the legacy globals consumed by the existing header/footer templates. */
    public static function populateOrganizationGlobals()
    {
        $details = self::organizationDetails(false);
        $map = array(
            'org_name' => 'organizationname', 'org_address' => 'address', 'org_city' => 'city',
            'org_state' => 'state', 'org_country' => 'country', 'org_code' => 'code',
            'org_phone' => 'phone', 'org_fax' => 'fax', 'org_taxid' => 'tax_id',
            'org_vatid' => 'vatid', 'org_irs' => 'irs', 'org_website' => 'website',
            'logo_name' => 'logoname', 'bank_name' => 'bankname', 'bank_street' => 'bankstreet',
            'bank_city' => 'bankcity', 'bank_zip' => 'bankzip', 'bank_country' => 'bankcountry',
            'bank_account' => 'bankaccount', 'bank_routing' => 'bankrouting',
            'bank_iban' => 'bankiban', 'bank_swift' => 'bankswift',
        );
        foreach ($map as $target => $source) {
            $value = isset($details[$source]) ? $details[$source] : '';
            $GLOBALS[$target] = $target === 'logo_name' ? decode_html($value) : $value;
        }
        return $details;
    }

    /** Return a normalized user/group owner context for inventory PDF headers and footers. */
    public static function ownerDetails($recordId, $organizationPhone = '')
    {
        global $adb;
        $owners = getRecordOwnerId($recordId);
        $ownerType = '';
        foreach ($owners as $type => $id) {
            $ownerType = $type;
        }
        if ($ownerType === 'Users') {
            $result = $adb->pquery(
                'SELECT u.*, c.smownerid FROM vtiger_users u INNER JOIN vtiger_crmentity c ON u.id = c.smownerid WHERE c.crmid = ?',
                array($recordId)
            );
            return array(
                'last_name' => $adb->query_result($result, 0, 'last_name'),
                'first_name' => $adb->query_result($result, 0, 'first_name'),
                'id' => $adb->query_result($result, 0, 'smownerid'),
                'phone' => $adb->query_result($result, 0, 'phone_work'),
                'email' => $adb->query_result($result, 0, 'email1'),
                'title' => decode_html(trim((string)$adb->query_result($result, 0, 'title'))),
            );
        }

        $result = $adb->pquery(
            'SELECT g.*, c.smownerid FROM vtiger_groups g INNER JOIN vtiger_crmentity c ON g.groupid = c.smownerid WHERE c.crmid = ?',
            array($recordId)
        );
        return array(
            'last_name' => '',
            'first_name' => $adb->query_result($result, 0, 'groupname'),
            'id' => $adb->query_result($result, 0, 'smownerid'),
            'phone' => $organizationPhone,
            'email' => '',
            'title' => '',
        );
    }

    /**
     * Apply the common TCPDF setup shared by Quote, SalesOrder, PurchaseOrder and Invoice.
     * Module adapters supply only document metadata and their legacy footer subclass.
     */
    public static function initializeDocument(InventoryPdfDocument $pdf, array $options)
    {
        $pdf->SetFont($options['font'], ' ', $options['font_size']);
        $pdf->setPrintHeader(false);
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        $pdf->SetTitle($options['title']);
        $pdf->SetAuthor($options['author']);
        $pdf->SetSubject($options['subject']);
        $pdf->SetCreator('CRM System berliCRM: www.crm-now.de ');
        $keywords = isset($options['keywords']) ? $options['keywords'] : array();
        $pdf->SetKeywords(is_array($keywords) ? implode(', ', $keywords) : (string)$keywords);
        $pdf->SetAutoPageBreak(true, PDF_MARGIN_FOOTER);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        if (isset($options['language'])) {
            $pdf->setLanguageArray($options['language']);
        }
        self::configureDocument($pdf, isset($options['legacy_letterhead']) ? $options['legacy_letterhead'] : null);
        return $pdf;
    }

    /** Calculate the legacy 1/2/4 bit mask used by the inventory body templates. */
    public static function productDetailMask(array $settings, $prefix)
    {
        $mask = 0;
        foreach (array('name', 'des', 'com') as $index => $suffix) {
            if (isset($settings[$prefix . $suffix]) && $settings[$prefix . $suffix] === 'true') {
                $mask += (1 << $index);
            }
        }
        return $mask;
    }

    public static function configureDocument(InventoryPdfDocument $pdf, $legacyFallbackPath = null)
    {
        $ids = InventoryPdfLetterheadSettings::getDocumentIds();
        $letterheads = array();
        if ($ids['first_document_id'] > 0) {
            $resolved = InventoryPdfLetterheadResolver::resolveDocument($ids['first_document_id']);
            if ($resolved['valid']) {
                $letterheads['first'] = $resolved;
            } else {
                InventoryPdfLetterheadSettings::log($resolved['message']);
            }
        }
        if ($ids['continuation_document_id'] > 0) {
            $resolved = InventoryPdfLetterheadResolver::resolveDocument($ids['continuation_document_id']);
            if ($resolved['valid']) {
                $letterheads['continuation'] = $resolved;
            } else {
                InventoryPdfLetterheadSettings::log($resolved['message']);
            }
        }

        $hasGlobalSelection = $ids['first_document_id'] > 0 || $ids['continuation_document_id'] > 0;
        if (!$hasGlobalSelection && is_string($legacyFallbackPath) && $legacyFallbackPath !== '' && is_file($legacyFallbackPath)) {
            $fallback = array('path' => realpath($legacyFallbackPath));
            $letterheads = array('first' => $fallback, 'continuation' => $fallback);
        }
        return $pdf->configureLetterheads($letterheads);
    }
}

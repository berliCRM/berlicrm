<?php
/**
 * Validates generated DOCX packages without extracting or executing their contents.
 *
 * @package berliCRM.WordAddin
 */
require_once dirname(__DIR__, 2) . '/utils/OfficeAddinI18n.php';

/**
 * Checks ZIP structure, size limits, required XML and the Word content type.
 *
 * @param string $bytes Complete binary DOCX package.
 * @return array{extension:string,mime:string} Validated attachment format.
 * @throws RuntimeException If ZIP support is unavailable or the package is invalid.
 */
function berli_wordMailMergeDocxFormat($bytes) {
    if (!class_exists('ZipArchive')) throw new RuntimeException(berli_wordT('SAVING_DOCX_FILES_REQUIRES_THE_PHP_ZIP_EXTENSION_THE_LEGACY_HTML_DOC_OUT'));
    $path = tempnam(sys_get_temp_dir(), 'berli-docx-check-');
    if ($path === false) throw new RuntimeException(berli_wordT('COULD_NOT_PREPARE_DOCX_VALIDATION'));
    $zip = new ZipArchive(); $opened = false;
    try {
        if (file_put_contents($path, $bytes, LOCK_EX) !== strlen($bytes) || $zip->open($path, ZipArchive::CHECKCONS) !== true) throw new RuntimeException(berli_wordT('THE_DOCX_FILE_IS_NOT_A_VALID_ZIP_PACKAGE'));
        $opened = true; $total = 0; $seen = array();
        if ($zip->numFiles > 4096) throw new RuntimeException(berli_wordT('THE_DOCX_PACKAGE_CONTAINS_TOO_MANY_PARTS'));
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entry = $zip->statIndex($index); $name = $entry['name'];
            $total += $entry['size'];
            if ($total > 128 * 1024 * 1024 || isset($seen[$name]) || strpos($name, "\0") !== false || strpos($name, '\\') !== false || substr($name, 0, 1) === '/' || in_array('..', explode('/', $name), true)) throw new RuntimeException(berli_wordT('THE_DOCX_PACKAGE_CONTAINS_INVALID_OR_OVERSIZED_PARTS'));
            if (preg_match('~vbaProject|^_xmlsignatures/~i', $name)) throw new RuntimeException(berli_wordT('MACROS_AND_DIGITAL_PACKAGE_SIGNATURES_ARE_NOT_SUPPORTED_WHEN_SAVING_DOCX'));
            $seen[$name] = true;
        }
        $readXml = function ($name) use ($zip) {
            $stat = $zip->statName($name);
            if (!$stat || $stat['size'] > 16 * 1024 * 1024) throw new RuntimeException(berli_wordT('A_REQUIRED_DOCX_XML_PART_IS_MISSING_OR_TOO_LARGE') . $name);
            $xml = $zip->getFromName($name);
            if (!is_string($xml) || preg_match('/<!DOCTYPE|<!ENTITY/i', $xml)) throw new RuntimeException(berli_wordT('INVALID_DOCX_XML_CONTENT') . $name);
            $previous = libxml_use_internal_errors(true);
            try {
                $doc = new DOMDocument();
                if (!$doc->loadXML($xml, LIBXML_NONET)) throw new RuntimeException(berli_wordT('INVALID_DOCX_XML_CONTENT') . $name);
            } finally { libxml_clear_errors(); libxml_use_internal_errors($previous); }
            return $doc;
        };
        $types = $readXml('[Content_Types].xml');
        $xpath = new DOMXPath($types); $xpath->registerNamespace('ct', 'http://schemas.openxmlformats.org/package/2006/content-types');
        if ($xpath->evaluate('string(/ct:Types/ct:Override[@PartName="/word/document.xml"]/@ContentType)') !== 'application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml') throw new RuntimeException(berli_wordT('THE_FILE_IS_NOT_A_STANDARD_WORD_DOCUMENT_DOCX'));
        $root = $readXml('_rels/.rels'); $xpath = new DOMXPath($root); $xpath->registerNamespace('r', 'http://schemas.openxmlformats.org/package/2006/relationships');
        $target = $xpath->evaluate('string(/r:Relationships/r:Relationship[@Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument"]/@Target)');
        if (!in_array($target, array('word/document.xml', '/word/document.xml'), true)) throw new RuntimeException(berli_wordT('THE_DOCX_DOCUMENT_RELATIONSHIP_IS_INVALID'));
        $main = $readXml('word/document.xml'); $xpath = new DOMXPath($main); $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        if ($xpath->query('/w:document/w:body')->length !== 1) throw new RuntimeException(berli_wordT('THE_DOCX_DOCUMENT_CONTENT_IS_MISSING'));
        return array('extension' => 'docx', 'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    } finally {
        if ($opened) $zip->close();
        if (is_file($path)) unlink($path);
    }
}

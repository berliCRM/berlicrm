<?php
/** Validates generated DOCX packages without extracting or executing their contents. */
function berli_wordMailMergeDocxFormat($bytes) {
    if (!class_exists('ZipArchive')) throw new RuntimeException('Für die DOCX-Ablage muss die PHP-Erweiterung ZIP aktiviert sein. Die bisherige HTML/.doc-Ausgabe bleibt verfügbar.');
    $path = tempnam(sys_get_temp_dir(), 'berli-docx-check-');
    if ($path === false) throw new RuntimeException('Die DOCX-Prüfung konnte nicht vorbereitet werden.');
    $zip = new ZipArchive(); $opened = false;
    try {
        if (file_put_contents($path, $bytes, LOCK_EX) !== strlen($bytes) || $zip->open($path, ZipArchive::CHECKCONS) !== true) throw new RuntimeException('Die DOCX-Datei ist kein gültiges ZIP-Paket.');
        $opened = true; $total = 0; $seen = array();
        if ($zip->numFiles > 4096) throw new RuntimeException('Das DOCX-Paket enthält zu viele Teile.');
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entry = $zip->statIndex($index); $name = $entry['name'];
            $total += $entry['size'];
            if ($total > 128 * 1024 * 1024 || isset($seen[$name]) || strpos($name, "\0") !== false || strpos($name, '\\') !== false || substr($name, 0, 1) === '/' || in_array('..', explode('/', $name), true)) throw new RuntimeException('Das DOCX-Paket enthält ungültige oder zu große Teile.');
            if (preg_match('~vbaProject|^_xmlsignatures/~i', $name)) throw new RuntimeException('Makros und digitale Paketsignaturen werden in der DOCX-Ablage nicht unterstützt.');
            $seen[$name] = true;
        }
        $readXml = function ($name) use ($zip) {
            $stat = $zip->statName($name);
            if (!$stat || $stat['size'] > 16 * 1024 * 1024) throw new RuntimeException('Ein erforderlicher DOCX-XML-Teil fehlt oder ist zu groß: ' . $name);
            $xml = $zip->getFromName($name);
            if (!is_string($xml) || preg_match('/<!DOCTYPE|<!ENTITY/i', $xml)) throw new RuntimeException('Ungültiger DOCX-XML-Inhalt: ' . $name);
            $previous = libxml_use_internal_errors(true);
            try {
                $doc = new DOMDocument();
                if (!$doc->loadXML($xml, LIBXML_NONET)) throw new RuntimeException('Ungültiger DOCX-XML-Inhalt: ' . $name);
            } finally { libxml_clear_errors(); libxml_use_internal_errors($previous); }
            return $doc;
        };
        $types = $readXml('[Content_Types].xml');
        $xpath = new DOMXPath($types); $xpath->registerNamespace('ct', 'http://schemas.openxmlformats.org/package/2006/content-types');
        if ($xpath->evaluate('string(/ct:Types/ct:Override[@PartName="/word/document.xml"]/@ContentType)') !== 'application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml') throw new RuntimeException('Die Datei ist kein normales Word-Dokument (.docx).');
        $root = $readXml('_rels/.rels'); $xpath = new DOMXPath($root); $xpath->registerNamespace('r', 'http://schemas.openxmlformats.org/package/2006/relationships');
        $target = $xpath->evaluate('string(/r:Relationships/r:Relationship[@Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument"]/@Target)');
        if (!in_array($target, array('word/document.xml', '/word/document.xml'), true)) throw new RuntimeException('Die DOCX-Dokumentbeziehung ist ungültig.');
        $main = $readXml('word/document.xml'); $xpath = new DOMXPath($main); $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        if ($xpath->query('/w:document/w:body')->length !== 1) throw new RuntimeException('Der DOCX-Dokumentinhalt fehlt.');
        return array('extension' => 'docx', 'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    } finally {
        if ($opened) $zip->close();
        if (is_file($path)) unlink($path);
    }
}

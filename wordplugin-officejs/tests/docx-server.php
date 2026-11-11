<?php
/** Offline package validation. Never loads CRM configuration or writes CRM data.
 * Generate fixtures with BERLICRM_DOCX_ARTIFACTS and docx.test.js first.
 * Run: php -d extension=zip wordplugin-officejs/tests/docx-server.php <fixture-directory>
 */
require dirname(__DIR__, 2) . '/include/Webservices/Custom/WordMailMergeDocx.php';
$directory = isset($argv[1]) ? $argv[1] : '';
if (!class_exists('ZipArchive') || !is_dir($directory)) throw new RuntimeException('ZIP and generated fixture directory required.');
$checks = 0;
/**
 * Asserts an offline DOCX validation result and increments the check count.
 *
 * @param bool $condition Expected condition.
 * @param string $message Failure description.
 * @return void
 * @throws RuntimeException If the condition is false.
 */
function checkDocx($condition, $message) { global $checks; if (!$condition) throw new RuntimeException($message); $checks++; }
/**
 * Verifies that the validator rejects a malformed binary package.
 *
 * @param string $bytes Candidate package.
 * @return void
 * @throws RuntimeException If the invalid package is accepted.
 */
function rejectDocx($bytes) {
    try { berli_wordMailMergeDocxFormat($bytes); } catch (RuntimeException $error) { checkDocx(true, 'rejected'); return; }
    throw new RuntimeException('Invalid package accepted.');
}
/**
 * Produces an invalid test package using only an owned temporary archive.
 *
 * @param string $bytes Original fixture package.
 * @param array<string,string|null> $changes Parts to replace or remove.
 * @return string Mutated ZIP bytes.
 */
function mutateDocx($bytes, $changes) {
    $path = tempnam(sys_get_temp_dir(), 'berli-docx-test-');
    try {
        file_put_contents($path, $bytes);
        $zip = new ZipArchive(); $zip->open($path);
        foreach ($changes as $name => $content) {
            if ($content === null) $zip->deleteName($name); else $zip->addFromString($name, $content);
        }
        $zip->close(); return file_get_contents($path);
    } finally { unlink($path); }
}
foreach (array('single', 'combined', 'multipage-single', 'multipage-combined') as $name) {
    $bytes = file_get_contents($directory . '/' . $name . '.docx');
    $format = berli_wordMailMergeDocxFormat($bytes);
    checkDocx($format['extension'] === 'docx', 'DOCX suffix');
    checkDocx($format['mime'] === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'DOCX MIME');
}
$bytes = file_get_contents($directory . '/single.docx');
rejectDocx('<html>This is not a Word package.</html>');
rejectDocx('PKbroken');
rejectDocx(mutateDocx($bytes, array('word/document.xml' => null)));
rejectDocx(mutateDocx($bytes, array('word/document.xml' => '<html/>')));
rejectDocx(mutateDocx($bytes, array('word/document.xml' => '<!DOCTYPE a [<!ENTITY x "x">]><a>&x;</a>')));
rejectDocx(mutateDocx($bytes, array('word/vbaProject.bin' => 'macro')));
rejectDocx(mutateDocx($bytes, array('_xmlsignatures/sig.xml' => '<x/>')));
rejectDocx(mutateDocx($bytes, array('../outside' => 'invalid')));
rejectDocx(mutateDocx($bytes, array('word\\outside' => 'invalid')));
rejectDocx(mutateDocx($bytes, array('[Content_Types].xml' => '<Types/>')));
rejectDocx(mutateDocx($bytes, array('_rels/.rels' => '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"/>')));
echo "DOCX server validation: $checks checks passed; no CRM connection.\n";

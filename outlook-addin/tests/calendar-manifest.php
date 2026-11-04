<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
$root = dirname(__DIR__, 2);
chdir($root);
require 'includes/runtime/BaseModel.php';
require 'modules/Settings/Vtiger/models/Module.php';
foreach (array('manifest.xml', 'manifest.generated.xml') as $filename) {
    $document = new DOMDocument();
    if (!$document->load('outlook-addin/' . $filename, LIBXML_NONET)) throw new RuntimeException('Invalid XML');
    $xpath = new DOMXPath($document);
    $xsi = 'http://www.w3.org/2001/XMLSchema-instance';
    if ($xpath->query('/*/*[local-name()="Version"]')->item(0)->textContent !== '1.0.15.3') throw new RuntimeException('Version not incremented');
    $rules = $xpath->query('/*/*[local-name()="Rule"]/*[@ItemType="Appointment"]');
    if ($rules->length !== 2) throw new RuntimeException('Read/edit appointment activation missing');
    foreach ($xpath->query('//*[local-name()="DesktopFormFactor"]') as $desktop) {
        $surfaceTypes = array();
        $ids = array();
        foreach ($xpath->query('./*[local-name()="ExtensionPoint"]', $desktop) as $surface) {
            $type = $surface->getAttributeNS($xsi, 'type');
            $surfaceTypes[] = $type;
            foreach ($xpath->query('.//*[local-name()="Control"]|.//*[local-name()="Group"]', $surface) as $node) {
                $id = $node->getAttribute('id');
                if (isset($ids[$id])) throw new RuntimeException('Duplicate control/group IDs');
                $ids[$id] = true;
            }
            if (strpos($type, 'Appointment') === 0 && $xpath->query('.//*[local-name()="SupportsPinning"]', $surface)->length) {
                throw new RuntimeException('Appointment surfaces must not use mail pinning');
            }
        }
        foreach (array('MessageReadCommandSurface', 'MessageComposeCommandSurface', 'AppointmentOrganizerCommandSurface', 'AppointmentAttendeeCommandSurface') as $type) {
            if (!in_array($type, $surfaceTypes, true)) throw new RuntimeException('Missing surface ' . $type);
        }
        $resources = $desktop->parentNode->parentNode->parentNode;
        $resourceIds = array();
        foreach ($xpath->query('./*[local-name()="Resources"]//*[@id]', $resources) as $node) $resourceIds[] = $node->getAttribute('id');
        foreach ($xpath->query('.//*[@resid]', $desktop) as $node) {
            if (!in_array($node->getAttribute('resid'), $resourceIds, true)) throw new RuntimeException('Undefined resource reference');
        }
    }
}
echo "Manifest structural checks passed: version, calendar activation, both override versions, unique controls and resource references.\n";

class PearDatabase {
    private static $instance;
    public $database, $operations = array(), $parameters = array(), $tables = array(), $failNextQuery = false, $settings = array();
    public function __construct() { $this->database = $this; }
    public static function getInstance() { if (!self::$instance) self::$instance = new self(); return self::$instance; }
    public function startTransaction() {}
    public function completeTransaction() {}
    public function hasFailedTransaction() { return false; }
    public function FailTrans() {}
    public function getUniqueID($table) { return count($this->operations) + 1; }
    public function num_rows($rows) { return count($rows); }
    public function query_result($rows, $index, $column) { return $rows[$index][$column]; }
    public function pquery($sql, $params) {
        if ($this->failNextQuery) { $this->failNextQuery = false; throw new RuntimeException('Simulated registration failure'); }
        if (strpos($sql, 'SELECT blockid FROM vtiger_settings_blocks') === 0) return array(array('blockid' => 12));
        if (strpos($sql, 'SELECT fieldid, blockid FROM vtiger_settings_field') === 0) return $this->settings;
        if (strpos($sql, 'SELECT MAX(sequence)') === 0) return array(array('sequence' => 0));
        if (strpos($sql, 'SELECT MAX(fieldid)') === 0) return array(array('fieldid' => 0));
        if (strpos($sql, 'INSERT INTO vtiger_settings_field ') === 0) { $this->settings[] = array('fieldid' => $params[0], 'blockid' => $params[1]); return array(); }
        if (strpos($sql, 'UPDATE vtiger_settings_field SET') === 0) return array();
        if (strpos($sql, 'CREATE TABLE IF NOT EXISTS vtiger_outlook_calendar_link (') === 0) { $this->tables['calendar'] = true; return array(); }
        if (strpos($sql, 'CREATE TABLE IF NOT EXISTS vtiger_outlook_contact_link (') === 0) { $this->tables['contacts'] = true; return array(); }
        if (strpos($sql, 'CREATE TABLE IF NOT EXISTS vtiger_outlook_mail_link (') === 0) { $this->tables['mail'] = true; return array(); }
        if (strpos($sql, 'CREATE TABLE IF NOT EXISTS vtiger_outlook_mail_file (') === 0) { $this->tables['files'] = true; return array(); }
        if (strpos($sql, 'SELECT operationid FROM vtiger_ws_operation') === 0) return isset($this->operations[$params[0]]) ? array(array('operationid' => $this->operations[$params[0]]['id'])) : array();
        if (strpos($sql, 'INSERT INTO vtiger_ws_operation (') === 0) {
            $this->operations[$params[1]] = array('id' => $params[0], 'path' => $params[2], 'method' => $params[3], 'type' => $params[4]); return array();
        }
        if (strpos($sql, 'UPDATE vtiger_ws_operation SET') === 0) return array();
        if (strpos($sql, 'DELETE FROM vtiger_ws_operation_parameters') === 0) { $this->parameters[$params[0]] = array(); return array(); }
        if (strpos($sql, 'INSERT INTO vtiger_ws_operation_parameters') === 0) { $this->parameters[$params[0]][] = array($params[1], $params[2]); return array(); }
        throw new RuntimeException('Unexpected installer SQL: ' . $sql);
    }
}
require 'outlook-addin/update.php';
chdir(sys_get_temp_dir());
$launchDirectory = getcwd();
berli_outlookAddinUpdate();
berli_outlookAddinUpdate();
if (getcwd() !== $launchDirectory) throw new RuntimeException('Update did not restore working directory');
chdir($root);
$db = PearDatabase::getInstance();
$operation = $db->operations['outlook_calendar_event'];
if (count($db->operations) !== 6 || !$db->tables['calendar'] || !$db->tables['contacts'] || !$db->tables['mail'] || !$db->tables['files']
    || $operation['method'] !== 'berli_outlookCalendarEvent' || $operation['type'] !== 'POST'
    || $db->parameters[$operation['id']] !== array(array('input', 'encoded'))) {
    throw new RuntimeException('Wrong calendar operation registration');
}
$contacts = $db->operations['outlook_contact_transfer'];
if ($contacts['method'] !== 'berli_outlookContactTransfer' || $contacts['type'] !== 'POST'
    || $db->parameters[$contacts['id']] !== array(array('input', 'encoded'))) throw new RuntimeException('Wrong contacts operation registration');
$archive = $db->operations['outlook_archive_email'];
if ($archive['method'] !== 'berli_outlookArchiveEmail' || $archive['type'] !== 'POST'
    || $db->parameters[$archive['id']] !== array(array('input', 'encoded'))) throw new RuntimeException('Wrong email archive registration');
echo "Registration checks passed: repeated registration, six operations, encoded payloads and add-in-owned link tables only.\n";

// Exercise the actual db_update hook without bootstrapping/running the full CRM update.
$updater = file_get_contents($root . '/db_update.php');
$start = strpos($updater, '// Outlook add-in update START');
$end = strpos($updater, '// Outlook add-in update END');
$versionWrite = strpos($updater, 'BerliCRM_Version_Utils::setInstalledTag');
if ($start === false || $end === false || $versionWrite === false || $end >= $versionWrite) {
    throw new RuntimeException('Outlook hook must precede CRM release tag update');
}
// __DIR__ in the extracted code must refer to db_update.php's CRM root.
$hook = str_replace('__DIR__', var_export($root, true), substr($updater, $start, $end - $start));
ob_start();
try { eval($hook); } finally { ob_end_clean(); }
$advanced = false;
$db->failNextQuery = true;
ob_start();
try {
    eval($hook . PHP_EOL . '$advanced = true;');
    throw new RuntimeException('Failed registration was swallowed');
} catch (RuntimeException $error) {
    if ($error->getMessage() !== 'Simulated registration failure') throw $error;
} finally { ob_end_clean(); }
if ($advanced || getcwd() !== $root) throw new RuntimeException('Update continued after failed Outlook registration');
echo "CRM update hook checks passed: same Outlook initializer, release tag ordering and failure propagation.\n";

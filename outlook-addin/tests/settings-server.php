<?php
/**
 * Tests Outlook add-in registration, manifest generation and settings access offline.
 *
 * Run: php outlook-addin/tests/settings-server.php
 * Uses an in-memory database fixture; never updates a configured CRM database.
 * An optional first argument points to a Smarty bootstrap for template checks.
 *
 * @package berliCRM.OutlookAddin.Tests
 */
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) throw new ErrorException($message, 0, $severity, $file, $line);
    return true;
});
chdir(dirname(__DIR__, 2));
require 'includes/runtime/BaseModel.php';
require 'modules/Settings/Vtiger/models/Module.php';
require 'modules/Settings/OutlookAddin/models/Module.php';
require 'includes/runtime/Controller.php';
require 'includes/http/Request.php';

/**
 * Fails the test run when an expected condition is not met.
 *
 * @param bool $condition Expected outcome.
 * @param string $message Failure description.
 * @return void
 * @throws RuntimeException If the condition is false.
 */
function checkOutlookAddin($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
}
/**
 * Requires a callback to reject an invalid input or failed operation.
 *
 * @param callable $callback Code expected to throw an exception.
 * @param string $message Failure description if no exception is thrown.
 * @return void
 * @throws RuntimeException If the callback completes successfully.
 */
function expectOutlookAddinError($callback, $message) {
    try { $callback(); } catch (Exception $error) { return; }
    throw new RuntimeException($message);
}
/**
 * @param mixed $value Fixture input.
 * @return mixed Unchanged input.
 */
function vtlib_purify($value) { return $value; }
/**
 * @param string $key Unused global name.
 * @return null No authenticated global user.
 */
function vglobal($key) { return null; }
/**
 * @param string $key Label key.
 * @param string $module Unused module.
 * @return string Label key.
 */
function vtranslate($key, $module = '') { return $GLOBALS['testLabels'][$key] ?? $key; }
/**
 * @param bool $fatal Unused fatal flag.
 * @return bool Configured fixture CSRF validity.
 */
function csrf_check($fatal = true) { return $GLOBALS['validCsrf']; }
/** Permission exception fixture used by the actual settings controllers. */
class AppException extends Exception {}
/** Supplies the current-user permissions and row-height preference for tests. */
class Users_Record_Model {
    /** @var bool Whether the fixture user is an administrator. */
    public static $admin = false;
    /** @return self Current-user fixture. */
    public static function getCurrentUserModel() { return new self(); }
    /** @return bool Whether the fixture user is an administrator. */
    public function isAdminUser() { return self::$admin; }
    /**
     * @param string $key Preference name.
     * @return string|null Fixture preference.
     */
    public function get($key) { return $key === 'rowheight' ? 'medium' : null; }
}
/** Minimal parent-view fixture for loading the real settings view classes. */
class Vtiger_Basic_View {
    /** Creates the parent-view fixture without loading a CRM session. */
    public function __construct() {}
    /**
     * @param Vtiger_Request $request Unused request.
     * @return OutlookAddinTestViewer Test viewer.
     */
    public function getViewer($request) { return $GLOBALS['viewer']; }
}
/** Captures template variables while suppressing actual page output. */
class OutlookAddinTestViewer {
    /** @var array<string, mixed> Assigned template variables. */
    public $values = array();
    /**
     * @param string $name Variable name.
     * @param mixed $value Variable value.
     * @return void
     */
    public function assign($name, $value) { $this->values[$name] = $value; }
    /**
     * @param string $template Unused template.
     * @param string $module Unused module.
     * @return void
     */
    public function view($template, $module) {}
}
require 'modules/Settings/Vtiger/views/Index.php';
require 'modules/Settings/OutlookAddin/views/Index.php';
require 'modules/Settings/OutlookAddin/actions/Install.php';
require 'modules/Settings/OutlookAddin/actions/DownloadManifest.php';

/** Minimal SQL fixture supporting the production model, with transaction rollback. */
class PearDatabase {
    /** @var array<string, array> In-memory CRM tables. */
    public $tables = array(
        'vtiger_ws_operation' => array(), 'vtiger_ws_operation_parameters' => array(),
        'vtiger_settings_field' => array(),
        'vtiger_settings_blocks' => array(
            array('blockid' => 7, 'label' => 'LBL_OTHER_SETTINGS'),
            array('blockid' => 12, 'label' => 'LBL_INTEGRATION')
        ),
        'vtiger_settings_field_seq' => array(array('id' => 1))
    );
    /** @var self ADOdb-compatible transaction fixture. */
    public $database;
    /** @var string SQL substring that triggers a simulated query failure. */
    public $failOn = '';
    /** @var array|null Table snapshot taken at transaction start. */
    private $snapshot;
    /** @var bool Current transaction failure status. */
    private $failed = false;
    /** @var int Fixture sequence counter. */
    private $nextId = 1;
    /** Exposes the fixture's transaction methods through its database property. */
    public function __construct() { $this->database = $this; }
    /** @return self Active in-memory database fixture. */
    public static function getInstance() { return $GLOBALS['db']; }
    /** @return void Saves the current table state for rollback. */
    public function startTransaction() { $this->snapshot = $this->tables; $this->failed = false; }
    /** @return void Marks the current transaction as failed. */
    public function FailTrans() { $this->failed = true; }
    /** @return bool Current transaction failure status. */
    public function hasFailedTransaction() { return $this->failed; }
    /** @return void Restores the snapshot if the transaction failed. */
    public function completeTransaction() { if ($this->failed) $this->tables = $this->snapshot; }
    /**
     * @param string $table Unused sequence name.
     * @return int Next fixture ID.
     */
    public function getUniqueID($table) { return ++$this->nextId; }
    /**
     * @param array $result Fixture rows.
     * @return int Number of rows.
     */
    public function num_rows($result) { return count($result); }
    /**
     * @param array $result Fixture rows.
     * @param int $row Row index.
     * @param string $field Column name.
     * @return mixed Selected value.
     */
    public function query_result($result, $row, $field) { return $result[$row][$field] ?? null; }
    /**
     * Executes supported registration queries against the fixture tables.
     *
     * @param string $sql Registration query.
     * @param array $params Positional query parameters.
     * @return array|bool Fixture rows or write/failure status.
     * @throws RuntimeException If the query falls outside the supported fixture SQL.
     */
    public function pquery($sql, $params = array()) {
        if ($this->failOn && strpos($sql, $this->failOn) !== false) { $this->failed = true; return false; }
        if (preg_match('/^CREATE TABLE IF NOT EXISTS (vtiger_outlook_\w+) /', $sql, $created)) { if (!isset($this->tables[$created[1]])) $this->tables[$created[1]] = array(); return true; }
        if (preg_match('/^SELECT (.+) FROM (\w+)(?: WHERE (\w+) = \?)?(?: ORDER BY (\w+))?$/', $sql, $match)) {
            $rows = $this->tables[$match[2]];
            if (!empty($match[3])) $rows = array_values(array_filter($rows, function ($row) use ($match, $params) {
                return (string) $row[$match[3]] === (string) $params[0];
            }));
            if (!empty($match[4])) usort($rows, function ($a, $b) use ($match) { return $a[$match[4]] <=> $b[$match[4]]; });
            if (preg_match('/^MAX\((\w+)\) AS (\w+)$/', $match[1], $aggregate)) {
                $values = array_column($rows, $aggregate[1]);
                return array(array($aggregate[2] => $values ? max($values) : null));
            }
            return $rows;
        }
        if (preg_match('/^INSERT INTO (\w+) \((.+)\) VALUES \((.+)\)$/', $sql, $match)) {
            $columns = explode(', ', $match[2]);
            $values = array();
            $index = 0;
            foreach (explode(', ', $match[3]) as $value) $values[] = $value === '?' ? $params[$index++] : (int) $value;
            $this->tables[$match[1]][] = array_combine($columns, $values);
            return true;
        }
        if (preg_match('/^DELETE FROM (\w+) WHERE (\w+) = \?$/', $sql, $match)) {
            $this->tables[$match[1]] = array_values(array_filter($this->tables[$match[1]], function ($row) use ($match, $params) {
                return (string) $row[$match[2]] !== (string) $params[0];
            }));
            return true;
        }
        if (preg_match('/^UPDATE (\w+) SET (.+?)(?: WHERE (\w+) = \?)?$/', $sql, $match)) {
            foreach ($this->tables[$match[1]] as &$row) {
                if (!empty($match[3]) && (string) $row[$match[3]] !== (string) end($params)) continue;
                $index = 0;
                foreach (explode(', ', $match[2]) as $assignment) {
                    list($column, $value) = explode(' = ', $assignment);
                    $row[$column] = $value === '?' ? $params[$index++] : (int) $value;
                }
            }
            return true;
        }
        throw new RuntimeException('Unexpected fixture query: ' . $sql);
    }
}

require 'modules/Settings/OutlookAddin/actions/DownloadConfig.php';

$model = new Settings_OutlookAddin_Module_Model();
$templateHash = hash_file('sha256', $model::MANIFEST_PATH);
$clientId = '11111111-2222-3333-4444-555555555555';
$tenantId = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee';
foreach (array('https://crm.example.net', 'https://crm.example.net:8443/a/b/', 'https://[::1]:8443/crm/') as $base) {
    $manifest = $model->createManifest($base);
    $xml = new DOMDocument();
    checkOutlookAddin($xml->loadXML($manifest), 'Invalid generated XML');
    $xpath = new DOMXPath($xml);
    checkOutlookAddin($xpath->evaluate('string(/*/*[local-name()="Id"])') === '4fbaf5e4-1f93-4c85-9cbf-128c7d8a1909', 'Manifest ID changed');
    checkOutlookAddin($xpath->evaluate('string(/*/*[local-name()="Version"])') === '1.1.0.2', 'Unexpected manifest version');
    checkOutlookAddin($xpath->evaluate('string(/*/*[local-name()="Permissions"])') === 'ReadWriteItem', 'Manifest permissions changed');
    checkOutlookAddin(strpos($manifest, rtrim($base, '/') . '/outlook-addin/taskpane.html') !== false, 'CRM subpath/port missing');
    checkOutlookAddin(strpos($manifest, 'crm.example.org') === false, 'Placeholder URL left in manifest');
    checkOutlookAddin($xpath->evaluate('string(/*/*[local-name()="AppDomains"]/*)') === $model->getUrls($base)['origin'], 'Wrong AppDomain');
    $php = $model->createConfiguration($base, strtoupper($clientId), strtoupper($tenantId));
    $generated = eval(substr($php, 5));
    checkOutlookAddin($generated['crm_webservice_url'] === rtrim($base, '/') . '/webservice.php', 'Wrong CRM webservice URL');
    checkOutlookAddin($generated['contacts_microsoft'] === array('client_id' => $clientId, 'tenant_id' => $tenantId,
        'redirect_uri' => rtrim($base, '/') . '/outlook-addin/contacts-auth.html'), 'Wrong Microsoft configuration');
    checkOutlookAddin($generated['recipient_limit'] === 500 && $generated['default_request_timeout'] === 30, 'Wrong defaults');
    checkOutlookAddin(array_intersect(array_keys($generated), array('api_username', 'api_access_key', 'client_secret')) === array(), 'Credentials exported');
}
checkOutlookAddin(hash_file('sha256', $model::MANIFEST_PATH) === $templateHash, 'Manifest template overwritten');
checkOutlookAddin($model->getMissingFiles() === array(), 'Missing Outlook runtime files');
foreach (array('', 'http://crm.test', '//crm.test', 'https://user:password@crm.test', 'https://crm.test/?key=secret',
    'https://crm.test/#fragment', "https://crm.test/\r\nHeader:x", 'https://crm.test/back\\slash', array()) as $bad) {
    expectOutlookAddinError(function () use ($model, $bad) { $model->createManifest($bad); }, 'Unsafe manifest URL accepted');
    expectOutlookAddinError(function () use ($model, $bad, $clientId, $tenantId) { $model->createConfiguration($bad, $clientId, $tenantId); }, 'Unsafe configuration URL accepted');
}
foreach (array('', null, array(), 'common', 'tenant.example.org', "'; echo 'injected';", '11111111-2222-3333-4444-555555555555-extra') as $bad) {
    expectOutlookAddinError(function () use ($model, $bad, $tenantId) { $model->createConfiguration('https://crm.test', $bad, $tenantId); }, 'Missing/invalid client ID accepted');
    expectOutlookAddinError(function () use ($model, $bad, $clientId) { $model->createConfiguration('https://crm.test', $clientId, $bad); }, 'Missing/invalid tenant ID accepted');
}

$db = new PearDatabase();
require 'outlook-addin/update.php';
berli_outlookAddinUpdate();
checkOutlookAddin(count($db->tables['vtiger_settings_field']) === 1 && $db->tables['vtiger_settings_field'][0]['blockid'] === 12, 'Outlook menu missing from Integration');
checkOutlookAddin(count($model->getOperationStatus()) === 7 && !in_array(false, $model->getOperationStatus(), true), 'Incomplete Outlook registrations');
$db->tables['vtiger_outlook_mail_link'][] = array('userid' => 3, 'emailid' => '10x100');
$installed = $db->tables;
berli_outlookAddinUpdate();
checkOutlookAddin($db->tables === $installed, 'Repeated updater changed registrations, links or menu');
$db->tables['vtiger_ws_operation'][0]['prelogin'] = 1;
$db->tables['vtiger_ws_operation_parameters'][0]['type'] = 'boolean';
checkOutlookAddin(in_array(false, $model->getOperationStatus(), true), 'Broken registration reported ready');
$model->registerOperation();
checkOutlookAddin($db->tables === $installed, 'Registration repair failed');
$db->failOn = 'INSERT INTO vtiger_ws_operation_parameters';
expectOutlookAddinError(function () { berli_outlookAddinUpdate(); }, 'Failed database query ignored');
checkOutlookAddin($db->tables === $installed, 'Failed repair partially committed');
$db->failOn = '';
$menuId = $db->tables['vtiger_settings_field'][0]['fieldid'];
$db->tables['vtiger_settings_field'][0]['pinned'] = 1;
$db->tables['vtiger_settings_field'][0]['blockid'] = 7;
$db->tables['vtiger_settings_field'][] = array('fieldid' => 100, 'blockid' => 12, 'sequence' => 8, 'name' => 'OTHER');
$model->registerSettingsMenu();
checkOutlookAddin(count($db->tables['vtiger_settings_field']) === 2 && $db->tables['vtiger_settings_field'][0]['fieldid'] === $menuId
    && $db->tables['vtiger_settings_field'][0]['pinned'] === 1 && $db->tables['vtiger_settings_field'][0]['blockid'] === 12
    && $db->tables['vtiger_settings_field'][0]['sequence'] === 9, 'Menu migration changed ID/pin or duplicated entry');
$db = new PearDatabase();
$db->tables['vtiger_settings_field'][] = array('fieldid' => 100, 'blockid' => 12, 'sequence' => 8, 'name' => 'OTHER');
$model->registerSettingsMenu();
checkOutlookAddin($db->tables['vtiger_settings_field'][1]['fieldid'] === 101, 'Stale sequence caused a menu ID collision');
$model->registerOperation();

$view = new Settings_OutlookAddin_Index_View();
$download = new Settings_OutlookAddin_DownloadManifest_Action();
$configuration = new Settings_OutlookAddin_DownloadConfig_Action();
$install = new Settings_OutlookAddin_Install_Action();
$request = new Vtiger_Request(array('module' => 'OutlookAddin', 'parent' => 'Settings'));
foreach (array($view, $download, $configuration, $install) as $controller) {
    expectOutlookAddinError(function () use ($controller, $request) { $controller->checkPermission($request); }, 'Non-admin accessed Outlook settings');
}
Users_Record_Model::$admin = true;
foreach (array($view, $download, $configuration, $install) as $controller) $controller->checkPermission($request);
foreach (array($configuration, $install) as $writer) {
    $_SERVER['REQUEST_METHOD'] = 'GET'; $validCsrf = true;
    expectOutlookAddinError(function () use ($writer, $request) { $writer->validateRequest($request); }, 'GET accepted for POST action');
    $_SERVER['REQUEST_METHOD'] = 'POST'; $validCsrf = false;
    expectOutlookAddinError(function () use ($writer, $request) { $writer->validateRequest($request); }, 'Invalid CSRF token accepted');
    $validCsrf = true;
    checkOutlookAddin($writer->validateRequest($request), 'Valid POST rejected');
}
$site_URL = 'https://crm.example.net:8443/crm';
ob_start(); $download->process($request); $downloaded = ob_get_clean();
checkOutlookAddin($downloaded === $model->createManifest($site_URL), 'Manifest download differs from generated XML');
expectOutlookAddinError(function () use ($configuration, $request) { $configuration->process($request); }, 'Config download accepted missing required IDs');
$configRequest = new Vtiger_Request(array('module' => 'OutlookAddin', 'parent' => 'Settings', 'client_id' => $clientId, 'tenant_id' => $tenantId));
ob_start(); $configuration->process($configRequest); $downloaded = ob_get_clean();
checkOutlookAddin($downloaded === $model->createConfiguration($site_URL, $clientId, $tenantId), 'Configuration download differs');

// Use a temporary installation to verify that existing credentials are never exposed or overwritten.
$root = getcwd();
$fixture = sys_get_temp_dir() . '/berlicrm-outlook-settings-' . bin2hex(random_bytes(6));
mkdir($fixture . '/outlook-addin/rest', 0700, true);
copy($model::MANIFEST_PATH, $fixture . '/' . $model::MANIFEST_PATH);
$privateFile = $fixture . '/' . $model::CONFIG_PATH;
file_put_contents($privateFile, '<?php return ' . var_export(array('api_username' => 'PRIVATE-USER', 'api_access_key' => 'PRIVATE-KEY',
    'contacts_microsoft' => array('client_id' => $clientId, 'tenant_id' => $tenantId, 'client_secret' => 'PRIVATE-SECRET')), true) . ';');
$beforeConfig = hash_file('sha256', $privateFile);
try {
    chdir($fixture);
    checkOutlookAddin($model->getMicrosoftIds() === array('client_id' => $clientId, 'tenant_id' => $tenantId), 'Private fields exposed by settings');
    $viewer = new OutlookAddinTestViewer();
    $beforeView = $db->tables;
    $view->process($request);
    checkOutlookAddin($viewer->values['CONFIGURED'] && $viewer->values['MICROSOFT_IDS'] === array('client_id' => $clientId, 'tenant_id' => $tenantId), 'Configured IDs not shown');
    checkOutlookAddin(strpos(json_encode($viewer->values), 'PRIVATE-') === false, 'Private configuration leaked into page');
    checkOutlookAddin($db->tables === $beforeView && hash_file('sha256', $privateFile) === $beforeConfig, 'Settings page mutated configuration/database');
    $site_URL = 'http://crm.example.net';
    $view->process($request);
    checkOutlookAddin($viewer->values['ADDIN_URLS'] === array() && $viewer->values['MANIFEST_ERROR'] === 'LBL_OUTLOOK_INVALID_URL', 'Unsafe URL offered active links');
    unlink($privateFile);
    $site_URL = 'https://crm.example.net/crm';
    $view->process($request);
    checkOutlookAddin(!$viewer->values['CONFIGURED'] && $viewer->values['MICROSOFT_IDS'] === array('client_id' => '', 'tenant_id' => ''), 'Unconfigured fields must remain empty');
} finally {
    chdir($root);
    if (file_exists($privateFile)) unlink($privateFile);
    unlink($fixture . '/' . $model::MANIFEST_PATH);
    rmdir($fixture . '/outlook-addin/rest'); rmdir($fixture . '/outlook-addin'); rmdir($fixture);
}

foreach (array('de_de', 'en_us') as $language) {
    require 'languages/' . $language . '/Settings/OutlookAddin.php';
    $template = file_get_contents('layouts/vlayout/modules/Settings/OutlookAddin/Index.tpl');
    preg_match_all("/vtranslate\('([^']+)'/", $template, $matches);
    foreach ($matches[1] as $key) checkOutlookAddin(isset($languageStrings[$key]), 'Missing translation: ' . $language . '/' . $key);
}

if (isset($argv[1])) {
    require $argv[1];
    require 'languages/de_de/Settings/OutlookAddin.php';
    $GLOBALS['testLabels'] = $languageStrings;
    $smarty = class_exists('Smarty\\Smarty') ? new \Smarty\Smarty() : new Smarty();
    $compileDir = sys_get_temp_dir() . '/berlicrm-outlook-templates-' . bin2hex(random_bytes(6));
    mkdir($compileDir);
    $smarty->setCompileDir($compileDir);
    $smarty->registerPlugin('modifier', 'vtranslate', 'vtranslate');
    try {
        foreach (array(true, false) as $ready) {
            $values = array_merge($viewer->values, array('QUALIFIED_MODULE' => 'Settings:OutlookAddin',
                'CONFIGURED' => $ready, 'SETUP_SUCCESS' => $ready, 'SETUP_ERROR' => !$ready,
                'MISSING_FILES' => $ready ? array() : array('<missing>'),
                'ADDIN_URLS' => $ready ? $model->getUrls('https://crm.example.net/crm') : array(),
                'CRM_URL' => '<script>alert(1)</script>',
                'MICROSOFT_IDS' => $ready ? array('client_id' => '<img onerror=alert(1)>', 'tenant_id' => $tenantId) : array('client_id' => '', 'tenant_id' => ''),
                'MANIFEST_ERROR' => $ready ? '' : 'LBL_OUTLOOK_INVALID_URL'));
            $smarty->assign($values);
            $html = $smarty->fetch('file:' . $root . '/layouts/vlayout/modules/Settings/OutlookAddin/Index.tpl');
            checkOutlookAddin(strpos($html, '<script>') === false && strpos($html, '<missing>') === false && strpos($html, '<img') === false, 'Template failed to escape dynamic values');
            checkOutlookAddin((strpos($html, 'action=DownloadManifest') !== false) === $ready, 'Invalid manifest download offered');
            $dom = new DOMDocument();
            $previous = libxml_use_internal_errors(true);
            try { $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NONET); }
            finally { libxml_clear_errors(); libxml_use_internal_errors($previous); }
            $xpath = new DOMXPath($dom);
            foreach (array('client_id', 'tenant_id') as $field) {
                checkOutlookAddin($xpath->query('//input[@name="' . $field . '"][@required]')->length === 1, 'Missing required form field');
                checkOutlookAddin($xpath->evaluate('string(//input[@name="' . $field . '"]/@pattern)') === $viewer->values['GUID_PATTERN'], 'Malformed browser GUID pattern');
                if (!$ready) checkOutlookAddin($xpath->evaluate('string(//input[@name="' . $field . '"]/@value)') === '', 'Dummy must be a placeholder, not an input value');
                checkOutlookAddin($xpath->evaluate('string(//input[@name="' . $field . '"]/@placeholder)') === 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx', 'Missing format placeholder');
            }
            checkOutlookAddin(($xpath->query('//button[@id="outlookCheckConnection"][@disabled]')->length === 0) === $ready, 'Connection test enabled without saved configuration');
            if (isset($argv[2]) && $ready) {
                $smarty->assign(array_merge($values, array('CRM_URL' => 'https://crm.example.net/crm', 'MICROSOFT_IDS' => array('client_id' => $clientId, 'tenant_id' => $tenantId))));
                file_put_contents($argv[2], $smarty->fetch('file:' . $root . '/layouts/vlayout/modules/Settings/OutlookAddin/Index.tpl'));
            }
        }
    } finally {
        foreach (glob($compileDir . '/*') as $file) unlink($file);
        rmdir($compileDir);
    }
}
// Execute the fresh installer's actual Office setup against the schema's seeded
// menu rows. No configured CRM database or unrelated installation code is loaded.
$installerSource = file_get_contents('modules/Install/models/InitSchema.php');
checkOutlookAddin(preg_match('/public static function setCRMNOWmodifications\(\).*?\/\/ Office add-ins installation START\R(.*?)\/\/ Office add-ins installation END/s',
    $installerSource, $installBlock) === 1, 'Fresh installer does not initialize Office add-ins');
checkOutlookAddin(strpos($installerSource, '// Office add-ins installation END') < strpos($installerSource, '//last step, set info this system was installed'),
    'Installer marks setup complete before initializing Office add-ins');
$schema = file_get_contents('schema/DatabaseSchema.sql');
checkOutlookAddin(preg_match('/INSERT INTO `vtiger_settings_field` \(([^;]+?)\) VALUES\s*(.*?);/s', $schema, $seed) === 1,
    'Settings menu seed is missing');
preg_match_all('/`([^`]+)`/', $seed[1], $columns);
preg_match_all('/^\(([^\r\n]*)\)[,;]/m', $seed[2] . ';', $rows);
$seedMenus = array();
foreach ($rows[1] as $row) {
    $values = array_map('trim', str_getcsv($row, ',', "'", '\\'));
    $seedMenus[] = array_combine($columns[1], $values);
}
checkOutlookAddin(preg_match('/\((\d+), \'LBL_INTEGRATION\', \d+\)/', $schema, $integration) === 1, 'Integration block seed missing');
checkOutlookAddin(preg_match('/INSERT INTO `vtiger_settings_field_seq` \(`id`\) VALUES\s*\((\d+)\);/', $schema, $sequence) === 1,
    'Settings menu sequence seed missing');
$seedNames = array_column($seedMenus, 'name');
checkOutlookAddin(count(array_keys($seedNames, 'LBL_WORD_ADDIN', true)) === 1
    && count(array_keys($seedNames, 'LBL_OUTLOOK_ADDIN', true)) === 1, 'Schema does not seed both Office menus exactly once');
checkOutlookAddin((int) $sequence[1] >= max(array_column($seedMenus, 'fieldid')), 'Settings sequence trails seeded menu IDs');

require_once 'modules/Settings/WordAddin/models/Module.php';
$wordModel = new Settings_WordAddin_Module_Model();
foreach (array(false, true) as $legacyWordOnly) {
    $db = new PearDatabase();
    $db->tables['vtiger_settings_blocks'] = array(array('blockid' => (int) $integration[1], 'label' => 'LBL_INTEGRATION'));
    $db->tables['vtiger_settings_field'] = array_values(array_filter($seedMenus, static function ($row) use ($legacyWordOnly) {
        return !$legacyWordOnly || $row['name'] !== 'LBL_OUTLOOK_ADDIN';
    }));
    $db->tables['vtiger_settings_field_seq'] = array(array('id' => (int) $sequence[1]));
    $seededIds = array_column($db->tables['vtiger_settings_field'], 'fieldid', 'name');
    eval($installBlock[1]);
    checkOutlookAddin(!in_array(false, $wordModel->getOperationStatus(), true), 'Fresh installation did not register Word services');
    checkOutlookAddin(!in_array(false, $model->getOperationStatus(), true), 'Fresh installation did not register Outlook services');
    checkOutlookAddin(count($db->tables['vtiger_ws_operation']) === 9, 'Shared Office preferences operation was duplicated');
    $menus = $db->tables['vtiger_settings_field'];
    foreach (array('LBL_WORD_ADDIN', 'LBL_OUTLOOK_ADDIN') as $name) {
        $matches = array_values(array_filter($menus, static function ($row) use ($name) { return $row['name'] === $name; }));
        checkOutlookAddin(count($matches) === 1 && (int) $matches[0]['blockid'] === (int) $integration[1], 'Office menu missing or duplicated');
        if (isset($seededIds[$name])) checkOutlookAddin($matches[0]['fieldid'] === $seededIds[$name], 'Seeded Office menu ID changed');
    }
    foreach (array('calendar_link', 'contact_link', 'mail_link', 'mail_file') as $table) {
        checkOutlookAddin(array_key_exists('vtiger_outlook_' . $table, $db->tables), 'Fresh installer omitted an Outlook link table');
    }
    $db->tables['vtiger_outlook_mail_link'][] = array('userid' => 3, 'emailid' => '10x100');
    $installed = $db->tables;
    eval($installBlock[1]);
    checkOutlookAddin($db->tables === $installed, 'Repeating fresh Office setup changed seeded menus, service IDs or existing links');
}
$db->failOn = 'CREATE TABLE IF NOT EXISTS vtiger_outlook_calendar_link';
expectOutlookAddinError(static function () use ($installBlock) { eval($installBlock[1]); }, 'Fresh installer ignored failed Outlook initialization');
$db->failOn = '';

echo "PASS: fresh Office installation, seeded menus, repeatable registration, failure propagation, Outlook settings/repair, seven services, manifest URLs and identity, required GUIDs, executable PHP downloads, admin permissions, POST/CSRF and configuration privacy.\n";

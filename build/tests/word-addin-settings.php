<?php
/**
 * Tests Word add-in registration, manifest generation and settings access offline.
 *
 * Run: php build/tests/word-addin-settings.php
 * Uses an in-memory database fixture; never updates a configured CRM database.
 * An optional first argument points to a Smarty bootstrap for template checks.
 *
 * @package berliCRM.WordAddin.Tests
 */
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) throw new ErrorException($message, 0, $severity, $file, $line);
    return true;
});
chdir(dirname(__DIR__, 2));
require 'includes/runtime/BaseModel.php';
require 'modules/Settings/Vtiger/models/Module.php';
require 'modules/Settings/WordAddin/models/Module.php';
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
function checkWordAddin($condition, $message) {
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
function expectWordAddinError($callback, $message) {
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
function vtranslate($key, $module = '') { return $key; }
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
     * @return WordAddinTestViewer Test viewer.
     */
    public function getViewer($request) { return $GLOBALS['viewer']; }
}
/** Captures template variables while suppressing actual page output. */
class WordAddinTestViewer {
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
require 'modules/Settings/WordAddin/views/Index.php';
require 'modules/Settings/WordAddin/actions/Install.php';
require 'modules/Settings/WordAddin/actions/DownloadManifest.php';

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

$model = new Settings_WordAddin_Module_Model();
$templateHash = hash_file('sha256', $model::MANIFEST_PATH);
foreach (array('https://crm.example.net', 'https://crm.example.net/crm/', 'https://crm.example.net:8443/a/b/', 'https://[::1]:8443/crm/') as $base) {
    $xml = $model->createManifest($base);
    $document = new DOMDocument();
    checkWordAddin($document->loadXML($xml), 'Generated XML is invalid');
    $xpath = new DOMXPath($document);
    $xpath->registerNamespace('o', 'http://schemas.microsoft.com/office/appforoffice/1.1');
    checkWordAddin($xpath->evaluate('string(/o:OfficeApp/o:DefaultSettings/o:SourceLocation/@DefaultValue)') === rtrim($base, '/') . '/wordplugin-officejs/taskpane.html', 'Wrong task pane URL');
    checkWordAddin($xpath->evaluate('string(/o:OfficeApp/o:AppDomains/o:AppDomain)') === $model->getUrls($base)['origin'], 'AppDomain contains a path');
    checkWordAddin($xpath->evaluate('string(/o:OfficeApp/o:Id)') === 'fb7ac3f9-1c9a-4999-8a7a-f5f5f69e712c', 'Add-in ID changed');
    checkWordAddin($xpath->evaluate('string(/o:OfficeApp/o:Permissions)') === 'ReadWriteDocument', 'Permissions changed');
    checkWordAddin(strpos($xml, 'crm.example.org') === false, 'Example URL leaked into manifest');
}
foreach (array('', 'http://crm.test', '//crm.test', 'https://user:password@crm.test', 'https://crm.test/?key=secret', 'https://crm.test/#fragment', "https://crm.test/\r\nHeader:x", 'https://crm.test/back\\slash', array()) as $bad) {
    expectWordAddinError(function () use ($model, $bad) { $model->createManifest($bad); }, 'Unsafe URL accepted');
}
checkWordAddin(hash_file('sha256', $model::MANIFEST_PATH) === $templateHash, 'Template was overwritten');
checkWordAddin($model->getMissingFiles() === array(), 'Required runtime files are missing');

$db = new PearDatabase();
// Execute the actual Word update block without running unrelated CRM migrations.
$updaterSource = file_get_contents('db_update.php');
checkWordAddin(preg_match('/\/\/ Word add-in update START\R(.*?)\/\/ Word add-in update END/s', $updaterSource, $updateBlock) === 1,
    'Word add-in update block is missing');
ob_start();
try {
    eval($updateBlock[1]);
} finally {
    ob_end_clean();
}
checkWordAddin(count($db->tables['vtiger_settings_field']) === 1
    && $db->tables['vtiger_settings_field'][0]['blockid'] === 12,
    'CRM updater did not register the Word menu in Integration');
checkWordAddin(!in_array(false, $model->getOperationStatus(), true), 'CRM updater did not register both services with their parameters');
$updatedTables = $db->tables;
ob_start();
try {
    eval($updateBlock[1]);
} finally {
    ob_end_clean();
}
checkWordAddin($db->tables === $updatedTables, 'Repeated CRM update changed IDs or duplicated Word registrations');
$db = new PearDatabase();
checkWordAddin(!in_array(true, $model->getOperationStatus(), true), 'Uninstalled services reported ready');
$model->registerOperations();
checkWordAddin(!in_array(false, $model->getOperationStatus(), true), 'Registration incomplete');
$registered = $db->tables;
$model->registerOperations();
checkWordAddin($db->tables === $registered, 'Repeated installation duplicated services or parameters');
$db->tables['vtiger_ws_operation'][0]['prelogin'] = 1;
$db->tables['vtiger_ws_operation_parameters'][0]['type'] = 'boolean';
checkWordAddin(!$model->getOperationStatus()['getWordMailMergeData'], 'Broken registration reported ready');
$model->registerOperations();
checkWordAddin($db->tables === $registered, 'Repair did not restore services');
$db->failOn = 'INSERT INTO vtiger_ws_operation_parameters';
expectWordAddinError(function () use ($model) { $model->registerOperations(); }, 'Database failure was ignored');
checkWordAddin($db->tables === $registered, 'Failed repair partially committed');
$db->failOn = '';
$db->tables['vtiger_settings_field'][] = array('fieldid' => 100, 'blockid' => 7, 'sequence' => 20, 'name' => 'OTHER');
$model->registerSettingsMenu();
checkWordAddin($db->tables['vtiger_settings_field'][1]['fieldid'] === 101, 'Stale menu sequence caused an ID collision');
checkWordAddin($db->tables['vtiger_settings_field'][1]['blockid'] === 12, 'New Word menu was not placed in Integration');
$db->tables['vtiger_settings_field'][1]['pinned'] = 1;
$db->tables['vtiger_settings_field'][1]['blockid'] = 7;
$db->tables['vtiger_settings_field'][1]['sequence'] = 21;
$db->tables['vtiger_settings_field'][] = array('fieldid' => 200, 'blockid' => 12, 'sequence' => 8, 'name' => 'OTHER_INTEGRATION');
$model->registerSettingsMenu();
checkWordAddin(count($db->tables['vtiger_settings_field']) === 3, 'Moving Word menu duplicated it');
checkWordAddin($db->tables['vtiger_settings_field'][1]['fieldid'] === 101
    && $db->tables['vtiger_settings_field'][1]['pinned'] === 1
    && $db->tables['vtiger_settings_field'][1]['blockid'] === 12
    && $db->tables['vtiger_settings_field'][1]['sequence'] === 9,
    'Migration to Integration did not preserve ID/pin or append to the existing menu');
$menuTables = $db->tables;
$model->registerSettingsMenu();
checkWordAddin($db->tables === $menuTables, 'Repeated menu registration duplicated menu or reset pin');

$request = new Vtiger_Request(array());
$install = new Settings_WordAddin_Install_Action();
$download = new Settings_WordAddin_DownloadManifest_Action();
$view = new Settings_WordAddin_Index_View();
foreach (array($install, $download, $view) as $controller) {
    expectWordAddinError(function () use ($controller, $request) { $controller->checkPermission($request); }, 'Non-admin accessed Word settings');
}
Users_Record_Model::$admin = true;
foreach (array($install, $download, $view) as $controller) $controller->checkPermission($request);
$_SERVER['REQUEST_METHOD'] = 'GET';
$validCsrf = true;
expectWordAddinError(function () use ($install, $request) { $install->validateRequest($request); }, 'Install accepted GET');
$_SERVER['REQUEST_METHOD'] = 'POST';
$validCsrf = false;
expectWordAddinError(function () use ($install, $request) { $install->validateRequest($request); }, 'Install accepted invalid CSRF token');
$validCsrf = true;
checkWordAddin($install->validateRequest($request), 'Valid POST rejected');
$site_URL = 'https://crm.example.net:8443/crm';
ob_start();
$download->process($request);
$downloaded = ob_get_clean();
checkWordAddin($downloaded === $model->createManifest($site_URL), 'Download differs from generated manifest');

$viewer = new WordAddinTestViewer();
$pageRequest = new Vtiger_Request(array('module' => 'WordAddin', 'parent' => 'Settings'));
$beforeView = $db->tables;
$view->process($pageRequest);
checkWordAddin($viewer->values['READY'], 'Complete installation reported incomplete');
checkWordAddin($db->tables === $beforeView, 'Page visit modified the database');
$site_URL = 'http://crm.example.net';
$view->process($pageRequest);
checkWordAddin(!$viewer->values['READY'] && $viewer->values['MANIFEST_ERROR'] === 'LBL_WORD_ADDIN_INVALID_URL', 'HTTP installation offered a manifest');
checkWordAddin($viewer->values['ADDIN_URLS'] === array(), 'Invalid URL exposed active links');

// Optionally smoke-test the template with an installed Smarty bootstrap/class file.
if (isset($argv[1])) {
    require $argv[1];
    $smarty = class_exists('Smarty\\Smarty') ? new \Smarty\Smarty() : new Smarty();
    $compileDir = sys_get_temp_dir() . '/berlicrm-word-templates-' . bin2hex(random_bytes(6));
    mkdir($compileDir);
    $smarty->setCompileDir($compileDir);
    $smarty->registerPlugin('modifier', 'vtranslate', 'vtranslate');
    foreach (array(true, false) as $ready) {
        $smarty->assign(array_merge($viewer->values, array(
            'READY' => $ready, 'SETUP_SUCCESS' => $ready, 'SETUP_ERROR' => !$ready,
            'MISSING_FILES' => $ready ? array() : array('<missing>'),
            'ADDIN_URLS' => $ready ? $model->getUrls('https://crm.example.net/crm') : array(),
            'CRM_URL' => '<script>alert(1)</script>',
            'MANIFEST_ERROR' => $ready ? '' : 'LBL_WORD_ADDIN_INVALID_URL'
        )));
        $html = $smarty->fetch('file:' . getcwd() . '/layouts/vlayout/modules/Settings/WordAddin/Index.tpl');
        checkWordAddin(strpos($html, '<script>') === false && strpos($html, '<missing>') === false, 'Template failed to escape dynamic values');
        checkWordAddin((strpos($html, 'action=DownloadManifest') !== false) === $ready, 'Template offered invalid manifest download');
        if (!$ready) checkWordAddin(strpos($html, 'disabled="disabled"') !== false, 'Missing files did not disable setup');
    }
    foreach (glob($compileDir . '/*') as $file) unlink($file);
    rmdir($compileDir);
}

foreach (array('de_de', 'en_us') as $language) {
    require 'languages/' . $language . '/Settings/WordAddin.php';
    $template = file_get_contents('layouts/vlayout/modules/Settings/WordAddin/Index.tpl');
    preg_match_all("/vtranslate\('([^']+)'/", $template, $matches);
    foreach ($matches[1] as $key) checkWordAddin(isset($languageStrings[$key]), 'Missing translation: ' . $language . '/' . $key);
}
echo "PASS: manifest URLs/XML, template preservation, runtime files, service registration/repair/rollback, menu idempotency, admin permissions, POST/CSRF, download and settings page.\n";

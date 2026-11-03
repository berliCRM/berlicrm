<?php

/**
 * Provides manifest generation and repeatable Word add-in installation support.
 *
 * File paths are relative to the CRM root. Registration is shared by the
 * authenticated admin actions, the CRM updater and the CLI installer.
 *
 * @package berliCRM.WordAddin
 */

/** Manages the Word add-in settings, required assets and service registrations. */
class Settings_WordAddin_Module_Model extends Settings_Vtiger_Module_Model {
    /** @var string Settings module name used by the CRM component loader. */
    var $name = 'WordAddin';

    /** @var string Relative route to the administrator installation page. */
    const INDEX_URL = 'index.php?module=WordAddin&parent=Settings&view=Index';
    /** @var string CRM-relative path to the unchanged XML manifest template. */
    const MANIFEST_PATH = 'wordplugin-officejs/manifest.xml';

    /**
     * Defines authenticated POST handlers and their ordered string parameters.
     *
     * @return array<string, array{path: string, method: string, params: string[]}>
     */
    public static function getOperations() {
        return array(
            'getWordMailMergeData' => array(
                'path' => 'include/Webservices/Custom/getWordMailMergeData.php',
                'method' => 'berli_getWordMailMergeData',
                'params' => array('moduleName', 'listId', 'page', 'recordIds', 'fieldNames')
            ),
            'saveWordMailMergeDocument' => array(
                'path' => 'include/Webservices/Custom/saveWordMailMergeDocument.php',
                'method' => 'berli_saveWordMailMergeDocument',
                'params' => array('moduleName', 'listId', 'recordId', 'notesTitle', 'fileContent')
            )
        );
    }

    /**
     * Lists the manifest, static assets and handlers needed by the add-in.
     *
     * @return string[] Paths relative to the CRM root.
     */
    public static function getRequiredFiles() {
        return array(
            self::MANIFEST_PATH, 'wordplugin-officejs/taskpane.html',
            'wordplugin-officejs/assets/icon-32.png', 'wordplugin-officejs/assets/icon-80.png',
            'wordplugin-officejs/src/taskpane.js', 'wordplugin-officejs/src/taskpane.css',
            'wordplugin-officejs/src/crm-client.js', 'wordplugin-officejs/src/zip.js',
            'wordplugin-officejs/src/docx-engine.js', 'wordplugin-officejs/src/docx-export.js',
            'wordplugin-officejs/src/vendor/fflate-0.8.3.js', 'wordplugin-officejs/src/vendor/fflate-LICENSE.txt',
            'include/Webservices/Custom/getWordMailMergeData.php',
            'include/Webservices/Custom/saveWordMailMergeDocument.php',
            'include/Webservices/Custom/WordMailMergeDocx.php'
        );
    }

    /**
     * Finds required files missing from or unreadable on the local server.
     *
     * This does not verify HTTPS reachability or certificate trust on clients.
     *
     * @return string[] Missing or unreadable CRM-relative paths.
     */
    public static function getMissingFiles() {
        return array_values(array_filter(self::getRequiredFiles(), function ($path) {
            return !is_readable($path);
        }));
    }

    /**
     * Validates the configured HTTPS CRM URL and removes trailing slashes.
     *
     * Request Host/proxy headers are never used to generate manifest addresses.
     *
     * @param mixed $url Configured site_URL; expected to be a string.
     * @return string HTTPS URL without credentials, query or fragment.
     * @throws InvalidArgumentException If the configured URL is unsuitable.
     */
    public static function getCrmUrl($url) {
        if (!is_string($url) || preg_match('/[\x00-\x20\x7f\\\\]/', $url)) {
            throw new InvalidArgumentException('LBL_WORD_ADDIN_INVALID_URL');
        }
        $parts = parse_url($url);
        if (!$parts || !isset($parts['scheme'], $parts['host']) || strtolower($parts['scheme']) !== 'https'
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])
            || !filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('LBL_WORD_ADDIN_INVALID_URL');
        }
        return rtrim($url, '/');
    }

    /**
     * Builds CRM and asset URLs, preserving installation subpaths and ports.
     *
     * @param string $url Configured HTTPS CRM URL.
     * @return array{crm: string, origin: string, taskpane: string, icon32: string, icon80: string}
     * @throws InvalidArgumentException If the configured URL is unsuitable.
     */
    public static function getUrls($url) {
        $base = self::getCrmUrl($url);
        $parts = parse_url($base);
        $origin = 'https://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
        return array(
            'crm' => $base . '/',
            'origin' => $origin,
            'taskpane' => $base . '/wordplugin-officejs/taskpane.html',
            'icon32' => $base . '/wordplugin-officejs/assets/icon-32.png',
            'icon80' => $base . '/wordplugin-officejs/assets/icon-80.png'
        );
    }

    /**
     * Generates installation-specific XML without modifying the template file.
     *
     * Replaces only asset, support and application-domain URLs; preserves the
     * shipped add-in identity, version and document permissions.
     *
     * @param string $url Configured HTTPS CRM URL.
     * @return string Serialized Office add-in XML manifest.
     * @throws InvalidArgumentException If the configured URL is unsuitable.
     * @throws RuntimeException If the template is missing or structurally invalid.
     */
    public static function createManifest($url) {
        $urls = self::getUrls($url);
        if (!is_readable(self::MANIFEST_PATH)) {
            throw new RuntimeException('LBL_WORD_ADDIN_MANIFEST_ERROR');
        }
        $document = new DOMDocument();
        $previousErrors = libxml_use_internal_errors(true);
        try {
            $loaded = $document->load(self::MANIFEST_PATH, LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrors);
        }
        if (!$loaded) {
            throw new RuntimeException('LBL_WORD_ADDIN_MANIFEST_ERROR');
        }
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('o', 'http://schemas.microsoft.com/office/appforoffice/1.1');
        $replacements = array(
            '/o:OfficeApp/o:IconUrl' => $urls['icon32'],
            '/o:OfficeApp/o:HighResolutionIconUrl' => $urls['icon80'],
            '/o:OfficeApp/o:SupportUrl' => $urls['crm'],
            '/o:OfficeApp/o:DefaultSettings/o:SourceLocation' => $urls['taskpane']
        );
        foreach ($replacements as $query => $value) {
            $nodes = $xpath->query($query);
            if ($nodes->length !== 1) {
                throw new RuntimeException('LBL_WORD_ADDIN_MANIFEST_ERROR');
            }
            $nodes->item(0)->setAttribute('DefaultValue', $value);
        }
        $domains = $xpath->query('/o:OfficeApp/o:AppDomains/o:AppDomain');
        if ($domains->length !== 1) {
            throw new RuntimeException('LBL_WORD_ADDIN_MANIFEST_ERROR');
        }
        $domains->item(0)->textContent = $urls['origin'];
        return $document->saveXML();
    }

    /**
     * Executes a parameterized database query, rejecting reported failures.
     *
     * @param string $sql Query with positional placeholders.
     * @param array $params Values bound to the placeholders.
     * @return object|bool Database result or successful write status.
     * @throws RuntimeException If PearDatabase reports a query failure.
     */
    protected static function query($sql, $params = array()) {
        $result = PearDatabase::getInstance()->pquery($sql, $params);
        if ($result === false) {
            throw new RuntimeException('LBL_WORD_ADDIN_DATABASE_ERROR');
        }
        return $result;
    }

    /**
     * Checks handler paths, POST/login requirements and ordered parameters.
     *
     * @return array<string, bool> Registration validity keyed by operation name.
     * @throws RuntimeException If registration metadata cannot be queried.
     */
    public static function getOperationStatus() {
        $db = PearDatabase::getInstance();
        $status = array();
        foreach (self::getOperations() as $name => $operation) {
            $result = self::query('SELECT * FROM vtiger_ws_operation WHERE name = ?', array($name));
            $valid = $db->num_rows($result) === 1;
            if ($valid) {
                $id = $db->query_result($result, 0, 'operationid');
                $valid = $db->query_result($result, 0, 'handler_path') === $operation['path']
                    && $db->query_result($result, 0, 'handler_method') === $operation['method']
                    && $db->query_result($result, 0, 'type') === 'POST'
                    && (string) $db->query_result($result, 0, 'prelogin') === '0';
                $parameters = self::query(
                    'SELECT name, type, sequence FROM vtiger_ws_operation_parameters WHERE operationid = ? ORDER BY sequence', array($id)
                );
                $valid = $valid && $db->num_rows($parameters) === count($operation['params']);
                foreach ($operation['params'] as $index => $param) {
                    if ($index >= $db->num_rows($parameters)) {
                        $valid = false;
                        break;
                    }
                    $valid = $valid && $db->query_result($parameters, $index, 'name') === $param
                        && $db->query_result($parameters, $index, 'type') === 'string'
                        && (int) $db->query_result($parameters, $index, 'sequence') === $index + 1;
                }
            }
            $status[$name] = $valid;
        }
        return $status;
    }

    /**
     * Creates or repairs both mail-merge web-service registrations.
     *
     * Called by an authorized admin POST, db_update.php or the CLI installer.
     * Existing operation IDs are retained, parameters are replaced in order,
     * and failures roll back changes made within the registration transaction.
     *
     * @return void
     * @throws RuntimeException If handlers are unreadable or registration fails.
     * @throws Throwable Propagates unexpected failures after transaction cleanup.
     */
    public static function registerOperations() {
        foreach (self::getOperations() as $operation) {
            if (!is_readable($operation['path'])) {
                throw new RuntimeException('LBL_WORD_ADDIN_FILES_MISSING');
            }
        }
        $db = PearDatabase::getInstance();
        $db->startTransaction();
        try {
            foreach (self::getOperations() as $name => $operation) {
                $result = self::query('SELECT operationid FROM vtiger_ws_operation WHERE name = ?', array($name));
                if ($db->num_rows($result) > 1) {
                    throw new RuntimeException('LBL_WORD_ADDIN_DATABASE_ERROR');
                }
                if ($db->num_rows($result)) {
                    $id = $db->query_result($result, 0, 'operationid');
                    self::query('UPDATE vtiger_ws_operation SET handler_path = ?, handler_method = ?, type = ?, prelogin = 0 WHERE operationid = ?',
                        array($operation['path'], $operation['method'], 'POST', $id));
                    self::query('DELETE FROM vtiger_ws_operation_parameters WHERE operationid = ?', array($id));
                } else {
                    $id = $db->getUniqueID('vtiger_ws_operation');
                    self::query('INSERT INTO vtiger_ws_operation (operationid, name, handler_path, handler_method, type, prelogin) VALUES (?, ?, ?, ?, ?, 0)',
                        array($id, $name, $operation['path'], $operation['method'], 'POST'));
                }
                foreach ($operation['params'] as $index => $param) {
                    self::query('INSERT INTO vtiger_ws_operation_parameters (operationid, name, type, sequence) VALUES (?, ?, ?, ?)',
                        array($id, $param, 'string', $index + 1));
                }
            }
            if ($db->hasFailedTransaction()) {
                throw new RuntimeException('LBL_WORD_ADDIN_DATABASE_ERROR');
            }
        } catch (Throwable $error) {
            $db->database->FailTrans();
            $db->completeTransaction();
            throw $error;
        }
        $db->completeTransaction();
    }

    /**
     * Creates the Integration menu entry or moves an existing Word entry there.
     *
     * Preserves existing entry IDs and pin settings, and appends moved/new
     * entries to the Integration block. Repeat calls do not duplicate entries.
     *
     * @return void
     * @throws RuntimeException If Integration is missing or a database query fails.
     */
    public static function registerSettingsMenu() {
        $db = PearDatabase::getInstance();
        $block = self::query('SELECT blockid FROM vtiger_settings_blocks WHERE label = ?', array('LBL_INTEGRATION'));
        if (!$db->num_rows($block)) {
            throw new RuntimeException('LBL_WORD_ADDIN_DATABASE_ERROR');
        }
        $blockId = $db->query_result($block, 0, 'blockid');
        $existing = self::query('SELECT fieldid, blockid FROM vtiger_settings_field WHERE name = ?', array('LBL_WORD_ADDIN'));
        if ($db->num_rows($existing)) {
            if ((string) $db->query_result($existing, 0, 'blockid') !== (string) $blockId) {
                $sequence = self::query('SELECT MAX(sequence) AS sequence FROM vtiger_settings_field WHERE blockid = ?', array($blockId));
                self::query('UPDATE vtiger_settings_field SET blockid = ?, sequence = ?, linkto = ?, active = 0 WHERE name = ?',
                    array($blockId, (int) $db->query_result($sequence, 0, 'sequence') + 1, self::INDEX_URL, 'LBL_WORD_ADDIN'));
            } else {
                self::query('UPDATE vtiger_settings_field SET linkto = ?, active = 0 WHERE name = ?', array(self::INDEX_URL, 'LBL_WORD_ADDIN'));
            }
            return;
        }
        $sequence = self::query('SELECT MAX(sequence) AS sequence FROM vtiger_settings_field WHERE blockid = ?', array($blockId));
        $id = $db->getUniqueID('vtiger_settings_field');
        // Some historical installers inserted menu IDs without advancing the sequence.
        $max = self::query('SELECT MAX(fieldid) AS fieldid FROM vtiger_settings_field');
        $maxId = (int) $db->query_result($max, 0, 'fieldid');
        if ($id <= $maxId) {
            $id = $maxId + 1;
            self::query('UPDATE vtiger_settings_field_seq SET id = ?', array($id));
        }
        self::query('INSERT INTO vtiger_settings_field (fieldid, blockid, name, iconpath, description, linkto, sequence, active, pinned) VALUES (?, ?, ?, ?, ?, ?, ?, 0, 0)',
            array($id, $blockId, 'LBL_WORD_ADDIN', 'migrate.gif', 'LBL_WORD_ADDIN_DESCRIPTION', self::INDEX_URL,
                (int) $db->query_result($sequence, 0, 'sequence') + 1));
    }
}

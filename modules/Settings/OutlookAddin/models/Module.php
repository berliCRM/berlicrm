<?php

/**
 * Registers the Outlook add-in web services without changing the existing
 * template-based send_mail_with_attachments service.
 */
class Settings_OutlookAddin_Module_Model extends Settings_Vtiger_Module_Model {
    /** @var string Settings module name used by the CRM component loader. */
    var $name = 'OutlookAddin';
    const INDEX_URL = 'index.php?module=OutlookAddin&parent=Settings&view=Index';
    const MANIFEST_PATH = 'outlook-addin/manifest.xml';
    const CONFIG_PATH = 'outlook-addin/rest/outlook.config.php';
    const OPERATION_NAME = 'outlook_send_mail';
    const HANDLER_PATH = 'include/Webservices/Custom/OutlookSendMail.php';
    const HANDLER_METHOD = 'berli_outlookSendMail';
    const VERTEILER_CONTACTS_OPERATION_NAME = 'outlook_get_verteiler_contacts';
    const VERTEILER_CONTACTS_HANDLER_PATH = 'include/Webservices/Custom/OutlookGetVerteilerContacts.php';
    const VERTEILER_CONTACTS_HANDLER_METHOD = 'berli_outlookGetVerteilerContacts';
    const EVENT_DISPLAY_OPERATION_NAME = 'outlook_get_event_display';
    const EVENT_DISPLAY_HANDLER_PATH = 'include/Webservices/Custom/OutlookGetEventDisplay.php';
    const EVENT_DISPLAY_HANDLER_METHOD = 'berli_outlookGetEventDisplay';

    /**
     * Creates add-in-owned link tables and registers or repairs all six Outlook operations.
     *
     * @return void
     * @throws RuntimeException When schema or registration queries fail.
     */
    public static function registerOperation() {
        // Add-in-owned link data; no existing CRM tables or privileges are altered.
        self::query('CREATE TABLE IF NOT EXISTS vtiger_outlook_calendar_link ('
            . 'userid INT NOT NULL, sourcehash CHAR(64) NOT NULL, eventid VARCHAR(64) NULL, '
            . 'state VARCHAR(16) NOT NULL, PRIMARY KEY (userid, sourcehash)) ENGINE=InnoDB', array());
        self::query('CREATE TABLE IF NOT EXISTS vtiger_outlook_contact_link ('
            . 'userid INT NOT NULL, sourcehash CHAR(64) NOT NULL, contactid VARCHAR(64) NULL, '
            . 'state VARCHAR(16) NOT NULL, PRIMARY KEY (userid, sourcehash)) ENGINE=InnoDB', array());
        self::query('CREATE TABLE IF NOT EXISTS vtiger_outlook_mail_link ('
            . 'userid INT NOT NULL, sourcehash CHAR(64) NOT NULL, emailid VARCHAR(64) NULL, '
            . 'state VARCHAR(16) NOT NULL, PRIMARY KEY (userid, sourcehash)) ENGINE=InnoDB', array());
        self::query('CREATE TABLE IF NOT EXISTS vtiger_outlook_mail_file ('
            . 'userid INT NOT NULL, sourcehash CHAR(64) NOT NULL, filehash CHAR(64) NOT NULL, '
            . 'attachmentid INT NULL, state VARCHAR(16) NOT NULL, '
            . 'PRIMARY KEY (userid, sourcehash, filehash)) ENGINE=InnoDB', array());
        foreach (self::getOperations() as $operation) {
            self::registerWebserviceOperation($operation);
        }
    }

    /**
     * Lists the Outlook POST operations, handlers and ordered parameter definitions.
     *
     * @return array
     */
    public static function getOperations() {
        return array(
            array(
                'name' => self::OPERATION_NAME,
                'path' => self::HANDLER_PATH,
                'method' => self::HANDLER_METHOD,
                'params' => array(
                    array('receiverIds', 'encoded'),
                    array('templateId', 'string'),
                    array('subject', 'string'),
                    array('body', 'string'),
                    array('documentIds', 'encoded'),
                    array('mailFieldToUse', 'string'),
                    array('fromId', 'string'),
                    array('fromName', 'string'),
                    array('attachments', 'encoded')
                )
            ),
            array(
                'name' => self::VERTEILER_CONTACTS_OPERATION_NAME,
                'path' => self::VERTEILER_CONTACTS_HANDLER_PATH,
                'method' => self::VERTEILER_CONTACTS_HANDLER_METHOD,
                'params' => array(array('verteilerId', 'string'))
            ),
            array(
                'name' => self::EVENT_DISPLAY_OPERATION_NAME,
                'path' => self::EVENT_DISPLAY_HANDLER_PATH,
                'method' => self::EVENT_DISPLAY_HANDLER_METHOD,
                'params' => array()
            ),
            array(
                'name' => 'outlook_calendar_event',
                'path' => 'include/Webservices/Custom/OutlookCalendarEvent.php',
                'method' => 'berli_outlookCalendarEvent',
                'params' => array(array('input', 'encoded'))
            ),
            array(
                'name' => 'outlook_contact_transfer',
                'path' => 'include/Webservices/Custom/OutlookContactTransfer.php',
                'method' => 'berli_outlookContactTransfer',
                'params' => array(array('input', 'encoded'))
            ),
            array(
                'name' => 'outlook_archive_email',
                'path' => 'include/Webservices/Custom/OutlookArchiveEmail.php',
                'method' => 'berli_outlookArchiveEmail',
                'params' => array(array('input', 'encoded'))
            )
        );

    }

    /**
     * Validates the configured HTTPS CRM URL and derives add-in endpoint addresses.
     *
     * @param mixed $url Configured CRM installation URL.
     * @return array<string,string>
     * @throws InvalidArgumentException On unsafe or invalid CRM URLs.
     */
    public static function getUrls($url) {
        if (!is_string($url) || preg_match('/[\x00-\x20\x7f\\\\]/', $url)) {
            throw new InvalidArgumentException('LBL_OUTLOOK_INVALID_URL');
        }
        $parts = parse_url($url);
        if (!$parts || strtolower($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])
            || !filter_var($url, FILTER_VALIDATE_URL)) throw new InvalidArgumentException('LBL_OUTLOOK_INVALID_URL');
        $base = rtrim($url, '/');
        return array('crm' => $base, 'origin' => 'https://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : ''),
            'webservice' => $base . '/webservice.php', 'taskpane' => $base . '/outlook-addin/taskpane.html',
            'redirect' => $base . '/outlook-addin/contacts-auth.html');
    }

    /**
     * Generates installation-specific XML without modifying the manifest template.
     *
     * @param string $url Configured HTTPS CRM installation URL.
     * @return string Downloadable Outlook XML manifest.
     * @throws InvalidArgumentException If the CRM URL is invalid.
     * @throws RuntimeException If the template is unreadable or malformed.
     */
    public static function createManifest($url) {
        $urls = self::getUrls($url);
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try { $loaded = is_readable(self::MANIFEST_PATH) && $document->load(self::MANIFEST_PATH, LIBXML_NONET); }
        finally { libxml_clear_errors(); libxml_use_internal_errors($previous); }
        if (!$loaded) throw new RuntimeException('LBL_OUTLOOK_MANIFEST_ERROR');
        $xpath = new DOMXPath($document);
        $placeholder = 'https://crm.example.org';
        foreach ($xpath->query('//*[@DefaultValue]') as $node) {
            $value = $node->getAttribute('DefaultValue');
            if (strpos($value, $placeholder) === 0) $node->setAttribute('DefaultValue', $urls['crm'] . substr($value, strlen($placeholder)));
        }
        $domains = $xpath->query('//*[local-name()="AppDomains"]/*[local-name()="AppDomain"]');
        if ($domains->length !== 1) throw new RuntimeException('LBL_OUTLOOK_MANIFEST_ERROR');
        $domains->item(0)->textContent = $urls['origin'];
        $xml = $document->saveXML();
        if ($xml === false || strpos($xml, $placeholder) !== false) throw new RuntimeException('LBL_OUTLOOK_MANIFEST_ERROR');
        return $xml;
    }

    /**
     * Validates and normalizes an Entra client or tenant GUID.
     *
     * @param mixed $value Candidate GUID.
     * @param string $field Client or tenant field name for validation messages.
     * @return string
     * @throws InvalidArgumentException If the GUID is invalid or missing.
     */
    public static function validateMicrosoftId($value, $field) {
        if (!is_string($value) || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/iD', trim($value))) {
            throw new InvalidArgumentException($field === 'client_id' ? 'LBL_OUTLOOK_CLIENT_REQUIRED' : 'LBL_OUTLOOK_TENANT_REQUIRED');
        }
        return strtolower(trim($value));
    }

    /**
     * Generates a PHP configuration download containing public Entra IDs and no credentials.
     *
     * @param string $url Configured CRM installation URL.
     * @param mixed $clientId Entra application/client GUID.
     * @param mixed $tenantId Entra directory/tenant GUID.
     * @return string
     * @throws InvalidArgumentException If the URL or required IDs are invalid.
     */
    public static function createConfiguration($url, $clientId, $tenantId) {
        $urls = self::getUrls($url);
        $config = array('crm_webservice_url' => $urls['webservice'], 'default_request_timeout' => 30, 'recipient_limit' => 500,
            'contacts_microsoft' => array('client_id' => self::validateMicrosoftId($clientId, 'client_id'),
                'tenant_id' => self::validateMicrosoftId($tenantId, 'tenant_id'), 'redirect_uri' => $urls['redirect']));
        return "<?php\n\n/**\n * Installation-specific Outlook add-in settings.\n * No CRM credentials or Microsoft client secret are stored here.\n *\n * @package berliCRM.OutlookAddin\n */\nreturn "
            . var_export($config, true) . ";\n";
    }

    /**
     * Reads only public Entra IDs from the private installation configuration.
     *
     * @return array{client_id:string,tenant_id:string} IDs or empty setup values.
     * @throws RuntimeException If an existing configuration cannot be read safely.
     */
    public static function getMicrosoftIds() {
        if (!file_exists(self::CONFIG_PATH)) return array('client_id' => '', 'tenant_id' => '');
        if (!is_readable(self::CONFIG_PATH)) throw new RuntimeException('LBL_OUTLOOK_CONFIG_ERROR');
        $config = (static function ($path) { return require $path; })(self::CONFIG_PATH);
        if (!is_array($config)) throw new RuntimeException('LBL_OUTLOOK_CONFIG_ERROR');
        $settings = $config['contacts_microsoft'] ?? array();
        return array('client_id' => is_string($settings['client_id'] ?? null) ? $settings['client_id'] : '',
            'tenant_id' => is_string($settings['tenant_id'] ?? null) ? $settings['tenant_id'] : '');
    }

    /**
     * Lists unreadable runtime assets and handlers required by the Outlook add-in.
     *
     * @return string[]
     */
    public static function getMissingFiles() {
        $files = array(self::MANIFEST_PATH, 'outlook-addin/taskpane.html', 'outlook-addin/taskpane.js', 'outlook-addin/settings-connection.js',
            'outlook-addin/calendar.js', 'outlook-addin/mail-read.js', 'outlook-addin/contacts.html', 'outlook-addin/contacts.js',
            'outlook-addin/contacts-auth.html', 'outlook-addin/contacts-auth.css', 'outlook-addin/contacts.css',
            'outlook-addin/contact-model.js', 'outlook-addin/contact-graph.js',
            'outlook-addin/assets/contacts-auth.bundle.js', 'outlook-addin/assets/contacts-sso.bundle.js',
            'outlook-addin/assets/icon-32.png', 'outlook-addin/assets/icon-80.png',
            'outlook-addin/rest/outlook.php', 'outlook-addin/rest/OutlookContactsConfig.php', 'outlook-addin/rest/OutlookWebserviceClient.php');
        foreach (self::getOperations() as $operation) $files[] = $operation['path'];
        return array_values(array_filter($files, function ($file) { return !is_readable($file); }));
    }

    /**
     * Checks registered handlers, authenticated POST requirements and parameter order.
     *
     * @return array<string,bool>
     * @throws RuntimeException If registration metadata cannot be queried.
     */
    public static function getOperationStatus() {
        $db = PearDatabase::getInstance();
        $status = array();
        foreach (self::getOperations() as $operation) {
            $result = self::query('SELECT * FROM vtiger_ws_operation WHERE name = ?', array($operation['name']));
            $valid = $db->num_rows($result) === 1;
            if ($valid) {
                $id = $db->query_result($result, 0, 'operationid');
                $valid = $db->query_result($result, 0, 'handler_path') === $operation['path']
                    && $db->query_result($result, 0, 'handler_method') === $operation['method']
                    && $db->query_result($result, 0, 'type') === 'POST'
                    && (string) $db->query_result($result, 0, 'prelogin') === '0';
                $parameters = self::query('SELECT name, type, sequence FROM vtiger_ws_operation_parameters WHERE operationid = ? ORDER BY sequence', array($id));
                $valid = $valid && $db->num_rows($parameters) === count($operation['params']);
                foreach ($operation['params'] as $index => $param) {
                    $valid = $valid && $db->query_result($parameters, $index, 'name') === $param[0]
                        && $db->query_result($parameters, $index, 'type') === $param[1]
                        && (int) $db->query_result($parameters, $index, 'sequence') === $index + 1;
                }
            }
            $status[$operation['name']] = $valid;
        }
        return $status;
    }

    /**
     * Creates or repairs the administrator settings entry in the Integration block.
     *
     * @return void
     * @throws RuntimeException On missing settings metadata or failed SQL.
     */
    public static function registerSettingsMenu() {
        $db = PearDatabase::getInstance();
        $block = self::query('SELECT blockid FROM vtiger_settings_blocks WHERE label = ?', array('LBL_INTEGRATION'));
        if ($db->num_rows($block) !== 1) throw new RuntimeException('LBL_OUTLOOK_DATABASE_ERROR');
        $blockId = $db->query_result($block, 0, 'blockid');
        $existing = self::query('SELECT fieldid, blockid FROM vtiger_settings_field WHERE name = ?', array('LBL_OUTLOOK_ADDIN'));
        if ($db->num_rows($existing) > 1) throw new RuntimeException('LBL_OUTLOOK_DATABASE_ERROR');
        if ($db->num_rows($existing)) {
            if ((string) $db->query_result($existing, 0, 'blockid') !== (string) $blockId) {
                $sequence = self::query('SELECT MAX(sequence) AS sequence FROM vtiger_settings_field WHERE blockid = ?', array($blockId));
                self::query('UPDATE vtiger_settings_field SET blockid = ?, sequence = ?, linkto = ?, active = 0 WHERE name = ?',
                    array($blockId, (int) $db->query_result($sequence, 0, 'sequence') + 1, self::INDEX_URL, 'LBL_OUTLOOK_ADDIN'));
            } else {
                self::query('UPDATE vtiger_settings_field SET linkto = ?, active = 0 WHERE name = ?', array(self::INDEX_URL, 'LBL_OUTLOOK_ADDIN'));
            }
            return;
        }
        $sequence = self::query('SELECT MAX(sequence) AS sequence FROM vtiger_settings_field WHERE blockid = ?', array($blockId));
        $id = $db->getUniqueID('vtiger_settings_field');
        $max = self::query('SELECT MAX(fieldid) AS fieldid FROM vtiger_settings_field', array());
        if ($id <= (int) $db->query_result($max, 0, 'fieldid')) {
            $id = (int) $db->query_result($max, 0, 'fieldid') + 1;
            self::query('UPDATE vtiger_settings_field_seq SET id = ?', array($id));
        }
        self::query('INSERT INTO vtiger_settings_field (fieldid, blockid, name, iconpath, description, linkto, sequence, active, pinned) VALUES (?, ?, ?, ?, ?, ?, ?, 0, 0)',
            array($id, $blockId, 'LBL_OUTLOOK_ADDIN', 'migrate.gif', 'LBL_OUTLOOK_ADDIN_DESCRIPTION', self::INDEX_URL,
                (int) $db->query_result($sequence, 0, 'sequence') + 1));
    }

    /**
     * Registers one authenticated operation transactionally, retaining its existing ID.
     *
     * @param array<string,mixed> $operation Handler, method and ordered parameters.
     * @return void
     * @throws RuntimeException On missing handlers or failed registration.
     */
    private static function registerWebserviceOperation(array $operation) {
        if (!is_readable($operation['path'])) {
            throw new RuntimeException('Outlook add-in handler is missing: ' . $operation['path']);
        }

        $db = PearDatabase::getInstance();
        $db->startTransaction();
        try {
            $operationResult = self::query(
                'SELECT operationid FROM vtiger_ws_operation WHERE name = ?',
                array($operation['name'])
            );
            if ($db->num_rows($operationResult) > 1) {
                throw new RuntimeException('Multiple registrations exist for ' . $operation['name'] . '.');
            }

            if ($db->num_rows($operationResult)) {
                $operationId = $db->query_result($operationResult, 0, 'operationid');
                self::query(
                    'UPDATE vtiger_ws_operation SET handler_path = ?, handler_method = ?, type = ?, prelogin = 0 WHERE operationid = ?',
                    array($operation['path'], $operation['method'], 'POST', $operationId)
                );
                self::query(
                    'DELETE FROM vtiger_ws_operation_parameters WHERE operationid = ?',
                    array($operationId)
                );
            } else {
                $operationId = $db->getUniqueID('vtiger_ws_operation');
                self::query(
                    'INSERT INTO vtiger_ws_operation (operationid, name, handler_path, handler_method, type, prelogin) VALUES (?, ?, ?, ?, ?, 0)',
                    array($operationId, $operation['name'], $operation['path'], $operation['method'], 'POST')
                );
            }

            foreach ($operation['params'] as $index => $param) {
                self::query(
                    'INSERT INTO vtiger_ws_operation_parameters (operationid, name, type, sequence) VALUES (?, ?, ?, ?)',
                    array($operationId, $param[0], $param[1], $index + 1)
                );
            }

            if ($db->hasFailedTransaction()) {
                throw new RuntimeException('Outlook add-in web-service registration failed for ' . $operation['name'] . '.');
            }
        } catch (Throwable $error) {
            $db->database->FailTrans();
            $db->completeTransaction();
            throw $error;
        }

        $db->completeTransaction();
    }

    /**
     * Runs a parameterized settings query and rejects reported database failures.
     *
     * @param string $sql Parameterized SQL statement.
     * @param array $params Bound values.
     * @return object|bool
     * @throws RuntimeException On a failed database query.
     */
    private static function query($sql, $params) {
        $result = PearDatabase::getInstance()->pquery($sql, $params);
        if ($result === false) {
            throw new RuntimeException('Outlook add-in web-service registration database query failed.');
        }
        return $result;
    }
}

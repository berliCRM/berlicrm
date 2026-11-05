<?php

/**
 * Installs the Word add-in menu and mail-merge web services and authenticated language preferences from the CLI.
 *
 * Run: php wordplugin-officejs/build/registerWordMailMergeOperation.php
 * Resolves the CRM root relative to this file, loads the configured CRM runtime
 * and reuses the same repeatable registration methods as db_update.php.
 * Exits with status 1 on missing configuration or registration failure; rejects
 * HTTP execution with status 403.
 *
 * @package berliCRM.WordAddin
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Run this installer from the CRM server's command line.\n");
}

chdir(dirname(__DIR__, 2));
if (!is_readable('config.inc.php')) {
    fwrite(STDERR, "Bitte zuerst berliCRM auf dem Server konfigurieren (config.inc.php fehlt).\n");
    exit(1);
}

require_once 'config.inc.php';
require_once 'includes/Loader.php';
// PearDatabase::query_result() needs to_html() and its Vtiger_Cache dependency.
require_once 'include/utils/utils.php';
require_once 'include/database/PearDatabase.php';
require_once 'includes/runtime/BaseModel.php';
require_once 'modules/Settings/Vtiger/models/Module.php';
require_once 'modules/Settings/WordAddin/models/Module.php';

try {
    Settings_WordAddin_Module_Model::registerSettingsMenu();
    Settings_WordAddin_Module_Model::registerOperations();
    echo "Word-Add-in-Admin-Menü und Word-Seriendruck-Webservices wurden eingerichtet.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'Einrichtung fehlgeschlagen: ' . $exception->getMessage() . "\n");
    exit(1);
}

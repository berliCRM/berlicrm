<?php
/**
 * Shared, repeatable Outlook setup used by the CRM updater and CLI installer.
 *
 * @package berliCRM.OfficeAddins
 */

// Shared by the CRM version updater and the optional standalone CLI installer.
// The caller must bootstrap berliCRM before invoking this routine.
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    http_response_code(403);
    exit("Use db_update.php or outlook-addin/build/registerOutlookAddinOperation.php.\n");
}

/**
 * Initializes Outlook webservices, add-in link tables and settings after CRM bootstrap.
 *
 * @return void
 * @throws Throwable If registration fails; the CRM release update must not advance.
 */
function berli_outlookAddinUpdate(): void
{
    $crmRoot = dirname(__DIR__);
    require_once $crmRoot . '/modules/Settings/OutlookAddin/models/Module.php';
    $previousDirectory = getcwd();
    if (!chdir($crmRoot)) {
        throw new RuntimeException('The CRM directory is not accessible for the Outlook add-in update.');
    }
    try {
        // Repeatable: repair/create all six operations and create missing link tables.
        // Let failures reach db_update.php, so it cannot advance the CRM release tag.
        Settings_OutlookAddin_Module_Model::registerOperation();
        Settings_OutlookAddin_Module_Model::registerSettingsMenu();
    } finally {
        if ($previousDirectory !== false) chdir($previousDirectory);
    }
}

<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Run this installer from the CRM server's command line.\n");
}

chdir(dirname(__DIR__, 2));
if (!is_readable('config.inc.php')) {
    fwrite(STDERR, "Please configure berliCRM first (config.inc.php is missing).\n");
    exit(1);
}

require_once 'config.inc.php';
require_once 'includes/Loader.php';
require_once 'include/utils/utils.php';
require_once 'include/database/PearDatabase.php';
require_once 'includes/runtime/BaseModel.php';
require_once dirname(__DIR__) . '/update.php';

try {
    berli_outlookAddinUpdate();
    echo "Outlook add-in web services registered.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'Registration failed: ' . $exception->getMessage() . "\n");
    exit(1);
}

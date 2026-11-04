<?php
/**
 * Read-only CLI diagnosis of distribution-list registration and cached permissions.
 *
 * @package berliCRM.OfficeAddins
 */

// Local, read-only inspection of the module registration and effective cache flags.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Nur auf der Kommandozeile ausführbar.\n");
}

$options = getopt('', array('help', 'user-id:'));
if (isset($options['help'])) {
    exit("Aufruf im CRM-Hauptverzeichnis: php outlook-addin/build/checkVerteilerWebservice.php --user-id 3\n"
        . "Liest nur Modulregistrierung und Berechtigungsdaten. Keine Änderungen.\n");
}
$userId = $options['user-id'] ?? '';
if (!is_string($userId) || !preg_match('/^[1-9][0-9]*$/D', $userId)) {
    fwrite(STDERR, "Bitte die numerische CRM-Benutzer-ID mit --user-id angeben (bei 19x3: 3).\n");
    exit(1);
}

chdir(dirname(__DIR__, 2));
if (!is_readable('config.inc.php')) {
    fwrite(STDERR, "Dieses Skript muss unter outlook-addin/build/ der betreffenden CRM-Installation liegen.\n");
    exit(1);
}
require_once 'config.inc.php';
require_once 'includes/Loader.php';
require_once 'include/utils/utils.php';
require_once 'include/database/PearDatabase.php';
require_once 'includes/runtime/BaseModel.php';
require_once 'modules/Users/Users.php';

/**
 * Executes a parameterized diagnostic SELECT without modifying CRM data.
 *
 * @param PearDatabase $db CRM database connection.
 * @param string $sql Read-only diagnostic statement.
 * @param array $params Bound values.
 * @return array
 * @throws RuntimeException If the SELECT fails.
 */
function outlookVerteilerReadRows($db, $sql, array $params)
{
    $result = $db->pquery($sql, $params);
    if ($result === false) {
        throw new RuntimeException('Diagnose-SELECT fehlgeschlagen.');
    }
    $rows = array();
    while ($row = $db->fetchByAssoc($result, -1, false)) {
        $rows[] = $row;
    }
    return $rows;
}

/**
 * Reads only the module and profile flags required for the owner diagnostic.
 *
 * @param int|string $userId Numeric CRM user identifier.
 * @param int|string $tabId Distribution-list module identifier.
 * @return array<string,mixed>
 */
function outlookVerteilerCacheFlags($userId, $tabId)
{
    // Includes are scoped here: never print user_info, credentials, or unrelated flags.
    include 'tabdata.php';
    include 'user_privileges/user_privileges_' . $userId . '.php';
    $cacheTabId = $tab_info_array['Verteiler'] ?? null;
    $presence = $cacheTabId !== null ? ($tab_seq_array[$cacheTabId] ?? null) : null;
    $permission = $profileTabsPermission[$tabId] ?? null;
    return array(
        'cache_tabid' => $cacheTabId,
        'presence_wert' => $presence,
        'presence_php_typ' => gettype($presence),
        'profil_modulrecht_wert' => $permission,
        'profil_modulrecht_php_typ' => gettype($permission),
        'is_admin' => $is_admin ?? null,
        'global_view_all' => $profileGlobalPermission[1] ?? null,
        'global_edit_all' => $profileGlobalPermission[2] ?? null
    );
}

/**
 * Prints one labeled JSON diagnostic value.
 *
 * @param string $label Human-readable diagnostic label.
 * @param mixed $value Value to encode.
 * @return void
 */
function outlookVerteilerPrint($label, $value)
{
    echo $label . ': ' . json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
}

try {
    $adb = PearDatabase::getInstance();
    $modules = outlookVerteilerReadRows($adb,
        'SELECT tabid, name, presence, isentitytype FROM vtiger_tab WHERE name = ?', array('Verteiler'));
    $entities = outlookVerteilerReadRows($adb,
        'SELECT id, name, ismodule, handler_path, handler_class FROM vtiger_ws_entity WHERE name = ?', array('Verteiler'));
    $users = outlookVerteilerReadRows($adb,
        'SELECT id, user_name, is_admin, status FROM vtiger_users WHERE id = ? AND deleted = 0', array($userId));
    echo "Nur Diagnose: keine INSERT-, UPDATE- oder DELETE-Abfragen.\n";
    outlookVerteilerPrint('Modul in vtiger_tab', $modules);
    outlookVerteilerPrint('Registrierung in vtiger_ws_entity', $entities);
    outlookVerteilerPrint('CRM-Benutzer', $users);
    if (count($modules) !== 1 || count($users) !== 1) {
        throw new RuntimeException('Modul oder Benutzer fehlt oder ist nicht eindeutig.');
    }
    $privilegeFile = 'user_privileges/user_privileges_' . $userId . '.php';
    if (!is_readable('tabdata.php') || !is_readable($privilegeFile)) {
        throw new RuntimeException('Die Modul- oder Benutzer-Berechtigungsdatei fehlt bzw. ist nicht lesbar.');
    }
    $flags = outlookVerteilerCacheFlags($userId, $modules[0]['tabid']);
    outlookVerteilerPrint('Effektive Cache-Flags', $flags);
    $seedUser = new Users();
    $current_user = $seedUser->retrieveCurrentUserInfoFromFile($userId);
    $permitted = getPermittedModuleNames();
    $allowed = in_array('Verteiler', $permitted, true);
    outlookVerteilerPrint('Verteiler in getPermittedModuleNames', $allowed);
    echo "--- Auswertung ---\n";
    if (count($entities) === 0) {
        echo "Die Webservice-Modulregistrierung für Verteiler fehlt. Die Registrierung von query oder outlook_get_verteiler_contacts ersetzt sie nicht.\n";
    } elseif (count($entities) !== 1) {
        echo "Mehrere Webservice-Modulregistrierungen für Verteiler gefunden. Vor einer Korrektur prüfen.\n";
    } elseif ((string)$entities[0]['ismodule'] !== '1') {
        echo "Verteiler ist nicht als Modul (ismodule=1) registriert.\n";
    } elseif (!$allowed) {
        echo "Die Modulregistrierung ist vorhanden. Verteiler wird durch Modul-/Profilfreigabe oder deren Cache ausgeschlossen; Werte und PHP-Typen stehen oben.\n";
    } else {
        echo "Modulregistrierung und Modulliste sind lokal vorhanden. Prüfen, ob die HTTP-Webservice-URL genau diese CRM-Installation und denselben Benutzer verwendet.\n";
    }
} catch (Throwable $error) {
    fwrite(STDERR, 'Diagnose fehlgeschlagen: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}

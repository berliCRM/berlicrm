<?php
/**
 * Shared language catalogs and authenticated preferences for the Office add-ins.
 * @package berliCRM.OfficeAddins
 */

/**
 * Normalizes supported CRM/Office locales, falling back to English.
 * @param mixed $language Language identifier.
 * @return string Supported catalog identifier.
 */
function berli_officeLanguage($language) {
    return is_string($language) && preg_match('/^de(?:[-_]|$)/i', $language) ? 'de_de' : 'en_us';
}

/**
 * Reads the authenticated user's language; request hints apply only outside CRM.
 * @return string Supported catalog identifier.
 */
function berli_officeCurrentLanguage() {
    global $current_user, $default_language;
    $language = null;
    if (is_object($current_user)) {
        $language = $current_user->language ?? ($current_user->column_fields['language'] ?? null);
        if (!$language && method_exists($current_user, 'get')) $language = $current_user->get('language');
    }
    return berli_officeLanguage($language ?: ($GLOBALS['berli_office_language'] ?? ($default_language ?? 'de_de')));
}

/**
 * Formats a catalog message without interpreting HTML or executable content.
 * @param string $component Fixed component identifier (word or outlook).
 * @param string $key Catalog key.
 * @param array $values Positional replacement values.
 * @param string|null $language Explicit locale for template compatibility checks.
 * @return string Localized message.
 */
function berli_officeT($component, $key, array $values = array(), $language = null) {
    static $catalogs = array();
    $directory = $component === 'word' ? 'wordplugin-officejs' : 'outlook-addin';
    $language = $language === null ? berli_officeCurrentLanguage() : berli_officeLanguage($language);
    $cacheKey = $directory . '/' . $language;
    if (!isset($catalogs[$cacheKey])) {
        $path = dirname(__DIR__, 2) . '/' . $directory . '/languages/' . $language . '.php';
        $catalogs[$cacheKey] = require $path;
    }
    $text = $catalogs[$cacheKey][$key] ?? $key;
    return preg_replace_callback('/\{(\d+)\}/', function ($match) use ($values) {
        return array_key_exists((int)$match[1], $values) ? (string)$values[(int)$match[1]] : $match[0];
    }, $text);
}

/**
 * Formats a Word add-in message.
 * @param string $key Catalog key.
 * @param array $values Replacement values.
 * @return string Localized message.
 */
function berli_wordT($key, array $values = array()) { return berli_officeT('word', $key, $values); }

/**
 * Formats an Outlook add-in message.
 * @param string $key Catalog key.
 * @param array $values Replacement values.
 * @return string Localized message.
 */
function berli_outlookT($key, array $values = array()) { return berli_officeT('outlook', $key, $values); }

/**
 * Returns preferences for the authenticated session; accepts no target user ID.
 * @param Users $user Authenticated CRM user supplied by the operation manager.
 * @return array Language identifier for the signed-in user.
 * @throws WebServiceException If the authenticated user is unavailable.
 */
function berli_officeAddinUserPreferences($user) {
    global $adb;
    $id = isset($user->id) ? (int)$user->id : 0;
    if ($id < 1) throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, 'AUTHENTICATION_REQUIRED');
    $result = $adb->pquery('SELECT language FROM vtiger_users WHERE id = ?', array($id));
    $language = $result && $adb->num_rows($result) ? $adb->query_result($result, 0, 'language') : null;
    $GLOBALS['berli_office_language'] = berli_officeLanguage($language ?: ($GLOBALS['default_language'] ?? 'en_us'));
    return array('language' => $GLOBALS['berli_office_language']);
}

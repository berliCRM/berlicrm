<?php
/**
 * Permission-aware transfer between CRM and personal Microsoft Graph contacts.
 *
 * @package berliCRM.OfficeAddins
 */

require_once 'include/Webservices/DescribeObject.php';
require_once 'include/Webservices/Retrieve.php';
require_once 'include/Webservices/Create.php';
require_once 'include/Webservices/Query.php';

/**
 * Returns the Graph extended-property identifier used for CRM contact links.
 *
 * @return string
 */
function berli_outlookContactPropertyId()
{
    return 'String {f9e177c7-325a-4d90-ade5-a45bd5922b2e} Name berliCRMContact';
}

/**
 * Calculates the stable namespace for this CRM installation.
 *
 * @return string
 * @throws RuntimeException If the CRM installation URL is missing.
 */
function berli_outlookContactInstance()
{
    global $site_URL;
    if (empty($site_URL)) throw new RuntimeException('Die CRM-Installationsadresse fehlt.');
    return hash('sha256', rtrim($site_URL, '/'));
}

/**
 * Returns permitted contact fields, mandatory flags and authenticated owner metadata.
 *
 * @param Users $user Authenticated CRM user.
 * @return array<string,mixed>
 */
function berli_outlookContactDescription($user)
{
    $description = vtws_describe('Contacts', $user);
    $fields = array();
    foreach ($description['fields'] as $field) {
        $fields[$field['name']] = array(
            'name' => $field['name'],
            'label' => html_entity_decode($field['label'], ENT_QUOTES, 'UTF-8'),
            'mandatory' => !empty($field['mandatory']),
            'editable' => !empty($field['editable']),
            'fieldsize' => (int)($field['fieldsize'] ?? 0)
        );
    }
    global $adb;
    $users = VtigerWebserviceObject::fromName($adb, 'Users');
    return array('fields' => array_values($fields), 'canCreate' => !empty($description['createable']),
        'ownerId' => vtws_getId($users->getEntityId(), $user->id),
        'instance' => berli_outlookContactInstance(), 'propertyId' => berli_outlookContactPropertyId());
}

/**
 * Executes a parameterized contact-link query and rejects database failures.
 *
 * @param string $sql Parameterized SQL statement.
 * @param array $params Bound values.
 * @return object|bool
 * @throws RuntimeException On a contact-link database failure.
 */
function berli_outlookContactQuery($sql, array $params)
{
    global $adb;
    $result = $adb->pquery($sql, $params);
    if ($result === false) throw new RuntimeException('Die Kontaktverknüpfung konnte nicht gespeichert oder gelesen werden. Bitte den Outlook-Installer ausführen.');
    return $result;
}

/**
 * Validates that a webservice identifier belongs to the Contacts module.
 *
 * @param mixed $id Candidate contact identifier.
 * @return string
 * @throws InvalidArgumentException If the identifier is invalid.
 */
function berli_outlookContactId($id)
{
    global $adb;
    $object = VtigerWebserviceObject::fromName($adb, 'Contacts');
    if (!is_string($id) || !preg_match('/^' . preg_quote((string)$object->getEntityId(), '/') . 'x[1-9][0-9]*$/D', $id)) {
        throw new InvalidArgumentException('Ungültige CRM-Kontakt-ID.');
    }
    return $id;
}

/**
 * Maps Graph values to editable CRM fields and reports missing mandatory fields.
 *
 * @param array<string,mixed> $contact Personal Outlook contact.
 * @param array<string,mixed> $metadata User-visible CRM field metadata.
 * @return array<string,mixed>
 * @throws InvalidArgumentException On invalid field values.
 */
function berli_outlookContactMap(array $contact, array $metadata)
{
    $mapping = array('firstname' => 'givenName', 'lastname' => 'surname', 'mobile' => 'mobilePhone',
        'title' => 'jobTitle', 'department' => 'department', 'description' => 'personalNotes');
    $values = array('assigned_user_id' => $metadata['ownerId']);
    foreach ($mapping as $crm => $graph) $values[$crm] = $contact[$graph] ?? '';
    $emails = $contact['emailAddresses'] ?? array();
    $phones = $contact['businessPhones'] ?? array();
    if (!is_array($emails) || !is_array($phones)) throw new InvalidArgumentException('Ungültige Outlook-Kontaktdaten.');
    foreach ($emails as $email) {
        if (!is_array($email) || !is_string($email['address'] ?? null)) throw new InvalidArgumentException('Ungültige Outlook-E-Mail-Adresse.');
    }
    $values['email'] = $emails[0]['address'] ?? '';
    $values['secondaryemail'] = $emails[1]['address'] ?? '';
    $values['phone'] = $phones[0] ?? '';
    foreach (array('businessAddress' => 'mailing', 'homeAddress' => 'other') as $addressKey => $prefix) {
        $address = $contact[$addressKey] ?? array();
        if (!is_array($address)) throw new InvalidArgumentException('Ungültige Outlook-Adresse.');
        foreach (array('street' => 'street', 'city' => 'city', 'state' => 'state', 'postalCode' => 'zip', 'countryOrRegion' => 'country') as $graph => $crm) {
            $values[$prefix . $crm] = $address[$graph] ?? '';
        }
    }
    $fields = array();
    $missing = array();
    foreach ($metadata['fields'] as $field) {
        $name = $field['name'];
        if (array_key_exists($name, $values) && ($field['editable'] || $name === 'assigned_user_id')) {
            $value = $values[$name];
            if (!is_string($value) || !preg_match('//u', $value) || strlen($value) > 100000) {
                throw new InvalidArgumentException('Ungültiger Wert für ' . $field['label'] . '.');
            }
            $value = trim($value);
            if ($field['fieldsize'] > 0 && preg_match_all('/./us', $value) > $field['fieldsize']) {
                throw new InvalidArgumentException($field['label'] . ' überschreitet die CRM-Feldlänge.');
            }
            if (in_array($name, array('email', 'secondaryemail'), true) && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Ungültige E-Mail-Adresse im Feld ' . $field['label'] . '.');
            }
            $fields[$name] = $value;
        }
        // Do not invent surnames, account references, or custom mandatory values.
        if ($field['mandatory'] && (!isset($fields[$name]) || trim($fields[$name]) === '')) {
            $missing[] = array('name' => $name, 'label' => $field['label']);
        }
    }
    return array('fields' => $fields, 'missing' => $missing);
}

/**
 * Converts a readable CRM contact into Graph contact fields and its stable CRM link.
 *
 * @param mixed $id Candidate CRM contact webservice identifier.
 * @param array<string,mixed> $metadata Current user field permissions.
 * @param Users $user Authenticated CRM user.
 * @return array<string,mixed>
 */
function berli_outlookContactExport($id, array $metadata, $user)
{
    $record = vtws_retrieve(berli_outlookContactId($id), $user);
    $readable = array_column($metadata['fields'], 'name');
    $value = function ($name) use ($record, $readable) {
        return in_array($name, $readable, true) && isset($record[$name]) && is_scalar($record[$name])
            ? html_entity_decode((string)$record[$name], ENT_QUOTES, 'UTF-8') : '';
    };
    $contact = array();
    foreach (array('firstname' => 'givenName', 'lastname' => 'surname', 'mobile' => 'mobilePhone',
        'title' => 'jobTitle', 'department' => 'department', 'description' => 'personalNotes') as $crm => $graph) {
        if ($value($crm) !== '') $contact[$graph] = $value($crm);
    }
    $contact['displayName'] = trim($value('firstname') . ' ' . $value('lastname')) ?: 'CRM-Kontakt ' . $id;
    $contact['emailAddresses'] = array();
    foreach (array('email', 'secondaryemail') as $field) {
        if ($value($field) !== '') {
            if (!filter_var($value($field), FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Der CRM-Kontakt enthält eine ungültige E-Mail-Adresse.');
            $contact['emailAddresses'][] = array('address' => $value($field), 'name' => $contact['displayName']);
        }
    }
    if ($value('phone') !== '') $contact['businessPhones'] = array($value('phone'));
    foreach (array('businessAddress' => 'mailing', 'homeAddress' => 'other') as $graphKey => $prefix) {
        $address = array();
        foreach (array('street' => 'street', 'city' => 'city', 'state' => 'state', 'zip' => 'postalCode', 'country' => 'countryOrRegion') as $crm => $graph) {
            if ($value($prefix . $crm) !== '') $address[$graph] = $value($prefix . $crm);
        }
        if ($address) $contact[$graphKey] = $address;
    }
    $key = $metadata['instance'] . ':' . $id;
    $contact['singleValueExtendedProperties'] = array(array('id' => berli_outlookContactPropertyId(), 'value' => $key));
    return array('recordId' => $id, 'key' => $key, 'contact' => $contact);
}

/**
 * Imports a valid personal Outlook contact with duplicate checks and safe retry links.
 *
 * @param array<string,mixed> $input Contact and Microsoft account identity.
 * @param array<string,mixed> $metadata Current user field permissions and mandatory flags.
 * @param Users $user Authenticated CRM user.
 * @return array<string,mixed>
 */
function berli_outlookContactImport(array $input, array $metadata, $user)
{
    global $adb;
    if (!$metadata['canCreate']) throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, 'Keine Berechtigung zum Anlegen von CRM-Kontakten.');
    $contact = $input['contact'] ?? null;
    $account = $input['account'] ?? '';
    if (!is_array($contact) || !is_string($account) || $account === '' || strlen($account) > 1024
        || empty($contact['id']) || !is_string($contact['id']) || strlen($contact['id']) > 4096
        || preg_match('/[\x00-\x1f]/', $account . $contact['id'])) {
        throw new InvalidArgumentException('Die Microsoft-Konto- oder Kontakt-ID fehlt oder ist ungültig.');
    }
    $mapped = berli_outlookContactMap($contact, $metadata);
    if ($mapped['missing']) return array('status' => 'skipped_missing_fields', 'missingFields' => $mapped['missing']);
    // A CRM export coming back through Outlook must not create a second CRM record.
    $properties = $contact['singleValueExtendedProperties'] ?? array();
    if (!is_array($properties)) throw new InvalidArgumentException('Ungültige Outlook-Kontaktverknüpfung.');
    foreach ($properties as $property) {
        if (!is_array($property)) throw new InvalidArgumentException('Ungültige Outlook-Kontaktverknüpfung.');
        if (($property['id'] ?? '') === berli_outlookContactPropertyId() && is_string($property['value'] ?? null)
            && strpos($property['value'], $metadata['instance'] . ':') === 0) {
            $id = berli_outlookContactId(substr($property['value'], strlen($metadata['instance']) + 1));
            vtws_retrieve($id, $user);
            return array('status' => 'skipped_existing', 'recordId' => $id);
        }
    }
    // This key is an idempotency namespace, never an authorization identity.
    // All rights and the CRM owner come exclusively from authenticated $user.
    $source = hash('sha256', $account . "\n" . $contact['id']);
    $lockName = 'outlook-contact-' . substr(hash('sha256', $user->id . ':' . $source), 0, 43);
    $lock = berli_outlookContactQuery('SELECT GET_LOCK(?, 5) AS acquired', array($lockName));
    if ((string)$adb->query_result($lock, 0, 'acquired') !== '1') throw new RuntimeException('Dieser Kontakt wird bereits übernommen.');
    try {
        $link = berli_outlookContactQuery('SELECT contactid, state FROM vtiger_outlook_contact_link WHERE userid = ? AND sourcehash = ?', array((int)$user->id, $source));
        if ($adb->num_rows($link)) {
            $row = $adb->fetchByAssoc($link, -1, false);
            if ($row['state'] !== 'ready' || empty($row['contactid'])) {
                throw new RuntimeException('Eine frühere Kontaktübernahme wurde nicht eindeutig bestätigt. Bitte administrativ prüfen lassen; es wird kein weiterer Kontakt angelegt.');
            }
            vtws_retrieve(berli_outlookContactId($row['contactid']), $user);
            return array('status' => 'skipped_existing', 'recordId' => $row['contactid']);
        }
        $id = null;
        if (!empty($mapped['fields']['email'])) {
            $email = str_replace(array('\\', "'"), array('\\\\', "''"), $mapped['fields']['email']);
            $existing = vtws_query("select id from Contacts where email = '$email' limit 2;", $user);
            if (count($existing) > 1) return array('status' => 'skipped_duplicate', 'message' => 'Mehrere lesbare CRM-Kontakte mit derselben E-Mail-Adresse.');
            if (count($existing) === 1) $id = berli_outlookContactId($existing[0]['id']);
        }
        berli_outlookContactQuery('INSERT INTO vtiger_outlook_contact_link (userid, sourcehash, contactid, state) VALUES (?, ?, NULL, ?)', array((int)$user->id, $source, 'pending'));
        if ($id === null) {
            $created = vtws_create('Contacts', $mapped['fields'], $user);
            $id = berli_outlookContactId($created['id'] ?? null);
            $status = 'created';
        } else {
            vtws_retrieve($id, $user);
            $status = 'skipped_existing';
        }
        berli_outlookContactQuery('UPDATE vtiger_outlook_contact_link SET contactid = ?, state = ? WHERE userid = ? AND sourcehash = ?', array($id, 'ready', (int)$user->id, $source));
        return array('status' => $status, 'recordId' => $id);
    } finally {
        $adb->pquery('SELECT RELEASE_LOCK(?)', array($lockName));
    }
}

/**
 * Dispatches contact metadata, export and import using authenticated CRM permissions.
 *
 * @param mixed $input Decoded contact action.
 * @param Users $user Authenticated CRM user.
 * @return array<string,mixed>
 * @throws InvalidArgumentException On an invalid action or contact payload.
 * @throws WebServiceException If required CRM permissions are missing.
 */
function berli_outlookContactTransfer($input, $user)
{
    if (!is_array($input) || !in_array($input['mode'] ?? '', array('describe', 'export', 'import'), true)) {
        throw new InvalidArgumentException('Ungültige Kontaktaktion.');
    }
    $metadata = berli_outlookContactDescription($user);
    if ($input['mode'] === 'describe') return $metadata;
    if ($input['mode'] === 'export') return berli_outlookContactExport($input['recordId'] ?? null, $metadata, $user);
    return berli_outlookContactImport($input, $metadata, $user);
}

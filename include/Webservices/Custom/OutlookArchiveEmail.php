<?php
/**
 * Authenticated Outlook message archival, sender-contact lookup and attachment storage.
 *
 * @package berliCRM.OfficeAddins
 */
require_once dirname(__DIR__, 2) . '/utils/OfficeAddinI18n.php';


require_once 'include/Webservices/DescribeObject.php';
require_once 'include/Webservices/Query.php';
require_once 'include/Webservices/Retrieve.php';
require_once 'include/Webservices/Create.php';
require_once 'include/fields/DateTimeField.php';

/**
 * Executes a parameterized archive-link query and rejects database failures.
 *
 * @param string $sql Parameterized SQL statement.
 * @param array $params Bound values.
 * @return object|bool
 * @throws RuntimeException On an archive-link database failure.
 */
function berli_outlookMailQuery($sql, array $params)
{
    global $adb;
    $result = $adb->pquery($sql, $params);
    if ($result === false) throw new RuntimeException(berli_outlookT('COULD_NOT_READ_OR_SAVE_THE_EMAIL_LINK_PLEASE_RUN_THE_OUTLOOK_INSTALLER'));
    return $result;
}

/**
 * Validates and normalizes an email address used for exact contact matching.
 *
 * @param mixed $address Candidate email address.
 * @return string
 * @throws InvalidArgumentException On an invalid email address.
 */
function berli_outlookMailAddress($address)
{
    if (!is_string($address) || strlen($address) > 254 || !filter_var(trim($address), FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException(berli_outlookT('THE_EMAIL_ADDRESS_IN_THE_FROM_FIELD_IS_MISSING_OR_INVALID'));
    }
    return strtolower(trim($address));
}

/**
 * Validates a webservice identifier against its expected module and returns its numeric ID.
 *
 * @param mixed $id Candidate webservice identifier.
 * @param string $module Expected CRM module.
 * @return int
 * @throws InvalidArgumentException If the identifier does not match.
 */
function berli_outlookMailId($id, $module)
{
    global $adb;
    $object = VtigerWebserviceObject::fromName($adb, $module);
    if (!is_string($id) || !preg_match('/^' . preg_quote((string)$object->getEntityId(), '/') . 'x([1-9][0-9]*)$/D', $id, $match)) {
        throw new InvalidArgumentException(berli_outlookT('INVALID') . $module . berli_outlookT('RECORD_ID'));
    }
    return (int)$match[1];
}

/**
 * Loads user-visible contact/email fields, dynamic labels and archive-create permission.
 *
 * @param Users $user Authenticated CRM user.
 * @return array<string,mixed>
 * @throws WebServiceException When the contact email field is not readable.
 */
function berli_outlookMailRights($user)
{
    $contacts = vtws_describe('Contacts', $user);
    $emails = vtws_describe('Emails', $user);
    $fields = array_column($contacts['fields'], 'name');
    if (!in_array('email', $fields, true)) throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, berli_outlookT('NO_READ_PERMISSION_FOR_THE_CONTACT_EMAIL_SEARCH_FIELD'));
    $emailLabel = '';
    foreach ($contacts['fields'] as $field) {
        if ($field['name'] === 'email') $emailLabel = trim(html_entity_decode($field['label'] ?? '', ENT_QUOTES, 'UTF-8'));
    }
    if ($emailLabel === '') throw new RuntimeException(berli_outlookT('THE_CRM_DID_NOT_RETURN_A_LABEL_FOR_THE_CONTACT_EMAIL_SEARCH_FIELD'));
    return array('canCreate' => !empty($emails['createable']), 'contactFields' => $fields,
        'contactEmailLabel' => $emailLabel, 'emailFields' => $emails['fields']);
}

/**
 * Finds all readable contacts whose primary email exactly matches the sender.
 *
 * @param string $from Normalized sender email.
 * @param array<string,mixed> $rights User-visible contact fields.
 * @param Users $user Authenticated CRM user.
 * @return array
 * @throws RuntimeException If query pagination repeats a page.
 */
function berli_outlookMailFindContacts($from, array $rights, $user)
{
    $email = str_replace(array('\\', "'"), array('\\\\', "''"), $from);
    $fields = array_intersect(array('id', 'firstname', 'lastname', 'account_id', 'email'), $rights['contactFields']);
    if (!in_array('id', $fields, true)) $fields[] = 'id';
    $matches = array(); $accounts = array(); $seen = array();
    for ($offset = 0; ; $offset += 100) {
        $page = vtws_query('select ' . implode(', ', $fields) . " from Contacts where email = '$email' order by id limit $offset,100;", $user);
        foreach ($page as $contact) {
            if (isset($seen[$contact['id']])) throw new RuntimeException(berli_outlookT('THE_CRM_SEARCH_REPEATED_A_CONTACT_PAGE'));
            $seen[$contact['id']] = true;
            if (strtolower(trim(html_entity_decode($contact['email'] ?? '', ENT_QUOTES, 'UTF-8'))) !== $from) continue;
            $company = ''; $accountId = $contact['account_id'] ?? '';
            if ($accountId) {
                if (!array_key_exists($accountId, $accounts)) {
                    try {
                        berli_outlookMailId($accountId, 'Accounts');
                        $account = vtws_retrieve($accountId, $user);
                        $accounts[$accountId] = $account['accountname'] ?? '';
                    } catch (WebServiceException $error) {
                        // The company label is optional; never bypass account read rights.
                        if (!in_array($error->code, array(WebServiceErrorCode::$ACCESSDENIED, WebServiceErrorCode::$RECORDNOTFOUND), true)) throw $error;
                        $accounts[$accountId] = '';
                    }
                }
                $company = $accounts[$accountId];
            }
            $decode = function ($value) { return html_entity_decode((string)$value, ENT_QUOTES, 'UTF-8'); };
            $matches[] = array('id' => $contact['id'], 'firstname' => $decode($contact['firstname'] ?? ''),
                'lastname' => $decode($contact['lastname'] ?? ''), 'company' => $decode($company));
        }
        if (count($page) < 100) break;
    }
    return $matches;
}

/**
 * Validates a UTF-8 mail field against its byte limit and required-value constraint.
 *
 * @param array<string,mixed> $input Mail payload.
 * @param string $key Field name.
 * @param int $max Maximum byte length.
 * @param bool $required Whether blank text is rejected.
 * @return string
 * @throws InvalidArgumentException When the field is invalid.
 */
function berli_outlookMailString(array $input, $key, $max, $required = false)
{
    $value = $input[$key] ?? '';
    if (!is_string($value) || strlen($value) > $max || !preg_match('//u', $value) || strpos($value, "\0") !== false || ($required && trim($value) === '')) {
        throw new InvalidArgumentException(berli_outlookT('INVALID_EMAIL_FIELD') . $key . '.');
    }
    return $value;
}

/**
 * Validates, normalizes and deduplicates an email recipient list.
 *
 * @param mixed $recipients Candidate address list.
 * @return string
 * @throws InvalidArgumentException On invalid addresses or recipient counts.
 */
function berli_outlookMailRecipients($recipients)
{
    if (!is_array($recipients) || count($recipients) > 1000) throw new InvalidArgumentException(berli_outlookT('INVALID_EMAIL_RECIPIENT_LIST'));
    $addresses = array();
    foreach ($recipients as $recipient) $addresses[] = berli_outlookMailAddress($recipient);
    return implode(',', array_unique($addresses));
}

/**
 * Validates attachment names, MIME types and base64 content within size limits.
 *
 * @param mixed $attachments Candidate attachment list.
 * @return array
 * @throws InvalidArgumentException On malformed or oversized attachments.
 */
function berli_outlookMailFiles($attachments)
{
    global $upload_badext;
    if (!is_array($attachments) || count($attachments) > 100) throw new InvalidArgumentException(berli_outlookT('AT_MOST_100_ATTACHMENTS_PER_EMAIL_ARE_ALLOWED'));
    $files = array(); $total = 0;
    foreach ($attachments as $attachment) {
        if (!is_array($attachment)) throw new InvalidArgumentException(berli_outlookT('INVALID_EMAIL_ATTACHMENT'));
        $name = berli_outlookMailString($attachment, 'name', 255, true);
        if (preg_match('/[\x00-\x1f]/', $name)) throw new InvalidArgumentException(berli_outlookT('INVALID_FILENAME'));
        $name = basename(str_replace('\\', '/', $name));
        $name = sanitizeUploadFileName($name, $upload_badext);
        if ($name === '' || $name === '.' || $name === '..' || strlen($name) > 255) throw new InvalidArgumentException(berli_outlookT('INVALID_FILENAME'));
        $encoded = berli_outlookMailString($attachment, 'content', 14 * 1024 * 1024);
        $data = base64_decode($encoded, true);
        if ($data === false || strlen($data) > 10 * 1024 * 1024) throw new InvalidArgumentException(berli_outlookT('AN_ATTACHMENT_IS_INVALID_OR_EXCEEDS_10_MB'));
        $total += strlen($data);
        if ($total > 20 * 1024 * 1024) throw new InvalidArgumentException(berli_outlookT('THE_ATTACHMENTS_EXCEED_20_MB_IN_TOTAL'));
        $type = berli_outlookMailString($attachment, 'type', 150);
        if (!preg_match('~^[a-z0-9!#$&^_.+-]+/[a-z0-9!#$&^_.+-]+$~iD', $type)) $type = 'application/octet-stream';
        $hash = hash('sha256', $name . "\n" . $type . "\n" . $data);
        $files[$hash] = array('name' => $name, 'type' => $type, 'data' => $data);
    }
    return $files;
}

/**
 * Persists archive attachments with idempotent attachment links in CRM storage.
 *
 * @param array $files Validated attachment metadata and bytes.
 * @param string $source Archive identity hash.
 * @param int $email Numeric CRM email identifier.
 * @param Users $user Authenticated CRM user.
 * @return void
 * @throws RuntimeException On incomplete attachment storage.
 */
function berli_outlookMailStoreFiles(array $files, $source, $email, $user)
{
    global $adb, $root_directory;
    foreach ($files as $hash => $file) {
        $params = array((int)$user->id, $source, $hash);
        $link = berli_outlookMailQuery('SELECT attachmentid, state FROM vtiger_outlook_mail_file WHERE userid = ? AND sourcehash = ? AND filehash = ?', $params);
        if ($adb->num_rows($link)) {
            $existing = $adb->fetchByAssoc($link, -1, false);
            if ($existing['state'] !== 'ready' || empty($existing['attachmentid'])) throw new RuntimeException(berli_outlookT('AN_EARLIER_ATTACHMENT_SAVE_WAS_NOT_UNAMBIGUOUSLY_CONFIRMED_PLEASE_ASK_AN'));
            $found = berli_outlookMailQuery('SELECT attachmentsid FROM vtiger_seattachmentsrel INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_seattachmentsrel.attachmentsid WHERE vtiger_seattachmentsrel.crmid = ? AND attachmentsid = ? AND deleted = 0', array($email, (int)$existing['attachmentid']));
            if (!$adb->num_rows($found)) throw new RuntimeException(berli_outlookT('AN_ATTACHMENT_PREVIOUSLY_SAVED_WAS_REMOVED_FROM_CRM_PLEASE_ASK_AN_ADMINI'));
            continue;
        }
        $path = decideFilePath();
        $directory = realpath(rtrim($root_directory, '/\\') . '/' . $path);
        $root = realpath($root_directory);
        if ($directory === false || $root === false || strpos($directory . DIRECTORY_SEPARATOR, $root . DIRECTORY_SEPARATOR) !== 0) throw new RuntimeException(berli_outlookT('THE_CRM_ATTACHMENT_DIRECTORY_IS_INVALID'));
        $attachmentId = $adb->getUniqueID('vtiger_crmentity');
        berli_outlookMailQuery('INSERT INTO vtiger_outlook_mail_file (userid, sourcehash, filehash, attachmentid, state) VALUES (?, ?, ?, NULL, ?)', array_merge($params, array('pending')));
        // Exclusive creation: never overwrite an existing CRM storage file.
        $stream = @fopen($directory . DIRECTORY_SEPARATOR . $attachmentId . '_' . $file['name'], 'xb');
        if (!$stream) throw new RuntimeException(berli_outlookT('COULD_NOT_SAVE_THE_EMAIL_ATTACHMENT'));
        try {
            $written = 0; $length = strlen($file['data']);
            while ($written < $length) {
                $count = fwrite($stream, substr($file['data'], $written));
                if ($count === false || $count === 0) throw new RuntimeException(berli_outlookT('THE_EMAIL_ATTACHMENT_WAS_NOT_COMPLETELY_SAVED'));
                $written += $count;
            }
        } finally { fclose($stream); }
        $now = $adb->formatDate(date('Y-m-d H:i:s'), true);
        berli_outlookMailQuery('INSERT INTO vtiger_crmentity (crmid, smcreatorid, smownerid, setype, description, createdtime, modifiedtime, presence, deleted) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            array($attachmentId, $user->id, $user->id, 'Emails Attachment', '', $now, $now, 1, 0));
        berli_outlookMailQuery('INSERT INTO vtiger_attachments (attachmentsid, name, description, type, path) VALUES (?, ?, ?, ?, ?)', array($attachmentId, $file['name'], '', $file['type'], $path));
        berli_outlookMailQuery('INSERT INTO vtiger_seattachmentsrel (crmid, attachmentsid) VALUES (?, ?)', array($email, $attachmentId));
        berli_outlookMailQuery('UPDATE vtiger_outlook_mail_file SET attachmentid = ?, state = ? WHERE userid = ? AND sourcehash = ? AND filehash = ?', array_merge(array($attachmentId, 'ready'), $params));
    }
}

/**
 * Archives the message and links all selected readable contacts without sending mail.
 *
 * @param array<string,mixed> $input Mail content, sender and selected contacts.
 * @param array<string,mixed> $rights Current user archive and field permissions.
 * @param Users $user Authenticated CRM user.
 * @return array<string,mixed>
 * @throws InvalidArgumentException On invalid fields or contacts.
 * @throws RuntimeException On locking, storage or unconfirmed-save failures.
 * @throws WebServiceException If permissions are missing.
 */
function berli_outlookMailSave(array $input, array $rights, $user)
{
    global $adb;
    if (!$rights['canCreate']) throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, berli_outlookT('NO_PERMISSION_TO_CREATE_CRM_EMAILS'));
    $mail = $input['mail'] ?? null;
    if (!is_array($mail)) throw new InvalidArgumentException(berli_outlookT('EMAIL_DATA_IS_MISSING'));
    $from = berli_outlookMailAddress($mail['from'] ?? null);
    $mailbox = berli_outlookMailAddress($mail['mailbox'] ?? null);
    $itemId = berli_outlookMailString($mail, 'itemId', 4096, true);
    $messageId = berli_outlookMailString($mail, 'internetMessageId', 2048);
    if (preg_match('/[\x00-\x1f]/', $itemId . $messageId)) throw new InvalidArgumentException(berli_outlookT('INVALID_OUTLOOK_MESSAGE_ID'));
    $subject = berli_outlookMailString($mail, 'subject', 1024);
    if (trim($subject) === '') $subject = berli_outlookT('NO_SUBJECT');
    $body = berli_outlookMailString($mail, 'body', 2 * 1024 * 1024);
    $to = berli_outlookMailRecipients($mail['to'] ?? array());
    $cc = berli_outlookMailRecipients($mail['cc'] ?? array());
    $files = berli_outlookMailFiles($mail['attachments'] ?? array());
    $sent = berli_outlookMailString($mail, 'dateTimeCreated', 40, true);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{3})?Z$/D', $sent)) throw new InvalidArgumentException(berli_outlookT('INVALID_MESSAGE_DATE'));
    $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z', substr($sent, 0, 19) . 'Z', new DateTimeZone('UTC'));
    $errors = DateTimeImmutable::getLastErrors();
    if (!$date || ($errors && ($errors['warning_count'] || $errors['error_count']))) throw new InvalidArgumentException(berli_outlookT('INVALID_MESSAGE_DATE'));
    $date = $date->setTimezone(new DateTimeZone(DateTimeField::getDBTimeZone()));
    $selected = $input['contactIds'] ?? null;
    if (!is_array($selected) || !$selected || count($selected) > 1000) throw new InvalidArgumentException(berli_outlookT('PLEASE_SELECT_AT_LEAST_ONE_CONTACT_AT_MOST_1_000'));
    $ids = array();
    foreach ($selected as $id) {
        $numeric = berli_outlookMailId($id, 'Contacts');
        $contact = vtws_retrieve($id, $user);
        if (strtolower(trim(html_entity_decode($contact['email'] ?? '', ENT_QUOTES, 'UTF-8'))) !== $from) {
            throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, berli_outlookT('A_SELECTED_CONTACT_NO_LONGER_MATCHES_THE_FROM_ADDRESS_PLEASE_START_THE_S'));
        }
        $ids[$id] = $numeric;
    }
    $source = hash('sha256', $mailbox . "\n" . ($messageId !== '' ? 'message:' . $messageId : 'item:' . $itemId));
    $lockName = 'outlook-mail-' . substr(hash('sha256', $user->id . ':' . $source), 0, 48);
    $lock = berli_outlookMailQuery('SELECT GET_LOCK(?, 5) AS acquired', array($lockName));
    if ((string)$adb->query_result($lock, 0, 'acquired') !== '1') throw new RuntimeException(berli_outlookT('THIS_EMAIL_IS_ALREADY_BEING_SAVED'));
    try {
        $link = berli_outlookMailQuery('SELECT emailid, state FROM vtiger_outlook_mail_link WHERE userid = ? AND sourcehash = ?', array((int)$user->id, $source));
        $existing = $adb->num_rows($link) ? $adb->fetchByAssoc($link, -1, false) : null;
        $created = false;
        if ($existing) {
            if (empty($existing['emailid']) || !in_array($existing['state'], array('created', 'ready'), true)) {
                throw new RuntimeException(berli_outlookT('AN_EARLIER_EMAIL_SAVE_WAS_NOT_UNAMBIGUOUSLY_CONFIRMED_PLEASE_ASK_AN_ADMI'));
            }
            $emailId = $existing['emailid'];
            berli_outlookMailId($emailId, 'Emails');
            $storedMail = vtws_retrieve($emailId, $user);
            if (berli_outlookMailAddress($storedMail['from_email'] ?? null) !== $from) throw new InvalidArgumentException(berli_outlookT('THE_EMAIL_ALREADY_SAVED_BELONGS_TO_ANOTHER_FROM_ADDRESS'));
            if (isPermitted('Emails', 'EditView', berli_outlookMailId($emailId, 'Emails')) !== 'yes') {
                throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, berli_outlookT('NO_WRITE_PERMISSION_FOR_THE_CRM_EMAIL_ALREADY_SAVED'));
            }
        } else {
            $users = VtigerWebserviceObject::fromName($adb, 'Users');
            $values = array('subject' => $subject, 'description' => vtlib_purify($body), 'assigned_user_id' => vtws_getId($users->getEntityId(), $user->id),
                'parent_id' => array_key_first($ids), 'activitytype' => 'Emails', 'email_flag' => 'SAVED',
                'from_email' => $from, 'saved_toid' => $to, 'ccmail' => $cc,
                'date_start' => $date->format('Y-m-d'), 'time_start' => $date->format('H:i:s'));
            foreach ($rights['emailFields'] as $field) {
                if (!empty($field['mandatory']) && (!isset($values[$field['name']]) || $values[$field['name']] === '')) {
                    throw new InvalidArgumentException(berli_outlookT('A_REQUIRED_CRM_FIELD_IS_MISSING_FOR_SAVING_THE_EMAIL') . $field['label'] . '.');
                }
                // TEXT columns may report -1 (unspecified), not a character limit.
                $maxLength = (int)($field['fieldsize'] ?? 0);
                if (isset($values[$field['name']]) && $maxLength > 0
                    && preg_match_all('/./us', $values[$field['name']]) > $maxLength) throw new InvalidArgumentException(berli_outlookT('THE_EMAIL_FIELD') . html_entity_decode($field['label'], ENT_QUOTES, 'UTF-8') . berli_outlookT('EXCEEDS_THE_CRM_FIELD_LENGTH'));
            }
            berli_outlookMailQuery('INSERT INTO vtiger_outlook_mail_link (userid, sourcehash, emailid, state) VALUES (?, ?, NULL, ?)', array((int)$user->id, $source, 'pending'));
            // Standard create only persists an Emails record; no send operation is called.
            $record = vtws_create('Emails', $values, $user);
            $emailId = $record['id'] ?? null; berli_outlookMailId($emailId, 'Emails');
            berli_outlookMailQuery('UPDATE vtiger_outlook_mail_link SET emailid = ?, state = ? WHERE userid = ? AND sourcehash = ?', array($emailId, 'created', (int)$user->id, $source));
            $created = true;
        }
        $numericEmail = berli_outlookMailId($emailId, 'Emails');
        berli_outlookMailStoreFiles($files, $source, $numericEmail, $user);
        foreach ($ids as $numericContact) {
            berli_outlookMailQuery('INSERT INTO vtiger_seactivityrel (crmid, activityid) SELECT ?, ? WHERE NOT EXISTS (SELECT 1 FROM vtiger_seactivityrel WHERE crmid = ? AND activityid = ?)', array($numericContact, $numericEmail, $numericContact, $numericEmail));
        }
        berli_outlookMailQuery('UPDATE vtiger_outlook_mail_link SET emailid = ?, state = ? WHERE userid = ? AND sourcehash = ?', array($emailId, 'ready', (int)$user->id, $source));
        return array('emailId' => $emailId, 'created' => $created, 'contactIds' => array_keys($ids));
    } finally { $adb->pquery('SELECT RELEASE_LOCK(?)', array($lockName)); }
}

/**
 * Dispatches permission checks, sender-contact lookup and idempotent email archiving.
 *
 * @param mixed $input Decoded archive action.
 * @param Users $user Authenticated CRM user.
 * @return array<string,mixed>
 * @throws InvalidArgumentException On an invalid archive action.
 * @berli-openapi-begin
 * {"summary":"Archive an Outlook email","description":"Checks archive permissions, finds sender contacts, or idempotently stores an Outlook email and its attachments according to input.mode.","tags":["Outlook"],"parameters":{"input":{"required":true,"description":"JSON email archive action. mode must be check, find or save.","example":"{\"mode\":\"find\",\"from\":\"sender@example.org\"}","decodedSchema":{"type":"object","required":["mode"],"properties":{"mode":{"type":"string","enum":["check","find","save"]}},"additionalProperties":true}}},"result":{"schema":{"type":"object","additionalProperties":true},"example":{"canCreate":true,"contactEmailLabel":"Email","contacts":[]}}}
 * @berli-openapi-end
 */
function berli_outlookArchiveEmail($input, $user)
{
    if (!is_array($input)) throw new InvalidArgumentException(berli_outlookT('INVALID_EMAIL_ACTION'));
    $rights = berli_outlookMailRights($user);
    if (($input['mode'] ?? '') === 'check') return array('canCreate' => $rights['canCreate'], 'contactEmailLabel' => $rights['contactEmailLabel']);
    if (($input['mode'] ?? '') === 'find') return array('canCreate' => $rights['canCreate'], 'contactEmailLabel' => $rights['contactEmailLabel'],
        'contacts' => berli_outlookMailFindContacts(berli_outlookMailAddress($input['from'] ?? null), $rights, $user));
    if (($input['mode'] ?? '') === 'save') return berli_outlookMailSave($input, $rights, $user);
    throw new InvalidArgumentException(berli_outlookT('INVALID_EMAIL_ACTION'));
}

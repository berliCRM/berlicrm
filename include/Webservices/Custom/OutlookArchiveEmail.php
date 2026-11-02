<?php

require_once 'include/Webservices/DescribeObject.php';
require_once 'include/Webservices/Query.php';
require_once 'include/Webservices/Retrieve.php';
require_once 'include/Webservices/Create.php';
require_once 'include/fields/DateTimeField.php';

function berli_outlookMailQuery($sql, array $params)
{
    global $adb;
    $result = $adb->pquery($sql, $params);
    if ($result === false) throw new RuntimeException('Die E-Mail-Verknüpfung konnte nicht gelesen oder gespeichert werden. Bitte den Outlook-Installer ausführen.');
    return $result;
}

function berli_outlookMailAddress($address)
{
    if (!is_string($address) || strlen($address) > 254 || !filter_var(trim($address), FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Die E-Mail-Adresse im Von-Feld fehlt oder ist ungültig.');
    }
    return strtolower(trim($address));
}

function berli_outlookMailId($id, $module)
{
    global $adb;
    $object = VtigerWebserviceObject::fromName($adb, $module);
    if (!is_string($id) || !preg_match('/^' . preg_quote((string)$object->getEntityId(), '/') . 'x([1-9][0-9]*)$/D', $id, $match)) {
        throw new InvalidArgumentException('Ungültige ' . $module . '-Datensatz-ID.');
    }
    return (int)$match[1];
}

function berli_outlookMailRights($user)
{
    $contacts = vtws_describe('Contacts', $user);
    $emails = vtws_describe('Emails', $user);
    $fields = array_column($contacts['fields'], 'name');
    if (!in_array('email', $fields, true)) throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, 'Keine Leseberechtigung für das E-Mail-Suchfeld der Kontakte.');
    $emailLabel = '';
    foreach ($contacts['fields'] as $field) {
        if ($field['name'] === 'email') $emailLabel = trim(html_entity_decode($field['label'] ?? '', ENT_QUOTES, 'UTF-8'));
    }
    if ($emailLabel === '') throw new RuntimeException('Das CRM hat keine Feldbezeichnung für das E-Mail-Suchfeld der Kontakte geliefert.');
    return array('canCreate' => !empty($emails['createable']), 'contactFields' => $fields,
        'contactEmailLabel' => $emailLabel, 'emailFields' => $emails['fields']);
}

function berli_outlookMailFindContacts($from, array $rights, $user)
{
    $email = str_replace(array('\\', "'"), array('\\\\', "''"), $from);
    $fields = array_intersect(array('id', 'firstname', 'lastname', 'account_id', 'email'), $rights['contactFields']);
    if (!in_array('id', $fields, true)) $fields[] = 'id';
    $matches = array(); $accounts = array(); $seen = array();
    for ($offset = 0; ; $offset += 100) {
        $page = vtws_query('select ' . implode(', ', $fields) . " from Contacts where email = '$email' order by id limit $offset,100;", $user);
        foreach ($page as $contact) {
            if (isset($seen[$contact['id']])) throw new RuntimeException('Die CRM-Suche hat eine Kontaktseite wiederholt.');
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

function berli_outlookMailString(array $input, $key, $max, $required = false)
{
    $value = $input[$key] ?? '';
    if (!is_string($value) || strlen($value) > $max || !preg_match('//u', $value) || strpos($value, "\0") !== false || ($required && trim($value) === '')) {
        throw new InvalidArgumentException('Ungültiges E-Mail-Feld: ' . $key . '.');
    }
    return $value;
}

function berli_outlookMailRecipients($recipients)
{
    if (!is_array($recipients) || count($recipients) > 1000) throw new InvalidArgumentException('Ungültige E-Mail-Empfängerliste.');
    $addresses = array();
    foreach ($recipients as $recipient) $addresses[] = berli_outlookMailAddress($recipient);
    return implode(',', array_unique($addresses));
}

function berli_outlookMailFiles($attachments)
{
    global $upload_badext;
    if (!is_array($attachments) || count($attachments) > 100) throw new InvalidArgumentException('Höchstens 100 Anhänge pro E-Mail sind erlaubt.');
    $files = array(); $total = 0;
    foreach ($attachments as $attachment) {
        if (!is_array($attachment)) throw new InvalidArgumentException('Ungültiger E-Mail-Anhang.');
        $name = berli_outlookMailString($attachment, 'name', 255, true);
        if (preg_match('/[\x00-\x1f]/', $name)) throw new InvalidArgumentException('Ungültiger Dateiname.');
        $name = basename(str_replace('\\', '/', $name));
        $name = sanitizeUploadFileName($name, $upload_badext);
        if ($name === '' || $name === '.' || $name === '..' || strlen($name) > 255) throw new InvalidArgumentException('Ungültiger Dateiname.');
        $encoded = berli_outlookMailString($attachment, 'content', 14 * 1024 * 1024);
        $data = base64_decode($encoded, true);
        if ($data === false || strlen($data) > 10 * 1024 * 1024) throw new InvalidArgumentException('Ein Anhang ist ungültig oder größer als 10 MB.');
        $total += strlen($data);
        if ($total > 20 * 1024 * 1024) throw new InvalidArgumentException('Die Anhänge überschreiten zusammen 20 MB.');
        $type = berli_outlookMailString($attachment, 'type', 150);
        if (!preg_match('~^[a-z0-9!#$&^_.+-]+/[a-z0-9!#$&^_.+-]+$~iD', $type)) $type = 'application/octet-stream';
        $hash = hash('sha256', $name . "\n" . $type . "\n" . $data);
        $files[$hash] = array('name' => $name, 'type' => $type, 'data' => $data);
    }
    return $files;
}

function berli_outlookMailStoreFiles(array $files, $source, $email, $user)
{
    global $adb, $root_directory;
    foreach ($files as $hash => $file) {
        $params = array((int)$user->id, $source, $hash);
        $link = berli_outlookMailQuery('SELECT attachmentid, state FROM vtiger_outlook_mail_file WHERE userid = ? AND sourcehash = ? AND filehash = ?', $params);
        if ($adb->num_rows($link)) {
            $existing = $adb->fetchByAssoc($link, -1, false);
            if ($existing['state'] !== 'ready' || empty($existing['attachmentid'])) throw new RuntimeException('Eine frühere Anhangablage wurde nicht eindeutig bestätigt. Bitte administrativ prüfen lassen.');
            $found = berli_outlookMailQuery('SELECT attachmentsid FROM vtiger_seattachmentsrel INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_seattachmentsrel.attachmentsid WHERE vtiger_seattachmentsrel.crmid = ? AND attachmentsid = ? AND deleted = 0', array($email, (int)$existing['attachmentid']));
            if (!$adb->num_rows($found)) throw new RuntimeException('Ein bereits abgelegter Anhang wurde im CRM entfernt. Bitte administrativ prüfen lassen.');
            continue;
        }
        $path = decideFilePath();
        $directory = realpath(rtrim($root_directory, '/\\') . '/' . $path);
        $root = realpath($root_directory);
        if ($directory === false || $root === false || strpos($directory . DIRECTORY_SEPARATOR, $root . DIRECTORY_SEPARATOR) !== 0) throw new RuntimeException('Das CRM-Anhangverzeichnis ist ungültig.');
        $attachmentId = $adb->getUniqueID('vtiger_crmentity');
        berli_outlookMailQuery('INSERT INTO vtiger_outlook_mail_file (userid, sourcehash, filehash, attachmentid, state) VALUES (?, ?, ?, NULL, ?)', array_merge($params, array('pending')));
        // Exclusive creation: never overwrite an existing CRM storage file.
        $stream = @fopen($directory . DIRECTORY_SEPARATOR . $attachmentId . '_' . $file['name'], 'xb');
        if (!$stream) throw new RuntimeException('Der E-Mail-Anhang konnte nicht gespeichert werden.');
        try {
            $written = 0; $length = strlen($file['data']);
            while ($written < $length) {
                $count = fwrite($stream, substr($file['data'], $written));
                if ($count === false || $count === 0) throw new RuntimeException('Der E-Mail-Anhang wurde nicht vollständig gespeichert.');
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

function berli_outlookMailSave(array $input, array $rights, $user)
{
    global $adb;
    if (!$rights['canCreate']) throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, 'Keine Berechtigung zum Anlegen von CRM-E-Mails.');
    $mail = $input['mail'] ?? null;
    if (!is_array($mail)) throw new InvalidArgumentException('Die E-Mail-Daten fehlen.');
    $from = berli_outlookMailAddress($mail['from'] ?? null);
    $mailbox = berli_outlookMailAddress($mail['mailbox'] ?? null);
    $itemId = berli_outlookMailString($mail, 'itemId', 4096, true);
    $messageId = berli_outlookMailString($mail, 'internetMessageId', 2048);
    if (preg_match('/[\x00-\x1f]/', $itemId . $messageId)) throw new InvalidArgumentException('Ungültige Outlook-Nachrichten-ID.');
    $subject = berli_outlookMailString($mail, 'subject', 1024);
    if (trim($subject) === '') $subject = '(Ohne Betreff)';
    $body = berli_outlookMailString($mail, 'body', 2 * 1024 * 1024);
    $to = berli_outlookMailRecipients($mail['to'] ?? array());
    $cc = berli_outlookMailRecipients($mail['cc'] ?? array());
    $files = berli_outlookMailFiles($mail['attachments'] ?? array());
    $sent = berli_outlookMailString($mail, 'dateTimeCreated', 40, true);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{3})?Z$/D', $sent)) throw new InvalidArgumentException('Ungültiges Nachrichtendatum.');
    $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z', substr($sent, 0, 19) . 'Z', new DateTimeZone('UTC'));
    $errors = DateTimeImmutable::getLastErrors();
    if (!$date || ($errors && ($errors['warning_count'] || $errors['error_count']))) throw new InvalidArgumentException('Ungültiges Nachrichtendatum.');
    $date = $date->setTimezone(new DateTimeZone(DateTimeField::getDBTimeZone()));
    $selected = $input['contactIds'] ?? null;
    if (!is_array($selected) || !$selected || count($selected) > 1000) throw new InvalidArgumentException('Bitte mindestens einen Kontakt auswählen (höchstens 1000).');
    $ids = array();
    foreach ($selected as $id) {
        $numeric = berli_outlookMailId($id, 'Contacts');
        $contact = vtws_retrieve($id, $user);
        if (strtolower(trim(html_entity_decode($contact['email'] ?? '', ENT_QUOTES, 'UTF-8'))) !== $from) {
            throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, 'Ein ausgewählter Kontakt gehört nicht mehr zur Von-Adresse. Bitte die Suche erneut starten.');
        }
        $ids[$id] = $numeric;
    }
    $source = hash('sha256', $mailbox . "\n" . ($messageId !== '' ? 'message:' . $messageId : 'item:' . $itemId));
    $lockName = 'outlook-mail-' . substr(hash('sha256', $user->id . ':' . $source), 0, 48);
    $lock = berli_outlookMailQuery('SELECT GET_LOCK(?, 5) AS acquired', array($lockName));
    if ((string)$adb->query_result($lock, 0, 'acquired') !== '1') throw new RuntimeException('Diese E-Mail wird bereits abgelegt.');
    try {
        $link = berli_outlookMailQuery('SELECT emailid, state FROM vtiger_outlook_mail_link WHERE userid = ? AND sourcehash = ?', array((int)$user->id, $source));
        $existing = $adb->num_rows($link) ? $adb->fetchByAssoc($link, -1, false) : null;
        $created = false;
        if ($existing) {
            if (empty($existing['emailid']) || !in_array($existing['state'], array('created', 'ready'), true)) {
                throw new RuntimeException('Eine frühere E-Mail-Ablage wurde nicht eindeutig bestätigt. Bitte administrativ prüfen lassen; es wird keine zweite E-Mail angelegt.');
            }
            $emailId = $existing['emailid'];
            berli_outlookMailId($emailId, 'Emails');
            $storedMail = vtws_retrieve($emailId, $user);
            if (berli_outlookMailAddress($storedMail['from_email'] ?? null) !== $from) throw new InvalidArgumentException('Die bereits abgelegte E-Mail gehört zu einer anderen Von-Adresse.');
            if (isPermitted('Emails', 'EditView', berli_outlookMailId($emailId, 'Emails')) !== 'yes') {
                throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, 'Keine Schreibberechtigung für die bereits abgelegte CRM-E-Mail.');
            }
        } else {
            $users = VtigerWebserviceObject::fromName($adb, 'Users');
            $values = array('subject' => $subject, 'description' => vtlib_purify($body), 'assigned_user_id' => vtws_getId($users->getEntityId(), $user->id),
                'parent_id' => array_key_first($ids), 'activitytype' => 'Emails', 'email_flag' => 'SAVED',
                'from_email' => $from, 'saved_toid' => $to, 'ccmail' => $cc,
                'date_start' => $date->format('Y-m-d'), 'time_start' => $date->format('H:i:s'));
            foreach ($rights['emailFields'] as $field) {
                if (!empty($field['mandatory']) && (!isset($values[$field['name']]) || $values[$field['name']] === '')) {
                    throw new InvalidArgumentException('Für die E-Mail-Ablage fehlt das CRM-Pflichtfeld ' . $field['label'] . '.');
                }
                // TEXT columns may report -1 (unspecified), not a character limit.
                $maxLength = (int)($field['fieldsize'] ?? 0);
                if (isset($values[$field['name']]) && $maxLength > 0
                    && preg_match_all('/./us', $values[$field['name']]) > $maxLength) throw new InvalidArgumentException('Das E-Mail-Feld ' . html_entity_decode($field['label'], ENT_QUOTES, 'UTF-8') . ' überschreitet die CRM-Feldlänge.');
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

function berli_outlookArchiveEmail($input, $user)
{
    if (!is_array($input)) throw new InvalidArgumentException('Ungültige E-Mail-Aktion.');
    $rights = berli_outlookMailRights($user);
    if (($input['mode'] ?? '') === 'check') return array('canCreate' => $rights['canCreate'], 'contactEmailLabel' => $rights['contactEmailLabel']);
    if (($input['mode'] ?? '') === 'find') return array('canCreate' => $rights['canCreate'], 'contactEmailLabel' => $rights['contactEmailLabel'],
        'contacts' => berli_outlookMailFindContacts(berli_outlookMailAddress($input['from'] ?? null), $rights, $user));
    if (($input['mode'] ?? '') === 'save') return berli_outlookMailSave($input, $rights, $user);
    throw new InvalidArgumentException('Ungültige E-Mail-Aktion.');
}

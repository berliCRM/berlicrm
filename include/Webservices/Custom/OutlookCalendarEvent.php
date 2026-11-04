<?php
/**
 * Authenticated, owner-scoped Outlook calendar import and event updates.
 *
 * @package berliCRM.OfficeAddins
 */

require_once 'include/Webservices/Create.php';
require_once 'include/Webservices/Revise.php';
require_once 'include/Webservices/Retrieve.php';
require_once 'include/Webservices/Custom/OutlookGetEventDisplay.php';
require_once 'include/fields/DateTimeField.php';

/**
 * Executes a parameterized query against the add-in calendar link table.
 *
 * @param string $sql Parameterized SQL statement.
 * @param array $params Bound values.
 * @return object|bool
 * @throws WebServiceException On a database failure.
 */
function berli_outlookCalendarQuery($sql, array $params)
{
    global $adb;
    $result = $adb->pquery($sql, $params);
    if ($result === false) {
        throw new WebServiceException(WebServiceErrorCode::$DATABASEQUERYERROR,
            'Die Outlook-Terminverknüpfung konnte nicht gelesen oder gespeichert werden. Bitte den Outlook-Installer ausführen.');
    }
    return $result;
}

/**
 * Validates mailbox/item identity and calculates the calendar idempotency hash.
 *
 * @param array<string,mixed> $input Outlook event identity.
 * @param bool $requireId Whether saving requires a stable item identifier.
 * @return string|null
 * @throws InvalidArgumentException On malformed identity data.
 */
function berli_outlookCalendarSource(array $input, $requireId)
{
    $mailbox = $input['mailbox'] ?? '';
    $itemId = $input['itemId'] ?? '';
    if (!is_string($mailbox) || !filter_var($mailbox, FILTER_VALIDATE_EMAIL)
        || !is_string($itemId) || strlen($itemId) > 4096 || preg_match('/[\x00-\x1f]/', $itemId)
        || ($requireId && $itemId === '')) {
        throw new InvalidArgumentException('Die Outlook-Postfach- oder Termin-ID fehlt oder ist ungültig.');
    }
    return $itemId === '' ? null : hash('sha256', strtolower(trim($mailbox)) . "\n" . $itemId);
}

/**
 * Resolves a confirmed calendar link and rechecks access to the CRM event.
 *
 * @param string|null $sourceHash Outlook item identity hash.
 * @param Users $user Authenticated CRM user.
 * @return string|null
 * @throws RuntimeException If a previous save was not confirmed.
 * @throws WebServiceException When the event is not accessible.
 */
function berli_outlookCalendarLink($sourceHash, $user)
{
    global $adb;
    if ($sourceHash === null) {
        return null;
    }
    $result = berli_outlookCalendarQuery(
        'SELECT eventid, state FROM vtiger_outlook_calendar_link WHERE userid = ? AND sourcehash = ?',
        array((int)$user->id, $sourceHash)
    );
    if (!$adb->num_rows($result)) {
        return null;
    }
    $link = $adb->fetchByAssoc($result, -1, false);
    if ($link['state'] !== 'ready' || empty($link['eventid'])) {
        throw new RuntimeException('Eine frühere Übernahme dieses Termins wurde nicht eindeutig bestätigt. '
            . 'Bitte die Verknüpfung administrativ prüfen lassen; es wird kein weiterer Termin angelegt.');
    }
    $object = VtigerWebserviceObject::fromName($adb, 'Events');
    if (!preg_match('/^' . preg_quote((string)$object->getEntityId(), '/') . 'x[1-9][0-9]*$/D', $link['eventid'])) {
        throw new RuntimeException('Die gespeicherte CRM-Terminverknüpfung ist ungültig.');
    }
    // Recheck record access even when only displaying the saved link.
    vtws_retrieve($link['eventid'], $user);
    return $link['eventid'];
}

/**
 * Maps the personal CRM date and hour preferences to a PHP date format.
 *
 * @param Users $user Authenticated CRM user.
 * @return string
 * @throws InvalidArgumentException On unsupported date preferences.
 */
function berli_outlookCalendarTimeFormat($user)
{
    $dateFormat = !empty($user->date_format) ? $user->date_format : 'dd-mm-yyyy';
    $formats = array('dd-mm-yyyy' => 'd-m-Y', 'mm-dd-yyyy' => 'm-d-Y', 'yyyy-mm-dd' => 'Y-m-d');
    if (!isset($formats[$dateFormat])) {
        throw new InvalidArgumentException('Das persönliche CRM-Datumsformat wird nicht unterstützt.');
    }
    return $formats[$dateFormat] . ((string)($user->hour_format ?? '24') === '12' ? ' h:i A' : ' H:i');
}

/**
 * Resolves the personal timezone with the configured CRM timezone as fallback.
 *
 * @param Users $user Authenticated CRM user.
 * @return DateTimeZone
 */
function berli_outlookCalendarTimezone($user)
{
    global $default_timezone;
    return new DateTimeZone(!empty($user->time_zone) ? $user->time_zone : ($default_timezone ?: 'UTC'));
}

/**
 * Parses a strict Outlook UTC timestamp without silently correcting invalid dates.
 *
 * @param mixed $value Expected ISO UTC timestamp.
 * @return DateTimeImmutable
 * @throws InvalidArgumentException On malformed timestamps or dates.
 */
function berli_outlookCalendarUtc($value)
{
    if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{3})?Z$/D', $value)) {
        throw new InvalidArgumentException('Der Outlook-Termin enthält einen ungültigen Zeitpunkt.');
    }
    $format = strpos($value, '.') !== false ? '!Y-m-d\TH:i:s.v\Z' : '!Y-m-d\TH:i:s\Z';
    $date = DateTimeImmutable::createFromFormat($format, $value, new DateTimeZone('UTC'));
    $errors = DateTimeImmutable::getLastErrors();
    if (!$date || ($errors && ($errors['warning_count'] || $errors['error_count']))) {
        throw new InvalidArgumentException('Der Outlook-Termin enthält ein ungültiges Datum.');
    }
    return $date;
}

/**
 * Parses a user-formatted event time and rejects ambiguous daylight-saving edits.
 *
 * @param mixed $value User-edited local date/time text.
 * @param string $originalUtc Original Outlook UTC timestamp.
 * @param Users $user User whose timezone and date preferences apply.
 * @return DateTimeImmutable
 * @throws InvalidArgumentException On invalid or ambiguous local times.
 */
function berli_outlookCalendarParseEditedTime($value, $originalUtc, $user)
{
    $format = berli_outlookCalendarTimeFormat($user);
    $zone = berli_outlookCalendarTimezone($user);
    if (!is_string($value) || strlen($value) > 40) {
        throw new InvalidArgumentException('Beginn und Ende müssen im persönlichen CRM-Datumsformat angegeben werden.');
    }
    // Preserve the Outlook instant at the autumn DST overlap, including seconds.
    $original = berli_outlookCalendarUtc($originalUtc);
    if ($original->setTimezone($zone)->format($format) === $value) {
        return $original;
    }
    $date = DateTimeImmutable::createFromFormat('!' . $format, $value, $zone);
    $errors = DateTimeImmutable::getLastErrors();
    if (!$date || ($errors && ($errors['warning_count'] || $errors['error_count'])) || $date->format($format) !== $value) {
        throw new InvalidArgumentException('Beginn oder Ende ist ungültig oder entspricht nicht dem persönlichen CRM-Datumsformat.');
    }
    // A changed local time occurring twice cannot be resolved without an offset.
    foreach ($zone->getTransitions($date->getTimestamp() - 86400, $date->getTimestamp() + 86400) ?: array() as $transition) {
        $candidate = (new DateTimeImmutable('@' . ($date->getTimestamp() + $date->getOffset() - $transition['offset'])))->setTimezone($zone);
        if ($candidate->getTimestamp() !== $date->getTimestamp() && $candidate->format($format) === $value) {
            throw new InvalidArgumentException('Diese Uhrzeit kommt bei der Zeitumstellung zweimal vor. Bitte die Zeit in Outlook ändern und erneut übernehmen.');
        }
    }
    return $date;
}

/**
 * Validates editable event values and converts them to CRM database date/time fields.
 *
 * @param array<string,mixed> $input Edited event values and original UTC timestamps.
 * @param Users $user Authenticated CRM user.
 * @return array<string,string>
 * @throws InvalidArgumentException When an event field or time range is invalid.
 */
function berli_outlookCalendarFields(array $input, $user)
{
    $subject = $input['subject'] ?? '';
    $location = $input['location'] ?? '';
    $description = $input['description'] ?? '';
    if (!is_string($subject) || !preg_match('/^.{1,255}$/us', trim($subject))
        || !is_string($location) || !preg_match('/^.{0,255}$/us', $location)
        || !is_string($description) || strlen($description) > 100000) {
        throw new InvalidArgumentException('Bitte Betreff (maximal 255 Zeichen), Ort (maximal 255 Zeichen) und Beschreibung prüfen.');
    }
    $start = berli_outlookCalendarParseEditedTime($input['start'] ?? '', $input['originalStart'] ?? '', $user);
    $end = berli_outlookCalendarParseEditedTime($input['end'] ?? '', $input['originalEnd'] ?? '', $user);
    if ($end <= $start) {
        throw new InvalidArgumentException('Das Terminende muss nach dem Beginn liegen.');
    }
    // Standard CRM web services take database dates/times; their field sanitizers
    // perform the later user-format conversion required by CRMEntity::save.
    $zone = new DateTimeZone(DateTimeField::getDBTimeZone());
    $duration = (int)floor(($end->getTimestamp() - $start->getTimestamp()) / 60);
    return array(
        'subject' => trim($subject), 'location' => $location, 'description' => $description,
        'date_start' => $start->setTimezone($zone)->format('Y-m-d'),
        'time_start' => $start->setTimezone($zone)->format('H:i:s'),
        'due_date' => $end->setTimezone($zone)->format('Y-m-d'),
        'time_end' => $end->setTimezone($zone)->format('H:i:s'),
        'duration_hours' => (string)floor($duration / 60), 'duration_minutes' => (string)($duration % 60)
    );
}

/**
 * Prepares or saves an Outlook event using owner-scoped links and standard CRM rights.
 *
 * @param mixed $input Decoded prepare/save payload.
 * @param Users $user Authenticated CRM user.
 * @return array<string,mixed>
 * @throws InvalidArgumentException On invalid input.
 * @throws RuntimeException On locking or unconfirmed save failures.
 * @throws WebServiceException On CRM permission or database failures.
 */
function berli_outlookCalendarEvent($input, $user)
{
    global $adb;
    if (!is_array($input) || !in_array($input['mode'] ?? '', array('prepare', 'save'), true)) {
        throw new InvalidArgumentException('Ungültige Kalenderaktion.');
    }
    $save = $input['mode'] === 'save';
    $sourceHash = berli_outlookCalendarSource($input, $save);
    $display = berli_outlookGetEventDisplay($user);
    $zone = berli_outlookCalendarTimezone($user);
    if (!$save) {
        $start = berli_outlookCalendarUtc($input['originalStart'] ?? '');
        $end = berli_outlookCalendarUtc($input['originalEnd'] ?? '');
        if ($end <= $start) {
            throw new InvalidArgumentException('Das Terminende muss nach dem Beginn liegen.');
        }
        $format = berli_outlookCalendarTimeFormat($user);
        return array(
            'eventId' => berli_outlookCalendarLink($sourceHash, $user),
            'display' => $display, 'timezone' => $zone->getName(),
            'start' => $start->setTimezone($zone)->format($format),
            'end' => $end->setTimezone($zone)->format($format)
        );
    }
    $fields = berli_outlookCalendarFields($input, $user);
    $lockName = 'outlook-event-' . substr(hash('sha256', (string)$user->id . ':' . $sourceHash), 0, 45);
    $lock = berli_outlookCalendarQuery('SELECT GET_LOCK(?, 5) AS acquired', array($lockName));
    if ((string)$adb->query_result($lock, 0, 'acquired') !== '1') {
        throw new RuntimeException('Dieser Termin wird bereits übernommen. Bitte gleich erneut versuchen.');
    }
    try {
        $eventId = berli_outlookCalendarLink($sourceHash, $user);
        if ($eventId !== null) {
            $event = vtws_revise(array_merge($fields, array('id' => $eventId)), $user);
            return array('eventId' => $event['id'], 'updated' => true);
        }
        if (empty($display['canCreate'])) {
            throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, 'Keine Berechtigung zum Anlegen von CRM-Terminen.');
        }
        // Durable reservation prevents a retry after an uncertain HTTP/PHP failure
        // from creating a second event. Only the installer creates the link table.
        berli_outlookCalendarQuery(
            'INSERT INTO vtiger_outlook_calendar_link (userid, sourcehash, eventid, state) VALUES (?, ?, NULL, ?)',
            array((int)$user->id, $sourceHash, 'pending')
        );
        $usersObject = VtigerWebserviceObject::fromName($adb, 'Users');
        $event = vtws_create('Events', array_merge($fields, array(
            'assigned_user_id' => vtws_getId($usersObject->getEntityId(), $user->id),
            'activitytype' => 'Meeting', 'eventstatus' => 'Planned', 'visibility' => 'Private'
        )), $user);
        if (!is_array($event) || empty($event['id'])) {
            throw new RuntimeException('Das CRM hat die Terminanlage nicht eindeutig bestätigt.');
        }
        berli_outlookCalendarQuery(
            'UPDATE vtiger_outlook_calendar_link SET eventid = ?, state = ? WHERE userid = ? AND sourcehash = ?',
            array($event['id'], 'ready', (int)$user->id, $sourceHash)
        );
        return array('eventId' => $event['id'], 'updated' => false);
    } finally {
        $adb->pquery('SELECT RELEASE_LOCK(?)', array($lockName));
    }
}

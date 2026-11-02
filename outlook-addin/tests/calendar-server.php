<?php
// Isolated contract tests: fake CRM transports/DB; never bootstrap a real CRM.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
$root = dirname(__DIR__, 2);
$fixture = sys_get_temp_dir() . '/berlicrm-calendar-' . bin2hex(random_bytes(8));
$files = array(
    'include/Webservices/Create.php' => '<?php function vtws_create($type, $fields, $user) { return FakeCalendarCrm::create($type, $fields, $user); }',
    'include/Webservices/Revise.php' => '<?php function vtws_revise($fields, $user) { return FakeCalendarCrm::revise($fields, $user); }',
    'include/Webservices/Retrieve.php' => '<?php function vtws_retrieve($id, $user) { return FakeCalendarCrm::retrieve($id, $user); }',
    'include/Webservices/Custom/OutlookGetEventDisplay.php' => '<?php function berli_outlookGetEventDisplay($user) { return array("dateFormat" => $user->date_format, "fieldLabels" => array(), "canCreate" => $user->canCreate); }',
    'include/fields/DateTimeField.php' => '<?php class DateTimeField { static function getDBTimeZone() { return "UTC"; } }',
);
foreach ($files as $file => $contents) {
    if (!is_dir(dirname($fixture . '/' . $file))) mkdir(dirname($fixture . '/' . $file), 0700, true);
    file_put_contents($fixture . '/' . $file, $contents);
}
set_include_path($fixture . PATH_SEPARATOR . get_include_path());

class WebServiceErrorCode { public static $DATABASEQUERYERROR = 'DB', $ACCESSDENIED = 'DENIED'; }
class WebServiceException extends RuntimeException { public function __construct($code, $message) { parent::__construct($message); } }
class VtigerWebserviceObject {
    private $name;
    public function __construct($name) { $this->name = $name; }
    public static function fromName($db, $name) { return new self($name); }
    public function getEntityId() { return $this->name === 'Events' ? 9 : 19; }
}
function vtws_getId($module, $id) { return $module . 'x' . $id; }
class FakeCalendarCrm {
    public static $events = array(), $creates = 0, $revisions = 0, $failCreate = false;
    public static function create($type, $fields, $user) {
        if ($type !== 'Events') throw new RuntimeException('Wrong standard entity type');
        self::$creates++;
        if (self::$failCreate) throw new RuntimeException('Uncertain create failure');
        $id = '9x' . (100 + self::$creates);
        self::$events[$id] = array_merge($fields, array('id' => $id, 'custom_field' => 'keep me'));
        return self::$events[$id];
    }
    public static function retrieve($id, $user) {
        if (!empty($user->denyRead)) throw new WebServiceException('DENIED', 'Read denied');
        if (!isset(self::$events[$id])) throw new RuntimeException('Linked event deleted');
        return self::$events[$id];
    }
    public static function revise($fields, $user) {
        if (!empty($user->denyWrite)) throw new WebServiceException('DENIED', 'Write denied');
        self::$revisions++;
        $id = $fields['id'];
        self::$events[$id] = array_merge(self::retrieve($id, $user), $fields);
        return self::$events[$id];
    }
}
class FakeCalendarDb {
    public $links = array(), $locked = false, $failUpdate = false, $releases = 0;
    public function pquery($sql, $params) {
        if (strpos($sql, 'SELECT GET_LOCK') === 0) return array(array('acquired' => $this->locked ? 0 : 1));
        if (strpos($sql, 'SELECT RELEASE_LOCK') === 0) { $this->releases++; return array(array('released' => 1)); }
        if (strpos($sql, 'SELECT eventid, state FROM vtiger_outlook_calendar_link') === 0) {
            $key = $params[0] . ':' . $params[1];
            return isset($this->links[$key]) ? array($this->links[$key]) : array();
        }
        if (strpos($sql, 'INSERT INTO vtiger_outlook_calendar_link') === 0) {
            $key = $params[0] . ':' . $params[1];
            if (isset($this->links[$key])) throw new RuntimeException('Duplicate link');
            $this->links[$key] = array('eventid' => null, 'state' => $params[2]);
            return array();
        }
        if (strpos($sql, 'UPDATE vtiger_outlook_calendar_link SET') === 0) {
            if ($this->failUpdate) return false;
            $this->links[$params[2] . ':' . $params[3]] = array('eventid' => $params[0], 'state' => $params[1]);
            return array();
        }
        throw new RuntimeException('Unexpected SQL: ' . $sql);
    }
    public function num_rows($rows) { return count($rows); }
    public function fetchByAssoc($rows, $index, $decode) { return $rows[0]; }
    public function query_result($rows, $index, $column) { return $rows[$index][$column]; }
}

function check($value, $message) { if (!$value) throw new RuntimeException($message); }
function rejects($callback, $message) {
    try { $callback(); } catch (Throwable $error) {
        check(strpos($error->getMessage(), $message) !== false, 'Unexpected failure: ' . $error->getMessage());
        return;
    }
    throw new RuntimeException('Expected failure containing: ' . $message);
}

try {
    require $root . '/include/Webservices/Custom/OutlookCalendarEvent.php';
    $adb = new FakeCalendarDb();
    $user = (object)array('id' => 3, 'date_format' => 'dd-mm-yyyy', 'hour_format' => '24', 'time_zone' => 'Europe/Berlin', 'canCreate' => true);
    $input = array('mode' => 'prepare', 'mailbox' => 'mailbox@example.test', 'itemId' => '',
        'originalStart' => '2026-04-09T08:00:00.000Z', 'originalEnd' => '2026-04-09T09:00:00.000Z');
    $prepared = berli_outlookCalendarEvent($input, $user);
    check($prepared['start'] === '09-04-2026 10:00' && $prepared['end'] === '09-04-2026 11:00', 'User timezone conversion');
    check($prepared['eventId'] === null && FakeCalendarCrm::$creates === 0 && !$adb->links, 'Prepare must be read-only');
    $save = array_merge($input, array('mode' => 'save', 'itemId' => 'outlook-1', 'subject' => 'Test meeting',
        'start' => $prepared['start'], 'end' => $prepared['end'], 'location' => 'Berlin', 'description' => 'Notes'));
    $result = berli_outlookCalendarEvent($save, $user);
    $eventId = $result['eventId'];
    check(!$result['updated'] && FakeCalendarCrm::$creates === 1, 'First import must create exactly once');
    $event = FakeCalendarCrm::$events[$eventId];
    check($event['assigned_user_id'] === '19x3' && $event['activitytype'] === 'Meeting' && $event['visibility'] === 'Private', 'Owner/default fields');
    check($event['date_start'] === '2026-04-09' && $event['time_start'] === '08:00:00' && $event['time_end'] === '09:00:00', 'Standard create needs DB timezone');
    check($event['duration_hours'] === '1' && $event['duration_minutes'] === '0', 'Duration');
    FakeCalendarCrm::$events[$eventId]['eventstatus'] = 'Held';
    $save['subject'] = 'Updated subject';
    $save['eventId'] = '9x999'; // Supplied record IDs are ignored; only server mapping selects the target.
    $again = berli_outlookCalendarEvent($save, $user);
    check($again['updated'] && $again['eventId'] === $eventId && FakeCalendarCrm::$creates === 1, 'Retry/update must reuse mapping');
    check(FakeCalendarCrm::$events[$eventId]['custom_field'] === 'keep me' && FakeCalendarCrm::$events[$eventId]['eventstatus'] === 'Held', 'Partial update preserves other fields');
    $prepared = berli_outlookCalendarEvent(array_merge($input, array('itemId' => 'outlook-1')), $user);
    check($prepared['eventId'] === $eventId, 'Reopen must find mapping');
    $user->denyRead = true;
    rejects(function () use ($save, $user) { berli_outlookCalendarEvent($save, $user); }, 'Read denied');
    unset($user->denyRead);
    $user->denyWrite = true;
    rejects(function () use ($save, $user) { berli_outlookCalendarEvent($save, $user); }, 'Write denied');
    unset($user->denyWrite);
    check($adb->releases === 4, 'Locks must be released after success and failure');
    $adb->locked = true;
    rejects(function () use ($save, $user) { berli_outlookCalendarEvent($save, $user); }, 'bereits');
    $adb->locked = false;

    $otherUser = clone $user;
    $otherUser->id = 4;
    $other = berli_outlookCalendarEvent($save, $otherUser);
    check($other['eventId'] !== $eventId && FakeCalendarCrm::$events[$other['eventId']]['assigned_user_id'] === '19x4', 'Mappings are scoped to authenticated CRM user');
    $user->canCreate = false;
    $before = count($adb->links);
    rejects(function () use ($save, $user) { berli_outlookCalendarEvent(array_merge($save, array('itemId' => 'denied-new')), $user); }, 'Berechtigung');
    check(count($adb->links) === $before, 'Denied create must not reserve an item');
    $user->canCreate = true;

    foreach (array('2026-02-30T08:00:00.000Z', 'invalid') as $invalid) {
        rejects(function () use ($input, $invalid, $user) { berli_outlookCalendarEvent(array_merge($input, array('originalStart' => $invalid)), $user); }, 'ungültig');
    }
    rejects(function () use ($save, $user) { berli_outlookCalendarEvent(array_merge($save, array('end' => '09-04-2026 09:00')), $user); }, 'nach dem Beginn');
    rejects(function () use ($save, $user) { berli_outlookCalendarEvent(array_merge($save, array('start' => '29-03-2026 02:30')), $user); }, 'ungültig');
    rejects(function () use ($save, $user) { berli_outlookCalendarEvent(array_merge($save, array('start' => '25-10-2026 02:30')), $user); }, 'zweimal');
    foreach (array('2026-10-25T00:30:00.000Z', '2026-10-25T01:30:00.000Z') as $utc) {
        $parsed = berli_outlookCalendarParseEditedTime('25-10-2026 02:30', $utc, $user);
        check($parsed->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.000\Z') === $utc, 'DST overlap must preserve original UTC instant');
    }
    $usUser = clone $user;
    $usUser->date_format = 'mm-dd-yyyy'; $usUser->hour_format = '12'; $usUser->time_zone = 'America/New_York';
    $us = berli_outlookCalendarEvent($input, $usUser);
    check($us['start'] === '04-09-2026 04:00 AM', 'US format and timezone');
    $parsed = berli_outlookCalendarParseEditedTime('04-09-2026 05:30 AM', $input['originalStart'], $usUser);
    check($parsed->setTimezone(new DateTimeZone('UTC'))->format('H:i') === '09:30', 'Edited 12h time');

    FakeCalendarCrm::$failCreate = true;
    $uncertain = array_merge($save, array('itemId' => 'outlook-uncertain'));
    rejects(function () use ($uncertain, $user) { berli_outlookCalendarEvent($uncertain, $user); }, 'Uncertain');
    FakeCalendarCrm::$failCreate = false;
    $before = FakeCalendarCrm::$creates;
    rejects(function () use ($uncertain, $user) { berli_outlookCalendarEvent($uncertain, $user); }, 'nicht eindeutig');
    check(FakeCalendarCrm::$creates === $before, 'Uncertain prior create must never be retried blindly');
    $adb->failUpdate = true;
    $lostLink = array_merge($save, array('itemId' => 'outlook-lost-link'));
    rejects(function () use ($lostLink, $user) { berli_outlookCalendarEvent($lostLink, $user); }, 'nicht gelesen');
    $adb->failUpdate = false;
    $before = FakeCalendarCrm::$creates;
    rejects(function () use ($lostLink, $user) { berli_outlookCalendarEvent($lostLink, $user); }, 'nicht eindeutig');
    check(FakeCalendarCrm::$creates === $before, 'Mapping failure must not create a duplicate on retry');
    unset(FakeCalendarCrm::$events[$eventId]);
    rejects(function () use ($save, $user) { berli_outlookCalendarEvent($save, $user); }, 'deleted');
    echo "Calendar server tests passed: prepare, create/update, permissions, owner scope, retries, locking, DB failures, date formats, timezone/DST and partial updates.\n";
} finally {
    // Remove only the fixture files/directories created above.
    foreach (array_keys($files) as $file) unlink($fixture . '/' . $file);
    foreach (array('include/Webservices/Custom', 'include/Webservices', 'include/fields', 'include', '') as $directory) rmdir($fixture . ($directory === '' ? '' : '/' . $directory));
}

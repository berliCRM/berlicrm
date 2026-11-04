<?php
/**
 * Isolated CRM contact import/export tests with no real database or mailbox access.
 *
 * @package berliCRM.OfficeAddins
 */
// Isolated contracts: no CRM bootstrap, real database, login or mailbox access.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
$root = dirname(__DIR__, 2);
$fixture = sys_get_temp_dir() . '/berlicrm-contacts-' . bin2hex(random_bytes(8));
$stubs = array(
    'DescribeObject.php' => 'function vtws_describe($type, $user) { if (!empty($user->denyRead)) throw new RuntimeException("Read denied"); return array("fields" => FakeContacts::$fields, "createable" => $user->canCreate); }',
    'Retrieve.php' => 'function vtws_retrieve($id, $user) { if (!empty($user->denyRead)) throw new RuntimeException("Read denied"); if (!isset(FakeContacts::$records[$id])) throw new RuntimeException("Missing contact"); return FakeContacts::$records[$id]; }',
    'Create.php' => 'function vtws_create($type, $fields, $user) { if ($type !== "Contacts" || !$user->canCreate) throw new RuntimeException("Create denied"); FakeContacts::$creates++; if (FakeContacts::$failCreate) throw new RuntimeException("Uncertain create"); $id="4x".(100+FakeContacts::$creates); return FakeContacts::$records[$id] = array_merge($fields,array("id"=>$id)); }',
    'Query.php' => 'function vtws_query($query, $user) { FakeContacts::$lastQuery=$query; return FakeContacts::$matches; }',
);
mkdir($fixture . '/include/Webservices', 0700, true);
foreach ($stubs as $file => $source) file_put_contents($fixture . '/include/Webservices/' . $file, '<?php ' . $source);
set_include_path($fixture . PATH_SEPARATOR . get_include_path());
class WebServiceErrorCode { public static $ACCESSDENIED = 'DENIED'; }
class WebServiceException extends RuntimeException { function __construct($code, $message) { parent::__construct($message); } }
class VtigerWebserviceObject {
    private $name;
    function __construct($name) { $this->name = $name; }
    static function fromName($db, $name) { return new self($name); }
    function getEntityId() { return $this->name === 'Contacts' ? 4 : 19; }
}
function vtws_getId($module, $id) { return $module . 'x' . $id; }
class FakeContacts {
    static $fields = array(), $records = array(), $matches = array(), $lastQuery, $creates = 0, $failCreate = false;
}
class ContactDb {
    public $links = array(), $releases = 0, $locked = false, $failUpdate = false;
    function pquery($sql, $params) {
        if (strpos($sql, 'SELECT GET_LOCK') === 0) return array(array('acquired' => $this->locked ? 0 : 1));
        if (strpos($sql, 'SELECT RELEASE_LOCK') === 0) { $this->releases++; return array(); }
        if (strpos($sql, 'SELECT contactid, state') === 0) {
            $key = $params[0] . ':' . $params[1]; return isset($this->links[$key]) ? array($this->links[$key]) : array();
        }
        if (strpos($sql, 'INSERT INTO vtiger_outlook_contact_link') === 0) {
            $key = $params[0] . ':' . $params[1];
            if (isset($this->links[$key])) throw new RuntimeException('Duplicate link');
            $this->links[$key] = array('contactid' => null, 'state' => $params[2]); return array();
        }
        if (strpos($sql, 'UPDATE vtiger_outlook_contact_link') === 0) {
            if ($this->failUpdate) return false;
            $this->links[$params[2] . ':' . $params[3]] = array('contactid' => $params[0], 'state' => $params[1]); return array();
        }
        throw new RuntimeException('Unexpected SQL: ' . $sql);
    }
    function num_rows($rows) { return count($rows); }
    function query_result($rows, $i, $column) { return $rows[$i][$column]; }
    function fetchByAssoc($rows, $i, $decode) { return $rows[0]; }
}
function check($value, $message) { if (!$value) throw new RuntimeException($message); }
function rejects($callback, $part) {
    try { $callback(); } catch (Throwable $e) { check(strpos($e->getMessage(), $part) !== false, 'Unexpected: ' . $e->getMessage()); return; }
    throw new RuntimeException('Expected error: ' . $part);
}
try {
    require $root . '/include/Webservices/Custom/OutlookContactTransfer.php';
    $adb = new ContactDb(); $site_URL = 'https://crm.example.test/';
    $user = (object)array('id' => 3, 'canCreate' => true);
    foreach (array('firstname','lastname','email','secondaryemail','phone','mobile','title','department','description','mailingstreet','mailingzip','othercity','assigned_user_id') as $name) {
        FakeContacts::$fields[] = array('name' => $name, 'label' => $name === 'lastname' ? 'Nachname' : $name,
            'mandatory' => in_array($name, array('lastname','assigned_user_id')), 'editable' => $name !== 'assigned_user_id', 'fieldsize' => $name === 'lastname' ? 12 : 255);
    }
    $metadata = berli_outlookContactTransfer(array('mode' => 'describe'), $user);
    check($metadata['ownerId'] === '19x3' && $metadata['canCreate'] && !$adb->links, 'Description is read-only, owner authenticated');
    $input = array('mode' => 'import', 'account' => 'microsoft-account', 'contact' => array('id' => 'contact1', 'givenName' => 'Ada', 'surname' => 'Lovelace',
        'emailAddresses' => array(array('address' => 'ada@example.test')), 'assigned_user_id' => '19x999', 'cf_admin' => 'injected',
        'businessAddress' => array('street' => 'Main 1', 'postalCode' => '01234'), 'homeAddress' => array('city' => 'Berlin')));
    $missing = $input; $missing['contact']['surname'] = ' ';
    $result = berli_outlookContactTransfer($missing, $user);
    check($result['status'] === 'skipped_missing_fields' && $result['missingFields'][0]['label'] === 'Nachname' && !$adb->links && FakeContacts::$creates === 0, 'Missing mandatory fields must never create/reserve');
    FakeContacts::$fields[] = array('name'=>'cf_required','label'=>'Kundennummer','mandatory'=>true,'editable'=>true);
    $result = berli_outlookContactTransfer($input, $user);
    check($result['status'] === 'skipped_missing_fields' && $result['missingFields'][0]['name'] === 'cf_required' && !$adb->links, 'Custom required fields must not be invented');
    array_pop(FakeContacts::$fields);
    $result = berli_outlookContactTransfer($input, $user); $id = $result['recordId'];
    check($result['status'] === 'created' && FakeContacts::$creates === 1, 'First import creates once');
    $record = FakeContacts::$records[$id];
    check($record['assigned_user_id'] === '19x3' && !isset($record['cf_admin']) && $record['mailingzip'] === '01234' && $record['othercity'] === 'Berlin', 'Whitelist, addresses, authenticated owner');
    $again = berli_outlookContactTransfer($input, $user);
    check($again['recordId'] === $id && $again['status'] === 'skipped_existing' && FakeContacts::$creates === 1, 'Repeat import must not duplicate');
    $export = berli_outlookContactTransfer(array('mode'=>'export','recordId'=>$id), $user);
    check($export['contact']['surname'] === 'Lovelace' && $export['contact']['businessAddress']['postalCode'] === '01234', 'Export maps readable fields');
    check(!isset($export['contact']['assigned_user_id']) && FakeContacts::$creates === 1, 'Export never writes CRM or exposes owner field');
    $roundtrip = $input; $roundtrip['contact'] = $export['contact'] + array('id'=>'roundtrip');
    check(berli_outlookContactTransfer($roundtrip, $user)['recordId'] === $id && FakeContacts::$creates === 1, 'CRM marker prevents roundtrip duplicates');
    $user->denyRead = true;
    rejects(function()use($input,$user){berli_outlookContactTransfer($input,$user);}, 'Read denied'); unset($user->denyRead);
    $user->canCreate = false; $before = count($adb->links);
    rejects(function()use($input,$user){berli_outlookContactTransfer($input,$user);}, 'Berechtigung');
    check(count($adb->links) === $before, 'Denied create must not reserve'); $user->canCreate = true;
    rejects(function()use($user){berli_outlookContactTransfer(array('mode'=>'export','recordId'=>'19x3'),$user);}, 'Kontakt-ID');
    $other = clone $user; $other->id = 4;
    check(berli_outlookContactTransfer($input, $other)['recordId'] !== $id && FakeContacts::$creates === 2, 'Mappings scoped by authenticated user');
    $input['contact']['id'] = 'email-existing'; FakeContacts::$matches = array(array('id'=>$id));
    check(berli_outlookContactTransfer($input,$user)['status'] === 'skipped_existing' && FakeContacts::$creates === 2, 'Existing email is skipped, not overwritten');
    $input['contact']['id'] = 'ambiguous'; FakeContacts::$matches[] = array('id'=>'4x999');
    check(berli_outlookContactTransfer($input,$user)['status'] === 'skipped_duplicate' && FakeContacts::$creates === 2, 'Ambiguous emails skip');
    FakeContacts::$matches = array();
    foreach (array(array('surname'=>str_repeat('ö',13)),array('emailAddresses'=>array(array('address'=>'invalid'))),array('singleValueExtendedProperties'=>'bad'),array('emailAddresses'=>array('bad'))) as $changes) {
        $bad = $input; $bad['contact'] = array_merge($input['contact'],$changes);
        rejects(function()use($bad,$user){berli_outlookContactTransfer($bad,$user);}, isset($changes['surname']) ? 'Feldlänge' : 'Ungültig');
    }
    $input['contact']['id'] = 'quoted-email'; $input['contact']['emailAddresses'][0]['address'] = "o'neil@example.test";
    berli_outlookContactTransfer($input,$user);
    check(strpos(FakeContacts::$lastQuery,"o''neil@example.test") !== false, 'Quoted email must be escaped');
    $input['contact']['id'] = 'uncertain'; FakeContacts::$failCreate = true;
    rejects(function()use($input,$user){berli_outlookContactTransfer($input,$user);}, 'Uncertain');
    FakeContacts::$failCreate = false; $before = FakeContacts::$creates;
    rejects(function()use($input,$user){berli_outlookContactTransfer($input,$user);}, 'nicht eindeutig');
    check(FakeContacts::$creates === $before, 'Uncertain write must not retry blindly');
    $input['contact']['id'] = 'lost-link'; $adb->failUpdate = true;
    rejects(function()use($input,$user){berli_outlookContactTransfer($input,$user);}, 'gespeichert'); $adb->failUpdate = false;
    $before = FakeContacts::$creates;
    rejects(function()use($input,$user){berli_outlookContactTransfer($input,$user);}, 'nicht eindeutig');
    check(FakeContacts::$creates === $before, 'Lost mapping confirmation must not duplicate');
    $adb->locked = true;
    rejects(function()use($input,$user){berli_outlookContactTransfer($input,$user);}, 'bereits');
    check($adb->releases >= 10, 'Locks released on success and failure');
    echo "Contacts server checks passed: metadata, mandatory/custom fields, rights, owner, roundtrip, duplicates, mapping and uncertain writes.\n";
} finally {
    // Delete only the explicitly created fixture files and empty directories.
    foreach (array_keys($stubs) as $file) unlink($fixture . '/include/Webservices/' . $file);
    rmdir($fixture . '/include/Webservices'); rmdir($fixture . '/include'); rmdir($fixture);
}

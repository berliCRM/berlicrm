<?php
/**
 * Exercises the Outlook HTTP gateway and cURL client using a local fake server.
 *
 * No CRM configuration, external service, real credentials or database is used.
 * Run with PHP 8.x and the cURL extension enabled.
 *
 * @package berliCRM.OutlookAddin.Tests
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
if (!extension_loaded('curl')) throw new RuntimeException('Enable the PHP cURL extension before running this HTTP/client test.');
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) throw new ErrorException($message, 0, $severity, $file, $line);
    return true;
});
$root = dirname(__DIR__, 2);
$fixture = sys_get_temp_dir() . '/berlicrm-php8-http-' . bin2hex(random_bytes(8));
mkdir($fixture, 0700);
$files = array('outlook.php', 'OutlookWebserviceClient.php', 'OutlookContactsConfig.php', 'OutlookAuthRelay.php');
foreach ($files as $file) {
    // Keep this flat HTTP fixture independent of private installation configuration.
    $source = file_get_contents($root . '/outlook-addin/rest/' . $file);
    $source = str_replace("dirname(__DIR__, 2) . '/include/utils/OfficeAddinI18n.php'",
        var_export($root . '/include/utils/OfficeAddinI18n.php', true), $source);
    file_put_contents($fixture . '/' . $file, $source);
}
$router = <<<'PHP'
<?php
if (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) !== '/crm.php') return false;
header('Content-Type: application/json');
$query = $_REQUEST['query'] ?? '';
if ($query === 'scalar') { echo 'true'; return; }
if ($query === 'scalar-result') { echo '{"success":true,"result":"invalid"}'; return; }
if ($query === 'invalid-json') { echo '{'; return; }
if ($query === 'denied') { http_response_code(403); echo '{"error":{"message":"Fixture denied"}}'; return; }
if (($_REQUEST['operation'] ?? '') === 'getchallenge') $result = array('token' => 'fixture-token');
elseif (($_REQUEST['operation'] ?? '') === 'login') $result = array('sessionName' => 'fixture-session');
else $result = array(array('id' => '4x1'));
echo json_encode(array('success' => true, 'result' => $result));
PHP;
file_put_contents($fixture . '/router.php', $router);
$socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
if ($socket === false) throw new RuntimeException('Cannot reserve a loopback test port.');
$address = stream_socket_get_name($socket, false);
fclose($socket);
$server = null;
$pipes = array();
$checks = 0;
$check = function ($condition, $message) use (&$checks) {
    if (!$condition) throw new RuntimeException($message);
    $checks++;
};
try {
    $command = array(PHP_BINARY, '-n', '-d', 'display_errors=1', '-d', 'error_reporting=-1',
        '-S', $address, '-t', $fixture, $fixture . '/router.php');
    $server = proc_open($command, array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes);
    if (!is_resource($server)) throw new RuntimeException('Cannot start the loopback fixture server.');
    fclose($pipes[0]);
    $ready = false;
    for ($attempt = 0; $attempt < 100; $attempt++) {
        $connection = @stream_socket_client('tcp://' . $address, $errno, $error, 0.05);
        if ($connection !== false) { fclose($connection); $ready = true; break; }
        usleep(20000);
    }
    $check($ready, 'The local fixture server did not start.');
    $request = function ($method, $body = '') use ($address) {
        $requestHandle = curl_init('http://' . $address . '/outlook.php');
        try {
            curl_setopt_array($requestHandle, array(CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5, CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_HTTPHEADER => array('Content-Type: application/json')));
            if ($method === 'POST') curl_setopt($requestHandle, CURLOPT_POSTFIELDS, $body);
            $response = curl_exec($requestHandle);
            if ($response === false) throw new RuntimeException(curl_error($requestHandle));
            return array((int)curl_getinfo($requestHandle, CURLINFO_HTTP_CODE), json_decode($response, true));
        } finally { unset($requestHandle); }
    };
    foreach (array('42', 'true', '"text"', 'null', '[]', '[{}]', '{', '') as $body) {
        list($status, $response) = $request('POST', $body);
        $check($status === 400 && is_array($response) && $response['success'] === false,
            'Malformed/non-object JSON must return a clean HTTP 400 JSON error.');
    }
    foreach (array('{}', '{"action":["invalid"]}', '{"action":{"invalid":true}}') as $body) {
        list($status, $response) = $request('POST', $body);
        $check($status === 400 && is_array($response) && $response['success'] === false,
            'Missing or non-string actions must return JSON without PHP type warnings.');
    }
    list($status, $response) = $request('GET');
    $check($status === 405 && $response['success'] === false, 'GET must be rejected.');
    list($status, $response) = $request('POST', '{"action":"getContactsMicrosoftConfig"}');
    $check($status === 200 && $response['success'] === true && $response['payload'] === array('configured' => false),
        'The unconfigured public contacts action must remain usable.');

    require_once $root . '/outlook-addin/rest/OutlookWebserviceClient.php';
    $client = new OutlookWebserviceClient(array('crm_webservice_url' => 'http://' . $address . '/crm.php'));
    $check($client->login('fixture-user', 'fixture-key')['sessionName'] === 'fixture-session', 'Challenge/login failed.');
    $check($client->query('valid', 'fixture-session')[0]['id'] === '4x1', 'Valid array result failed.');
    foreach (array('scalar', 'scalar-result', 'invalid-json', 'denied') as $query) {
        try { $client->query($query, 'fixture-session'); }
        catch (RuntimeException $failure) { $check(true, 'Clean CRM failure.'); continue; }
        throw new RuntimeException('Invalid CRM result was accepted: ' . $query);
    }
    require_once $root . '/outlook-addin/build/debugOutlookWebservice.php';
    $debugger = new OutlookWebserviceDebugger('https://crm.example.test/webservice.php',
        function ($endpoint, $method, $params) {
            return array('status' => 200, 'body' => '{"success":true,"result":{"token":"fixture-token"}}');
        }, function ($line) {});
    $check($debugger->request('getchallenge', array('username' => 'fixture-user'))['ok'], 'Injected diagnostic transport failed.');
    echo 'Outlook PHP ' . PHP_VERSION . ': ' . $checks . " local HTTP/client checks passed.\n";
} finally {
    if (is_resource($server)) { proc_terminate($server); fclose($pipes[1]); fclose($pipes[2]); proc_close($server); }
    foreach (array_merge($files, array('router.php')) as $file) unlink($fixture . '/' . $file);
    rmdir($fixture);
}

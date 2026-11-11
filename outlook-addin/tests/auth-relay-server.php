<?php
/** Offline checks for the encrypted response store; no real CRM/Microsoft requests. */
require dirname(__DIR__, 2) . '/include/utils/OfficeAddinI18n.php';
require dirname(__DIR__) . '/rest/OutlookAuthRelay.php';
error_reporting(E_ALL);
set_error_handler(static function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) throw new ErrorException($message, 0, $severity, $file, $line);
    return true;
});
$GLOBALS['berli_office_language'] = 'de_de';
$base = sys_get_temp_dir() . '/berlicrm-relay-test-' . bin2hex(random_bytes(8));
mkdir($base, 0700);
$now = time(); $checks = 0;
$config = ['crm_webservice_url' => 'https://crm.example.test/webservice.php', 'contacts_auth_relay_directory' => $base];
$clock = static function () use (&$now) { return $now; };
$check = static function ($value) use (&$checks) { if (!$value) throw new RuntimeException('Relay assertion failed'); $checks++; };
$denied = static function ($work) use ($check) {
    try { $work(); } catch (RuntimeException | InvalidArgumentException $error) { $check(true); return; }
    throw new RuntimeException('Unauthorized relay action accepted');
};
try {
    $store = new OutlookAuthRelay($config, $clock);
    $flow = $store->begin('session-one');
    $check($flow['expiresAt'] === ($now + OutlookAuthRelay::LIFETIME) * 1000);
    $check($store->poll($flow['flowId'], 'session-one')['state'] === 'pending');
    $denied(static function () use ($store, $flow) { $store->poll($flow['flowId'], 'session-two'); });
    $payload = ['iv' => str_repeat('A', 16), 'data' => str_repeat('B', 32), 'token' => 'must-not-be-stored'];
    $denied(static function () use ($store, $flow, $payload) { $store->complete($flow['flowId'], str_repeat('0', 64), $payload); });
    $check($store->complete($flow['flowId'], $flow['writeKey'], $payload)['accepted']);
    $denied(static function () use ($store, $flow, $payload) { $store->complete($flow['flowId'], $flow['writeKey'], $payload); });
    $reply = $store->poll($flow['flowId'], 'session-one');
    $check($reply['state'] === 'ready' && $reply['payload'] === ['iv' => $payload['iv'], 'data' => $payload['data']]);
    $directory = $base . '/berlicrm-auth-' . hash('sha256', $config['crm_webservice_url']);
    $file = file_get_contents($directory . '/' . $flow['flowId'] . '.json');
    $check(strpos($file, 'session-one') === false && strpos($file, $flow['writeKey']) === false && strpos($file, 'must-not-be-stored') === false);
    $denied(static function () use ($store, $flow) { $store->close($flow['flowId'], 'session-two'); });
    $check($store->close($flow['flowId'], 'session-one')['closed']);
    $denied(static function () use ($store, $flow) { $store->poll($flow['flowId'], 'session-one'); });
    $denied(static function () use ($store) { $store->poll('../outside', 'session-one'); });
    $flow = $store->begin('session-one'); $now += 301;
    $denied(static function () use ($store, $flow) { $store->poll($flow['flowId'], 'session-one'); });
    echo "Encrypted sign-in relay: $checks checks passed.\n";
} finally {
    foreach (new DirectoryIterator($base) as $directory) {
        if ($directory->isDot()) continue;
        if ($directory->isLink() || !$directory->isDir() || !preg_match('/^berlicrm-auth-[a-f0-9]{64}$/D', $directory->getFilename())) throw new RuntimeException('Unexpected fixture directory');
        foreach (new DirectoryIterator($directory->getPathname()) as $file) {
            if ($file->isDot()) continue;
            if ($file->isLink() || !preg_match('/^[a-f0-9]{64}\.json$/D', $file->getFilename())) throw new RuntimeException('Unexpected fixture file');
            unlink($file->getPathname());
        }
        rmdir($directory->getPathname());
    }
    rmdir($base);
}

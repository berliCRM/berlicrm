<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require dirname(__DIR__) . '/rest/OutlookContactsConfig.php';
function check($value) { if (!$value) throw new RuntimeException('Configuration check failed'); }
$ids = array('client_id' => '11111111-2222-3333-4444-555555555555', 'tenant_id' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee');
check(outlookContactsMicrosoftConfig(array('api_access_key' => 'never-public')) === array('configured' => false));
$public = outlookContactsMicrosoftConfig(array('crm_webservice_url' => 'https://crm.example.test/sub/webservice.php',
    'contacts_microsoft' => $ids + array('client_secret' => 'never-public'), 'api_access_key' => 'never-public'));
check($public['redirectUri'] === 'https://crm.example.test/sub/outlook-addin/contacts-auth.html');
check(count($public) === 4 && strpos(json_encode($public), 'never-public') === false);
foreach (array('http://crm.example.test/outlook-addin/contacts-auth.html', 'https://u:p@crm.example.test/contacts-auth.html',
    'https://crm.example.test/contacts-auth.html?token=x', 'https://crm.example.test/other.html', 'https://crm.example.test/contacts-auth.html#x') as $bad) {
    try { outlookContactsMicrosoftConfig(array('contacts_microsoft' => $ids + array('redirect_uri' => $bad))); }
    catch (InvalidArgumentException $e) { continue; }
    throw new RuntimeException('Accepted unsafe redirect');
}
foreach (array('bad', array('client_id' => 'common', 'tenant_id' => 'organizations')) as $bad) {
    try { outlookContactsMicrosoftConfig(array('contacts_microsoft' => $bad)); }
    catch (InvalidArgumentException $e) { continue; }
    throw new RuntimeException('Accepted invalid tenant/client configuration');
}
echo "Contacts configuration checks passed: public IDs only, tenant restrictions and HTTPS redirect.\n";

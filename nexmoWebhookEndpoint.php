<?php
/**
 * HTTP request endpoint for push-notifications from Nexmo
*/

include_once 'includes/main/WebUI.php';

$request = array_merge($_GET, $_POST);
// Check that this is a delivery receipt.
if (!isset($request['messageId']) || !isset($request['status'])) {
    exit;
}
	
// initiate notification action if required
try {
	processNotifications ($request);
}
catch(Exception $e) {
	$requestError= $e->getMessage();
	if (is_object( $e->detail->FaultInfo )) {
		$requestError = $e->detail->FaultInfo->errorMessage;
	}
	exit;
}
exit;

function processNotifications ($notification) {
	require_once 'modules/SMSNotifier/SMSNotifier.php';
	require_once 'modules/SMSNotifier/models/Provider.php';
	
	$messageId = vtlib_purify($notification['messageId']);
	$status = vtlib_purify($notification['status']);
	$errcode ='';
	$SMSNotifierManager = new SMSNotifierManager();
	$providerInstance = $SMSNotifierManager->getActiveProviderInstance();
	
	$client_ip = (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];

	$is_permitted = false;
	foreach ($providerInstance->provider_ip_addresses as $ip_range) {
		$check = ip_in_range( $client_ip, $ip_range );
		if ($check == true) {
			$is_permitted = true;
			break;
		}
	}
	if ($is_permitted == true) {
	
		if (isset ($notification['err-code'])) {
			$errcode = vtlib_purify($notification['err-code']);
			$errcode = $providerInstance->provider_status[$errcode];
		}
		$SMSNotifier = new SMSNotifier();
		$SMSNotifier -> setSMSStatusInfo($messageId, $status ,$errcode) ;
	}
	return;
}

/**
 * Check if a given ip is in a network
 * @param  string $ip    IP to check in IPV4 format eg. 127.0.0.1
 * @param  string $range IP/CIDR netmask eg. 127.0.0.0/24, also 127.0.0.1 is accepted and /32 assumed
 * @return boolean true if the ip is in this range / false if not.
 */
function ip_in_range( $ip, $range ) {
	if ( strpos( $range, '/' ) == false ) {
		$range .= '/32';
	}
	// $range is in IP/CIDR format eg 127.0.0.1/24
	list( $range, $netmask ) = explode( '/', $range, 2 );
	$range_decimal = ip2long( $range );
	$ip_decimal = ip2long( $ip );
	$wildcard_decimal = pow( 2, ( 32 - $netmask ) ) - 1;
	$netmask_decimal = ~ $wildcard_decimal;
	return ( ( $ip_decimal & $netmask_decimal ) == ( $range_decimal & $netmask_decimal ) );
}
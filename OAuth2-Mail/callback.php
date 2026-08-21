<?php
ini_set('include_path', ini_get('include_path'). PATH_SEPARATOR . '../');
require_once('includes/main/WebUI.php');
require_once('include/utils/utils.php');

$includePath = 'vendor/autoload.php';
require_once($includePath);

use TheNetworg\OAuth2\Client\Provider\Azure;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ^E_DEPRECATED);
ini_set('display_errors', 'on');

// todo: check which provider sent this callback
if (true) {
	if (isset($_REQUEST['code'])) {
		$code = $_REQUEST['code'];
		
		require_once 'modules/Settings/Vtiger/models/ConfigoAuth.php';
		$settingsoAuth = Settings_Vtiger_oAuth::getInstance();
		$oAuthDetails = $settingsoAuth->getData();

		$scopes = ['offline_access',
				   'https://outlook.office.com/SMTP.Send'
				  ];
		$provider = new Azure([
			'clientId'               => $oAuthDetails['client_id'],
			'clientSecret'           => $oAuthDetails['client_secret'],
			'tenant'                 => $oAuthDetails['tenant_id'],
			'redirectUri'            => 'https://alexberli48.i1.crm-now.de/OAuth2-Mail/callback.php',
			'defaultEndPointVersion' => Azure::ENDPOINT_VERSION_2_0,
			'scopes'                 => $scopes
		]);
		$token = $provider->getAccessToken('authorization_code', [
			'code' => $code
			]);
		$refreshToken = $token->getRefreshToken();
		$expires = $token->getExpires();
		// save refresh token
		$request = new Vtiger_Request(array('hidden_refresh_token' => $refreshToken, 'hidden_refresh_token_expire' => $expires));
		$settingsoAuth->save($request);
		echo "Token successfully created";
	} else {
		echo "Expected Code not present";
	}
} else {
	echo "Unknown Provider Callback";
}
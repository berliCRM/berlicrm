<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
 
use TheNetworg\OAuth2\Client\Provider\Azure;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

class Settings_Vtiger_RefreshoAuthToken_Action extends Settings_Vtiger_Basic_Action {
    
    public function process(Vtiger_Request $request) {
		global $site_URL;
		$response = new Vtiger_Response();
		
		$includePath = 'vendor/autoload.php';
		if (file_exists($includePath)) {
			require_once($includePath);
			$provider = $request->get('provider');
			$type = $request->get('type');
			if ($provider == 'AZURE') {
				if (class_exists('TheNetworg\OAuth2\Client\Provider\Azure')) {
					require_once 'modules/Settings/Vtiger/models/ConfigoAuth.php';
					$settingsoAuth = Settings_Vtiger_oAuth::getInstance($type);
					$oAuthDetails = $settingsoAuth->getData();
					$scopes = [
						'offline_access',
						'https://outlook.office.com/IMAP.AccessAsUser.All'
					];
					$provider = new Azure([
						'clientId'               => $oAuthDetails['client_id'],
						'clientSecret'           => $oAuthDetails['client_secret'],
						'tenant'                 => $oAuthDetails['tenant_id'],
						'defaultEndPointVersion' => Azure::ENDPOINT_VERSION_2_0,
						'scopes'                 => $scopes,
					]);
					$token = $provider->getAccessToken('refresh_token', [
						'refresh_token' => $oAuthDetails['hidden_refresh_token'],
						'scope'         => implode(' ', $scopes),
					]);
					$accessToken = (string)$token->getToken();
					$expires = $token->getExpires();
					$request = new Vtiger_Request(array('hidden_access_token' => $accessToken, 'hidden_access_token_expire' => $expires));
					$settingsoAuth->save($request);
					
					$response->setResult($accessToken);
				}
			} else {
				$response->setError("Unknown provider '$provider'");
			}
		} else {
			$response->setError("Missing Composer");
		}
		
		$response->emit();
	}
}
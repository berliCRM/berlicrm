<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class SMSNotifier_ClickATell_Provider implements SMSNotifier_ISMSProvider_Model {

	private $userName;
	private $password;
	private $parameters = array();
	
	public $provider = 'ClickATell';
	public $provider_status = array(
		'001'=>'Message unknown',
		'002'=>'Message queued',
		'003'=>'Delivered to gateway',
		'004'=>'Received by recipient',
		'005'=>'Error with message',
		'006'=>'User cancelled message delivery',
		'007'=>'Error delivering message',
		'009'=>'Routing error',
		'010'=>'Message expired',
		'011'=>'Message scheduled for later delivery',
		'012'=>'Out of credit',
		'013'=>'Clickatell cancelled message delivery',
		'014'=>'Maximum MT limit exceeded',
	); 
	
	public $provider_ip_addresses = array (
		'0'=>'34.249.228.79',
		'1'=>'34.252.24.144',
		'2'=>'34.252.100.242',
		'3'=>'34.250.34.25',
		'4'=>'34.252.234.174',
	);
	
	const SERVICE_URI = 'https://platform.clickatell.com';
	// you may add 'from' if communications is two way
	private static $REQUIRED_PARAMETERS = array('apiKey');

	/**
	 * Function to get provider name
	 * @return <String> provider name
	 */
	public function getName() {
		return 'ClickATell';
	}

	/**
	 * Function to get required parameters other than (userName)
	 * @return <array> required parameters list
	 */
	public function getRequiredParams() {
		return self::$REQUIRED_PARAMETERS;
	}

	/**
	 * Function to get service URL to use for a given type
	 * @param <String> $type like SEND, PING, QUERY
	 */
	public function getServiceURL($type = false) {
		if($type) {
			switch(strtoupper($type)) {
				case self::SERVICE_AUTH:	return self::SERVICE_URI . '/http/auth';
				case self::SERVICE_SEND:	return self::SERVICE_URI . '/messages/http/send';
				case self::SERVICE_QUERY:	return self::SERVICE_URI . '/http/querymsg';
			}
		}
		return false;
	}

	/**
	 * Function to set authentication parameters
	 * @param <String> $userName
	 * @param <String> $password
	 */
	public function setAuthParameters($userName, $password) {
		$this->userName = $userName;
	}

	/**
	 * Function to set non-auth parameter.
	 * @param <String> $key
	 * @param <String> $value
	 */
	public function setParameter($key, $value) {
		$this->parameters[$key] = $value;
	}

	/**
	 * Function to get parameter value
	 * @param <String> $key
	 * @param <String> $defaultValue
	 * @return <String> value/$default value
	 */
	public function getParameter($key, $defaultValue = false) {
		if(isset($this->parameters[$key])) {
			return $this->parameters[$key];
		}
		return $defaultValue;
	}

	/**
	 * Function to prepare parameters
	 * @return <Array> parameters
	 */
	protected function prepareParameters() {
		$params = array('user' => $this->userName);
		foreach (self::$REQUIRED_PARAMETERS as $key) {
			$params[$key] = $this->getParameter($key);
		}
		return $params;
	}

	/**
	 * Function to handle SMS Send operation
	 * @param <String> $message
	 * @param <Mixed> $toNumbers One or Array of numbers
	 */
	public function send($message, $toNumbers) {
		if(!is_array($toNumbers)) {
			$toNumbers = array($toNumbers);
		}

		$params = $this->prepareParameters();
		$message = utf8_encode($message);
		// switch on call back
		$params['callback'] = '3';
		$params['content'] = $message;
		$params['to'] = implode(',', $toNumbers);

		$serviceURL = $this->getServiceURL(self::SERVICE_SEND);
		$httpClient = new Vtiger_Net_Client($serviceURL);
		$response = $httpClient->doGet($params);
		$response = json_decode($response);

		$results = array();
		$i=0;
		if (isset($response->messages) && !empty ($response->messages)) {
			foreach($response->messages as $responseObject) {
				if(empty($responseObject)) continue;
				$status = $responseObject->accepted;
				if ($status == 1) {
					$status = self::MSG_STATUS_PROCESSING;
				}

				$result = array( 
					'id' => $responseObject->apiMessageId, 
					'status' =>$status, 
					'to' => $responseObject->to, 
					'errorCode' => $responseObject->errorCode, 
					'error' => $responseObject->error, 
					'errorDescription' => $responseObject->errorDescription, 
				);
				
				$results[] = $result;
			}
		}
		else {
			$results[]   = array( 
				'error' => $response->errorCode, 
				'statusmessage' => $response->error,
			);
		}
		return $results;
	}

	/**
	 * Function to get query for status using messgae id
	 * @param <Number> $messageId
	 */
	public function query($messageId) {
		$params = $this->prepareParameters();
		$params['apimsgid'] = $messageId;

		$serviceURL = $this->getServiceURL(self::SERVICE_QUERY);
		$httpClient = new Vtiger_Net_Client($serviceURL);
		$response = $httpClient->doPost($params);
		$response = trim($response);

		$result = array( 'error' => false, 'needlookup' => 1, 'statusmessage' => '' );

		if(preg_match("/ERR: (.*)/", $response, $matches)) {
			$result['error'] = true;
			$result['needlookup'] = 0;
			$result['statusmessage'] = $matches[0];
		} else if(preg_match("/ID: ([^ ]+) Status: ([^ ]+)/", $response, $matches)) {
			$result['id'] = trim($matches[1]);
			$status = trim($matches[2]);

			// Capture the status code as message by default.
			$result['statusmessage'] = "CODE: $status";

			if($status == '002' || $status == '008' || $status == '011' ) {
				$result['status'] = self::MSG_STATUS_PROCESSING;
			} else if($status == '003' || $status == '004') {
				$result['status'] = self::MSG_STATUS_DISPATCHED;
				$result['needlookup'] = 0;
			} else {
				$statusMessage = '';
				switch($status) {
					case '001': $statusMessage = 'Message unknown';					break;
					case '005': $statusMessage = 'Error with message';				break;
					case '006': $statusMessage = 'User cancelled message delivery';	break;
					case '007': $statusMessage = 'Error delivering message';		break;
					case '009': $statusMessage = 'Routing error';					break;
					case '010': $statusMessage = 'Message expired';					break;
					case '012': $statusMessage = 'Out of credit';					break;
				}
				if(!empty($statusMessage)) {
					$result['error'] = true;
					$result['needlookup'] = 0;
					$result['statusmessage'] = $statusMessage;
				}
			}
		}
		return $result;
	}
}
?>

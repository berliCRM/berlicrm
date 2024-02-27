<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
class VTSimpleTemplate{

	function __construct($templateString){
		$this->template = $templateString;
	}

	function render($entityCache, $entityId){
		$this->cache = $entityCache;
		$this->parent = $this->cache->forId($entityId);
		return $this->parseTemplate();
	}

	private function matchHandler($match){
		preg_match('/\((\w+) : \(([_\w]+)\) (\w+)\)/', $match[1], $matches);
		if ($match[1] == 'logo') {
			return $this->getMetaValue($match[1]);
		}
        // If parent is empty then we can't do any thing here
        if(!empty($this->parent)) {
			if(count($matches)==0) {
				$fieldname = $match[1];

				//To handle comments for this module
				if (in_array($fieldname, array('lastComment', 'last5Comments', 'allComments'))) {
					return $this->getComments($this->parent->getModuleName(), $fieldname, $this->parent->getId());
				}

				$data = $this->parent->getData();

				if(isset($data[$fieldname]) || $fieldname === '_DATE_FORMAT_') {
					if($this->useValue($data, $fieldname)) {
						$result = $this->transformToUserFormat($this->parent->getModuleName(), $fieldname, $data[$fieldname]);
					} else {
						$result ='';
					}
				} else {
					$result ='$'.$fieldname;
				}
            } else {
				list($full, $referenceField, $referenceModule, $fieldname) = $matches;
				if($referenceModule === '__VtigerMeta__' || $fieldname === 'dbLabel') {
					$result = $this->getMetaValue($fieldname);
				} elseif ('__VtigerCompany__' == $referenceModule) { 
					$result = $this->getCompanySetting($fieldname); 
				} else {
					$referenceId = $this->parent->get($referenceField);
					if($referenceId==null) {
						$result="";
					} else {
						//To handle comments for this reference module
						if (in_array($fieldname, array('lastComment', 'last5Comments', 'allComments'))) {
							return $this->getComments($referenceModule, $fieldname, $referenceId);
						}

						if ($referenceField === 'contact_id') {
							$referenceIdsList = explode(',', $referenceId);
							$parts = array();
							foreach ($referenceIdsList as $referenceId) {
								$entity = $this->cache->forId($referenceId);
								$data = $entity->getData();
								if($this->useValue($data, $fieldname)) {
									$parts[] = $this->transformToUserFormat($referenceModule, $fieldname, $data[$fieldname]);
								}
							}
							return implode(',', $parts);
						}
						
						$entity = $this->cache->forId($referenceId);
						if($referenceModule==="Users" && $entity->getModuleName()=="Groups") {
							list($groupEntityId, $groupId) = vtws_getIdComponents($referenceId);

							require_once('include/utils/GetGroupUsers.php');
							$ggu = new GetGroupUsers();
							$ggu->getAllUsersInGroup($groupId);

							$users = $ggu->group_users;
							$parts = Array();
							foreach($users as $userId) {
								$refId = vtws_getWebserviceEntityId("Users", $userId);
								$entity = $this->cache->forId($refId);
								$data = $entity->getData();
								if($this->useValue($data, $fieldname)) {
									$parts[] = $this->transformToUserFormat($referenceModule, $fieldname, $data[$fieldname]);
								}
							}
							$result = implode(", ", $parts);

						} elseif($entity->getModuleName()===$referenceModule) {
							$data = $entity->getData();
							if($this->useValue($data, $fieldname)) {
								$handler = vtws_getModuleHandlerFromName($referenceModule, $this->cache->user);
								$meta = $handler->getMeta();
								$referenceFieldList = $meta->getReferenceFieldDetails();
								if(array_key_exists($fieldname,$referenceFieldList)) {
									$webserviceId = $data[$fieldname];
									$idComponents = vtws_getIdComponents($webserviceId);
									if($fieldname == 'currency_id' && $referenceModule == 'Users') {
										$result = decode_html(getCurrencyName($idComponents[1]));
									} else {
										$result = decode_html(Vtiger_Util_Helper::getLabel($idComponents[1]));
									}
								} else {
									$result = $this->transformToUserFormat($referenceModule, $fieldname, $data[$fieldname]);
								}
							} else {
								$result = '';
							}
						} else {
							$result = '';
						}
					}
				}
            }
        }
		return $result;

	}

	protected function useValue($data, $fieldname) {
        return true;
	}

	function parseTemplate(){
		return preg_replace_callback('/\\$(\w+|\((\w+) : \(([_\w]+)\) (\w+)\))/', array($this,"matchHandler"), $this->template);
	}
        
        function getCompanySetting($fieldname) { 
            return Settings_Vtiger_CompanyDetails_Model::getSetting($fieldname); 
        } 

	function getMetaValue($fieldname){
		require_once 'config.inc.php';
		// used as token
		global $application_unique_key;
		global $site_URL, $PORTAL_URL, $current_user, $HELPDESK_SUPPORT_NAME, $HELPDESK_SUPPORT_EMAIL_ID;
		$ownerObject = NULL;
		$referenceId = $this->parent->get('assigned_user_id');
		if ($referenceId) {
			$entity = $this->cache->forId($referenceId);
			list($entityId, $ownerId) = vtws_getIdComponents($referenceId);
			if($entity->getModuleName() === 'Groups') {
				list($moduleId, $recordId) = vtws_getIdComponents($this->parent->getId());
				$ownerId = Vtiger_Util_Helper::getCreator($recordId);
			}
			$ownerObject = new Users();
			$ownerObject->retrieveCurrentUserInfoFromFile($ownerId);
		}
		$date = new DateTimeField(null);
		switch($fieldname){
			case 'date'					:	return $date->getDisplayDate($ownerObject);
			case 'time'					:	return $date->getDisplayTime($ownerObject);
			case 'dbtimezone'			:	return DateTimeField::getDBTimeZone();
			case 'usertimezone'			:	if ($ownerId) {
												$ownerFocus = CRMEntity::getInstance('Users');
												$ownerFocus->retrieve_entity_info($ownerId, 'Users');
												return getTranslatedString($ownerFocus->column_fields['time_zone'], 'Users');
											}
											return '';
			case 'crmdetailviewurl'		:	$wsId = $this->parent->getId();
											$parts = explode('x', $wsId);
											$recordId = $parts[1];
											$moduleName = $this->parent->getModuleName();
											return "<a href='$site_URL/index.php?module=$moduleName&view=Detail&record=$recordId'>$moduleName</a>";
			case 'portaldetailviewurl'	: 	$wsId = $this->parent->getId();
											$parts = explode('x', $wsId);
											$recordId = $parts[1];
											$moduleName = $this->parent->getModuleName();
											$recorIdName='id';
											if($moduleName == 'HelpDesk') $recorIdName = 'ticketid';
											if($moduleName == 'Faq') $recorIdName = 'faqid';
											if($moduleName == 'Products') $recorIdName = 'productid';
											return "<a href='" . $PORTAL_URL.'/index.php?module='.$moduleName.'&action=index&'.$recorIdName.'='.$recordId.'&status=true'."'>Portal</a>";
            case 'portalpdfurl'         :   $wsId = $this->parent->getId();
											$parts = explode('x', $wsId);
											$recordId = $parts[1];
											$moduleName = $this->parent->getModuleName();
											$recorIdName='id';
											return "<a href='" . $PORTAL_URL.'/index.php?module='.$moduleName.'&action=index&'.$recorIdName.'='.$recordId.'&downloadfile=true'."'>Download</a>";
			case 'siteurl'				:	return "<a href='$site_URL'>$site_URL</a>";
			case 'portalurl'			:	return "<a href='$PORTAL_URL'>$PORTAL_URL</a>";
			case 'logo'					:	return '<img src="cid:logo" />';

			case 'recordId'				: list($moduleId, $recordId) = vtws_getIdComponents($this->parent->getId());
										  return $recordId;

			case 'supportName'			: return $HELPDESK_SUPPORT_NAME;
			case 'supportEmailId'		: return $HELPDESK_SUPPORT_EMAIL_ID;
			case 'mailtoken'			:	// use Json Token (JWT)
											$wsId = $this->parent->getId();
											$parts = explode('x', $wsId);
											$recordId = $parts[1];
																			
											$issuedAt = time();
											// token valid for 7 days (60 seconds * 60 minutes * 24 hours * 7 days)
											$expirationTime = $issuedAt + 60 * 60 * 24 * 7;

											$payload = array(
												'iat' => $issuedAt,
												'exp' => $expirationTime,
												'recordid' => $recordId,
											);
											// secret key per customer
											// use HS512 
											$key = $application_unique_key;
											$alg = 'HS512';

											// Encode
											$token = JWT::encode($payload, $key);
											
											// we add 'x' to make sure that the URL gets displayed correctly in Outlook
											return $token.'x';
			default: '';
		}
	}

	/**
	 * Function to transform the field values into user format
	 * @param <String> $moduleName
	 * @param <String> $fieldName
	 * @param <String> $fieldValue
	 * @return <String> $fieldValue
	 */
	public function transformToUserFormat($moduleName, $fieldName, $fieldValue) {
		global $adb, $log, $current_user;

		//getting owner info
		$referenceId = $this->parent->get('assigned_user_id');
		if ($referenceId) {
			$entity = $this->cache->forId($referenceId);
			list($entityId, $ownerId) = vtws_getIdComponents($referenceId);

			if($entity->getModuleName() === 'Groups') {
				list($moduleId, $recordId) = vtws_getIdComponents($this->parent->getId());
				$ownerId = Vtiger_Util_Helper::getCreator($recordId);
			}
		}
		if(!empty($ownerId)) {
			$ownerObject = new Users();
			$ownerObject->retrieveCurrentUserInfoFromFile($ownerId);
		}
		
		if ($ownerObject && $fieldName === '_DATE_FORMAT_') {
			return $ownerObject->column_fields['date_format'];
		}

		//getting field instance info
		$entityObject = VtigerWebserviceObject::fromName($adb, $moduleName);
		$handlerPath = $entityObject->getHandlerPath();
		$handlerClass = $entityObject->getHandlerClass();

		require_once $handlerPath;

		$entityHandler = new $handlerClass($entityObject, $current_user, $adb, $log);
		$entityMeta = $entityHandler->getMeta();
		$entityFields = $entityMeta->getModuleFields();
		//crm-now: prevent unknown fields to cause a crash and enable $id for links
		if ($fieldName == 'id') {
			$id = explode('x', $fieldValue);
			return $id[1];
		} elseif (!isset($entityFields[$fieldName])) {
			return $fieldName;
		}
		$fieldInstance = $entityFields[$fieldName];

		switch($fieldInstance->getFieldDataType()) {

			case 'date'			:	if (($moduleName === 'Events' && in_array($fieldName, array('date_start', 'due_date'))) ||
										($moduleName === 'Calendar' && $fieldName === 'date_start')) {
										if ($fieldName === 'date_start') {
											$dateTime = $this->parent->get('date_start') .' '. $this->parent->get('time_start');
										} else {
											$dateTime = $this->parent->get('due_date') .' '. $this->parent->get('time_end');
										}

										$fieldValue = Vtiger_Util_Helper::convertDateTimeIntoUsersDisplayFormat($dateTime, $ownerObject);

									} else {
										$dateFieldObj = new DateTimeField($fieldValue);
										$fieldValue = $dateFieldObj->getDisplayDate($ownerObject);
									}
									break;

			case 'datetime'		:	$fieldValue = Vtiger_Util_Helper::convertDateTimeIntoUsersDisplayFormat($fieldValue, $ownerObject);
									break;

			case 'currency'		:	if ($fieldInstance->getUIType() === '72') {
										$fieldValue = CurrencyField::convertToUserFormat($fieldValue, $ownerObject, true);
									} else if($fieldInstance->getUIType() == '71') {
										$fieldValue = CurrencyField::convertToUserFormat($fieldValue, $ownerObject);
									}
									break;

			case 'time'			:	$fieldValue = Vtiger_Util_Helper::convertTimeIntoUsersDisplayFormat($fieldValue, $ownerObject);
									break;

            case 'picklist'     :   require_once 'includes/runtime/LanguageHandler.php';
                                    require_once 'includes/runtime/Globals.php';
                                    $fieldValue = vtranslate($fieldValue,$moduleName,$ownerObject->column_fields['language']);
                                    break;

            case 'multipicklist' :  require_once 'includes/runtime/LanguageHandler.php';
                                    require_once 'includes/runtime/Globals.php';
                                    $fieldValueParts = explode(';',$fieldValue);
                                    foreach($fieldValueParts as $index=>$fieldValue) {
                                        $fieldValueParts[$index] = vtranslate($fieldValue,$moduleName,$ownerObject->column_fields['language']);
                                    }
                                    $fieldValue = implode(';', $fieldValueParts);
                                    break;
            case 'boolean'       :  require_once 'includes/runtime/LanguageHandler.php';
                                    require_once 'includes/runtime/Globals.php';
                                    if($fieldValue == 1){
                                        $fieldValue = vtranslate('LBL_YES',$moduleName);
                                    }else {
                                        $fieldValue = vtranslate('LBL_NO',$moduleName);
                                    }
                                    break;
            case 'reference'     :  if($fieldName == 'currency_id') {
                                        $currencyId = explode('x', $fieldValue);
                                        $currencyId = $currencyId[1];
                                        $fieldValue = decode_html(getCurrencyName($currencyId));
                                    }else{
                                        if($fieldValue != 0 && $fieldValue != null){
                                            $id = explode('x', $fieldValue);
                                            $webServiceObject = VtigerWebserviceObject::fromId($adb,$id[0]);
                                            if($webServiceObject){
                                                $Value = getEntityName($webServiceObject->getEntityName(), $id);
                                                $fieldValue = html_entity_decode($Value[$id[1]]);
                                            }
                                        }
                                    }
									break;
			case 'double'		:   if ($moduleName === 'HelpDesk' && in_array($fieldName, array('days', 'hours'))) {
										$fieldValue = decimalFormat($fieldValue);
									} 
									else {
										$fieldValue = NumberField::convertToUserFormat($fieldValue);
									}
									break;
			default				:	$fieldValue;

		}
		return nl2br(htmlentities($fieldValue));
	}

	/**
	 * Function to fieldvalues of Comments
	 * @param <String> $moduleName
	 * @param <String> $fieldName
	 * @param <String> $fieldValue
	 * @return <String> $comments
	 */
	public function getComments($moduleName, $fieldName, $fieldValue) {
		global $adb, $log, $current_user;

		$sql = 'SELECT commentcontent FROM vtiger_modcomments WHERE related_to = ? ORDER BY modcommentsid DESC';

		switch ($fieldName) {
			case 'lastComment'		: $sql .= ' LIMIT 1';	break;
			case 'last5Comments'	: $sql .= ' LIMIT 5';	break;
			default					: $sql;					break;
		}
		list($entityId, $recordId) = vtws_getIdComponents($fieldValue);

		$result = $adb->pquery($sql, array($recordId));
		$numOfRows = $adb->num_rows($result);

		$commentsList = '';
		for ($i=0; $i<$numOfRows; $i++) {
			$comment = $adb->query_result($result, $i, 'commentcontent');
			if ($comment != '') {
				$commentsList .= '<br><br>' . nl2br($comment);
			}
		}

		return $commentsList;
	}
}

// Json Web Token (JWT) Class
class JWT {
    const ASN1_INTEGER = 0x02;
    const ASN1_SEQUENCE = 0x10;
    const ASN1_BIT_STRING = 0x03;

    /**
     * When checking nbf, iat or expiration times,
     * we want to provide some extra leeway time to
     * account for clock skew.
     */
    public static $leeway = 0;

    /**
     * Allow the current timestamp to be specified.
     * Useful for fixing a value within unit testing.
     *
     * Will default to PHP time() value if null.
     */
    public static $timestamp = null;

    public static $supported_algs = array(
        'ES256' => array('openssl', 'SHA256'),
        'HS256' => array('hash_hmac', 'SHA256'),
        'HS384' => array('hash_hmac', 'SHA384'),
        'HS512' => array('hash_hmac', 'SHA512'),
        'RS256' => array('openssl', 'SHA256'),
        'RS384' => array('openssl', 'SHA384'),
        'RS512' => array('openssl', 'SHA512'),
    );

    /**
     * Decodes a JWT string into a PHP object.
     *
     * @param string                    $jwt            The JWT
     * @param string|array|resource     $key            The key, or map of keys.
     *                                                  If the algorithm used is asymmetric, this is the public key
     * @param array                     $allowed_algs   List of supported verification algorithms
     *                                                  Supported algorithms are 'ES256', 'HS256', 'HS384', 'HS512', 'RS256', 'RS384', and 'RS512'
     *
     * @return object The JWT's payload as a PHP object
     *
     * @throws UnexpectedValueException     Provided JWT was invalid
     * @throws SignatureInvalidException    Provided JWT was invalid because the signature verification failed
     * @throws BeforeValidException         Provided JWT is trying to be used before it's eligible as defined by 'nbf'
     * @throws BeforeValidException         Provided JWT is trying to be used before it's been created as defined by 'iat'
     * @throws ExpiredException             Provided JWT has since expired, as defined by the 'exp' claim
     *
     * @uses jsonDecode
     * @uses urlsafeB64Decode
     */
    public static function decode($jwt, $key, array $allowed_algs = array()) {
        $timestamp = \is_null(static::$timestamp) ? \time() : static::$timestamp;

        if (empty($key)) {
            throw new InvalidArgumentException('Key may not be empty');
        }
        $tks = \explode('.', $jwt);
        if (\count($tks) != 3) {
            throw new UnexpectedValueException('Wrong number of segments');
        }
        list($headb64, $bodyb64, $cryptob64) = $tks;
        if (null === ($header = static::jsonDecode(static::urlsafeB64Decode($headb64)))) {
            throw new UnexpectedValueException('Invalid header encoding');
        }
        if (null === $payload = static::jsonDecode(static::urlsafeB64Decode($bodyb64))) {
            throw new UnexpectedValueException('Invalid claims encoding');
        }
        if (false === ($sig = static::urlsafeB64Decode($cryptob64))) {
            throw new UnexpectedValueException('Invalid signature encoding');
        }
        if (empty($header->alg)) {
            throw new UnexpectedValueException('Empty algorithm');
        }
        if (empty(static::$supported_algs[$header->alg])) {
            throw new UnexpectedValueException('Algorithm not supported');
        }
        if (!\in_array($header->alg, $allowed_algs)) {
            throw new UnexpectedValueException('Algorithm not allowed');
        }
        if ($header->alg === 'ES256') {
            // OpenSSL expects an ASN.1 DER sequence for ES256 signatures
            $sig = self::signatureToDER($sig);
        }

        if (\is_array($key) || $key instanceof \ArrayAccess) {
            if (isset($header->kid)) {
                if (!isset($key[$header->kid])) {
                    throw new UnexpectedValueException('"kid" invalid, unable to lookup correct key');
                }
                $key = $key[$header->kid];
            } 
			else {
                throw new UnexpectedValueException('"kid" empty, unable to lookup correct key');
            }
        }

        // Check the signature
        if (!static::verify("$headb64.$bodyb64", $sig, $key, $header->alg)) {
            throw new SignatureInvalidException('Signature verification failed');
        }

        // Check the nbf if it is defined. This is the time that the
        // token can actually be used. If it's not yet that time, abort.
        if (isset($payload->nbf) && $payload->nbf > ($timestamp + static::$leeway)) {
            throw new BeforeValidException(
                'Cannot handle token prior to ' . \date(DateTime::ISO8601, $payload->nbf)
            );
        }

        // Check that this token has been created before 'now'. This prevents
        // using tokens that have been created for later use (and haven't
        // correctly used the nbf claim).
        if (isset($payload->iat) && $payload->iat > ($timestamp + static::$leeway)) {
            throw new BeforeValidException(
                'Cannot handle token prior to ' . \date(DateTime::ISO8601, $payload->iat)
            );
        }

        // Check if this token has expired.
        if (isset($payload->exp) && ($timestamp - static::$leeway) >= $payload->exp) {
            throw new ExpiredException('Expired token');
        }

        return $payload;
    }

    /**
     * Converts and signs a PHP object or array into a JWT string.
     *
     * @param object|array  $payload    PHP object or array
     * @param string        $key        The secret key.
     *                                  If the algorithm used is asymmetric, this is the private key
     * @param string        $alg        The signing algorithm.
     *                                  Supported algorithms are 'ES256', 'HS256', 'HS384', 'HS512', 'RS256', 'RS384', and 'RS512'
     * @param mixed         $keyId
     * @param array         $head       An array with header elements to attach
     *
     * @return string A signed JWT
     *
     * @uses jsonEncode
     * @uses urlsafeB64Encode
     */
    public static function encode($payload, $key, $alg = 'HS512', $keyId = null, $head = null) {
        $header = array('typ' => 'JWT', 'alg' => $alg);
        if ($keyId !== null) {
            $header['kid'] = $keyId;
        }
        if (isset($head) && \is_array($head)) {
            $header = \array_merge($head, $header);
        }
        $segments = array();
        $segments[] = static::urlsafeB64Encode(static::jsonEncode($header));
        $segments[] = static::urlsafeB64Encode(static::jsonEncode($payload));
        $signing_input = \implode('.', $segments);

        $signature = static::sign($signing_input, $key, $alg);
        $segments[] = static::urlsafeB64Encode($signature);

        return \implode('.', $segments);
    }

    /**
     * Sign a string with a given key and algorithm.
     *
     * @param string            $msg    The message to sign
     * @param string|resource   $key    The secret key
     * @param string            $alg    The signing algorithm.
     *                                  Supported algorithms are 'ES256', 'HS256', 'HS384', 'HS512', 'RS256', 'RS384', and 'RS512'
     *
     * @return string An encrypted message
     *
     * @throws DomainException Unsupported algorithm was specified
     */
    public static function sign($msg, $key, $alg = 'HS512') {
        if (empty(static::$supported_algs[$alg])) {
            throw new DomainException('Algorithm not supported');
        }
        list($function, $algorithm) = static::$supported_algs[$alg];
        switch ($function) {
            case 'hash_hmac':
                return \hash_hmac($algorithm, $msg, $key, true);
            case 'openssl':
                $signature = '';
                $success = \openssl_sign($msg, $signature, $key, $algorithm);
                if (!$success) {
                    throw new DomainException("OpenSSL unable to sign data");
                } 
				else {
                    if ($alg === 'ES256') {
                        $signature = self::signatureFromDER($signature, 256);
                    }
                    return $signature;
                }
        }
    }

    /**
     * Verify a signature with the message, key and method. Not all methods
     * are symmetric, so we must have a separate verify and sign method.
     *
     * @param string            $msg        The original message (header and body)
     * @param string            $signature  The original signature
     * @param string|resource   $key        For HS*, a string key works. for RS*, must be a resource of an openssl public key
     * @param string            $alg        The algorithm
     *
     * @return bool
     *
     * @throws DomainException Invalid Algorithm or OpenSSL failure
     */
    private static function verify($msg, $signature, $key, $alg) {
        if (empty(static::$supported_algs[$alg])) {
            throw new DomainException('Algorithm not supported');
        }

        list($function, $algorithm) = static::$supported_algs[$alg];
        switch ($function) {
            case 'openssl':
                $success = \openssl_verify($msg, $signature, $key, $algorithm);
                if ($success === 1) {
                    return true;
                } 
				elseif ($success === 0) {
                    return false;
                }
                // returns 1 on success, 0 on failure, -1 on error.
                throw new DomainException(
                    'OpenSSL error: ' . \openssl_error_string()
                );
            case 'hash_hmac':
            default:
                $hash = \hash_hmac($algorithm, $msg, $key, true);
                if (\function_exists('hash_equals')) {
                    return \hash_equals($signature, $hash);
                }
                $len = \min(static::safeStrlen($signature), static::safeStrlen($hash));

                $status = 0;
                for ($i = 0; $i < $len; $i++) {
                    $status |= (\ord($signature[$i]) ^ \ord($hash[$i]));
                }
                $status |= (static::safeStrlen($signature) ^ static::safeStrlen($hash));

                return ($status === 0);
        }
    }

    /**
     * Decode a JSON string into a PHP object.
     *
     * @param string $input JSON string
     *
     * @return object Object representation of JSON string
     *
     * @throws DomainException Provided string was invalid JSON
     */
    public static function jsonDecode($input){
        if (\version_compare(PHP_VERSION, '5.4.0', '>=') && !(\defined('JSON_C_VERSION') && PHP_INT_SIZE > 4)) {
            /** In PHP >=5.4.0, json_decode() accepts an options parameter, that allows you
             * to specify that large ints (like Steam Transaction IDs) should be treated as
             * strings, rather than the PHP default behaviour of converting them to floats.
             */
            $obj = \json_decode($input, false, 512, JSON_BIGINT_AS_STRING);
        } 
		else {
            /** Not all servers will support that, however, so for older versions we must
             * manually detect large ints in the JSON string and quote them (thus converting
             *them to strings) before decoding, hence the preg_replace() call.
             */
            $max_int_length = \strlen((string) PHP_INT_MAX) - 1;
            $json_without_bigints = \preg_replace('/:\s*(-?\d{'.$max_int_length.',})/', ': "$1"', $input);
            $obj = \json_decode($json_without_bigints);
        }

        if ($errno = \json_last_error()) {
            static::handleJsonError($errno);
        } 
		elseif ($obj === null && $input !== 'null') {
            throw new DomainException('Null result with non-null input');
        }
        return $obj;
    }

    /**
     * Encode a PHP object into a JSON string.
     *
     * @param object|array $input A PHP object or array
     *
     * @return string JSON representation of the PHP object or array
     *
     * @throws DomainException Provided object could not be encoded to valid JSON
     */
    public static function jsonEncode($input) {
        $json = \json_encode($input);
        if ($errno = \json_last_error()) {
            static::handleJsonError($errno);
        } elseif ($json === 'null' && $input !== null) {
            throw new DomainException('Null result with non-null input');
        }
        return $json;
    }

    /**
     * Decode a string with URL-safe Base64.
     *
     * @param string $input A Base64 encoded string
     *
     * @return string A decoded string
     */
    public static function urlsafeB64Decode($input) {
        $remainder = \strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= \str_repeat('=', $padlen);
        }
        return \base64_decode(\strtr($input, '-_', '+/'));
    }

    /**
     * Encode a string with URL-safe Base64.
     *
     * @param string $input The string you want encoded
     *
     * @return string The base64 encode of what you passed in
     */
    public static function urlsafeB64Encode($input) {
        return \str_replace('=', '', \strtr(\base64_encode($input), '+/', '-_'));
    }

    /**
     * Helper method to create a JSON error.
     *
     * @param int $errno An error number from json_last_error()
     *
     * @return void
     */
    private static function handleJsonError($errno) {
        $messages = array(
            JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
            JSON_ERROR_STATE_MISMATCH => 'Invalid or malformed JSON',
            JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
            JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
            JSON_ERROR_UTF8 => 'Malformed UTF-8 characters' //PHP >= 5.3.3
        );
        throw new DomainException(
            isset($messages[$errno])
            ? $messages[$errno]
            : 'Unknown JSON error: ' . $errno
        );
    }

    /**
     * Get the number of bytes in cryptographic strings.
     *
     * @param string $str
     *
     * @return int
     */
    private static function safeStrlen($str) {
        if (\function_exists('mb_strlen')) {
            return \mb_strlen($str, '8bit');
        }
        return \strlen($str);
    }

    /**
     * Convert an ECDSA signature to an ASN.1 DER sequence
     *
     * @param   string $sig The ECDSA signature to convert
     * @return  string The encoded DER object
     */
    private static function signatureToDER($sig) {
        // Separate the signature into r-value and s-value
        list($r, $s) = \str_split($sig, (int) (\strlen($sig) / 2));

        // Trim leading zeros
        $r = \ltrim($r, "\x00");
        $s = \ltrim($s, "\x00");

        // Convert r-value and s-value from unsigned big-endian integers to
        // signed two's complement
        if (\ord($r[0]) > 0x7f) {
            $r = "\x00" . $r;
        }
        if (\ord($s[0]) > 0x7f) {
            $s = "\x00" . $s;
        }

        return self::encodeDER(
            self::ASN1_SEQUENCE,
            self::encodeDER(self::ASN1_INTEGER, $r) .
            self::encodeDER(self::ASN1_INTEGER, $s)
        );
    }

    /**
     * Encodes a value into a DER object.
     *
     * @param   int     $type DER tag
     * @param   string  $value the value to encode
     * @return  string  the encoded object
     */
    private static function encodeDER($type, $value) {
        $tag_header = 0;
        if ($type === self::ASN1_SEQUENCE) {
            $tag_header |= 0x20;
        }

        // Type
        $der = \chr($tag_header | $type);

        // Length
        $der .= \chr(\strlen($value));

        return $der . $value;
    }

    /**
     * Encodes signature from a DER object.
     *
     * @param   string  $der binary signature in DER format
     * @param   int     $keySize the number of bits in the key
     * @return  string  the signature
     */
    private static function signatureFromDER($der, $keySize) {
        // OpenSSL returns the ECDSA signatures as a binary ASN.1 DER SEQUENCE
        list($offset, $_) = self::readDER($der);
        list($offset, $r) = self::readDER($der, $offset);
        list($offset, $s) = self::readDER($der, $offset);

        // Convert r-value and s-value from signed two's compliment to unsigned
        // big-endian integers
        $r = \ltrim($r, "\x00");
        $s = \ltrim($s, "\x00");

        // Pad out r and s so that they are $keySize bits long
        $r = \str_pad($r, $keySize / 8, "\x00", STR_PAD_LEFT);
        $s = \str_pad($s, $keySize / 8, "\x00", STR_PAD_LEFT);

        return $r . $s;
    }

    /**
     * Reads binary DER-encoded data and decodes into a single object
     *
     * @param string $der the binary data in DER format
     * @param int $offset the offset of the data stream containing the object
     * to decode
     * @return array [$offset, $data] the new offset and the decoded object
     */
    private static function readDER($der, $offset = 0)     {
        $pos = $offset;
        $size = \strlen($der);
        $constructed = (\ord($der[$pos]) >> 5) & 0x01;
        $type = \ord($der[$pos++]) & 0x1f;

        // Length
        $len = \ord($der[$pos++]);
        if ($len & 0x80) {
            $n = $len & 0x1f;
            $len = 0;
            while ($n-- && $pos < $size) {
                $len = ($len << 8) | \ord($der[$pos++]);
            }
        }

        // Value
        if ($type == self::ASN1_BIT_STRING) {
            $pos++; // Skip the first contents octet (padding indicator)
            $data = \substr($der, $pos, $len - 1);
            $pos += $len - 1;
        } 
		elseif (!$constructed) {
            $data = \substr($der, $pos, $len);
            $pos += $len;
        } 
		else {
            $data = null;
        }

        return array($pos, $data);
    }
}
 
?>

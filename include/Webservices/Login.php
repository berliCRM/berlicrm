<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
	
	function vtws_login($username,$pwd){
		
		$user = new Users();
		$userId = $user->retrieve_user_id($username);
		
		$token = vtws_getActiveToken($userId);
		if($token == null){
			throw new WebServiceException(WebServiceErrorCode::$INVALIDTOKEN,"Specified token is invalid or expired");
		}
		
		$accessKey = vtws_getUserAccessKey($userId);
		if($accessKey == null){
			throw new WebServiceException(WebServiceErrorCode::$ACCESSKEYUNDEFINED,"Access key for the user is undefined");
		}
		
		$accessCrypt = md5($token.$accessKey);
		if(strcmp($accessCrypt,$pwd)!==0){
			crmnow_login_protection($username, 5);
			throw new WebServiceException(WebServiceErrorCode::$INVALIDUSERPWD,"Invalid username or password");
		}
		$user = $user->retrieve_entity_info($userId, 'Users');
		if($user->status != 'Inactive'){
			global $adb;
			$adb->pquery("DELETE FROM berli_failed_logins WHERE user_name = ?;", array($username));

            // get remote IP, possibly proxied, and log login
            $ip="0.0.0.0";
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && filter_var($_SERVER['HTTP_X_FORWARDED_FOR'],FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)) {
                $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
            }
            elseif (filter_var($_SERVER['REMOTE_ADDR'],FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
            $loginTime = date("Y-m-d H:i:s");
            $q = "INSERT INTO vtiger_loginhistory (user_name, user_ip, logout_time, login_time, status) VALUES (?,?,?,?,?)";
            $params = array($username, $ip, '0000-00-00 00:00:00',  $loginTime, 'WebService Login');
            $adb->pquery($q, $params);

			return $user;
		}
		throw new WebServiceException(WebServiceErrorCode::$AUTHREQUIRED,'Given user is inactive');
	}
	
	function vtws_getActiveToken($userId){
		global $adb;
		
		$sql = "select * from vtiger_ws_userauthtoken where userid=? and expiretime >= ?";
		$result = $adb->pquery($sql,array($userId,time()));
		if($result != null && isset($result)){
			if($adb->num_rows($result)>0){
				return $adb->query_result($result,0,"token");
			}
		}
		return null;
	}
	
	function vtws_getUserAccessKey($userId){
		global $adb;
		
		$sql = "select * from vtiger_users where id=?";
		$result = $adb->pquery($sql,array($userId));
		if($result != null && isset($result)){
			if($adb->num_rows($result)>0){
				return $adb->query_result($result,0,"accesskey");
			}
		}
		return null;
	}
	
?>

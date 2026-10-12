<?php
/*********************************************************************************
 ** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *
 ********************************************************************************/
require_once('session_security_manager.php');
SessionSecurityManager::init();
SessionSecurityManager::requireValidPostRequest($_POST['__csrf_token'] ?? '');

include("include.php");
include("version.php");
require_once("PortalConfig.php");
require_once("include/utils/utils.php");

global $version,$default_language,$result;
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['pw'] ?? '');

setPortalCurrentLanguage();
$default_language = getPortalCurrentLanguage();
checkFileAccess("language/".$default_language.".lang.php");
require_once("language/".$default_language.".lang.php");

$params = array('user_name' => "$username",
	'user_password'=>"$password",
	'version' => "$version");


$result = $client->call('authenticate_user', $params, $Server_Path, $Server_Path);
//The following are the debug informations
$err = $client->getError();
if ($err)
{
	//Uncomment the following lines to get the error message in login screen itself.
	/*
	echo '<h2>Error Message</h2><pre>' . $err . '</pre>';
	echo '<h2>request</h2><pre>' . htmlspecialchars($client->request, ENT_QUOTES) . '</pre>';
	echo '<h2>response</h2><pre>' . htmlspecialchars($client->response, ENT_QUOTES) . '</pre>';
	echo '<h2>debug</h2><pre>' . htmlspecialchars($client->debug_str, ENT_QUOTES) . '</pre>';
	exit;
	*/
	header("Location: login.php?login_error=" . base64_encode("LBL_CANNOT_CONNECT_SERVER"));
	exit;
}

if(
	is_array($result)
	&& isset($result[0])
	&& is_array($result[0])
	&& isset($result[0]['user_name'], $result[0]['user_password'])
	&& strtolower($result[0]['user_name']) == strtolower($username)
	&& strtolower($result[0]['user_password']) == strtolower($password)
)
{
	$_SESSION['customer_id'] = $result[0]['id'];
	$_SESSION['customer_sessionid'] = $result[0]['sessionid'];
	$_SESSION['customer_name'] = $result[0]['user_name'];
	SessionSecurityManager::onLogin($_SESSION['customer_id']);
	$_SESSION['last_login'] = $result[0]['last_login_time'];
	$_SESSION['support_start_date'] = $result[0]['support_start_date'];
	$_SESSION['support_end_date'] = $result[0]['support_end_date'];
	$customerid = $_SESSION['customer_id'];
	$sessionid = $_SESSION['customer_sessionid'];

	$params1 = Array(Array('id' => "$customerid", 'sessionid'=>"$sessionid", 'flag'=>"login"));

	$result2 = $client->call('update_login_details', $params1, $Server_Path, $Server_Path);

	$params = array('customerid'=>$customerid);
	$permission = $client->call('get_modules',$params,$Server_Path,$Server_Path);
	
	if(!is_array($permission) || empty($permission))
	{
		echo getTranslatedString('LBL_NO_PERMISSION_FOR_ANY_MODULE');
		exit;
	}
	$module = $permission[0];
	
	// Store the permitted modules in session for re-use
	$_SESSION['__permitted_modules'] = $permission;
	
	header("Location: index.php?action=index&module=$module");
	exit;
}
else
{
	$resultCode = '';
	if (is_array($result) && isset($result[0]) && is_string($result[0])) {
		$resultCode = $result[0];
	} 
	elseif (is_string($result)) {
		$resultCode = $result;
	}

	if($resultCode == 'NOT COMPATIBLE'){
		$error_msg = "LBL_VERSION_INCOMPATIBLE";
	}
		elseif($resultCode == 'INVALID_USERNAME_OR_PASSWORD') {
		$error_msg = "LBL_ENTER_VALID_USER";	
	}
		elseif($resultCode == 'MORE_THAN_ONE_USER'){
		$error_msg = "MORE_THAN_ONE_USER";
	}
	else
		$error_msg = "LBL_CANNOT_CONNECT_SERVER";

	header("Location: login.php?login_error=" . base64_encode($error_msg));
	exit;
}

?>

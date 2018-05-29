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
include("include.php");
include("version.php");
require_once("PortalConfig.php");
require_once("include/utils/utils.php");

global $version,$default_language,$result;
$username = trim($_REQUEST['username']);
$password = trim($_REQUEST['pw']);

session_start();
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
	$login_error_msg = getTranslatedString("LBL_CANNOT_CONNECT_SERVER");
	$login_error_msg = base64_encode('<font color=red size=1px;> '.$login_error_msg.' </font>');
	header("Location: login.php?login_error=$login_error_msg");
	exit;
}

if(strtolower($result[0]['user_name']) == strtolower($username) && strtolower($result[0]['user_password']) == strtolower($password))
{
	session_start();
	$_SESSION['customer_id'] = $result[0]['id'];
	$_SESSION['customer_sessionid'] = $result[0]['sessionid'];
	$_SESSION['customer_name'] = $result[0]['user_name'];
	$_SESSION['last_login'] = $result[0]['last_login_time'];
	$_SESSION['support_start_date'] = $result[0]['support_start_date'];
	$_SESSION['support_end_date'] = $result[0]['support_end_date'];
	$customerid = $_SESSION['customer_id'];
	$sessionid = $_SESSION['customer_sessionid'];

	$params1 = Array(Array('id' => "$customerid", 'sessionid'=>"$sessionid", 'flag'=>"login"));

	$result2 = $client->call('update_login_details', $params1, $Server_Path, $Server_Path);

	$params = array('customerid'=>$customerid);
	$permission = $client->call('get_modules',$params,$Server_path,$Server_path);
	$module = $permission[0];		
	
	if($permission == '')
	{
		echo getTranslatedString('LBL_NO_PERMISSION_FOR_ANY_MODULE');
		exit;
	}
	
	// Store the permitted modules in session for re-use
	$_SESSION['__permitted_modules'] = $permission;
	
	header("Location: index.php?action=index&module=$module");
}
else
{
	if($result[0] == 'NOT COMPATIBLE'){
		$error_msg = getTranslatedString("LBL_VERSION_INCOMPATIBLE");
	}elseif($result[0] == 'INVALID_USERNAME_OR_PASSWORD') {
		$error_msg = getTranslatedString("LBL_ENTER_VALID_USER");	
	}elseif($result[0] == 'MORE_THAN_ONE_USER'){
		$error_msg = getTranslatedString("MORE_THAN_ONE_USER");
	}
	else
		$error_msg = getTranslatedString("LBL_CANNOT_CONNECT_SERVER");

	$login_error_msg = base64_encode('<font color=red size=1px;> '.$error_msg.' </font>');
	header("Location: login.php?login_error=$login_error_msg");
}

?>

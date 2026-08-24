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
include_once('include/utils/utils.php');

function portalDownloadFilename($value)
{
	$filename = basename(str_replace('\\', '/', (string) $value));
	$filename = preg_replace('/[\x00-\x1F\x7F]/', '', $filename);
	if (!is_string($filename) || $filename === '') {
		return 'download';
	}

	return substr($filename, 0, 200);
}

function portalDownloadContentType($value)
{
	$contentType = trim((string) $value);
	if (preg_match('#^[a-z0-9][a-z0-9!#$&^_.+-]*/[a-z0-9][a-z0-9!#$&^_.+-]*$#i', $contentType)) {
		return $contentType;
	}

	return 'application/octet-stream';
}

include("include.php");
include("version.php");
$is_logged = 0;
$isAjax = false;

if($_REQUEST){
if(($_REQUEST['param'] ?? '') == 'forgot_password')
{
	SessionSecurityManager::requireValidPostRequest($_POST['__csrf_token'] ?? '');
	global $client;

	$email = $_POST['email_id'] ?? '';
	$params = array('email' => "$email");
	$result = $client->call('send_mail_for_password', $params);
         $_REQUEST['mail_send_message'] = $result;
	require_once("supportpage.php");
        }

else {
        include_once 'csrf-protect.php';
}

}

if(($_REQUEST['logout'] ?? '') == 'true')
{
	$customerid = $_SESSION['customer_id'] ?? '';
	$sessionid = $_SESSION['customer_sessionid'] ?? '';

	if ($customerid !== '' && $sessionid !== '') {
		$params = Array(Array('id' => "$customerid", 'sessionid'=>"$sessionid", 'flag'=>"logout"));
		$result = $client->call('update_login_details', $params);
	}

	SessionSecurityManager::destroy();
	include("login.php");
}
else
{
	$module = '';
	$action = 'login.php';
	$isAjax = (($_REQUEST['ajax'] ?? '') == 'true');
	
	if(!empty($_SESSION['customer_id']))
	{
		$customerid = $_SESSION['customer_id'];
		$sessionid = $_SESSION['customer_sessionid'] ?? '';

		// Set customer account id
		if(isset($_SESSION['customer_account_id'])) {
			$account_id = $_SESSION['customer_account_id']; 
		} else {		
			$params = Array('id'=>$customerid);
			$account_id = $client->call('get_check_account_id', $params, $Server_Path, $Server_Path);
			$_SESSION['customer_account_id'] = $account_id;
		}
		// End
		$is_logged = 1;

		//Added to download attachments
		if($_REQUEST['downloadfile'] == 'true' && requestValidateReadAccess())
		{
			$filename = isset($_REQUEST['filename']) ? (string) $_REQUEST['filename'] : '';
			$fileType = isset($_REQUEST['filetype']) ? (string) $_REQUEST['filetype'] : '';
			//$fileid = $_REQUEST['fileid'];
			$filesize = isset($_REQUEST['filesize']) ? (int) $_REQUEST['filesize'] : 0;

			//Added for enhancement from Rosa Weber

			if($_REQUEST['module'] == 'Invoice' || $_REQUEST['module'] == 'Quotes')
			{
				$id=$_REQUEST['id'];
				$block = $_REQUEST['module'];
				$params = array('id' => "$id", 'block'=>"$block", 'contactid'=>"$customerid",'sessionid'=>"$sessionid");
				$fileContent = $client->call('get_pdf', $params, $Server_Path, $Server_Path);
				$fileType ='application/pdf';
				$fileContent = $fileContent[0];
				$filesize = strlen(base64_decode($fileContent));
				$filename = "$block.pdf";

			}
			else if($_REQUEST['module'] == 'Documents')
			{
				$id=$_REQUEST['id'];
				$folderid = $_REQUEST['folderid'];
				$block = $_REQUEST['module'];
				$params = array('id' => "$id", 'folderid'=> "$folderid",'block'=>"$block", 'contactid'=>"$customerid",'sessionid'=>"$sessionid");
				$result = $client->call('get_filecontent_detail', $params, $Server_Path, $Server_Path);
				$fileType=$result[0]['filetype'];
				$filesize=$result[0]['filesize'];
				$filename=html_entity_decode($result[0]['filename']);
				$fileContent=$result[0]['filecontents'];
			}
			else
			{
				$ticketid = $_REQUEST['ticketid'];
				$fileid = $_REQUEST['fileid'];
				//we have to get the content by passing the customerid, fileid and filename
				$customerid = $_SESSION['customer_id'];
				$sessionid = $_SESSION['customer_sessionid'];
				$params = array(Array('id'=>$customerid,'fileid'=>$fileid,'filename'=>$filename,'sessionid'=>$sessionid,'ticketid'=>$ticketid));
				$fileContent = $client->call('get_filecontent', $params, $Server_Path, $Server_Path);
				$fileContent = $fileContent[0];
				$filesize = strlen(base64_decode($fileContent));

			}
			// : End

			//we have to get the content by passing the customerid, fileid and filename
			$customerid = $_SESSION['customer_id'];
			$sessionid = $_SESSION['customer_sessionid'];

			$decodedContent = base64_decode((string) $fileContent, true);
			if ($decodedContent === false) {
				http_response_code(502);
				exit('Invalid attachment data.');
			}
			$filename = portalDownloadFilename($filename);
			$fileType = portalDownloadContentType($fileType);
			$filesize = strlen($decodedContent);
			$asciiFilename = preg_replace('/[^\x20-\x7E]/', '_', $filename);
			$asciiFilename = str_replace(array('"', '\\'), '_', $asciiFilename);

			header("Content-Type: $fileType");
			header("Content-Length: $filesize");
			header("Cache-Control: private");
			header('X-Content-Type-Options: nosniff');
			header('Content-Disposition: attachment; filename="'.$asciiFilename.'"; filename*=UTF-8\'\''.rawurlencode($filename));
			header("Content-Description: PHP Generated Data");
			echo $decodedContent;
			exit;
		}
		if($_REQUEST['module'] != '' && $_REQUEST['action'] != '')
		{
			$customerid = $_SESSION['customer_id'];
				
			$permission = array();
			// Look if we have the information already
			if(isset($_SESSION['__permitted_modules'])) {
				$permission = $_SESSION['__permitted_modules'];
			} else {
				// Get the information from server
				$params = array($customerid);
				$permission = $client->call('get_modules',$params,$Server_path,$Server_path);
				// Store for futher re-use
				$_SESSION['__permitted_modules'] = $permission;
			}			
			$isPermitted = false;
			for($i=0;$i<count($permission);$i++){
				if($permission[$i] == $_REQUEST['module']) {
					$isPermitted = true;
					break;
				}
			}
			if($isPermitted == true) {
				$module = $_REQUEST['module']."/";
				$action = $_REQUEST['action'].".php";
			}
			if($isPermitted == false || ($module == '' && $action == '')){
				echo '#NOT AUTHORISED#';
				exit();
			}
		}
		elseif($_REQUEST['action'] != '' && $_REQUEST['module'] == '')
		{
			$action = $_REQUEST['action'].".php";
		}
		elseif($_SESSION['customer_id'] != '')
		{
			$permission = array();
			// Look if we have the information already
			if(isset($_SESSION['__permitted_modules'])) {
				$permission = $_SESSION['__permitted_modules'];
			} else {
				// Get the information from server
				$params = array();
				$permission = $client->call('get_modules',$params,$Server_path,$Server_path);
				// Store for futher re-use
				$_SESSION['__permitted_modules'] = $permission;
			}
			$module = $permission[0];
			$action = "index.php";
		}
	}
	$filename = $module.$action;

	if($is_logged == 1 && requestValidateReadAccess())
	{
		include("HelpDesk/Utils.php");
		global $default_charset, $default_language;
		$default_language = getPortalCurrentLanguage();
		include("language/$default_language.lang.php");
		header('Content-Type: text/html; charset='.$default_charset);
		
		if(!$isAjax) {
			include("header.html");
		}

		?>

		<?php
		// Hide non-permitted tabs if not Ajax Request		
		if(!$isAjax) {
			
			echo '<script type="text/javascript">';
		
			// Look if we have the information already
			$tabArray = array();
			if(isset($_SESSION['__permitted_modules'])) {
				$tabArray = $_SESSION['__permitted_modules'];
			} else {
				// Get the information from server
				$params = array();
				$tabArray = $client->call('get_modules',$params,$Server_path,$Server_path);
				// Store for futher re-use
				$_SESSION['__permitted_modules'] = $tabArray;
			}
			$module = $_REQUEST['module'];
			foreach($tabArray as $key => $tabName) {
				if(file_exists($tabName)) {
					if(strcmp(rtrim($module,"/"),$tabName) == 0) {
		?>
					document.getElementById("<?php echo $tabName;?>").className = "dvtSelectedCell";
		<?php
					}
					else {
		?>
					document.getElementById("<?php echo $tabName;?>").className = "dvtUnSelectedCell";
		<?php
					}
				}
			}
			echo '</script>';
		}
		?>
		
		<?php
		if(is_file($filename)) {
			checkFileAccess($filename);			
			include($filename);
		} else if($_SESSION['customer_id'] != ''){
			$permission = array();
			// Look if we have the information already
			if(isset($_SESSION['__permitted_modules'])) {
				$permission = $_SESSION['__permitted_modules'];
				// Store for further re-use
				$_SESSION['__permitted_modules'] = $permission;
			} else {
				// Get the information from server
				$params = array();
				$permission = $client->call('get_modules',$params,$Server_path,$Server_path);
			}
			$module = $permission[0];
			
			checkFileAccess("$module/index.php");
			include("$module/index.php");
		}
		if(!$isAjax) {
			include("footer.html");
		}
	}
	else {
		header("Location: login.php");
	}

}

?>

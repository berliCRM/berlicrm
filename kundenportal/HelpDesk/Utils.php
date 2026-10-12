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


function getComboList($name, $value, $defaultval='', $selectval='')
{
	$list = '<select name="'.$name.'" size="1" class="form-control">';

	//Add the default value as a first option
	if($defaultval != '')
		$list .= '<OPTION value="'.$defaultval.'">'.$defaultval.'</OPTION>';

	foreach($value as $index => $val)
	{
		$selected = '';
		if($selectval == $val)
			$selected = ' selected ';
		$list .= '<OPTION value="'.$val.'" '.$selected.'>'.$val.'</OPTION>';
	}
	$list .= '</select>';

	return $list;
}

function UpdateComment()
{
	global $client,$Server_Path;
	$ticketid = $_REQUEST['ticketid'];
	$ownerid = $_SESSION['customer_id'];
	$comments = $_REQUEST['comments'];
	$customerid = $_SESSION['customer_id'];
	$sessionid = $_SESSION['customer_sessionid'];

	$params = Array(Array('id'=>"$customerid", 'sessionid'=>"$sessionid", 'ticketid'=>"$ticketid",'ownerid'=>"$customerid",'comments'=>"$comments"));

        $commentresult = $client->call('update_ticket_comment', $params, $Server_Path, $Server_Path);
}

function Close_Ticket($ticketid)
{
	global $client,$Server_Path;
	$customerid = $_SESSION['customer_id'];
	$sessionid = $_SESSION['customer_sessionid'];
	$params = Array(Array('id'=>"$customerid", 'sessionid'=>"$sessionid", 'ticketid'=>"$ticketid"));

	$result = $client->call('close_current_ticket', $params, $Server_Path, $Server_Path);
	return $result;
}

function getPicklist($picklist_name)
{
	
	// Static cache to re-use information
	static $_picklist_cache = array();	
	if(isset($_picklist_cache[$picklist_name])) {
		return $_picklist_cache[$picklist_name];
	}
	
	global $client,$Server_Path;
	$customerid = $_SESSION['customer_id'];
	$sessionid = $_SESSION['customer_sessionid'];

	$params = Array(Array('id'=>"$customerid", 'sessionid'=>"$sessionid", 'picklist_name'=>"$picklist_name"));
	$ticket_picklist_array = $client->call('get_picklists', $params, $Server_Path, $Server_Path);
	
	// Save the result for re-use
	$_picklist_cache[$picklist_name] = $ticket_picklist_array;

	return $ticket_picklist_array;
}

function getStatusComboList($selectedvalue='')
{
	$temp_array = getPicklist('ticketstatus');

	$status_combo = "<option value=''>".getTranslatedString('LBL_ALL')."</option>";
	foreach($temp_array as $index => $val)
	{
		$select = '';
		if($val == $selectedvalue)
			$select = ' selected';

		$status_combo .= '<option value="'.$val.'"'.$select.'>'.getTranslatedString($val).'</option>';
	}

	return $status_combo;
}

//Added for My Settings - Save Password
function SavePassword($version)
{
	global $client;
	
	$customer_name = $_SESSION['customer_name'];
	$oldpw = isset($_POST['old_password']) ? (string) $_POST['old_password'] : '';
	$newpw = isset($_POST['new_password']) ? (string) $_POST['new_password'] : '';
	$confirmpw = isset($_POST['confirm_password']) ? (string) $_POST['confirm_password'] : '';

	if ($newpw !== $confirmpw) {
		return getTranslatedString('MSG_ENTER_NEW_PASSWORDS_SAME');
	}

	if ($newpw === $oldpw) {
		return getTranslatedString('MSG_PASSWORD_MUST_DIFFER');
	}

	if (!isPortalPasswordValid($newpw)) {
		return getTranslatedString('MSG_PASSWORD_POLICY');
	}

	$params = Array('user_name'=>"$customer_name",'user_password'=>"$oldpw",'version'=>"$version",'login'=>'false');
	$result = $client->call('authenticate_user',$params);
	if(
		is_array($result)
		&& isset($result[0])
		&& is_array($result[0])
		&& isset($result[0]['user_password'], $result[0]['id'])
		&& is_string($result[0]['user_password'])
		&& hash_equals($result[0]['user_password'], $oldpw)
	)
	{
		$customerid = $result[0]['id'];
		$sessionid = $_SESSION['customer_sessionid'];

		$params = Array(Array('id'=>"$customerid", 'sessionid'=>"$sessionid", 'username'=>"$customer_name",'password'=>"$newpw",'version'=>"$version"));

		$result_change_password = $client->call('change_password',$params);
		if(is_array($result_change_password) && isset($result_change_password[0]) && $result_change_password[0] == 'MORE_THAN_ONE_USER'){
			return getTranslatedString('MORE_THAN_ONE_USER');
		}

		return getTranslatedString('MSG_PASSWORD_CHANGED');
	}

	$resultCode = '';
	if (is_array($result) && isset($result[0]) && is_string($result[0])) {
		$resultCode = $result[0];
	} elseif (is_string($result)) {
		$resultCode = $result;
	}

	if($resultCode == 'INVALID_USERNAME_OR_PASSWORD') {
		return getTranslatedString('LBL_ENTER_VALID_USER');
	}
	if($resultCode == 'MORE_THAN_ONE_USER'){
		return getTranslatedString('MORE_THAN_ONE_USER');
	}

	return getTranslatedString('MSG_YOUR_PASSWORD_WRONG');
}

function isPortalPasswordValid($password)
{
	return strlen($password) >= 10
		&& preg_match('/[A-Z]/', $password)
		&& preg_match('/[0-9]/', $password)
		&& preg_match('/[^A-Za-z0-9\s]/', $password);
}

function getTicketAttachmentsList($ticketid)
{
	global $client;
	
	$customer_name = $_SESSION['customer_name'];
	$customerid = $_SESSION['customer_id'];
	$sessionid = $_SESSION['customer_sessionid'];
	$params = Array(Array('id'=>"$customerid", 'sessionid'=>"$sessionid", 'ticketid'=>"$ticketid"));
	$result = $client->call('get_ticket_attachments',$params);

	return $result;
}

function AddAttachment()
{
	global $client, $Server_Path;
	global $upload_dir;

	$ticketid = isset($_POST['ticketid']) && is_scalar($_POST['ticketid'])
		? (string) $_POST['ticketid']
		: '';
	$upload = isset($_FILES['customerfile']) && is_array($_FILES['customerfile'])
		? $_FILES['customerfile']
		: array();
	$fileerror = isset($upload['error']) ? (int) $upload['error'] : UPLOAD_ERR_NO_FILE;

	if ($fileerror === UPLOAD_ERR_NO_FILE) {
		return getTranslatedString('LBL_GIVE_VALID_FILE');
	}
	if ($fileerror === UPLOAD_ERR_INI_SIZE || $fileerror === UPLOAD_ERR_FORM_SIZE) {
		return getTranslatedString('LBL_UPLOAD_FILE_LARGE');
	}
	if ($fileerror !== UPLOAD_ERR_OK) {
		return getTranslatedString('LBL_PROBLEM_UPLOAD');
	}

	$uploadedPath = isset($upload['tmp_name']) ? (string) $upload['tmp_name'] : '';
	if ($uploadedPath === '' || !is_uploaded_file($uploadedPath)) {
		return getTranslatedString('LBL_UPLOAD_VALID_FILE');
	}

	$filename = isset($upload['name']) ? (string) $upload['name'] : '';
	$filename = basename(str_replace('\\', '/', $filename));
	$filename = preg_replace('/[\x00-\x1F\x7F]/', '', $filename);
	if (!is_string($filename) || $filename === '') {
		$filename = 'attachment';
	}
	$filename = substr($filename, 0, 255);

	$temporaryDirectory = is_dir($upload_dir) && is_writable($upload_dir)
		? $upload_dir
		: sys_get_temp_dir();
	if (!is_dir($temporaryDirectory) || !is_writable($temporaryDirectory)) {
		return getTranslatedString('LBL_NOTSET_UPLOAD_DIR');
	}

	$temporaryPath = tempnam($temporaryDirectory, 'kp_upload_');
	if ($temporaryPath === false || !move_uploaded_file($uploadedPath, $temporaryPath)) {
		if (is_string($temporaryPath) && is_file($temporaryPath)) {
			unlink($temporaryPath);
		}
		return getTranslatedString('LBL_PROBLEM_UPLOAD');
	}

	try {
		$rawContents = file_get_contents($temporaryPath);
		$filesize = filesize($temporaryPath);
		$filetype = 'application/octet-stream';
		if (class_exists('finfo')) {
			$fileInfo = new finfo(FILEINFO_MIME_TYPE);
			$detectedType = $fileInfo->file($temporaryPath);
			if (is_string($detectedType) && $detectedType !== '') {
				$filetype = $detectedType;
			}
		}
	} finally {
		unlink($temporaryPath);
	}

	if (!is_string($rawContents) || $rawContents === '' || $filesize === false || $filesize <= 0) {
		return getTranslatedString('LBL_FILE_HAS_NO_CONTENTS');
	}

	$customerid = $_SESSION['customer_id'];
	$sessionid = $_SESSION['customer_sessionid'];
	$params = Array(Array(
		'id'=>"$customerid",
		'sessionid'=>"$sessionid",
		'ticketid'=>"$ticketid",
		'filename'=>"$filename",
		'filetype'=>"$filetype",
		'filesize'=>(int) $filesize,
		'filecontents'=>base64_encode($rawContents)
	));
	$client->call('add_ticket_attachment', $params, $Server_Path, $Server_Path);

	return '';
}

?>

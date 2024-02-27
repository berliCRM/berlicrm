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
	
	$customer_id = $_SESSION['customer_id'];
	$customer_name = $_SESSION['customer_name'];
	$oldpw = trim($_REQUEST['old_password']);
	$newpw = trim($_REQUEST['new_password']);
	$confirmpw = trim($_REQUEST['confirm_password']);

	$params = Array('user_name'=>"$customer_name",'user_password'=>"$oldpw",'version'=>"$version",'login'=>'false');
	$result = $client->call('authenticate_user',$params);
	$sessionid = $_SESSION['customer_sessionid'];
	if($oldpw == $result[0]['user_password'])
	{
		if(strcasecmp($newpw,$confirmpw) == 0)
		{
			$customerid = $result[0]['id'];
						
		//	$customerid = $_SESSION['customer_id'];
			$sessionid = $_SESSION['customer_sessionid'];

			$params = Array(Array('id'=>"$customerid", 'sessionid'=>"$sessionid", 'username'=>"$customer_name",'password'=>"$newpw",'version'=>"$version"));

			$result_change_password = $client->call('change_password',$params);
			if($result_change_password[0] == 'MORE_THAN_ONE_USER'){
				$errormsg .= getTranslatedString('MORE_THAN_ONE_USER');
			}else{
				$errormsg .= getTranslatedString('MSG_PASSWORD_CHANGED');
			}
		}
		else
		{
			$errormsg .= getTranslatedString('MSG_ENTER_NEW_PASSWORDS_SAME');
		}
	}elseif($result[0] == 'INVALID_USERNAME_OR_PASSWORD') {
		$errormsg .= getTranslatedString('LBL_ENTER_VALID_USER');	
	}elseif($result[0] == 'MORE_THAN_ONE_USER'){
		$errormsg .= getTranslatedString('MORE_THAN_ONE_USER');
	}
	else
	{
		$errormsg .= getTranslatedString('MSG_YOUR_PASSWORD_WRONG');
	}

	return $errormsg;
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
	$ticketid = $_REQUEST['ticketid'];
	$ownerid = $_SESSION['customer_id'];

	$filename = $_FILES['customerfile']['name'];
	$filetype = $_FILES['customerfile']['type'];
	$filesize = $_FILES['customerfile']['size'];
	$fileerror = $_FILES['customerfile']['error'];
	if (isset($_REQUEST['customerfile_hidden'])) {
		$filename = $_REQUEST['customerfile_hidden'];
	}
	
	$upload_error = '';
	if($fileerror == 4)
	{
		$upload_error = getTranslatedString('LBL_GIVE_VALID_FILE');
	}
	elseif($fileerror == 2)
	{
		$upload_error = getTranslatedString('LBL_UPLOAD_FILE_LARGE');
	}
	elseif($fileerror == 3)
	{
		$upload_error = getTranslatedString('LBL_PROBLEM_UPLOAD');
	}

	//Copy the file in temp and then read and pass the contents of the file as a string to db
	global	$upload_dir;
	if(!is_dir($upload_dir)) {
		echo getTranslatedString('LBL_NOTSET_UPLOAD_DIR');
		exit;
	}
	if($filesize > 0)
	{
		if(move_uploaded_file($_FILES["customerfile"]["tmp_name"],$upload_dir.'/'.$filename))
		{
			$filecontents = base64_encode(fread(fopen($upload_dir.'/'.$filename, "r"), $filesize));
		}

		$customerid = $_SESSION['customer_id'];
		$sessionid = $_SESSION['customer_sessionid'];

		$params = Array(Array(
				'id'=>"$customerid",
				'sessionid'=>"$sessionid",
				'ticketid'=>"$ticketid",
				'filename'=>"$filename",
				'filetype'=>"$filetype",
				'filesize'=>"$filesize",
				'filecontents'=>"$filecontents"
			));
		if($filecontents != ''){
			$commentresult = $client->call('add_ticket_attachment', $params, $Server_Path, $Server_Path);
		}else{
			echo getTranslatedString('LBL_FILE_HAS_NO_CONTENTS');
			exit();
		}	
	}
	else
	{
		$upload_error = getTranslatedString('LBL_UPLOAD_VALID_FILE');
	}

	return $upload_error;
}

?>

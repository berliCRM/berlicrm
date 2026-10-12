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

require_once 'include/utils/CommonUtils.php';
require_once 'include/utils/VTCacheUtils.php';

/**   Function used to send email
  *   $module 		-- current module
  *   $to_email 	-- to email address
  *   $from_name	-- currently loggedin user name
  *   $from_email	-- currently loggedin vtiger_users's email id. you can give as '' if you are not in HelpDesk module
  *   $subject		-- subject of the email you want to send
  *   $contents		-- body of the email you want to send
  *   $cc		-- add email ids with comma seperated. - optional
  *   $bcc		-- add email ids with comma seperated. - optional.
  *   $attachment	-- whether we want to attach the currently selected file or all vtiger_files.[values = current,all] - optional
  *   $emailid		-- id of the email object which will be used to get the vtiger_attachments
  */
function send_mail($module, $to_email, $from_name, $from_email, $subject, $contents, $cc='', $bcc='', $attachment='', $emailid='', $logo='', $useGivenFromEmailAddress=false)
{
	// rework this function to use Record_Model
	$adb = PearDatabase::getInstance();
	global $HELPDESK_SUPPORT_EMAIL_ID, $HELPDESK_SUPPORT_NAME;

	try {
		if (!empty($emailid)) {
			// isn't even used?
			$recordModel = Emails_Record_Model::getInstanceById($emailid, 'Emails');
		} else {
			$recordModel = Emails_Record_Model::getCleanInstance('Emails');
		}

		$to_email = array_filter(explode(',', $to_email));
		$toMails = array();
		foreach($to_email AS $toMail) {
			$toMails[] = array($toMail);
		}
		
		$recordModel->set('subject', $subject);
		$recordModel->set('description', $contents);
		$recordModel->set('toemailinfo', $toMails);
		$recordModel->set('ccmail', $cc);
		$recordModel->set('bccmail', $bcc);
		if (!empty($from_email)) {
			$recordModel->set('fromAddress', $from_email);
		}
		$recordModel->sendWithoutRelation = true;
		
		$mail_status = $recordModel->send();
	
	} catch (Exception $e) {
		$mail_status = $e->getMessage();
	}

	return $mail_status;
}

/**	Function to get the user Email id based on column name and column value
  *	$name -- column name of the vtiger_users vtiger_table
  *	$val  -- column value
  */
function getUserEmailId($name, $val)
{
	global $adb;
	$adb->println("Inside the function getUserEmailId. --- ".$name." = '".$val."'");
	if($val != '')
	{
		//done to resolve the PHP5 specific behaviour
		$sql = "SELECT email1, email2, secondaryemail  from vtiger_users WHERE status='Active' AND ". $adb->sql_escape_string($name)." = ?";
		$res = $adb->pquery($sql, array($val));
		$email = $adb->query_result($res,0,'email1');
		if($email == '')
		{
			$email = $adb->query_result($res,0,'email2');
			if($email == '')
			{
				$email = $adb->query_result($res,0,'secondaryemail ');
			}
		}
		$adb->println("Email id is selected  => '".$email."'");
		return $email;
	}
	else
	{
		$adb->println("User id is empty. so return value is ''");
		return '';
	}
}

/**
 * Function to get the group users Email ids
 */
function getDefaultAssigneeEmailIds($groupId) {
	global $adb;
	$emails = Array();
	if($groupId != '') {
		require_once 'include/utils/GetGroupUsers.php';
		$userGroups = new GetGroupUsers();
		$userGroups->getAllUsersInGroup($groupId);

		if(count($userGroups->group_users) == 0) return array();

		$result = $adb->pquery('SELECT email1,email2,secondaryemail FROM vtiger_users WHERE vtiger_users.id IN
											('.  generateQuestionMarks($userGroups->group_users).') AND vtiger_users.status= ?',
								array($userGroups->group_users, 'Active'));
		$rows = $adb->num_rows($result);
		for($i = 0;$i < $rows; $i++) {
			$email = $adb->query_result($result,$i,'email1');
			if($email == '') {
				$email = $adb->query_result($result,$i,'email2');
				if($email == '') {
					$email = $adb->query_result($result,$i,'secondaryemail');
				} else {
					$email = '';
				}
			}
			array_push($emails,$email);
		}
		$adb->println("Email ids are selected  => '".$emails."'");
		return $emails;
	} else {
		$adb->println("User id is empty. so return value is ''");
		return array();
	}
}
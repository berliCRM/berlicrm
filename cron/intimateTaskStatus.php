<?php
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
********************************************************************************/
return;
ini_set("include_path", "../");

require('send_mail.php');
require_once('config.php');
require_once('include/utils/utils.php');
require_once('include/utils/CommonUtils.php');
require_once 'includes/runtime/LanguageHandler.php';
require_once 'includes/runtime/Globals.php';
require_once('modules/Users/Users.php');

// Email Setup
global $adb;
global $current_language,$current_user;

$emailresult = $adb->pquery("SELECT email1 from vtiger_users", array());
$emailid = $adb->fetch_array($emailresult);
$emailaddress = $emailid[0];
$mailserveresult = $adb->pquery("SELECT server,server_username,server_password,smtp_auth FROM vtiger_systems where server_type = ?", array('email'));
$mailrow = $adb->fetch_array($mailserveresult);
$mailserver = $mailrow[0];
$mailuname = $mailrow[1];
$mailpwd = $mailrow[2];
$smtp_auth = $mailrow[3];
// End Email Setup
//get user's infos
$user_obj = new Users();
;

//query the vtiger_notificationscheduler vtiger_table and get data for those notifications which are active
$sql = "select active from vtiger_notificationscheduler where schedulednotificationid=1";
$result = $adb->pquery($sql, array());

$activevalue = $adb->fetch_array($result);

if($activevalue[0] == 1)
{
	//Delayed Tasks Notification

	//get all those activities where the status is not completed even after the passing of 24 hours
	$today = date("Ymd"); 
	$result = $adb->pquery("select vtiger_activity.status,vtiger_activity.activityid,subject,(vtiger_activity.date_start +1),vtiger_crmentity.smownerid from vtiger_activity inner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_activity.activityid where vtiger_crmentity.deleted=0 and vtiger_activity.status <> 'Completed' and activitytype='Task' and ".$today." > (vtiger_activity.date_start+1)", array());

	while ($myrow = $adb->fetch_array($result)) {
		$status=$myrow[0];
		$act_id = $myrow[1];
		//crm-now: modified for full length
		$subject = $myrow[2];
		$subject_short = (strlen($myrow[2]) > 50)?substr($myrow[2],0,47).'...':$myrow[2];
		$user_id = $myrow[4];
		if($user_id != '') {
			//crm-now: we are sending the email not to admin (as vtiger does) but to the user who owns the task
			$user_res = $adb->pquery('select user_name, email1 from vtiger_users where id=?',array($user_id));
			$assigned_user = $adb->query_result($user_res,0,'user_name');
			$assigned_user_mail = array();
			$assigned_user_mail[0] = $adb->query_result($user_res,0,'email1');
			//crm-now: should the var be empty it's most likely a group
			if ($assigned_user_mail[0] == '') {
				require_once('include/utils/GetGroupUsers.php');
				$getGroupObj=new GetGroupUsers();
				$getGroupObj->getAllUsersInGroup($user_id);
				$userIds=$getGroupObj->group_users;
				$query2 = "SELECT email1 FROM vtiger_users WHERE status = 'active' AND email1 <> '' AND id IN (".generateQuestionMarks($userIds).")";
				$result2 = $adb->pquery($query2, $userIds);
				for($i=0; $i<$adb->num_rows($result2); $i++)
				{
					$email = $adb->query_result($result2, $i, "email1");
					$assigned_user_mail[$i] = $email;
				}
			}
			$seed_user = new Users();
			$current_user = $seed_user->retrieve_entity_info($user_id, 'Users');
			$current_language = $current_user->column_fields['language'];
			include_once("languages/$current_language/Vtiger.php");
		}
		$linkurl= $site_URL.'index.php?module=Calendar&view=Detail&record='.$act_id;

		$mail_body = $languageStrings['Dear_Admin_tasks_not_been_completed']." ".$languageStrings['LBL_SUBJECT'].": ".$subject."<br> ".$languageStrings['LBL_ASSIGNED_TO'].": ".$assigned_user."<br>Link: ".$linkurl."<br><br>".$languageStrings['Task_sign'];
	 	$sub = $languageStrings['Task_Not_completed'].': '.html_entity_decode($subject_short, ENT_COMPAT, 'UTF-8');
	 	sendmail($assigned_user_mail,$emailaddress,$sub,$mail_body,$mailserver,$mailuname,$mailpwd,"",$smtp_auth);
	}
}

//Big Deal Alert
$sql = "select active from vtiger_notificationscheduler where schedulednotificationid=2";
$result = $adb->pquery($sql, array());

$activevalue = $adb->fetch_array($result);
if($activevalue[0] == 1)
{
	$result = $adb->pquery("SELECT sales_stage,amount,potentialid,potentialname FROM vtiger_potential inner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_potential.potentialid where vtiger_crmentity.deleted=0 and sales_stage='Closed Won' and amount > 10000",array());
	while ($myrow = $adb->fetch_array($result))
	{
		$pot_id = $myrow['potentialid'];
		$pot_name = $myrow['potentialname'];
		$body_content = $languageStrings['Dear_Team'].$languageStrings['Dear_Team_Time_to_Party']."<br><br>".$languageStrings['Potential_Id']." ".$pot_id;
		$body_content .= $languageStrings['Potential_Name']." ".$pot_name."<br><br>";
		sendmail($emailaddress,$emailaddress,$languageStrings['Big_Deal_Closed_Successfully'],$body_content,$mailserver,$mailuname,$mailpwd,"",$smtp_auth);
	}
}
//Pending tickets
$sql = "select active from vtiger_notificationscheduler where schedulednotificationid=3";
$result = $adb->pquery($sql, array());

$activevalue = $adb->fetch_array($result);
if($activevalue[0] == 1)
{
	$result = $adb->pquery("SELECT vtiger_troubletickets.status,ticketid,ticket_no FROM vtiger_troubletickets INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid=vtiger_troubletickets.ticketid WHERE vtiger_crmentity.deleted='0' AND vtiger_troubletickets.status <> 'Completed' AND vtiger_troubletickets.status <> 'Closed'", array());

	while ($myrow = $adb->fetch_array($result))
	{
		$ticketid = $myrow['ticket_no'];
		sendmail($emailaddress,$emailaddress,$languageStrings['Pending_Ticket_notification'],$languageStrings['Kind_Attention'].$ticketid .$languageStrings['Thank_You_HelpDesk'],$mailserver,$mailuname,$mailpwd,"",$smtp_auth);
	}
}

//Too many tickets related to a particular vtiger_account/company causing concern
$sql = "select active from vtiger_notificationscheduler where schedulednotificationid=4";
$result = $adb->pquery($sql, array());

$activevalue = $adb->fetch_array($result);
if($activevalue[0] == 1)
{
	$result = $adb->pquery("SELECT count(*) as count FROM vtiger_troubletickets INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid=vtiger_troubletickets.ticketid WHERE vtiger_crmentity.deleted='0' AND vtiger_troubletickets.status <> 'Completed' AND vtiger_troubletickets.status <> 'Closed'", array());
$count = $adb->query_result($result,0,'count');
//changes made to get too many tickets notification only when tickets count is greater than or equal to 5
	if($count >= 5)
	{
		sendmail($emailaddress,$emailaddress,$languageStrings['Too_many_pending_tickets'],$languageStrings['Dear_Admin_too_many_tickets_pending'],$mailserver,$mailuname,$mailpwd,"",$smtp_auth);
	}
}

//Support Starting
$sql = "select active from vtiger_notificationscheduler where schedulednotificationid=5";
$result = $adb->pquery($sql, array());

$activevalue = $adb->fetch_array($result);
if($activevalue[0] == 1)
{
	$result = $adb->pquery("SELECT vtiger_products.productname FROM vtiger_products inner join vtiger_crmentity on vtiger_products.productid = vtiger_crmentity.crmid where vtiger_crmentity.deleted=0 and start_date like ?", array(date('Y-m-d'). "%"));
	while ($myrow = $adb->fetch_array($result))
	{
		$productname=$myrow[0];
		sendmail($emailaddress,$emailaddress,$languageStrings['Support_starting'],$languageStrings['Hello_Support'].$productname ."\n ".$languageStrings['Congratulations'],$mailserver,$mailuname,$mailpwd,"",$smtp_auth);
	}
}

//Support ending
$sql = "select active from vtiger_notificationscheduler where schedulednotificationid=6";
$result = $adb->pquery($sql, array());

$activevalue = $adb->fetch_array($result);
if($activevalue[0] == 1)
{
	$result = $adb->pquery("SELECT vtiger_products.productname from vtiger_products inner join vtiger_crmentity on vtiger_products.productid = vtiger_crmentity.crmid where vtiger_crmentity.deleted=0 and expiry_date like ?", array(date('Y-m-d') ."%"));
	while ($myrow = $adb->fetch_array($result))
	{
		$productname=$myrow[0];
		sendmail($emailaddress,$emailaddress,$languageStrings['Support_Ending_Subject'],$languageStrings['Support_Ending_Content'].$productname.$languageStrings['kindly_renew'],$mailserver,$mailuname,$mailpwd,"",$smtp_auth);
	}
}

?>
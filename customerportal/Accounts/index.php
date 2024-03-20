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
include("index.html");
global $result;

if($customerid == '')
	$customerid = $_SESSION['customer_id'];
$block = 'Accounts';
if($_REQUEST['id'] == '')
{
	$params = Array('id'=>$customerid);
	$accountid = $client->call('get_check_account_id', $params, $Server_Path, $Server_Path);
}
else
	$accountid = $_REQUEST['id'];

if($accountid != '')
	include("AccountDetail.php");
?>

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

@include("../PortalConfig.php");
if(!isset($_SESSION['customer_id']) || $_SESSION['customer_id'] == '')
{
	@header("Location: $Authenticate_Path/login.php");
	exit;
}
include("index.html");
global $result;
$customerid=$_SESSION['customer_id'];

if($_REQUEST['id'] != '') {
	$projectid =$_REQUEST['id'];
	$status =$_REQUEST['status'];
	$block = 'Project';
	include("ProjectDetail.php");
} else {
	include("ProjectsList.php");
}
?>

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
	$sessionid = $_SESSION['customer_sessionid'];
	$customerid = $_SESSION['customer_id'];
	$assetid = portal_purify($_REQUEST['id']);
	$block = 'Assets';
	if($assetid == '')
		include("AssetsList.php");
	else
		include("AssetDetail.php");

	echo '</table> </td></tr></table></td></tr></table>';
?>
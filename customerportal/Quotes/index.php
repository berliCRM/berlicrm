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
$only_mine = (isset($_REQUEST['only_mine'])) ? " checked " : ""; 

@include("../PortalConfig.php");
if(!isset($_SESSION['customer_id']) || $_SESSION['customer_id'] == '')
{
	@header("Location: $Authenticate_Path/login.php");
	exit;
}
include("index.html");
$customerid = $_SESSION['customer_id'];
$sessionid = $_SESSION['customer_sessionid'];
global $result;


	if($_REQUEST['id'] == '')
	{
		include("QuotesList.php");
	}
	else
	{
		$quote_id=$_REQUEST['id'];
		$block = "Quotes";
		include("QuoteDetail.php");
	}
	echo '</table> </td></tr></table></td></tr></table>';
	
?>

	


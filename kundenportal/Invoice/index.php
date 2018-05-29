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
require_once("include/utils/utils.php");

@include("../PortalConfig.php");
if(!isset($_SESSION['customer_id']) || $_SESSION['customer_id'] == '')
{
	@header("Location: $Authenticate_Path/login.php");
	exit;
}
	include("index.html");
	global $result;
	$customerid = $_SESSION['customer_id'];
	$sessionid = $_SESSION['customer_sessionid'];


	if($_REQUEST['id'] != '')
	{
		$id=$_REQUEST['id'];
		$status =$_REQUEST['status'];
		$block = "Invoice";
		if($status != true)
		{
			$params = array('id' => "$id", 'block'=>"$block", 'contactid'=>"$customerid",'sessionid'=>"$sessionid");
			$filecontent = $client->call('get_pdf', $params, $Server_Path, $Server_Path);
			if($filecontent != 'failure')
			{
				$filename="$Server_Path/test/product/".portal_purify($id)."_Invoice.pdf";
				header("Content-type: text/pdf");
				header("Cache-Control: private");
				header("Content-Disposition: attachment; filename=$filename");
				header("Content-Description: PHP Generated Data");
				echo base64_decode($filecontent);

				exit;

			}
			else
			{
				echo getTranslatedString('LBL_PDF_CANNOT_GENERATE');//We have to show the error message like "PDF output cannot be generated. Please contact admin"
			}
		}
		else
		{
		include("InvoiceDetail.php");
		}	

	}
	else
	{
		include("InvoiceList.php");
		echo '</table> </td></tr></table></td></tr></table>';
	}
	

?>

	
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
require_once("include/Zend/Json.php");
@include("../PortalConfig.php");
if(!isset($_SESSION['customer_id']) || $_SESSION['customer_id'] == '')
{
	@header("Location: $Authenticate_Path/login.php");
	exit;
}
include("HelpDesk/index.html");
include("HelpDesk/TicketSearch.php");

global $result;
$username = $_SESSION['customer_name'];
$customerid = $_SESSION['customer_id'];
$sessionid = $_SESSION['customer_sessionid'];

$onlymine=$_REQUEST['onlymine'];
if($onlymine == 'true') {
    $mine_selected = 'selected';
    $all_selected = '';
} else {
    $mine_selected = '';
    $all_selected = 'selected';
}

if($_REQUEST['fun'] == '' || $_REQUEST['fun'] == 'home' || $_REQUEST['fun'] == 'search')
{
	// This is an archaic parameter list
	$match_condition = (isset($_REQUEST['search_match']))?$_REQUEST['search_match']:'';
	$where = getTicketSearchQuery();
	$params = Array(Array('id'=>"$customerid", 'sessionid'=>"$sessionid", 'user_name' => "$username", 'onlymine' => $onlymine, 'where' => "$where", 'match' => "$match_condition"));	
	$result = $client->call('get_tickets_list', $params, $Server_Path, $Server_Path);
	include("TicketsList.php");
}
elseif($_REQUEST['fun'] == 'newticket')
{
	include("NewTicket.php");
}
elseif($_REQUEST['fun'] == 'updatecomment' || $_REQUEST['fun'] == 'close_ticket' || $_REQUEST['fun'] == 'uploadfile')
{
	if($_REQUEST['fun'] == 'updatecomment' && requestValidateWriteAccess())
	{
		UpdateComment();
	}
	if($_REQUEST['fun'] == 'close_ticket' && requestValidateWriteAccess())
	{
		$ticketid = $_REQUEST['ticketid'];
		$res = Close_Ticket($ticketid);
	}
	if($_REQUEST['fun'] == 'uploadfile' && requestValidateWriteAccess())
	{
		$upload_status = AddAttachment();
		if($upload_status != ''){
			echo $upload_status;
			exit(0);
		} 
	}

	?>
	<script>
		var ticketid = <?php echo Zend_Json::encode($_REQUEST['ticketid']); ?>;
		window.location.href = "index.php?module=HelpDesk&action=index&fun=detail&ticketid="+ticketid
	</script>
	<?php
	
}
elseif($_REQUEST['fun'] == 'detail' && requestValidateReadAccess())
{	
	
	$ticketid = Zend_Json::decode($_REQUEST['ticketid']);
	$block = 'HelpDesk';
	include("TicketDetail.php");
}
elseif($_REQUEST['fun'] == 'saveticket')
{
	include("SaveTicket.php");
}

echo '</table></td></tr></table></td></tr></table>';
?>

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

global $client;
global $result;
if(requestValidateWriteAccess()){
$ticket = Array(
		'title'=>'title',
		'productid'=>'productid',
		'description'=>'description',
		'priority'=>'priority',
		'category'=>'category',
		'owner'=>'owner',
		'module'=>'module'
	       );

foreach($ticket as $key => $val)
	$ticket[$key] = $_REQUEST[$key];

$ticket['owner'] = $username;
$ticket['productid'] = $_SESSION['combolist'][0]['productid'][$ticket['productid']];


$title = $_REQUEST['title'];
$description = $_REQUEST['description'];
$priority = $_REQUEST['priority'];
$severity = $_REQUEST['severity'];
$category = $_REQUEST['category'];
$parent_id = $_SESSION['customer_id'];
$productid = $_SESSION['combolist'][0]['productid'][$_REQUEST['productid']];

$module = $_REQUEST['ticket_module'];

$customerid = $_SESSION['customer_id'];
$sessionid = $_SESSION['customer_sessionid'];
$serviceid = $_REQUEST['servicename'];

$projectid = $_REQUEST['projectid'];

$params = Array(Array(
		'id'=>"$customerid",
		'sessionid'=>"$sessionid",
		'title'=>"$title",
		'description'=>"$description",
		'priority'=>"$priority",
		'severity'=>"$severity",
		'category'=>"$category",
		'user_name' => "$username",
		'parent_id'=>"$parent_id",
		'product_id'=>"$productid",
		'module'=>"$module",
		'assigned_to'=>"$Ticket_Assigned_to",
		'serviceid'=>"$serviceid",
		'projectid'=>"$projectid"
	));

$record_result = $client->call('create_ticket', $params);
if(isset($record_result[0]['new_ticket']) && $record_result[0]['new_ticket']['ticketid'] != '')
{
	$new_record = 1;
	$ticketid = $record_result[0]['new_ticket']['ticketid'];
}

if($new_record == 1)
{
	?>
	<script>
		var ticketid = <?php echo $ticketid; ?>;
		window.location.href = "index.php?module=HelpDesk&action=index&fun=detail&ticketid="+ticketid
	</script>
	<?php
}
else
{
	echo getTranslatedString('LBL_PROBLEM_IN_TICKET_SAVING');
	include("NewTicket.php");
}


}


?>

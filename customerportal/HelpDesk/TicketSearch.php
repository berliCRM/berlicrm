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

function getTicketSearchQuery() {
	
	if(trim($_REQUEST['search_ticketid']) != '')
	{
		$where .= "vtiger_troubletickets.ticketid = '".addslashes($_REQUEST['search_ticketid'])."'&&&";
	}
	if(trim($_REQUEST['search_title']) != '')
	{
		//$where .= "vtiger_troubletickets.title = '".$_REQUEST['search_title']."'&&&";
		$where .= "vtiger_troubletickets.title like '%".addslashes(trim($_REQUEST['search_title']))."%'&&&";
	}
	
	if(trim($_REQUEST['search_ticketstatus']) != '')
	{
		$where .= "vtiger_troubletickets.status = '".$_REQUEST['search_ticketstatus']."'&&&";
	}
	if(trim($_REQUEST['search_ticketpriority']) != '')
	{
		$where .= "vtiger_troubletickets.priority = '".$_REQUEST['search_ticketpriority']."'&&&";
	}
	if(trim($_REQUEST['search_ticketcategory']) != '')
	{
		$where .= "vtiger_troubletickets.category = '".$_REQUEST['search_ticketcategory']."'&&&";
	}
	$where = trim($where,'&&&');
	return $where;
}

?>

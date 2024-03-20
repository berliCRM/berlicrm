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
global $result;
global $client;
global $Server_Path;

$customerid = $_SESSION['customer_id'];
$sessionid = $_SESSION['customer_sessionid'];

if($projectid != '') {
	$params = array('id' => "$projectid", 'block'=>"$block",'contactid'=>$customerid,'sessionid'=>"$sessionid");
	$result = $client->call('get_details', $params, $Server_Path, $Server_Path);
	// Check for Authorization
	if (count($result) == 1 && $result[0] == "#NOT AUTHORIZED#") {
		echo '<aside class="right-side">';
		echo '<section class="content-header" style="box-shadow:none;"><div class="alert"><b>'.getTranslatedString('LBL_NOT_AUTHORISED').'</b></div></section></aside>';
		die();
	}
	$projectinfo = $result[0][$block];
	echo '<aside class="right-side">';
	echo '<section class="content-header" style="box-shadow:none;"><div class="row-pad">
			<div class="col-sm-10">
				<input class="btn btn-primary btn-flat" type="button" value="'.getTranslatedString('LBL_BACK_BUTTON').'" onclick="window.history.back();"/></div>
			<div class="col-sm-2 search-form"><div class="input-group-btn">
			<input class="btn btn-primary btn-flat" type="button" value="'.getTranslatedString('LBL_RAISE_TICKET_BUTTON').'" onclick="location.href=\'index.php?module=HelpDesk&action=index&fun=newticket&projectid='.$projectid.'\'"/></td>
			</div></div></div></section>';
	echo getblock_fieldlist($projectinfo);
	
	$projecttaskblock = 'ProjectTask';
	$params = array('id' => "$projectid", 'block'=>"$projecttaskblock",'contactid'=>$customerid,'sessionid'=>"$sessionid");
	$result = $client->call('get_project_components', $params, $Server_Path, $Server_Path);	
	echo '<tr><td class="detailedViewHeader" colspan="4"><b>'.getTranslatedString('LBL_PROJECT_TASKS').'</b></td></tr>';
	echo '<tr><td colspan="4"><table border="0" width="100%" cellspacing="0" cellpadding="5">';
	echo getblock_fieldlistview($result,"$projecttaskblock");	
	echo '</table></td></tr>';
	
	echo '<tr><td colspan="4">&nbsp;</td></tr>';
	
	$projectmilestoneblock = 'ProjectMilestone';
	$params = array('id' => "$projectid", 'block'=>"$projectmilestoneblock",'contactid'=>$customerid,'sessionid'=>"$sessionid");
	$result = $client->call('get_project_components', $params, $Server_Path, $Server_Path);	
	echo '<tr><td class="detailedViewHeader" colspan="4"><b>'.getTranslatedString('LBL_PROJECT_MILESTONES').'</b></td></tr>';
	echo '<tr><td colspan="4"><table border="0" width="100%" cellspacing="0" cellpadding="5">';
	echo getblock_fieldlistview($result,"$projectmilestoneblock");	
	echo '</table></td></tr>';
	
	echo '<tr><td colspan="4">&nbsp;</td></tr>';
	
	$projectticketsblock = 'HelpDesk';
	$params = array('id' => "$projectid", 'block'=>"$projectticketsblock",'contactid'=>$customerid,'sessionid'=>"$sessionid");
	$result = $client->call('get_project_tickets', $params, $Server_Path, $Server_Path);	
	echo '<tr><td class="detailedViewHeader" colspan="4"><b>'.getTranslatedString('LBL_PROJECT_TICKETS').'</b></td></tr>';
	echo '<tr><td colspan="4"><table border="0" width="100%" cellspacing="0" cellpadding="5">';
	echo getblock_fieldlistview($result,"$projectticketsblock");		
	echo '</table></td></tr>';

	echo '</table></td></tr>';	
	echo '</table></td></tr></table></td></tr></table>';
	echo '<!-- --End--  -->';
}

?>

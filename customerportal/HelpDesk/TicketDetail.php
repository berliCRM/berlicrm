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
?>



<aside class="right-side">
	<section class="content-header" style="box-shadow:none;">
		<div class="row-pad">
			<div class="col-sm-10">
				<input align="left" class="btn btn-primary btn-flat"type="button" value="<?PHP echo getTranslatedString('LBL_BACK_BUTTON');?>" onclick="window.location='index.php',module='HepDesk'"/>	
			</div>
			<div class="col-sm-2 search-form">
				<div class="input-group-btn">
					<input class="btn btn-primary" name="newticket" type="submit" value="<?PHP echo getTranslatedString('LBL_NEW_TICKET');?>" onclick="this.form.module.value='HelpDesk';this.form.action.value='index';this.form.fun.value='newticket'">&nbsp;&nbsp;&nbsp;
					<input class="btn btn-primary" name="srch" type="button" value="<?PHP echo getTranslatedString('LBL_SEARCH');?>" onClick="showSearchFormNow('tabSrch');">
				</div>
			</div>
		</div>
		</form>
	</section>
	<?PHP
		if (!function_exists('portalTicketEscape')) {
			function portalTicketEscape($value) {
				return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
			}
		}

		global $result;
		global $client;		
		global $Server_Path;
		
		$customerid = $_SESSION['customer_id'];	
		$sessionid = $_SESSION['customer_sessionid'];
		$ticketid = is_scalar($ticketid) ? (string) $ticketid : '';
		$ticketIdHtml = portalTicketEscape($ticketid);
		$list = '';
		$ticket_close_link = '';
		$upload_status = isset($upload_status) ? (string) $upload_status : '';
		
		if($ticketid != ''){
			$params = array('id' => "$ticketid", 'block'=>"$block",'contactid'=>$customerid,'sessionid'=>"$sessionid");
			$result = $client->call('get_details', $params, $Server_Path, $Server_Path);	
			
			// Check for Authorization
			if (is_array($result) && count($result) == 1 && isset($result[0]) && $result[0] == "#NOT AUTHORIZED#") {
				echo 	'<div class = "alert"><b>'.getTranslatedString('LBL_NOT_AUTHORISED').'</b></div>';
				include("footer.html");
				die();
			}

			if (!is_array($result) || !isset($result[0][$block]) || !is_array($result[0][$block])) {
				echo portalTicketEscape(getTranslatedString('LBL_NONE_SUBMITTED'));
				return;
			}
			
			$ticketinfo = $result[0][$block];
			
			$params = Array(Array('id'=>"$customerid", 'sessionid'=>"$sessionid", 'ticketid' => "$ticketid"));
			
			$commentresult = $client->call('get_ticket_comments', $params, $Server_Path, $Server_Path);
			
			$commentresult = is_array($commentresult) ? $commentresult : array();
			$commentscount = count($commentresult);
			
			$params = Array(Array('id'=>"$customerid", 'sessionid'=>"$sessionid", 'ticketid' => "$ticketid"));
		
			//Get the creator of this ticket
			$creator = $client->call('get_ticket_creator', $params, $Server_Path, $Server_Path);

			$ticket_status = '';
			foreach($ticketinfo as $key=>$value) {
				$fieldlabel = isset($value['fieldlabel']) ? $value['fieldlabel'] : '';
				$fieldvalue = isset($value['fieldvalue']) ? $value['fieldvalue'] : '';
				if ($fieldlabel == 'Status') {
					$ticket_status = $fieldvalue;
					break;
				}
			}

			//If the ticket is created by this customer and status is not Closed then allow him to Close this ticket otherwise not
                            echo '<div style = "clear:both;"></div>
					
					<div class = "widget-box">
						<div class = "widget-header">
							<h5 class = "widget-title">'. getTranslatedString("Ticket Information") . '<span style = "float:right;">' . $ticket_close_link ; 
                                
                                
                                if ($ticket_status != 'Closed' && $ticket_status != '') {
                             $ticket_close_link=getTranslatedString('LBL_CLOSE_TICKET');
				echo '<form class = "widget-title widget-box" style = "clear:both;"  name="fileattachment" method="post" enctype="multipart/form-data" action="index.php">
							<input type="hidden" name="module" value="HelpDesk">
							<input type="hidden" name="action" value="index">
							<input type="hidden" name="fun" value="close_ticket">
							<input type="hidden" name="ticketid" value="'.$ticketIdHtml.'">
									<input class="btn btn-primary" name="closed" type="submit" value="'.portalTicketEscape($ticket_close_link).'">
                                                </form> ';
                               
			} 
                                
                                
                                
                                echo '</span></h5>
						</div>
						
						<div class = "widget-body">
							<div class="widget-main no-padding single-entity-view">
								<div style="width:auto;padding:12px;display:block;" id="tblLeadInformation">';
			$z = 0;
			
			$field_count = count($ticketinfo);
			
			if($field_count != 0){
			
				for($i=0;$i<$field_count;$i++,$z++){
					$blockname = isset($ticketinfo[$i]['blockname']) ? (string) $ticketinfo[$i]['blockname'] : '';
					
					$data = isset($ticketinfo[$i]['fieldvalue']) ? (string) $ticketinfo[$i]['fieldvalue'] : '';
						
					if(isset($ticketinfo[$i]['fieldlabel']) && $ticketinfo[$i]['fieldlabel'] == 'Note'){
						$data = portal_purify(html_entity_decode($data, ENT_QUOTES, 'UTF-8'));
					} else {
						$data = portalTicketEscape($data);
					}
					
					if($data =='')
						$data ='&nbsp;';
						
						$previousBlockname = ($i > 0 && isset($ticketinfo[$i-1]['blockname']))
							? (string) $ticketinfo[$i-1]['blockname']
							: '';
						if(strcmp($blockname, $previousBlockname)){
							
							if($z > 0 && ($z % 2) == 1)
								echo "</div>";
								
							if($blockname != 'Ticket Information'){
								echo '</div></div></div></div>
								<div class="widget-box">
									<div class = "widget-header">
										<h5 class = "widget-title">'. portalTicketEscape($blockname) . '</h5>
									</div>
									<div class = "widget-body">
										<div class="widget-main no-padding single-entity-view">
											<div style="width:auto;padding:12px;display:block;" id="tblLeadInformation">';
							}
							
							$z = 0;
						}
						
						if($z==0 || $z%2==0)
							echo '<div class="row">';
							
						echo '<div class="form-group col-sm-6">
										<label class="col-sm-3 control-label no-padding-right">
											'.portalTicketEscape(getTranslatedString(isset($ticketinfo[$i]['fieldlabel']) ? $ticketinfo[$i]['fieldlabel'] : '')).
										'</label>
										<div class="col-sm-9 dvtCellInfo" align="left" valign="top">
											&nbsp;
											'.$data.'
										</div>
								</div>'; 
									
						if(
							$z%2 == 1 ||
							($i == ($field_count-1) ) 
						)
							echo '</div>';
					
					}	
				}
				
				$list .=  '<div class="widget-box">
								<div class = "widget-header">
									<h5 class = "widget-title">'.getTranslatedString('LBL_TICKET_COMMENTS').'</h5>
								</div>
								<div class = "widget-body">
									<div class="widget-main no-padding single-entity-view">
										<div style="width:auto;padding:12px;display:block;" id="tblLeadInformation">';
				
				if($commentscount >= 1 && is_array($commentresult)){
					
					$list .= '<div id="scrollTab2">
							<table width="100%"  border="0" cellspacing="5" cellpadding="5">';
							for($j=0;$j<$commentscount;$j++){
								$list .= '
									   <tr>
											<td width="5%" valign="top">'.($commentscount-$j).'</td>
											<td width="95%">'.nl2br(portalTicketEscape(isset($commentresult[$j]['comments']) ? $commentresult[$j]['comments'] : '')).'<br><span class="hdr">'.getTranslatedString('LBL_COMMENT_BY').' : '.portalTicketEscape(isset($commentresult[$j]['owner']) ? $commentresult[$j]['owner'] : '').' '.getTranslatedString('LBL_ON').' '.portalTicketEscape(isset($commentresult[$j]['createdtime']) ? $commentresult[$j]['createdtime'] : '').'</span></td>
									   </tr>';
							}
							$list .= '</table></div>';
				}
				
				if($ticket_status != 'Closed'){
					
					$list .= '<div class="row">
								<form name="comments" action="index.php" method="post">
									<input type="hidden" name="module">
									<input type="hidden" name="action">
									<input type="hidden" name="fun">
									<input type="hidden" name="ticketid" value="'.$ticketIdHtml.'">
									<div class="form-group col-sm-12 no-padding">
										<label class="col-sm-2 control-label no-padding-right">
											'.getTranslatedString('LBL_ADD_COMMENT').'
										</label>
										<div class="col-sm-10 dvtCellInfo" align="left" style = "background-color:none;">
											<textarea name="comments" style = "width:100%;"></textarea><br/><br/>
											<input class="btn btn-minier btn-success" title="'.getTranslatedString('LBL_SUBMIT').'" accesskey="S" class="small"  name="submit" value="'.getTranslatedString('LBL_SUBMIT').'" style="width: 100px;" type="submit" onclick="this.form.module.value=\'HelpDesk\';this.form.action.value=\'index\';this.form.fun.value=\'updatecomment\'; if(trim(this.form.comments.value) != \'\')	return true; else return false;"/>
										</div>
									</div>
								</form>
							</div>';
				}
				
				$list .= '</div></div></div></div>';
				
				$files_array = getTicketAttachmentsList($ticketid);
				$attachmentsEnabled = is_array($files_array)
					&& (!isset($files_array[0]) || $files_array[0] != "#MODULE INACTIVE#");
				$attachmentRecords = array();
				if ($attachmentsEnabled) {
					foreach ($files_array as $attachmentRecord) {
						if (is_array($attachmentRecord)) {
							$attachmentRecords[] = $attachmentRecord;
						}
					}
				}
				
				if($attachmentsEnabled){
					
					$list .= '<div class="widget-box">
								<div class = "widget-header">
									<h5 class = "widget-title">'.getTranslatedString('LBL_ATTACHMENTS').'</h5>
								</div>
								<div class = "widget-body">
									<div class="widget-main no-padding single-entity-view">
										<div style="width:auto;padding:12px;display:block;" id="tblLeadInformation">';
				
					$attachments_count = count($attachmentRecords);
					$z = 0;
				
					if($attachments_count > 0){
						
						for($j=0;$j<$attachments_count;$j++,$z++){
							
							$filename = isset($attachmentRecords[$j]['filename']) ? (string) $attachmentRecords[$j]['filename'] : '';
							$filetype = isset($attachmentRecords[$j]['filetype']) ? (string) $attachmentRecords[$j]['filetype'] : 'application/octet-stream';
							$filesize = isset($attachmentRecords[$j]['filesize']) ? (int) $attachmentRecords[$j]['filesize'] : 0;
							$fileid = isset($attachmentRecords[$j]['fileid']) ? (string) $attachmentRecords[$j]['fileid'] : '';
							$filelocationtype = isset($attachmentRecords[$j]['filelocationtype']) ? (string) $attachmentRecords[$j]['filelocationtype'] : '';
							$attachments_title = '';
							
							if($j == 0)
								$attachments_title = getTranslatedString('LBL_ATTACHMENTS');
							
							if($filelocationtype == 'I'){
								$downloadQuery = http_build_query(array(
									'downloadfile' => 'true',
									'fileid' => $fileid,
									'filename' => $filename,
									'filetype' => $filetype,
									'filesize' => $filesize,
									'ticketid' => $ticketid,
								), '', '&', PHP_QUERY_RFC3986);
								$displayFilename = basename(str_replace('\\', '/', $filename));
								$ticketPrefix = $ticketid . '_';
								if ($ticketPrefix !== '_' && strpos($displayFilename, $ticketPrefix) === 0) {
									$displayFilename = substr($displayFilename, strlen($ticketPrefix));
								}
								if($z==0 || $z%2==0) {
									$list .= '<div class = "row">';
								}
								$list .= '
										<div class="form-group col-sm-6">
											<label class="col-sm-3 control-label no-padding-right">
												'.$attachments_title.
											'</label>
											<div class="col-sm-9 dvtCellInfo" align="left" valign="top">
											<a href="index.php?'.portalTicketEscape($downloadQuery).'">'.portalTicketEscape($displayFilename).'</a>
											</div>
										</div>';
								
								if($z%2 == 1 ||($j == ($attachments_count-1) ))
									$list .= '</div>';
									
								} else {
									$externalUrl = filter_var($filename, FILTER_VALIDATE_URL);
									$externalScheme = $externalUrl ? strtolower((string) parse_url($externalUrl, PHP_URL_SCHEME)) : '';
									$externalLink = portalTicketEscape($filename);
									if ($externalUrl && in_array($externalScheme, array('http', 'https'), true)) {
										$externalLink = '<a target="_blank" rel="noopener noreferrer" href="'.portalTicketEscape($externalUrl).'">'.portalTicketEscape($filename).'</a>';
									}
									$list .= '<div class = "row">
										<div class="form-group col-sm-6">
											<label class="col-sm-3 control-label no-padding-right">
												'.$attachments_title.
											'</label>
											<div class="col-sm-9 dvtCellInfo" align="left" valign="top">
											&nbsp;
											'.$externalLink.'
											</div>
										</div>
									</div>';
								}
							}
					} else{
						$list .= '<div class = "row">'.getTranslatedString('NO_ATTACHMENTS').'</div>';
					}
				}
				
				//To display the file upload error
				if($upload_status != ''){
					$list .= '<div class = "row">
							<b>'.getTranslatedString('LBL_FILE_UPLOADERROR').'</b>
							<font color="red">'.portalTicketEscape($upload_status).'</font>
						   </div>';
				}

				//Provide the Add Comment option if the ticket is not Closed
				if($ticket_status != 'Closed' && $attachmentsEnabled){
					
					$list .= '<div class="row">
							<form name="fileattachment" method="post" enctype="multipart/form-data" action="index.php">
							<input type="hidden" name="module" value="HelpDesk">
							<input type="hidden" name="action" value="index">
							<input type="hidden" name="fun" value="uploadfile">
							<input type="hidden" name="ticketid" value="'.$ticketIdHtml.'">
						
									<div class="form-group col-sm-6">
											<label class="col-sm-3 control-label no-padding-right">
												'.getTranslatedString('LBL_ATTACH_FILE').
											'</label>
											<div class="col-sm-9 dvtCellInfo" align="left" valign="top">
												<input type="file" size="50" name="customerfile" class="detailedViewTextBox" onchange="validateFilename(this)" />
											<input type="hidden" name="customerfile_hidden"/>
											<br/><br/>
											<input class="tn btn-minier btn-success" name="Attach" type="submit" value="'.getTranslatedString('LBL_ATTACH').'">
										</div>
										</div>
										
										<div class="form-group col-sm-6">
											<label class="col-sm-3 control-label no-padding-right">
											&nbsp;	
											</label>
										</div>
									</form>
							</div>';
				}
			$list .= '</div></div></div></div>';
			echo $list;
		} else {
			echo getTranslatedString('LBL_NONE_SUBMITTED');
		}

$filevalidation_script = <<<JSFILEVALIDATION
<script type="text/javascript">
                
function getFileNameOnly(filename) {
	var onlyfilename = filename;
  	// Normalize the path (to make sure we use the same path separator)
 	var filename_normalized = filename.replace(/\\\\/g, '/');
  	if(filename_normalized.lastIndexOf("/") != -1) {
    	onlyfilename = filename_normalized.substring(filename_normalized.lastIndexOf("/") + 1);
  	}
  	return onlyfilename;
}
/* Function to validate the filename */
function validateFilename(form_ele) {
if (form_ele.value == '') return true;
	var value = getFileNameOnly(form_ele.value);
	// Color highlighting logic
	var err_bg_color = "#FFAA22";
	if (typeof(form_ele.bgcolor) == "undefined") {
		form_ele.bgcolor = form_ele.style.backgroundColor;
	}
	// Validation starts here
	var valid = true;
	/* Filename length is constrained to 255 at database level */
	if (value.length > 255) {
		alert(alert_arr.LBL_FILENAME_LENGTH_EXCEED_ERR);
		valid = false;
	}
	if (!valid) {
		form_ele.style.backgroundColor = err_bg_color;
		return false;
	}
	form_ele.style.backgroundColor = form_ele.bgcolor;
	form_ele.form[form_ele.name + '_hidden'].value = value;
	return true;
}
</script>
JSFILEVALIDATION;

echo $filevalidation_script;
?>

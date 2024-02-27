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
		global $result;
		global $client;		
		global $Server_Path;
		
		$customerid = $_SESSION['customer_id'];	
		$sessionid = $_SESSION['customer_sessionid'];
		
		if($ticketid != ''){
			$params = array('id' => "$ticketid", 'block'=>"$block",'contactid'=>$customerid,'sessionid'=>"$sessionid");
			$result = $client->call('get_details', $params, $Server_Path, $Server_Path);	
			
			// Check for Authorization
			if (count($result) == 1 && $result[0] == "#NOT AUTHORIZED#") {
				echo 	'<div class = "alert"><b>'.getTranslatedString('LBL_NOT_AUTHORISED').'</b></div>';
				include("footer.html");
				die();
			}
			
			$ticketinfo = $result[0][$block];
			
			$params = Array(Array('id'=>"$customerid", 'sessionid'=>"$sessionid", 'ticketid' => "$ticketid"));
			
			$commentresult = $client->call('get_ticket_comments', $params, $Server_Path, $Server_Path);
			
			$ticketscount = count($result);
			
			$commentscount = count($commentresult);
			
			$params = Array(Array('id'=>"$customerid", 'sessionid'=>"$sessionid", 'ticketid' => "$ticketid"));
		
			//Get the creator of this ticket
			$creator = $client->call('get_ticket_creator', $params, $Server_Path, $Server_Path);

			$ticket_status = '';
			foreach($ticketinfo as $key=>$value) {
				$fieldlabel = $value['fieldlabel'];
				$fieldvalue = $value['fieldvalue'];
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
							<input type="hidden" name="ticketid" value="'.$ticketid.'">
                                                        <input class="btn btn-primary" name="closed" type="submit" value="'.$ticket_close_link.'">
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
					$blockname = $ticketinfo[$i]['blockname'];
					
					$data = $ticketinfo[$i]['fieldvalue'];
						
					if($ticketinfo[$i]['fieldlabel'] == 'Note'){
						$data = html_entity_decode($data);
					}
					
					if($data =='')
						$data ='&nbsp;';
						
						if(strcmp($blockname,$ticketinfo[$i-1]['blockname'])){
							
							if($z > 0 && ($z % 2) == 1)
								echo "</div>";
								
							if($blockname != 'Ticket Information'){
								echo '</div></div></div></div>
								<div class="widget-box">
									<div class = "widget-header">
										<h5 class = "widget-title">'. $blockname . '</h5>
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
											'.getTranslatedString($ticketinfo[$i][fieldlabel]).
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
											<td width="95%">'.$commentresult[$j]['comments'].'<br><span class="hdr">'.getTranslatedString('LBL_COMMENT_BY').' : '.$commentresult[$j]['owner'].' '.getTranslatedString('LBL_ON').' '.$commentresult[$j]['createdtime'].'</span></td>
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
									<input type="hidden" name="ticketid" value="'.$ticketid.'">
									<div class="form-group col-sm-12 no-padding">
										<label class="col-sm-2 control-label no-padding-right">
											Add Comment
										</label>
										<div class="col-sm-10 dvtCellInfo" align="left" style = "background-color:none;">
											<textarea name="comments" style = "width:100%;"></textarea><br/><br/>
											<input class="btn btn-minier btn-success" title="'.getTranslatedString('LBL_SUBMIT').'" accesskey="S" class="small"  name="submit" value="'.getTranslatedString('LBL_SUBMIT').'" style="width: 70px;" type="submit" onclick="this.form.module.value=\'HelpDesk\';this.form.action.value=\'index\';this.form.fun.value=\'updatecomment\'; if(trim(this.form.comments.value) != \'\')	return true; else return false;"/>
										</div>
									</div>
								</form>
							</div>';
				}
				
				$list .= '</div></div></div></div>';
				
				$files_array = getTicketAttachmentsList($ticketid);
				
				if($files_array[0] != "#MODULE INACTIVE#"){
					
					$list .= '<div class="widget-box">
								<div class = "widget-header">
									<h5 class = "widget-title">'.getTranslatedString('LBL_ATTACHMENTS').'</h5>
								</div>
								<div class = "widget-body">
									<div class="widget-main no-padding single-entity-view">
										<div style="width:auto;padding:12px;display:block;" id="tblLeadInformation">';
				
					$attachments_count = count($files_array);
					$z = 0;
				
					if(is_array($files_array)){
						
						for($j=0;$j<$attachments_count;$j++,$z++){
							
							$filename = $files_array[$j]['filename'];
							$filetype = $files_array[$j]['filetype'];
							$filesize = $files_array[$j]['filesize'];
							$fileid = $files_array[$j]['fileid'];
							$filelocationtype = $files_array[$j]['filelocationtype'];
							$attachments_title = '';
							
							if($j == 0)
								$attachments_title = getTranslatedString('LBL_ATTACHMENTS');
							
							if($filelocationtype == 'I'){
								if($z==0 || $z%2==0) {
									$list .= '<div class = "row">';
								}
								$list .= '
										<div class="form-group col-sm-6">
											<label class="col-sm-3 control-label no-padding-right">
												'.$attachments_title.
											'</label>
											<div class="col-sm-9 dvtCellInfo" align="left" valign="top">
												<a href="index.php?downloadfile=true&fileid='.$fileid.'&filename='.$filename.'&filetype='.$filetype.'&filesize='.$filesize.'&ticketid='.$ticketid.'">'.ltrim($filename,$ticketid.'_').'</a>
											</div>
										</div>';
								
								if($z%2 == 1 ||($j == ($attachments_count-1) ))
									$list .= '</div>';
									
								} else {
									$list .= '<div class = "row">
										<div class="form-group col-sm-6">
											<label class="col-sm-3 control-label no-padding-right">
												'.$attachments_title.
											'</label>
											<div class="col-sm-9 dvtCellInfo" align="left" valign="top">
											&nbsp;
												<a target="blank" href='.$filename.'>'.$filename.'</a>
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
							<font color="red">'.$upload_status.'</font>
						   </div>';
				}

				//Provide the Add Comment option if the ticket is not Closed
				if($ticket_status != 'Closed' && $files_array[0] != "#MODULE INACTIVE#"){
					
					$list .= '<div class="row">
							<form name="fileattachment" method="post" enctype="multipart/form-data" action="index.php">
							<input type="hidden" name="module" value="HelpDesk">
							<input type="hidden" name="action" value="index">
							<input type="hidden" name="fun" value="uploadfile">
							<input type="hidden" name="ticketid" value="'.$ticketid.'">
						
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

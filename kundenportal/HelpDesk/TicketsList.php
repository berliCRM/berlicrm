<?php

	$onlymine=$_REQUEST['onlymine'];
	if($onlymine == 'true') {
    	$mine_selected = 'selected';
    	$all_selected = '';
	} else {
    	$mine_selected = '';
    	$all_selected = 'selected';
	}

?>
<aside class="right-side">
	<section class="content-header" style="box-shadow:none;">
		<div class="row-pad">
			
			<div class="col-sm-10">
					<div class="btn-group">
						<button type="button" class="btn btn-default dropdown-toggle ticket_status_btn" data-toggle="dropdown">
                            Status <span class="caret"></span>
						</button>
						<ul class="dropdown-menu ticket_status">
							<li><a href="#"><?php echo getTranslatedString('LBL_ALL'); ?></a></li>
							<?php
								$temp_array = getPicklist('ticketstatus');
								foreach($temp_array as $index => $val){
									$select = '';
									if($val == $selectedvalue)
										$select = ' selected';
									 echo '<li><a href = "#">'. getTranslatedString($val).'</a></li>';
								}
							?>
						</ul>
					</div>
					<?PHP 
						$show = $client->call('show_all',array('module'=>'HelpDesk'), $Server_Path, $Server_Path);
						if($show == 'true'){
					?>
							<div class="btn-group">
								<button type="button" class="btn btn-default dropdown-toggle ticket_owner_btn" data-toggle="dropdown">
									Show <span class="caret"></span>
								</button>
								<ul class="dropdown-menu ticket_owner">
									<li><a href="#"><?php echo getTranslatedString('MINE'); ?></a></li>
									<li><a href="#"><?php echo getTranslatedString('ALL'); ?></a></li>
								</ul>
							</div>
					<?php
						}
					?>
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
	<section class="content">
		<div class="row">
			<div class="col-xs-12">
				<?PHP
					global $result;
					$list = '';
					$closedlist = '';
				
					$list .= '<tr><td>';
	
					if($result == '') {
						$list .= '<tr"><td>';
						$list .= '<table class="table table-hover">';
						$list .= '<div class="box-header">';
						$list .= '<tr><td>'.getTranslatedString('LBL_NONE_SUBMITTED').'</td></tr></table>';
						$list .= '</tr></td>';
					} else {
					
						$header = $result[0]['head'][0];
						$nooffields = count($header);
						$data = $result[1]['data'];
						$rowcount = count($data);
						$showstatus = $_REQUEST['showstatus'];
						if($showstatus != '' && $rowcount >= 1) {
							$list .= '<div class="box">';
							$list .= '<div class="box-header">';
							$list .= '<h3 class="box-title" style="font-size:24px;">'.getTranslatedString($showstatus)." ".getTranslatedString('LBL_TICKETS').' </h3></div>';
							$list .= '<div class="box-body table-responsive no-padding"><table class="table table-hover">';
							$list .= '<tbody><tr>';
					
							for($i=0; $i<$nooffields; $i++)
							{
								$header_value = $header[$i]['fielddata'];
								$list .= '<th>'.$header_value.'</th>';
							}
							$list .= '</tr>';
					
							$ticketexist = 0;
							for($i=0;$i<count($data);$i++)
							{		
								$ticketlist = '';
						
								if ($i%2==0)
									$ticketlist .= '<tr>';
								else
									$ticketlist .= '<tr>';
							
								$ticket_status = '';
								for($j=0; $j<$nooffields; $j++) {			
									$ticketlist .= '<td>'.getTranslatedString($data[$i][$j]['fielddata']).'</td>';
									if ($header[$j]['fielddata'] == 'Status') {
										$ticket_status = $data[$i][$j]['fielddata'];
									}
								}
								$ticketlist .= '</tr>';
					
								if($ticket_status == $showstatus){
									$list .= $ticketlist; 
									$ticketexist++;
								}		
							}
							if($ticketexist == 0)
							{
								$list .= '<tr><td>'.getTranslatedString('LBL_NONE_SUBMITTED').'</td><td><td><td><td><td><td></td></td></td></td></td></td></tr>';
							}
						
							$list .= '</table>';
						
						}
						else {
							$list .= '<div class="box">';
							$list .= '<div class="box-header">';
							$list .= '<h3 class="box-title" style="font-size:24px;">'.getTranslatedString('LBL_MY_OPEN_TICKETS').'</h3></div>';
							$list .= '<div class="box-body table-responsive no-padding"><table class="table table-hover">';
							$list .= '<tbody><tr>';
						
							$closedlist .= '<div class="box">';
							$closedlist .= '<div class="box-header">';
							$closedlist .= '<h3 class="box-title" style="font-size:24px;">'.getTranslatedString('LBL_CLOSED_TICKETS').'</h3></div>';
							$closedlist .= '<div class="box-body table-responsive no-padding"><table class="table table-hover">';
							$closedlist .= '<tbody><tr>';
							
							for($i=0; $i<$nooffields; $i++)
							{
								$header_value = $header[$i]['fielddata'];
								$headerlist .= '<th>'.getTranslatedString($header_value).'</th>';
							}
							$headerlist .= '</tr>';
							
							$list .= $headerlist;
							$closedlist .= $headerlist;
						
							for($i=0;$i<count($data);$i++)
							{
								$ticketlist = '';
								
								if ($i%2==0)
									$ticketlist .= '<tr>';
								else
									$ticketlist .= '<tr>';
								
								$ticket_status = '';
								for($j=0; $j<$nooffields; $j++) {		
									$ticketlist .= '<td>'.$data[$i][$j]['fielddata'].'</td>';
									if ($header[$j]['fielddata'] == 'Status') {
										$ticket_status = $data[$i][$j]['fielddata'];
									}
								}
								$ticketlist .= '</tr>';
						
								if($ticket_status == getTranslatedString('LBL_STATUS_CLOSED'))
									$closedlist .= $ticketlist;
								elseif($ticket_status != '')
									$list .= $ticketlist;
							}	
						
							$list .= '</table>';
							$closedlist .= '</table></div>';
						
							$closedlist .= '</div></td></tr>';
						
							$list .= '</div></div><br><br>'.$closedlist;
						}
					}
					echo $list;
				?>
			</div>
		</div>
	</section>
</aside>
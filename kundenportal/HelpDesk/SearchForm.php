<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
?>		
<div class="modal-dialog">
<div id="" class="model-content" style="background-color:#FFFFFF;border: 3px solid #CCCCCC;">
	<div class="widget-box no-margin">
		<div class="widget-header">
            <h5 class="widget-title">Search Ticket</h5>
            <div class="widget-toolbar" style="float:right;padding:12px 10px;">
       			<a href="javascript:closeSearchFormNow('tabSrch');">
       				<i class="ace-icon fa fa-times" style="font-size:20px;"></i>
       			</a>            
			</div>
		</div><!--.widget-header-->
		    
		<div class="widget-body">
        	<div class="widget-main clearfix" style="padding:12px;>
				<table  cellpadding="5" cellspacing="0" width="95%" border="0" align="center">
					<tbody>
						<tr>
							<td class="small">
								<table border="0" cellspacing="0" cellpadding="0" width="95%" align="center" bgcolor="white">
									<tbody>
										<tr>
											<td colspan="4" class="detailedViewHeader" style="background:#dddcdd url(images/inner.gif) bottom repeat-x;border:1px solid #DDDDDD;padding:12px;color:#000000;">
												<label>
													<span class="lbl"><b><?PHP echo getTranslatedString('TICKETID');?></b></span>
												</label>
												<input name="search_ticketid" type="text" class="form-control" value="">
											</td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
						<tr>
							<td class="small">
								<table border="0" cellspacing="0" cellpadding="0" width="95%" align="center" bgcolor="white">
									<tbody>
										<tr>
											<td colspan="4" class="detailedViewHeader" style="background:#dddcdd url(images/inner.gif) bottom repeat-x;border:1px solid #DDDDDD;padding:12px;color:#000000;">
												<label>
													<span class="lbl"><b><?PHP echo getTranslatedString('TICKET_TITLE');?></b></span>
												</label>
												<input name="search_title" type="text" class="form-control" value="">
											</td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
						<tr>
							<td class="small">
								<table border="0" cellspacing="0" cellpadding="0" width="95%" align="center" bgcolor="white">
									<tbody>
										<tr>
											<td colspan="4" class="detailedViewHeader" style="background:#dddcdd url(images/inner.gif) bottom repeat-x;border:1px solid #DDDDDD;padding:12px;color:#000000;">
												<label>
													<span class="lbl"><b><?PHP echo getTranslatedString('TICKET_STATUS');?></b></span>
												</label>
												<?php
													$status_array = getPicklist('ticketstatus');
													echo getComboList('search_ticketstatus',$status_array,' ');
												?>
											</td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
						<tr>
							<td class="small">
								<table border="0" cellspacing="0" cellpadding="0" width="95%" align="center" bgcolor="white">
									<tbody>
										<tr>
											<td colspan="4" class="detailedViewHeader" style="background:#dddcdd url(images/inner.gif) bottom repeat-x;border:1px solid #DDDDDD;padding:12px;color:#000000;">
												<label>
													<span class="lbl"><b><?PHP echo getTranslatedString('TICKET_PRIORITY');?></b></span>
												</label>
												<?php
													$priority_array = getPicklist('ticketpriorities');
													echo getComboList('search_ticketpriority',$priority_array,' ');
												?>
											</td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
						<tr>
							<td class="small">
								<table border="0" cellspacing="0" cellpadding="0" width="95%" align="center" bgcolor="white">
									<tbody>
										<tr>
											<td colspan="4" class="detailedViewHeader" style="background:#dddcdd url(images/inner.gif) bottom repeat-x;border:1px solid #DDDDDD;padding:12px;color:#000000;">
												<label>
													<span class="lbl"><b><?PHP echo getTranslatedString('TICKET_CATEGORY');?></b></span>
												</label>
												<?php
													$category_array = getPicklist('ticketcategories');
													echo getComboList('search_ticketcategory',$category_array,' ');
												?>
											</td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
						<tr>
							<td class="small">
								<table border="0" cellspacing="0" cellpadding="0" width="95%" align="center" bgcolor="white">
									<tbody>
										<tr>
											<td colspan="4" class="detailedViewHeader" style="background:#dddcdd url(images/inner.gif) bottom repeat-x;border:1px solid #DDDDDD;padding:12px;color:#000000;">
												<label>
													<span class="lbl"><b><?PHP echo getTranslatedString('TICKET_MATCH');?></b></span>
												</label>
												<select name="search_match" class="form-control">
													<option value="all"><?php echo getTranslatedString('LBL_ALL'); ?></option>
													<option value="any"><?php echo getTranslatedString('LBL_ANY'); ?></option>
												</select>
											</td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>		
					</tbody>
				</table> 
				<div class="col-xs-12 well-sm text-center">
					<input name="Search" type="submit" value="<?php echo getTranslatedString('LBL_SEARCH'); ?>" class="btn btn-primary" onclick="this.form.module.value='HelpDesk';this.form.action.value='index';this.form.fun.value='search'">
				</div>
			</div>
		</div>
	</div>
</div>
</div>
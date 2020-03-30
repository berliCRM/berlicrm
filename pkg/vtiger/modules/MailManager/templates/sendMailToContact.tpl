{*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ('License'); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************}
{strip}
<div id="add_mail_to_contact" name="add_mail_to_contact" class="modelContainer">
<div class="modal-header contentsBackground">
	<button class="close" aria-hidden="true" data-dismiss="modal" type="button" title="Schließen">×</button>
	<h3>{vtranslate('LBL_COPY_MAIL_TO_OTHER','MailManager')}</h3>
</div>
<div class="modal-body tabbable" style="padding:0px">
	<div class="tab-content overflowVisible">
		<div style="margin:5px">
			<table class="massEditTable table table-bordered">
			<tr>
				<td valign="top" width="5%">   </td>
				<td class="showPanelBg" width="95%" valign="top">
					<div class="small" style="padding:0 10px">
						<span class="lvtHeaderText">{vtranslate('LBL_HEADLINE_CONTACTSEL2','MailManager')}</span>
						<br>
						<hr size="1" noshade="">
						<p>{vtranslate('LBL_SEARCH_LASTNAME','MailManager')}</p>
						<form name="ContactSearch">
							<input id="searchcontactid" name="searchcontactid" value="" type="hidden">
							<div class="ui-widget">
                                <input id="searchlastname" class="detailedViewTextBox" value="" onfocus="this.className='detailedViewTextBoxOn'" onblur="this.className='detailedViewTextBox'" type="text">
							</div>
						</form>
					</div>
					<div class="small" style="padding:10px 10px 0 10px">
						<p>{vtranslate('LBL_SHOW_LASTNAME','MailManager')}</p>
						<form name="ContactOptions" id="ContactOptions">
							<select id="selectedcontact" class="contactdrop" size=4 style="width:650px;line-height:18px">
							{foreach item=contactdata key=contactid from=$CONTACTS}
								<option value="{$contactid}">{$contactdata.lastname}, {$contactdata.firstname}, {$contactdata.email}, {$contactdata.contact_no}</option>		
							{/foreach}
							<input type="hidden" class="small" id="_foldername" name="_foldername" value="{$FOLDER}">
							<input type="hidden" class="small" id="_msgnumber" name="_msgnumber" value="{$MSGNO}">
							<input type="hidden" class="small" name="_mlinktotype" value="'MailManager'">
							<input type="hidden" class="small" name="_mlinkto" value="{$PARENT}">
						</form>
					</div>
				</td>
			</tr>	
			</table>
		</div>
		<div class="modal-footer quickCreateActions">
			<a class="cancelLink cancelLinkContainer pull-right" type="reset" data-dismiss="modal">{vtranslate('LBL_Cancel','MailManager')}</a>
			<button class="btn btn-success" id="savetocontact" type="text"><strong>{vtranslate('LBL_SAVE_EMAIL','MailManager')}</strong></button>
		</div>
	</div>
</div>
</div>
{/strip}
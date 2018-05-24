{*<!--/************************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/-->*}

{strip}
<div class="editViewContainer">
	<form action="javascript:void(0);" method="POST" id="EditView">
		<div class="widget_header row-fluid">
			<h3>{vtranslate('JSLBL_Settings',$MODULE)}</h3>
		</div>
		<hr>
		<table class="table table-bordered blockContainer showInlineTable">
			<thead>
				<tr>
					<th class="blockHeader" colspan="4">
						<strong>{vtranslate('LBL_CREATE_MAILBOX', $MODULE)}</strong>
					</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td class="fieldLabel">
						<label class="muted pull-right marginRight10px">{vtranslate('LBL_SELECT_ACCOUNT_TYPE',$MODULE)}</label>
					</td>
					<td class="fieldValue narrowWidthType" style="width: 70%;">
						<select id="_mbox_helper" class="chzn-select small" onchange="MailManager.handle_settings_confighelper(this);">
							<option value=''>{vtranslate('JSLBL_Choose_Server_Type',$MODULE)}</option>
							<option value='gmail' {if $SERVERNAME eq 'gmail'} selected {/if}>{vtranslate('JSLBL_Gmail',$MODULE)}</option>
							<option value='yahoo' {if $SERVERNAME eq 'yahoo'} selected {/if}>{vtranslate('JSLBL_Yahoo',$MODULE)}</option>
							<option value='fastmail' {if $SERVERNAME eq 'fastmail'} selected {/if}>{vtranslate('JSLBL_Fastmail',$MODULE)}</option>
							<option value='other' {if $SERVERNAME eq 'other'} selected {/if}>{vtranslate('JSLBL_Other',$MODULE)}</option>
						</select>
					</td>
				</tr>

				<tr class="settings_details" {if $SERVERNAME eq ''} style="display:none;"{/if}>
					<td class="fieldLabel" nowrap>
						<label class="muted pull-right marginRight10px"><font color="red">*</font> {vtranslate('LBL_Mail_Server',$MODULE)}</label>
					</td>
					<td class="fieldValue narrowWidthType" nowrap style="width: 70%;">
						<input name="_mbox_server" value="{$MAILBOX->server()}" data-validation-engine="validate[required]]" type="text" class="detailedViewTextBox" onblur="this.className='detailedViewTextBox'" onfocus="this.className='detailedViewTextBoxOn'" placeholder="mail.company.com or 192.168.X.X">
					</td>
				</tr>
				<tr class="settings_details" {if $SERVERNAME eq ''} style="display:none;"{/if}>
					<td class="fieldLabel" nowrap>
						<label class="muted pull-right marginRight10px"><font color="red">*</font> {vtranslate('LBL_Username',$MODULE)}</label>
					</td>
					<td class="fieldValue narrowWidthType" nowrap>
						<input name="_mbox_user" id="_mbox_user" onkeypress="MailManager.showSelectFolderDesc();" value="{$MAILBOX->username()}" type="text" class="detailedViewTextBox" onblur="this.className='detailedViewTextBox'" onfocus="this.className='detailedViewTextBoxOn'" placeholder="{vtranslate('LBL_Your_Mailbox_Account',$MODULE)}">
					</td>
				</tr>
				<tr class="settings_details" {if $SERVERNAME eq ''} style="display:none;"{/if}>
					<td class="fieldLabel" nowrap>
						<label class="muted pull-right marginRight10px"><font color="red">*</font> {vtranslate('LBL_Password',$MODULE)}</label>
					</td>
					<td class="fieldValue narrowWidthType" nowrap>
						<input name="_mbox_pwd" id="_mbox_pwd" value="{$MAILBOX->password()}" type="password" class="detailedViewTextBox" onblur="this.className='detailedViewTextBox'" onfocus="this.className='detailedViewTextBoxOn'" placeholder="{vtranslate('LBL_Account_Password',$MODULE)}">
					</td>
				</tr>
				<tr class="additional_settings" {if $SERVERNAME neq 'other'} style="display:none;" {/if}>
					<td class="fieldLabel" nowrap>
						<label class="muted pull-right marginRight10px">{vtranslate('LBL_Protocol',$MODULE)}</label>
					</td>
					<td class="fieldValue narrowWidthType" nowrap style="width: 70%;">
						<input type="radio" name="_mbox_protocol" value="IMAP2" {if strcasecmp($MAILBOX->protocol(), 'imap2')===0}checked=true{/if}> {vtranslate('LBL_Imap2',$MODULE)}
						<input type="radio" name="_mbox_protocol" value="IMAP4" {if strcasecmp($MAILBOX->protocol(), 'imap4')===0}checked=true{/if}> {vtranslate('LBL_Imap4',$MODULE)}
					</td>
				</tr>
				<tr class="additional_settings" {if $SERVERNAME neq 'other'} style="display:none;" {/if}>
					<td class="fieldLabel" nowrap>
						<label class="muted pull-right marginRight10px">{vtranslate('LBL_SSL_Options',$MODULE)}</label>
					</td>
					<td class="fieldValue narrowWidthType" nowrap>
						<input type="radio" name="_mbox_ssltype" value="notls" {if strcasecmp($MAILBOX->ssltype(), 'notls')===0}checked=true{/if}> {vtranslate('LBL_No_TLS',$MODULE)}
						<input type="radio" name="_mbox_ssltype" value="tls" {if strcasecmp($MAILBOX->ssltype(), 'tls')===0}checked=true{/if}> {vtranslate('LBL_TLS',$MODULE)}
						<input type="radio" name="_mbox_ssltype" value="ssl" {if strcasecmp($MAILBOX->ssltype(), 'ssl')===0}checked=true{/if}> {vtranslate('LBL_SSL',$MODULE)}
					</td>
				</tr>
				<tr class="additional_settings" {if $SERVERNAME neq 'other'} style="display:none;" {/if}>
					<td class="fieldLabel" nowrap>
						<label class="muted pull-right marginRight10px">{vtranslate('LBL_Certificate_Validations',$MODULE)}</label>
					</td>
					<td class="fieldValue narrowWidthType" nowrap>
						<input type="radio" name="_mbox_certvalidate" value="validate-cert" {if strcasecmp($MAILBOX->certvalidate(), 'validate-cert')===0}checked=true{/if} > {vtranslate('LBL_Validate_Cert',$MODULE)}
						<input type="radio" name="_mbox_certvalidate" value="novalidate-cert" {if strcasecmp($MAILBOX->certvalidate(), 'novalidate-cert')===0}checked=true{/if}> {vtranslate('LBL_Do_Not_Validate_Cert',$MODULE)}
					</td>
				</tr>

				<tr class="refresh_settings" {if $MAILBOX && $MAILBOX->exists()}{else} style="display:none;" {/if}>
					<td class="fieldLabel" nowrap>
						<label class="muted pull-right marginRight10px">{vtranslate('LBL_REFRESH_TIME',$MODULE)}</label>
					</td>
					<td class="fieldValue narrowWidthType" nowrap  style="width: 70%;">
						<select name="_mbox_refresh_timeout">
							<option value="" {if $MAILBOX->refreshTimeOut() eq ''}selected{/if}>{vtranslate('LBL_NONE',$MODULE)}</option>
							<option value="300000" {if strcasecmp($MAILBOX->refreshTimeOut(), '300000')==0}selected{/if}>{vtranslate('LBL_5_MIN',$MODULE)}</option>
							<option value="600000" {if strcasecmp($MAILBOX->refreshTimeOut(), '600000')==0}selected{/if}>{vtranslate('LBL_10_MIN',$MODULE)}</option>
						</select>
					</td>
				</tr>

				<tr class="settings_details" {if $SERVERNAME eq ''} style="display:none;"{/if}>
					<td class="fieldLabel" nowrap>
						<label class="muted pull-right marginRight10px">{vtranslate('LBL_CHOOSE_EXISTING_FOLDER',$MODULE)}</label>
					</td>
					<td class="fieldValue narrowWidthType selectFolderValue {if $MAILBOX->folder() eq ''}hide{/if}" nowrap style="width: 70%;">
						<select name="_mbox_sent_folder">
							{foreach item=FOLDER from=$FOLDERS}
								<option value="{$FOLDER->name()}" {if $FOLDER->name() eq $MAILBOX->folder()} selected {/if}>{$FOLDER->name()}</option>
							{/foreach}
						</select>
						<span class="mm_blur"> {vtranslate('LBL_CHOOSE_FOLDER',$MODULE)}</span>
                    </td>
					<td class="fieldValue narrowWidthType selectFolderDesc alert alert-info {if $MAILBOX->folder() neq ''}hide{/if}" nowrap style="width: 70%;">
						{vtranslate('LBL_CHOOSE_FOLDER_DESC',$MODULE)}
					</td>
				</tr>
			</tbody>
		</table>
		<br>

		<div class="row-fluid refresh_settings" {if $MAILBOX && $MAILBOX->exists()}{else}style="display:none;" {/if}>
			<div class="pull-right">
				<button class="btn btn-success" onclick="MailManager.save_settings(this.form);"><strong>{vtranslate('LBL_SAVE_BUTTON_LABEL',$MODULE)}</strong></button>
				{if $MAILBOX && $MAILBOX->exists()}
					<a href="javascript:;" class="cancelLink" onclick="MailManager.close_settings();">{vtranslate('LBL_CANCEL_BUTTON_LABEL',$MODULE)}</button>
					{/if}
			</div>
		</div>
	</form>
</div>
{/strip}
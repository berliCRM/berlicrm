{*<!--/************************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/-->*}

{strip}
<div class="detailViewContainer">
    <br>
	<form action="javascript:void(0);" method="POST" id="EditView">
        <div class="row-fluid">
            <div class="span6">
                <h3 class="title widgetTextOverflowEllipsis">{vtranslate('JSLBL_Settings',$MODULE)}</h3>
            </div>

            {if $MAILBOX && $MAILBOX->exists()}
            <div class="span6">
                <div class="pull-right">
                    <button class="btn edit" onclick="MailManager.open_settings()"><strong>{vtranslate('LBL_EDIT',$MODULE)}</strong></button>&nbsp;
                    <button class="btn btn-danger" onclick="MailManager.remove_settings(this.form);"><strong>{vtranslate('LBL_DELETE_Mailbox',$MODULE)}</strong></button>
                </div>
            </div>
            {/if}
        </div>
		<hr>
		{if $MAILBOX && $MAILBOX->exists()}
			<table class="table table-bordered blockContainer showInlineTable">
				<thead>
					<tr>
						<th class="blockHeader" colspan="4">{vtranslate('LBL_MAILBOX_DETAILS', $MODULE)}</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td class="fieldLabel">
							<label class="muted pull-right">{vtranslate('LBL_ACCOUNT_TYPE',$MODULE)}</label>
						</td>
						<td class="fieldValue narrowWidthType" style="width: 70%;">
							<div>
								{if $SERVERNAME eq 'gmail'}
									{vtranslate('JSLBL_Gmail',$MODULE)}
								{else if $SERVERNAME eq 'yahoo'}
									{vtranslate('JSLBL_Yahoo',$MODULE)}
								{else if $SERVERNAME eq 'fastmail'}
									{vtranslate('JSLBL_Fastmail',$MODULE)}
								{else if $SERVERNAME eq 'other'}
									{vtranslate('JSLBL_Other',$MODULE)}
								{/if}
							</div>
						</td>
					</tr>
					<tr>
						<td class="fieldLabel">
							<label class="muted pull-right">{vtranslate('LBL_Mail_Server',$MODULE)}</label>
						</td>
						<td class="fieldValue narrowWidthType" style="width: 70%;">
							<div>{$MAILBOX->server()}</div>
						</td>
					</tr>
					<tr>
						<td class="fieldLabel">
							<label class="muted pull-right">{vtranslate('LBL_Username',$MODULE)}</label>
						</td>
						<td class="fieldValue narrowWidthType" style="width: 70%;">
							<div>{$MAILBOX->username()}</div>
						</td>
					</tr>
					<tr>
						<td class="fieldLabel">
							<label class="muted pull-right">{vtranslate('LBL_REFRESH_TIME',$MODULE)}</label>
						</td>
						<td class="fieldValue narrowWidthType" style="width: 70%;">
							<div>
								{if strcasecmp($MAILBOX->refreshTimeOut(), '300000')==0}
									{vtranslate('LBL_5_MIN',$MODULE)}
								{else if strcasecmp($MAILBOX->refreshTimeOut(), '600000')==0}
									{vtranslate('LBL_10_MIN',$MODULE)}
								{else}
									{vtranslate('LBL_NONE',$MODULE)}
								{/if}
							</div>
						</td>
					</tr>
                    <tr>
						<td class="fieldLabel">
							<label class="muted pull-right">{vtranslate('LBL_SELECTED_FOLDER',$MODULE)}</label>
						</td>
						<td class="fieldValue narrowWidthType" style="width: 70%;">
							<div>{$MAILBOX->folder()}</div>
						</td>
					</tr>
				</tbody>
			</table>
		{else}
			<div class="mailConveterDesc" style="height: 225px;">
				<center>
					<br>
					<br>
					<div>{vtranslate('LBL_MODULE_DESCRIPTION',$MODULE)}</div>
					<br>
					<br>
					<a href="javascript:;" onclick="MailManager.open_settings();">
						<u class="cursorPointer" style="font-size:12pt;">{vtranslate('LBL_CREATE_MAILBOX', $MODULE)}</u>
					</a>
				</center>
			</div>
		{/if}
	</form>
</div>
{/strip}

{*<!--/************************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/-->*}

{strip}
	<div class="listViewPageDiv" id="email_con" name="email_con">
		<div class="row-fluid">
			<h3>{$FOLDER->name()}</h3>
		</div>
		<hr>
		<input type="hidden" id="jscal_dateformat" name="jscal_dateformat" value="{$USER_DATE_FORMAT}" />
		<div class="listViewTopMenuDiv noprint">
			<div class="listViewActionsDiv row-fluid">
				<div class="btn-toolbar span8">
					<button class='btn btn-danger delete' onclick="MailManager.massMailDelete('{$FOLDER->name()}');" value="{vtranslate('LBL_Delete',$MODULE)}">
						<strong>{vtranslate('LBL_Delete',$MODULE)}</strong>
					</button>&nbsp;
					<select style="width:auto;margin-bottom: 0px !important;" id="moveFolderList" onchange="MailManager.moveMail(this);">
						<option value="">{vtranslate('LBL_MOVE_TO',$MODULE)}</option>
						{foreach item=folder from=$FOLDERLIST}
							<option value="{$folder}" >{$folder}</option>
						{/foreach}
					</select>
					<div class="pull-right">
						<input type="text" id='search_txt' class='span3' value="{$QUERY}" style="margin-bottom: 0px;"  placeholder="{vtranslate('LBL_TYPE_SEARCH', $MODULE)}" />&nbsp;
						<img id="jscal_trigger_fval" width="20" align="absmiddle" height="20" src="" style="display:none">
						<strong>&nbsp;{vtranslate('LBL_IN',$MODULE)}</strong>&nbsp;&nbsp;
						<select style="width:auto; margin-bottom: 0px !important;" id="search_type" onchange="MailManager.addRequiredElements()">
							{foreach item=arr key=option from=$SEARCHOPTIONS}
								{if $arr eq $TYPE}
									<option value="{$arr}" selected >{vtranslate($option, $MODULE)}</option>
								{else}
									<option value="{$arr}" >{vtranslate($option, $MODULE)}</option>
								{/if}
							{/foreach}
						</select>&nbsp;
						<button class="btn edit" type=submit onclick="MailManager.search_mails('{$FOLDER->name()}');" value="{vtranslate('LBL_FIND',$MODULE)}" id="mm_search">
							<strong>{vtranslate('LBL_FIND',$MODULE)}</strong>
						</button>
					</div>
				</div>
				<div class="btn-toolbar span4">
					{if $FOLDER->mails()}
						<span class="pull-right btn-group listViewActions">
							<span class="pageNumbers">
								{$FOLDER->pageInfo()}&nbsp;
							</span>
							<span class="pull-right">
								<button class="btn"
									{if $FOLDER->hasPrevPage()}
										href="#{$FOLDER->name()}/page/{$FOLDER->pageCurrent(-1)}"
										onclick="MailManager.folder_open('{$FOLDER->name()}',{$FOLDER->pageCurrent(-1)});"
									{else}
										disabled="disabled"
									{/if}>
									<span class="icon-chevron-left"></span>
								</button>
								<button class="btn"
									{if $FOLDER->hasNextPage()}
										href="#{$FOLDER->name()}/page/{$FOLDER->pageCurrent(1)}"
										onclick="MailManager.folder_open('{$FOLDER->name()}',{$FOLDER->pageCurrent(1)});"
									{else}
										disabled="disabled"
									{/if}>
									<span class="icon-chevron-right"></span>
								</button>
							</span>
						</span>
					{/if}&nbsp;
					</span>
				</div>
			</div>
			<br>
			<div class="listViewContentDiv">
			<div class="listViewEntriesDiv">
				<table class="table table-bordered listViewEntriesTable">
				<thead>
						<tr class="listViewHeaders">
							<th width="3%" class="listViewHeaderValues" ><input align="left" type="checkbox" name="selectall" id="parentCheckBox" onClick='MailManager.toggleSelect(this.checked,"mc_box");'/></th>
                            {if $FOLDER->isSentFolder()}
                            <th width="27%" class="listViewHeaderValues"  >{vtranslate('LBL_TO', $MODULE)}</th>
							{else}
                            <th width="27%" class="listViewHeaderValues"  >{vtranslate('LBL_FROM', $MODULE)}</th>
                            {/if}
                            <th class="listViewHeaderValues" >{vtranslate('LBL_SUBJECT', $MODULE)}</th>
							<th width="17%" class="listViewHeaderValues"  align="right" >{vtranslate('LBL_Date', $MODULE)}</th>
						</tr>
					</thead>
					<tbody>
						{if $FOLDER->mails()}
							{if $FOLDER->isSentFolder()}
								{foreach item=MAIL from=$FOLDER->mails()}
									<tr class="listViewEntries {if $MAIL->isRead()}mm_normal{else}fontBold{/if} mm_clickable"
										id="_mailrow_{$MAIL->msgNo()}" onmouseover='MailManager.highLightListMail(this);' onmouseout='MailManager.unHighLightListMail(this);'>
										<td width="3%" class="narrowWidthType"><input type='checkbox' value = "{$MAIL->msgNo()}" name = 'mc_box' onclick='MailManager.toggleSelectMail(this.checked, this);'></td>
										<td width="27%" class="narrowWidthType" onclick="MailManager.mail_open('{$FOLDER->name()}', {$MAIL->msgNo()});">{vtranslate('LBL_TO', $MODULE)}: {$MAIL->to()}</td>
										<td class="narrowWidthType" onclick="MailManager.mail_open('{$FOLDER->name()}', {$MAIL->msgNo()});">{$MAIL->subject()}</td>
										<td width="17%" class="narrowWidthType" align="right" onclick="MailManager.mail_open('{$FOLDER->name()}', {$MAIL->msgNo()});">{$MAIL->date(true)}</td>
									</tr>
								{/foreach}
							{else}
								{foreach item=MAIL from=$FOLDER->mails()}
									<tr class="listViewEntries {if $MAIL->isRead()}mm_normal{else}fontBold{/if} mm_clickable"
										id="_mailrow_{$MAIL->msgNo()}" onmouseover='MailManager.highLightListMail(this);' onmouseout='MailManager.unHighLightListMail(this);'>
										<td width="3%" class="narrowWidthType"><input type='checkbox' value = "{$MAIL->msgNo()}" name = 'mc_box' onclick='MailManager.toggleSelectMail(this.checked, this);'></td>
										<td width="27%" class="narrowWidthType" onclick="MailManager.mail_open('{$FOLDER->name()}', {$MAIL->msgNo()});">{$MAIL->from(30)}</td>
										<td class="narrowWidthType" onclick="MailManager.mail_open('{$FOLDER->name()}', {$MAIL->msgNo()});">{$MAIL->subject()}</td>
										<td width="17%" class="narrowWidthType" align="right" onclick="MailManager.mail_open('{$FOLDER->name()}', {$MAIL->msgNo()});">{$MAIL->date(true)}</td>
									</tr>
								{/foreach}
							{/if}
						{else}
							<tr>
								<td colspan="3"><strong>{vtranslate('LBL_No_Mails_Found',$MODULE)}</strong></td>
							</tr>
						{/if}
					</tbody>
				</table>
			</div>
		</div>
	</div>
{/strip}
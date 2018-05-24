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
		<div class="row-fluid" id="mail_fldrname">
			<h3>{$FOLDER->name()}</h3>
		</div>
		<hr>
		<div class="listViewTopMenuDiv noprint">
			<div class="listViewActionsDiv row-fluid">
				<div class="btn-toolbar span9">
					<button class='btn btn-danger delete' onclick="MailManager.massMailDelete('__vt_drafts');" value="{vtranslate('LBL_Delete',$MODULE)}">
						<strong>{vtranslate('LBL_Delete',$MODULE)}</strong>
					</button>
					<div class="pull-right">
						<input type="text" id='search_txt' class='span3' value="{$QUERY}" style="margin-bottom: 0px;" placeholder="{vtranslate('LBL_TYPE_SEARCH', $MODULE)}"/>
						<strong>&nbsp;&nbsp;{vtranslate('LBL_IN', $MODULE)}&nbsp;&nbsp;</strong>
						<select class='small' id="search_type" style="margin-bottom: 0px;">
							{foreach item=label key=value from=$SEARCHOPTIONS}
								<option value="{$value}" >{vtranslate($label,$MODULE)}</option>
							{/foreach}
						</select>&nbsp;
						<button type=submit class="btn edit" onclick="MailManager.search_drafts();" value="{vtranslate('LBL_FIND',$MODULE)}" id="mm_search">
							<strong>{vtranslate('LBL_FIND',$MODULE)}</strong>
						</button>
					</div>
				</div>
				<div class="btn-toolbar span3">
					<span class="pull-right">
						{if $FOLDER->mails()}
							<span class="pull-right btn-group">
								<span class="pageNumbers alignTop listViewActions">
									{$FOLDER->pageInfo()}&nbsp;
								</span>
								<span class="pull-right">
									<button class="btn"
										{if $FOLDER->hasPrevPage()}
											href="#{$FOLDER->name()}/page/{$FOLDER->pageCurrent(-1)}"
											onclick="MailManager.folder_drafts({$FOLDER->pageCurrent(-1)});"
										{else}
											disabled="disabled"
										{/if}>
										<span class="icon-chevron-left"></span>
									</button>
									<button class="btn"
										{if $FOLDER->hasNextPage()}
											href="#{$FOLDER->name()}/page/{$FOLDER->pageCurrent(1)}"
											onclick="MailManager.folder_drafts({$FOLDER->pageCurrent(1)});"
										{else}
											disabled="disabled"
										{/if}>
										<span class="icon-chevron-right"></span>
									</button>
								</span>
							</span>
						{/if}
					</span>
				</div>
			</div>
		</div>
		<br>
		<div class="listViewContentDiv">
			<div class="listViewEntriesDiv">
				<table class="table table-bordered listViewEntriesTable">
					<thead>
						<tr class="listViewHeaders">
							<th width="3%" class="listViewHeaderValues" ><input align="left" type="checkbox" name="selectall" id="parentCheckBox" onClick='MailManager.toggleSelect(this.checked,"mc_box");'/></th>
                            <th width="27%" class="listViewHeaderValues"  >{vtranslate('LBL_TO', $MODULE)}</th>
							<th class="listViewHeaderValues" >{vtranslate('LBL_SUBJECT', $MODULE)}</th>
							<th width="17%" class="listViewHeaderValues"  align="right" >{vtranslate('LBL_Date', $MODULE)}</th>
						</tr>
					</thead>
					<tbody>
						{if $FOLDER->mails()}
							{foreach item=MAIL from=$FOLDER->mails()}
								<tr class="listViewEntries mm_normal mm_clickable"
									id="_mailrow_{$MAIL.id}" onmouseover='MailManager.highLightListMail(this);' onmouseout='MailManager.unHighLightListMail(this);'>
									<td width="3%" class="narrowWidthType">
										<input type='checkbox' value = "{$MAIL.id}" name = 'mc_box' class='small' onclick='MailManager.toggleSelectMail(this.checked, this);'>
									</td>
									<td width="27%" class="narrowWidthType" onclick="MailManager.mail_draft({$MAIL.id});">{$MAIL.saved_toid}</td>
									<td class="narrowWidthType" onclick="MailManager.mail_draft({$MAIL.id});">{$MAIL.subject}</td>
									<td width="17%" class="narrowWidthType" align="right" onclick="MailManager.mail_draft({$MAIL.id});">{$MAIL.date_start}</td>
								</tr>
							{/foreach}
						{elseif $FOLDER->mails() eq null}
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
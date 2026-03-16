{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ********************************************************************************/
-->*}
{strip}
<div class="container-fluid" id="ConfigEditorDetails">
	{assign var=WIDTHTYPE value=$CURRENT_USER_MODEL->get('rowheight')}
	<div class="widget_header row-fluid">
		<div class="span8"><h3>{vtranslate('LBL_CONFIG_EDITOR', $QUALIFIED_MODULE)}</h3></div>
		<div class="span4">
			<div class="pull-right">
				<button class="btn editButton" data-url='{$MODEL->getEditViewUrl()}' type="button" title="{vtranslate('LBL_EDIT', $QUALIFIED_MODULE)}"><strong>{vtranslate('LBL_EDIT', $QUALIFIED_MODULE)}</strong></button>
			</div>
		</div>
	</div>
	<hr>
	<div class="contents">
		<table class="table table-bordered table-condensed themeTableColor">
			<thead>
				<tr class="blockHeader">
					<th colspan="2" class="{$WIDTHTYPE}">
						<span class="alignMiddle">{vtranslate('LBL_CONFIG_FILE', $QUALIFIED_MODULE)}</span> 
					</th>
				</tr>
			</thead>
			<tbody>
				{assign var=FIELD_DATA value=$MODEL->getViewableData()}
				{foreach key=FIELD_NAME item=FIELD_DETAILS from=$MODEL->getEditableFields()}
					<tr><td width="30%" class="{$WIDTHTYPE}"><label class="muted marginRight10px pull-right">{vtranslate($FIELD_DETAILS['label'], $QUALIFIED_MODULE)}</label></td>
						<td style="border-left: none;" class="{$WIDTHTYPE}">
							<span>{if $FIELD_NAME == 'default_module'}{vtranslate($FIELD_DATA[$FIELD_NAME], $FIELD_DATA[$FIELD_NAME])}
								{else if $FIELD_DETAILS['fieldType'] == 'checkbox'}{vtranslate($FIELD_DATA[$FIELD_NAME], $QUALIFIED_MODULE)}
								{else}{$FIELD_DATA[$FIELD_NAME]}{/if}
								{if $FIELD_NAME == 'upload_maxsize'}&nbsp;{vtranslate('LBL_MB', $QUALIFIED_MODULE)}{/if}</span>
						</td></tr>
				{/foreach}
			</tbody>
		</table>
	</div>
</div>

<div>
    <p></p>
</div>
{* ============================= *
 * Ticket Mail Sender Details
 * ============================= *}
<div class="container-fluid" id="ConfigTicketMailSenderDetails">
	{assign var=WIDTHTYPE value=$CURRENT_USER_MODEL->get('rowheight')}

	<div class="widget_header row-fluid">
		<div class="span8">
			<h3>{vtranslate('LBL_CONFIGTICKETMAIL_EDITOR', $QUALIFIED_MODULE)}</h3>
		</div>
		<div class="span4">
			<div class="pull-right">
				<button class="btn editButton"
						data-url='{$MODEL->getEditViewUrlTicketMail()}'
						type="button"
						title="{vtranslate('LBL_EDIT', $QUALIFIED_MODULE)}">
					<strong>{vtranslate('LBL_EDIT', $QUALIFIED_MODULE)}</strong>
				</button>
			</div>
		</div>
	</div>

	<div class="col-md-10">
		{vtranslate('LBL_HEADLINE_CONFIGTICKETMAIL', $QUALIFIED_MODULE)}
	</div>

	<hr>

	<div class="contents">
		<table class="table table-bordered table-condensed themeTableColor">
			<thead>
				<tr class="blockHeader">
					<th colspan="2" class="{$WIDTHTYPE}">
						<span class="alignMiddle">{vtranslate('LBL_CONFIGTICKETMAIL_BLOCK_EDITOR', $QUALIFIED_MODULE)}</span>
					</th>
				</tr>
			</thead>
			<tbody>

				<tr>
					<td width="30%" class="{$WIDTHTYPE}">
						<label class="muted marginRight10px pull-right">
							{vtranslate('LBL_CONFIGTICKETMAIL_FIELD0', $QUALIFIED_MODULE)}
						</label>
					</td>
					<td style="border-left: none;" class="{$WIDTHTYPE}">
						<input type="checkbox" disabled="disabled"
							{if !empty($TICKETEMAIL_DATA.enabled)}checked="checked"{/if} />
					</td>
				</tr>

				<tr>
					<td width="30%" class="{$WIDTHTYPE}">
						<label class="muted marginRight10px pull-right">
							{vtranslate('LBL_CONFIGTICKETMAIL_FIELD1_EDITOR', $QUALIFIED_MODULE)}
						</label>
					</td>
					<td style="border-left: none;" class="{$WIDTHTYPE}">
                        <span>{$TICKETEMAIL_DATA.sender_name}</span>
						
					</td>
				</tr>

				<tr>
					<td width="30%" class="{$WIDTHTYPE}">
						<label class="muted marginRight10px pull-right">
							{vtranslate('LBL_CONFIGTICKETMAIL_FIELD2_EDITOR', $QUALIFIED_MODULE)}
						</label>
					</td>
					<td style="border-left: none;" class="{$WIDTHTYPE}">
						<span>{$TICKETEMAIL_DATA.sender_email}</span>
					</td>
				</tr>

				{* zuletzt geändert (datetime) *}
				<tr>
					<td width="30%" class="{$WIDTHTYPE}">
						<label class="muted marginRight10px pull-right">
							{vtranslate('LBL_LAST_CHANGED', $QUALIFIED_MODULE)}
						</label>
					</td>
					<td style="border-left: none;" class="{$WIDTHTYPE}">
						<span>
							{if !empty($TICKETEMAIL_DATA.updated_at)}
								{$TICKETEMAIL_DATA.updated_at}
							{else}
								-
							{/if}
						</span>
					</td>
				</tr>

				{* geändert von (user) *}
				<tr>
					<td width="30%" class="{$WIDTHTYPE}">
						<label class="muted marginRight10px pull-right">
							{vtranslate('LBL_LAST_CHANGED_BY', $QUALIFIED_MODULE)}
						</label>
					</td>
					<td style="border-left: none;" class="{$WIDTHTYPE}">
						<span>
							{if !empty($TICKETEMAIL_DATA.updated_by)}
								{$TICKETEMAIL_DATA.updated_by}
							{else}
								-
							{/if}
						</span>
					</td>
				</tr>

			</tbody>
		</table>
	</div>
</div>

<div><p></p></div>

{* ============================= *
 * Signature Details
 * ============================= *}
<div class="container-fluid" id="ConfigSignatureEditorDetails">
	{assign var=WIDTHTYPE value=$CURRENT_USER_MODEL->get('rowheight')}
	<div class="widget_header row-fluid">
		<div class="span8"><h3>{vtranslate('LBL_CONFIGSIGNATURE_EDITOR', $QUALIFIED_MODULE)}</h3></div>
		<div class="span4">
			<div class="pull-right">
				<button class="btn editButton" data-url='{$MODEL->getEditViewUrlSignature()}' type="button" title="{vtranslate('LBL_EDIT', $QUALIFIED_MODULE)}"><strong>{vtranslate('LBL_EDIT', $QUALIFIED_MODULE)}</strong></button>
			</div>
		</div>
	</div>

	<div class="col-md-10">
		{vtranslate('LBL_CONFIGSIG_HEADLINE_EDITOR', $QUALIFIED_MODULE)}
	</div>

	<hr>

	<div class="contents">
		<table class="table table-bordered table-condensed themeTableColor">
			<thead>
				<tr class="blockHeader">
					<th colspan="2" class="{$WIDTHTYPE}">
						<span class="alignMiddle">{vtranslate('LBL_CONFIGSIGNATURE_EDITOR', $QUALIFIED_MODULE)}</span>
					</th>
				</tr>
			</thead>
			<tbody>

				<tr>
					<td width="30%" class="{$WIDTHTYPE}">
						<label class="muted marginRight10px pull-right">
							{vtranslate('LBL_CONFIGSIG_FIELD1_EDITOR', $QUALIFIED_MODULE)}
						</label>
					</td>
					<td style="border-left: none;" class="{$WIDTHTYPE}">
						<input type="checkbox" disabled="disabled"
							{if !empty($SIGNATURE_DATA.enabled)}checked="checked"{/if} />
					</td>
				</tr>

				<tr>
					<td width="30%" class="{$WIDTHTYPE}">
						<label class="muted marginRight10px pull-right">
							{vtranslate('LBL_CONFIGSIGL_FIELD2_EDITOR', $QUALIFIED_MODULE)}
						</label>
					</td>
					<td style="border-left: none;" class="{$WIDTHTYPE}">
						{if !empty($SIGNATURE_DATA.signature_html)}
							<div style="background:#fff; padding:10px; border:1px solid #ddd;">
								{$SIGNATURE_DATA.signature_html|@htmlspecialchars_decode nofilter}
							</div>
						{/if}
					</td>
				</tr>

				{* zuletzt geändert (datetime) *}
				<tr>
					<td width="30%" class="{$WIDTHTYPE}">
						<label class="muted marginRight10px pull-right">
							{vtranslate('LBL_LAST_CHANGED', $QUALIFIED_MODULE)}
						</label>
					</td>
					<td style="border-left: none;" class="{$WIDTHTYPE}">
						<span>
							{if !empty($SIGNATURE_DATA.updated_at)}
								{$SIGNATURE_DATA.updated_at}
							{else}
								-
							{/if}
						</span>
					</td>
				</tr>

				{* geändert von (user) *}
				<tr>
					<td width="30%" class="{$WIDTHTYPE}">
						<label class="muted marginRight10px pull-right">
							{vtranslate('LBL_LAST_CHANGED_BY', $QUALIFIED_MODULE)}
						</label>
					</td>
					<td style="border-left: none;" class="{$WIDTHTYPE}">
						<span>
							{if !empty($SIGNATURE_DATA.updated_by)}
								{$SIGNATURE_DATA.updated_by}
							{else}
								-
							{/if}
						</span>
					</td>
				</tr>

			</tbody>
		</table>
	</div>
</div>
{/strip}
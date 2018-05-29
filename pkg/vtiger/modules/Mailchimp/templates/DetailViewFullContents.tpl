{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Modified and improved by crm-now.de
*
 ********************************************************************************/
-->*}
{strip}
	{include file='DetailViewBlockView.tpl'|@vtemplate_path:$MODULE_NAME RECORD_STRUCTURE=$RECORD_STRUCTURE MODULE_NAME=$MODULE_NAME}
		<table class="table table-bordered equalSplit detailview-table">
		<thead>
		<tr>
			<th class="blockHeader" colspan="4">
				{vtranslate('LBL_SYNC_HISTORY',{$MODULE_NAME})}
			</th>
		</tr>
		<tr>
			<td class="fieldValue" >
				<div readonly class="scrollable" name="mailchimplog" id="mailchimplog" style="max-height: 40vh;min-height:30vh; overflow:auto"></div>
			</td>
		</tr>
		</thead>
		</table>
{/strip}
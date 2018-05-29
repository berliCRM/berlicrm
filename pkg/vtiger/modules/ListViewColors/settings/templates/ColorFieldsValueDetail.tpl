{*<!--
/* +***********************************************************************************************************************************
 * The contents of this file are subject to the berliCRM Public License Version 1.0 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is from the crm-now GmbH.
 * The Initial Developer of the Original Code is crm-now. Portions created by crm-now are Copyright (C) www.crm-now.de. 
 * Portions created by vtiger are Copyright (C) www.vtiger.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */
-->*}
{strip}
{assign var = defaultListViewColor value = $FIELDMODEL->getDefaulListViewColor()}
{assign var = picklistcontents value = $FIELDMODEL->get('coloredlistfields')}
<br>
<ul class="nav nav-tabs massEditTabs" style="margin-bottom: 0;border-bottom: 0">
	<li class="active"><a href="#allValuesLayout" data-toggle="tab"><strong>{vtranslate($picklistcontents[0]['type'],$QUALIFIED_MODULE)}&nbsp;{vtranslate($FIELDLABEL,$SELECTED_MODULE_NAME)}&nbsp;</strong></a></li>
</ul>
<div class="tab-content layoutContent padding20 themeTableColor overflowVisible">
	<table class="table table-bordered blockContainer showInlineTable equalSplit" width="70%">
        {foreach item=CONTENT from=$picklistcontents}	
		<tr>
			<td class="fieldValue medium">
				{if $CONTENT['type'] eq 'LBL_CHECKBOX'}
					{if $CONTENT['picklistvalueid'] eq '0'}
						{vtranslate('LBL_NO')}
					{else}
						{vtranslate('LBL_YES')}
					{/if}
				{else}
				{vtranslate($CONTENT['fieldcontent'],$SELECTED_MODULE_NAME)}
				{/if}
			</td>
			<td class="fieldValue medium">
				<div class="colordisplay" style="display: inline-block;">
					{if $CONTENT['listcolor'] neq ''}
					<input id="colorSelectorValueid_{$CONTENT['picklistvalueid']}" data-recordvalue="{$CONTENT['fieldcontent']}" name="picklistid_{$FIELDMODEL->get('id')}" style="background-color:{$CONTENT['listcolor']};" type="text" value="{$CONTENT['listcolor']}" size="6" maxlength="6">
					{else}
					<input id="colorSelectorValueid_{$CONTENT['picklistvalueid']}" data-recordvalue="{$CONTENT['fieldcontent']}" name="picklistid_{$FIELDMODEL->get('id')}" style="background-color:{$defaultListViewColor};" type="text" value="{$CONTENT['listcolor']}" size="6" maxlength="6">
					{/if}
				</div>
				<div class="" id="removecolor" style="display: inline-block;">
					<a href='javascript:void(0);' class='remove'><img title="{vtranslate('LBL_DUMP_FIELD_COLOR',$QUALIFIED_MODULE)}"  alt="{vtranslate('LBL_DUMP_FIELD_COLOR',$QUALIFIED_MODULE)}" id ="removecolor_{$CONTENT['picklistvalueid']}" name ="picklistid_{$FIELDMODEL->get('id')}" src="{vimage_path('RecycleBin.png')}"></a>
				</div>
			</td>
		</tr>
		{/foreach}
	</table>
</div>	
{/strip}

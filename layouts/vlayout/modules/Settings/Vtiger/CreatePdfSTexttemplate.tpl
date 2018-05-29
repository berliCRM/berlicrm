{*<!--
/*********************************************************************************
  ** The contents of this file are subject to the vtiger CRM Public License Version 1.0
   * ("License"); You may not use this file except in compliance with the License
   * The Original Code is:  vtiger CRM Open Source
   * The Initial Developer of the Original Code is vtiger.
   * Portions created by vtiger are Copyright (C) vtiger.
   * All Rights Reserved.
  *
 ********************************************************************************/
-->*}
{strip}
<script language="JavaScript" type="text/javascript">
function cancelForm(frm)
	 {ldelim}
		frm.action.value='detailviewpdfstexttemplate';
		frm.parenttab.value='Settings';
		frm.submit();
	{rdelim}
</script>
	<div class="padding-left1per">
		<div class="row-fluid widget_header">
			<div class="span8">
				{if $MODE eq 'create'}
				<h3>{vtranslate('LBL_MULTI_TEXT_CREATE', $QUALIFIED_MODULE)}</h3>
				{else}	
				<h3>{vtranslate('LBL_MULTI_TEXT_FINISH_INFO', $QUALIFIED_MODULE)}</h3>
				{/if} 
				{if $DESCRIPTION}<span style="font-size:12px;color: black;"> - &nbsp;{vtranslate({$DESCRIPTION}, $QUALIFIED_MODULE)}</span>{/if}
			</div>
		</div>
		<hr>
		{assign var=WIDTHTYPE value=$CURRENT_USER_MODEL->get('rowheight')}
		<div  id="PdfTemplatesCreateContainer" class="{if !empty($ERROR_MESSAGE)}hide{/if}">
			<form  name="createform" id="createform" method="POST">
		    <input name="mode" type="hidden" value="{$MODE}">
	    	<input name="module" type="hidden" value="Settings">
			<input name="action" type="hidden" value="savepdftexttemplate">
			<input name="templateid" type="hidden" value="{$TEMPLATEID}">
		    <input name="textmodules" type="hidden" value="quotes">
			{if $MODE neq 'create'}
				<input name="displaymodul" type="hidden" value="{$TEXTTYPE}">
			{/if}
			<div class="row-fluid">
				<table class="table table-bordered">
					{if $MODE eq 'create'}
					<thead>
						<tr class="blockHeader">
							<th colspan="2" class="{$WIDTHTYPE}"><strong>{vtranslate('LBL_MULTI_TEXT_CREATE_SEL',$QUALIFIED_MODULE)}</strong></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td class="{$WIDTHTYPE}">
								<div class="selectType">
								<select name="displaymodul" id="displaymodul" class="detailedViewTextBox" >
									{foreach item=module from=$TEXTRELATIONS}
										<option {if $smarty.request.templatetype == $module}selected{/if} value='{$module}'>{vtranslate($module,$QUALIFIED_MODULE)}</option>
									{/foreach}
								</select>
								</div>
							</td>
						</tr>
					</tbody>
					{/if}
				</table>
				<br>
				<table class="table table-bordered">
					<tr>
						<td>
							<button id="cancel_template" class="btn pull-right" type="reset" onclick="javascript:window.history.back();"><strong>{vtranslate('LBL_CANCEL',$QUALIFIED_MODULE)}</strong></button>
							<button id="save_template" class="btn pull-left btn-success">{vtranslate('LBL_SAVE',$QUALIFIED_MODULE)}</button>
						</td>
					</tr>
				</table>
				<table class="table table-bordered">
					<tr class="blockHeader">
						<th colspan="3" class="{$WIDTHTYPE}"><strong>{vtranslate('LBL_MULTI_TEMPLATE',$QUALIFIED_MODULE)}</strong></th>
					</tr>
					<tr>
						<td class="fieldLabel medium" >
							<span class="redColor">*</span>{vtranslate('LBL_MULTI_TEXT_NAME',$QUALIFIED_MODULE)}
						</td>
						<td class="fieldValue large" colspan="2">
						<div class="row-fluid">
							<input id="templatename" size="100" type="text" style="width:99%;color: black;" value="{$TEMPLATESTITLE}" name="templatename" data-validation-engine="validate[required]">
						<div>
						</td>
					</tr>
					<tr>
						<td class="fieldLabel medium">
							{vtranslate('LBL_SELECT_SUBSTITUTE_TYPE',$QUALIFIED_MODULE)}
						</td>
						<td class="fieldValue largelarge" >	
							<select id="mergeFieldSelect" ONCHANGE="document.getElementById('body').value+=this.options[this.selectedIndex].value;"  tabindex="7">
								<OPTION VALUE="" selected>{vtranslate('LBL_NONE',$QUALIFIED_MODULE)}
								<OPTION VALUE="$contacts-salutation$">{vtranslate('Contact_Salutation',$QUALIFIED_MODULE)}                         
								<OPTION VALUE="$contacts-firstname$">{vtranslate('Contact_First_Name',$QUALIFIED_MODULE)}
								<OPTION VALUE="$contacts-lastname$" >{vtranslate('Contact_Last_Name',$QUALIFIED_MODULE)}
								<OPTION VALUE="$users-firstname$" >{vtranslate('User_First_Name',$QUALIFIED_MODULE)}
								<OPTION VALUE="$users-lastname$" >{vtranslate('User_Last_Name',$QUALIFIED_MODULE)}
								<OPTION VALUE="$users-title$" >{vtranslate('User_Title',$QUALIFIED_MODULE)}
							</select>
						<td>
					</tr>
					<tr>
						<td class="fieldLabel medium">{vtranslate('LBL_MULTI_TEMPLATE_TXT',$QUALIFIED_MODULE)}</td>
						<td class="fieldValue medium" colspan="2">
								<textarea id="body" class="row-fluid" name="body" rows="20">{$TEMPLATESTEXT}</textarea>
						</td>
					</tr>
					</form>
	
				</table>
			</div>
		</div>
<br>
<br>
{/strip}
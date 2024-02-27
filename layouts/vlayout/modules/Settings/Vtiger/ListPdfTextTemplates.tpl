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
<script>
</script>
<div class="padding-left1per">
		<div class="row-fluid widget_header">
			<div class="span8">
				<h3>{vtranslate('LBL_MULTI_TEXT_FINISH_INFO', $QUALIFIED_MODULE)}</h3>
				{if $DESCRIPTION}<span style="font-size:12px;color: black;"> - &nbsp;{vtranslate({$DESCRIPTION}, $QUALIFIED_MODULE)}</span>{/if}
			</div>
		</div>
		<hr>
		{assign var=WIDTHTYPE value=$CURRENT_USER_MODEL->get('rowheight')}
		<div  id="PdfTemplatesContainer" class="{if !empty($ERROR_MESSAGE)}hide{/if}">
			<div class="row-fluid">
				<table class="table table-bordered">
					<thead>
						<tr class="blockHeader">
							<th colspan="2" class="{$WIDTHTYPE}"><strong>{vtranslate('LBL_MULTI_TEXT_EDIT',$QUALIFIED_MODULE)}</strong></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td class="{$WIDTHTYPE}">
								<div class="selectType">
								<select name="displaymodul" id="displaymodul" class="detailedViewTextBox" style="width:30%;"  onchange="tableswitch(this.value);">
									{foreach item=module from=$TEXTRELATIONS}
										{if $module == $STARTTEXTYPE}
											<option selected value='{$module}'>{vtranslate($module,$QUALIFIED_MODULE)}</option>
										{else}		
											<option value='{$module}' >{vtranslate($module,$QUALIFIED_MODULE)}</option>
										{/if} 
									{/foreach}
								</select>
								</div>
							</td>
						</tr>
					</tbody>
				</table>
				<br>
				<table class="table table-bordered">
					<tr>
						<td>
							<button id="delete_template" class="btn pull-right"><strong>{vtranslate('LBL_DELETE',$QUALIFIED_MODULE)}</strong></button>
							<button id="new_template" class="btn pull-left addButton"><strong><i class="icon-plus"></i>{vtranslate('LBL_NEW_TEMPLATE',$QUALIFIED_MODULE)}</strong></button>
						</td>
					</tr>
				</table>
				<div id="LETTER" style="display:box" class="contents row-fluid">
					<form  name="massdelete_letter" method="POST">
		    			<input name="idlist" type="hidden">
	    				<input name="module" type="hidden" value="Settings">
						<input name="action" type="hidden" value="deletepdftexttemplate">
		    			<input name="texttype" type="hidden" value="letter">
		    			<input name="textmodules" type="hidden" value="quotes">
						<table class="table table-bordered">
							<tr class="blockHeader">
								<th colspan="2" class="{$WIDTHTYPE}"><strong>{vtranslate('LBL_MULTI_TEXT_SELECT_LETTER',$QUALIFIED_MODULE)}</strong></th>
							</tr>
							<tr>
								<td>
									<table border="0" cellspacing="0" cellpadding="5" width="100%" class="table table-bordered">
										<tr>
											<td width="5%" class="listViewHeaderValues" data-columnname="templatename" data-nextsortorderval="ASC" href="javascript:void(0);">#</td>
											<td width="5%" class="listViewHeaderValues" data-columnname="templatename" data-nextsortorderval="ASC" href="javascript:void(0);">{vtranslate('LBL_LIST_SELECT',$QUALIFIED_MODULE)}</td>
											<td width="90%" class="listViewHeaderValues" data-columnname="templatename" data-nextsortorderval="ASC" href="javascript:void(0);">{vtranslate('LBL_MULTI_TEXT_SELECT_LIST',$QUALIFIED_MODULE)}</td>
										</tr>
										{if $LETTERCOUNT == 0}
											<input id="nolettercount" type="hidden" value="0">
										{else}
											{foreach name=texttemplate item=template from=$LETTERTEMPLATES}
												<tr>
													<td class="listViewEntryValue medium" nowrap="" data-field-type="string" valign=top>{$smarty.foreach.texttemplate.iteration}</td>
													<td class="listViewEntryValue medium" nowrap="" data-field-type="string" valign=top><input type="checkbox" name="selected_id" id="selected_id{$smarty.foreach.texttemplate.iteration}" value="{$template.templateid}" class=small></td>
													<td class="listViewEntryValue medium" nowrap="" data-field-type="string" valign=top>
														<a href="index.php?parent=Settings&module=Vtiger&mode=edit&view=createpdfstexttemplate&templateid={$template.templateid}&texttype=letter&textmodules=quotes" ><b>{$template.templatename}</b></a>
													</td>
												</tr>
											{/foreach}
										{/if} 										
									</table>
								</td>
							</tr>
						</table>
					</form>
				</div>
				<div id="CONCLUSION" style="display:box" class="box">
					<form  name="massdelete_conclusion" method="POST">
		    			<input name="idlist" type="hidden">
	    				<input name="module" type="hidden" value="Settings">
						<input name="action" type="hidden" value="deletepdftexttemplate">
	    				<input name="texttype" type="hidden" value="conclusion">
	    				<input name="textmodules" type="hidden" value="quotes">
						<table class="table table-bordered">
							<tr class="blockHeader">
								<th colspan="2" class="{$WIDTHTYPE}"><strong>{vtranslate('LBL_MULTI_TEXT_SELECT_CONCLUSION',$QUALIFIED_MODULE)}</strong></th>
							</tr>
							<tr>
								<td>
									<table border="0" cellspacing="0" cellpadding="5" width="100%" class="table table-bordered">
										<tr>
											<td width="5%" class="listViewHeaderValues" data-columnname="templatename" data-nextsortorderval="ASC" href="javascript:void(0);">#</td>
											<td width="5%" class="listViewHeaderValues" data-columnname="templatename" data-nextsortorderval="ASC" href="javascript:void(0);">{vtranslate('LBL_LIST_SELECT',$QUALIFIED_MODULE)}</td>
											<td width="90%" class="listViewHeaderValues" data-columnname="templatename" data-nextsortorderval="ASC" href="javascript:void(0);">{vtranslate('LBL_MULTI_TEXT_SELECT_LIST',$QUALIFIED_MODULE)}</td>
										</tr>
										{if $CONCLUSIONCOUNT == 0}
											<input id="noconclusioncount" type="hidden" value="0">
										{else}
											{foreach name=texttemplate item=template from=$CONCLUSIONTEMPLATES}
												<tr>
													<td class="listTableRow small" valign=top>{$smarty.foreach.texttemplate.iteration}</td>
													<td class="listTableRow small" valign=top><input type="checkbox" name="selected_id" value="{$template.templateid}" class=small></td>
													<td class="listTableRow small" valign=top>
														<a href="index.php?parent=Settings&module=Vtiger&mode=edit&view=createpdfstexttemplate&templateid={$template.templateid}&texttype=conclusion&textmodules=quotes" ><b>{$template.templatename}</b></a>
													</td>
												</tr>
											{/foreach}	
										{/if} 										
									</table>
								</td>
							</tr>
						</table>
					</form>
				</div>
			</div>
		</div>
</div>
<br>
{literal}
<script language="JavaScript" type="text/javascript">
window.onload = function() {eval(setinitial(document.getElementById('displaymodul')));}

function tableswitch(modules) {
	var option=['LETTER','CONCLUSION'];
	for(var i=0; i<option.length; i++) { 
		obj=document.getElementById(option[i]);
		obj.style.display=(option[i]==modules) && !(obj.style.display=="block")? "block" : "none"; 
	}
}
function setinitial(allmenues) {
	var option=['LETTER','CONCLUSION'];
	for(var i=0; i<allmenues.length; i++) { 
		obj=allmenues.options[i];
		if (obj.selected==true)  tableswitch(option[i]);
	}
}
</script>
{/literal}
{/strip}

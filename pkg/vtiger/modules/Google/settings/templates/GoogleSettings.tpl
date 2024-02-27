{*<!--
/*********************************************************************************
  ** The contents of this file are subject to the vtiger CRM Public License Version 1.0
   * ("License"); You may not use this file except in compliance with the License
   * The Original Code is:  vtiger CRM Open Source
   * The Initial Developer of the Original Code is vtiger.
   * Portions created by vtiger are Copyright (C) vtiger.
   * All Rights Reserved.
   * Modified and improved by crm-now.de
 ********************************************************************************/
-->*}
{strip}
<form  method="POST" name="googlesettings"  id="googlesettings" >
<input type='hidden' name='module' value='{$MODULE}'>
<input type='hidden' name='action' value='saveGoogleSettings'>
	<div id="GoogleSettingContainer" name="GoogleSettingContainer" class="container-fluid span12">
		<div class="widget_header row-fluid">
			<h3>{vtranslate('LBL_GOOGLE_DESCRIPTION',$QUALIFIED_MODULE)}</h3>
		</div>
		<hr>
		<div class="widget_header row-fluid">
			<div class="contents row-fluid">
				<span>{vtranslate('LBL_GOOGLE_INSTRUCTIONS', $QUALIFIED_MODULE)}</span>
			</div>
		</div>
		<input class="btn editButton" id="editgoogleconfig" name="editgoogleconfig"   type="button" title="{vtranslate('LBL_EDIT',$QUALIFIED_MODULE)}" style ="visibility:hidden"  value="{vtranslate('LBL_EDIT',$QUALIFIED_MODULE)}">
		<div class="contents row-fluid paddingTop20">
			<table class="table table-bordered table-condensed themeTableColor">
					<tr class="blockHeader">
						<th class="medium" colspan="4">{vtranslate('LBL_GOOGLE_SETTINGS',$QUALIFIED_MODULE)}</th>
					</tr>
					<tr class="opacity" >
						<td class="textAlignLeft medium" style="border-left: block;">
							<label class="span3 menuItemLabel">{vtranslate('LBL_ENTER_GOOGLE_APP_KEY',$QUALIFIED_MODULE)}</label>
						</td>
						<td class="textAlignLeft medium" >
							<input class="span6" type="text" value="{$GOOGLEAPIKEY}" name="mapapikey" id="mapapikey">
						</td>
						<td class="textAlignLeft medium span4" style="border-left: block;">
							<input class="btn btn-success saveButton" id="checkmapdatakey" name="checkmapdatakey" title="{vtranslate('LBL_CHECK_KEY',$QUALIFIED_MODULE)}"  type="button" value="{vtranslate('LBL_CHECK_KEY',$QUALIFIED_MODULE)}">
						</td>
						<td class="textAlignLeft medium span10" style="border-left: block;">
							<div id="map_canvas">
								<input class="span6" type="text" value="Stromstrasse 5, 10555, Berlin, Deutschland" name="mapaddress" id="mapaddress">
							</div>
						</td>
					</tr>
					<tr class="opacity" >
						<td class="textAlignLeft medium" style="border-left: block;">
							<label class="span3 menuItemLabel">{vtranslate('LBL_ENTER_BERLIMAP_APP_KEY',$QUALIFIED_MODULE)}</label>
						</td>
						<td class="textAlignLeft medium" >
							<input class="span6" type="text" value="{$GOOGLEGEOAPIKEY}" name="geodataapikey" id="geodataapikey">
						</td>
						<td class="textAlignLeft medium span4" style="border-left: block;">
							<input class="btn btn-success saveButton" id="checkgeodatakey" name="checkgeodatakey" title="{vtranslate('LBL_CHECK_KEY',$QUALIFIED_MODULE)}"  type="button" value="{vtranslate('LBL_CHECK_KEY',$QUALIFIED_MODULE)}">
						</td>
						<td class="textAlignLeft medium" >
							 
						</td>
					</tr>
			</table>
		</div>
		<br>
		<table >
			<tr >
				<td class="small" align="right" >
					<input class="btn btn-success saveButton" id="savegoogleconfig" name="savegoogleconfig" title="{vtranslate('LBL_SAVE',$MODULE)}"  type="button" value="{vtranslate('LBL_SAVE',$MODULE)}">&nbsp;
				</td>
			</tr>
		</table>
		<br>
		<table >
			<tr>
				<td colspan="2"> <div class="alert">
					<strong>{vtranslate('LBL_GOOGLE_HINT', $QUALIFIED_MODULE)}</strong><br>
					{vtranslate('LBL_GOOGLE_HINT1', $QUALIFIED_MODULE)}<br><br>
					{vtranslate('LBL_GOOGLE_HINT2', $QUALIFIED_MODULE)}<br>
					</div>
				</td>	
			</tr>
		</table>
	</div>
</form>
{/strip}
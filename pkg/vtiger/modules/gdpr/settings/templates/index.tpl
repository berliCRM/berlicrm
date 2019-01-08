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
<div class="container-fluid" id="gdprConfigDetails">
	<div class="widget_header row-fluid">
		<div class="span8"><h3>{vtranslate('LBL_GDPR_SETTINGS', $QUALIFIED_MODULE)}</h3></div>
	</div>
	<hr>
 	<div class="widget_header row-fluid">
       
    <div class="contents row-fluid">
		<span>{vtranslate('LBL_GDPR_INSTRUCTIONS', $QUALIFIED_MODULE)}</span>
	</div>
	<br>
		<table class="table table-bordered table-condensed themeTableColor">
			<thead>
				<tr class="blockHeader">
					<th colspan="2" class="mediumWidthType">
						<span class="alignMiddle">{vtranslate('LBL_CONTACT_RELATED', $QUALIFIED_MODULE)}</span>
					</th>
				</tr>
				<tr>
					<td valign="top" class="small">
						{$ERROR}
					</td>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td class="alignMiddle"><strong>{vtranslate('LBL_OPERATION_MODE', $QUALIFIED_MODULE)}</strong></td>
					<td style="border-left: none;" class="">
						<select name="globalMode" id="globalMode" class="globalpicklist" >
							<option value="d" {if $GDPR_GLOBAL_SETTINGS->get('op_mode') eq "d"} selected {/if}>{vtranslate('LBL_OPERATION_DEACTIVATED', $QUALIFIED_MODULE)}</option>
							<option value="a" {if $GDPR_GLOBAL_SETTINGS->get('op_mode') eq "a"} selected {/if}>{vtranslate('LBL_OPERATION_AUTO', $QUALIFIED_MODULE)}</option>
							<option value="m" {if $GDPR_GLOBAL_SETTINGS->get('op_mode') eq "m"} selected {/if}>{vtranslate('LBL_OPERATION_MANUAL', $QUALIFIED_MODULE)}</option>
						</select>
					</td>
				</tr>
				<tr>
					<td colspan="2"> <div class="alert">
						<strong>{vtranslate('LBL_GDPR_HINT', $QUALIFIED_MODULE)}</strong><br>
						{vtranslate('LBL_GDPR_HINT1', $QUALIFIED_MODULE)}<br>
						</div>
					</td>	
				</tr>
				<tr>
					<td class="alignMiddle"><strong>{vtranslate('LBL_NOTIFICATION_TIME', $QUALIFIED_MODULE)}</strong></td>
					<td style="border-left: none;" class="">
						<select name="noti_time" id="noti_time" class="globalpicklist" >
							<option value="1"{if $GDPR_GLOBAL_SETTINGS->get('del_note_time_days') eq "1"} selected {/if}>{vtranslate('LBL_ONE_DAY', $QUALIFIED_MODULE)}</option>
							<option value="7" {if $GDPR_GLOBAL_SETTINGS->get('del_note_time_days') eq "7"} selected {/if}>{vtranslate('LBL_ONE_WEEK', $QUALIFIED_MODULE)}</option>
							<option value="14" {if $GDPR_GLOBAL_SETTINGS->get('del_note_time_days') eq "14"} selected {/if}>{vtranslate('LBL_TWO_WEEKS', $QUALIFIED_MODULE)}</option>
							<option value="21" {if $GDPR_GLOBAL_SETTINGS->get('del_note_time_days') eq "21"} selected {/if}>{vtranslate('LBL_THREE_WEEKS', $QUALIFIED_MODULE)}</option>
							<option value="28" {if $GDPR_GLOBAL_SETTINGS->get('del_note_time_days') eq "28"} selected {/if}>{vtranslate('LBL_FOUR_WEEKS', $QUALIFIED_MODULE)}</option>
					</select>
					</td>
				</tr>
				<tr>
					<td colspan="2"> <div class="alert">
						<strong>{vtranslate('LBL_GDPR_HINT', $QUALIFIED_MODULE)}</strong><br>
						{vtranslate('LBL_GDPR_HINT2', $QUALIFIED_MODULE)}
						</div>
					</td>	
				</tr>
			</tbody>
		</table>
		<br>
        <form action="index.php?parent=Settings&module=gdpr&action=saveFields" method="POST" id="gdprModules">
		<table class="table table-bordered table-condensed themeTableColor">
			<thead>
				<tr class="blockHeader">
					<th class="mediumWidthType">
						<span class="alignMiddle">{vtranslate('LBL_MODULES_RELATED', $QUALIFIED_MODULE)}</span>
					</th>
					<th class="mediumWidthType">
						<span class="alignMiddle">{vtranslate('LBL_GDPR_RELEVANT', $QUALIFIED_MODULE)}</span>
					</th>
					<th class="mediumWidthType">
						<span class="alignMiddle">{vtranslate('LBL_GDPR_RELEVANT_FIELDS', $QUALIFIED_MODULE)}</span>
					</th>
					<th class="mediumWidthType">
						<span class="alignMiddle">{vtranslate('LBL_ACTIVATED_DELETE_ACTION', $QUALIFIED_MODULE)}</span>
					</th>
				</tr>
			</thead>
			<tbody>
				{foreach key=TAB_ID item=MODEL from=$ALL_MODULES}

                    {assign var=MODULE_NAME value=$MODEL->get('name')}
                    {assign var=MODULE_FIELDS value=$MODEL->getFields($MODULE_NAME)}
                    <tr class="ModuleRow">
                        <td>
                            {vtranslate($MODULE_NAME, $MODULE_NAME)}
                        </td>
                        <td>
                            <select name="gdprRelevantModule[{$TAB_ID}]" id="gdprRelevantModule_{$TAB_ID}" class="gdprRelevantModule" style="width:100px">
                                <option value="0" {if empty($MODULE_SETTINGS[$TAB_ID])}selected{/if}>{vtranslate('LBL_NO')}</option>
                                <option value="1" {if !empty($MODULE_SETTINGS[$TAB_ID])}selected{/if}>{vtranslate('LBL_YES')}</option>
                            </select>
                        </td>
                        <td>
                            <select name="gdprFields[{$TAB_ID}][]" id="gdprFields_{$TAB_ID}" class="gdprFields select2" style="min-width:300px;" multiple>
                            {foreach from=$MODULE_FIELDS item=FIELD key=FIELDNAME}
                                <option value='{$FIELD->get("id")}' {if !empty($MODULE_SETTINGS[$TAB_ID]["fields"]) && in_array($FIELD->get("id"),$MODULE_SETTINGS[$TAB_ID]["fields"])}selected{/if}>{vtranslate($FIELD->get("label"), $MODULE_NAME)}</option>
                            {/foreach}
                            </select>
                        </td>
                        <td>
                            <select name="pickListDelete[{$TAB_ID}]" style="min-width:270px;" id="pickListDelete_{$TAB_ID}" class='gdpr_auto_delete'>
                                <option value="0" {if $MODULE_SETTINGS[$TAB_ID]["deletion_mode"]==0}selected{/if}>{vtranslate('LBL_NO_AUTO_DELETE',$QUALIFIED_MODULE)}</option>
                                <option value="1" {if $MODULE_SETTINGS[$TAB_ID]["deletion_mode"]==1}selected{/if}>{vtranslate('LBL_AUTO_DELETE_FIELD', $QUALIFIED_MODULE)}</option>
                                <option value="2" {if $MODULE_SETTINGS[$TAB_ID]["deletion_mode"]==2}selected{/if}>{vtranslate('LBL_AUTO_DELETE_MODULE', $QUALIFIED_MODULE)}</option>
                            </select>
                        </td>
                    </tr>
				{/foreach}
				<tr>
					<td colspan="4"> 
                        <div class="alert">
						<strong>{vtranslate('LBL_GDPR_HINT', $QUALIFIED_MODULE)}</strong><br>
						{vtranslate('LBL_GDPR_HINT3', $QUALIFIED_MODULE)}
						</div>
                        <button class="btn btn-success pull-right" type="submit">{vtranslate('LBL_SAVE')}</button>
					</td>	
				</tr>
			</tbody>
		</table>
        </form>
        {if !empty($MODULE_SETTINGS[$TAB_ID]['setting_date'])}<div class="alert alert-info">Diese Zuordnung wurde gespeichert am {DateTimeField::convertToUserFormat($MODULE_SETTINGS[$TAB_ID]['setting_date'])}</div>{/if}
		<br>
 		<table class="table table-bordered table-condensed themeTableColor">
			<thead>
				<tr class="blockHeader">
					<th colspan="2" class="mediumWidthType">
						<span class="alignMiddle">{vtranslate('LBL_DELETE_OPERATION', $QUALIFIED_MODULE)}</span>
					</th>
				</tr>
				<tr>
					<td valign="top" class="small">
						{$ERROR}
					</td>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td  class="alignMiddle"><strong>{vtranslate('LBL_OPERATION_MODE', $QUALIFIED_MODULE)}</strong></td>
					<td style="border-left: none;" class="">
						<select name="pickListDelete" id="pickListDelete" class="globalpicklist">
							<option value="0"  {if $GDPR_GLOBAL_SETTINGS->get('del_mode') eq "0"} selected {/if}>{vtranslate('LBL_DELETE_TRAY', $QUALIFIED_MODULE)}</option>
							<option value="1"  {if $GDPR_GLOBAL_SETTINGS->get('del_mode') eq "1"} selected {/if}>{vtranslate('LBL_DELETE_FOREVER', $QUALIFIED_MODULE)}</option>
						</select>
					</td>
				</tr>
				<tr>
					<td colspan="2"> <div class="alert">
						<strong>{vtranslate('LBL_GDPR_HINT', $QUALIFIED_MODULE)}</strong><br>
						{vtranslate('LBL_GDPR_HINT4', $QUALIFIED_MODULE)}<br>
						</div>
					</td>	
				</tr>
			</tbody>
		</table>
	</div>
</div>
<br>	
<div class="span8 alert alert-danger">
    <strong>{vtranslate('LBL_GDPR_IMPORTANT', $QUALIFIED_MODULE)}</strong><br>
    {vtranslate('LBL_GDPR_IMPORTANT1', $QUALIFIED_MODULE)}
</div>
{/strip}
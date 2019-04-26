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
<div class="container-fluid" id="softphoneConfigDetails">
	<div class="widget_header row-fluid">
		<div class="span8"><h3>{vtranslate('LBL_SOFTPHONE_SETTINGS', $QUALIFIED_MODULE)}</h3></div>
	</div>
	<hr>
 	<div class="widget_header row-fluid">
       
    <div class="contents row-fluid">
		<span>{vtranslate('LBL_PHONE_INSTRUCTIONS', $QUALIFIED_MODULE)}</span>
	</div>
	<br>
		<table class="table table-bordered table-condensed themeTableColor">
			<thead>
				<tr class="blockHeader">
					<th colspan="5" class="mediumWidthType">
						<span class="alignMiddle">{vtranslate('LBL_SOFTPHONE_SELECTION', $QUALIFIED_MODULE)}</span>
					</th>
				</tr>
				<tr>
					<td valign="top" class="small">
						{$ERROR}
					</td>
				</tr>
				<tr>
					<td  class="alignMiddle"><strong>{vtranslate('LBL_PHONE_ID', $QUALIFIED_MODULE)}</strong></td>
					<td  class="alignMiddle"><strong>{vtranslate('LBL_PHONE_NAME', $QUALIFIED_MODULE)}</strong></td>
					<td  class="alignMiddle"><strong>{vtranslate('LBL_PHONE_PREFIX', $QUALIFIED_MODULE)}</strong></td>
					<td  class="alignMiddle"><strong>{vtranslate('LBL_PHONE_ACTIVE', $QUALIFIED_MODULE)}</strong></td>
					<td  class="alignMiddle"><strong>{vtranslate('LBL_PHONE_DESCRIPTION', $QUALIFIED_MODULE)}</strong></td>
				</tr>
			</thead>
			<tbody>
				{assign var=FIELDS value= $PHONESETTINGS->getData()}
				{foreach key=phonechar item=phonecont from=$FIELDS}
					<tr>
						<td class="muted">{$phonecont.phoneid}</td>
						<td class="muted">{$phonecont.phonename}</td>
						<td class="muted">{$phonecont.phoneprefix}</td>
						<td class="muted">
							<strong><input class="muted" type="checkbox" id="phactive_{$phonechar}"  name="radiocheck" {$phonecont.phactive}/></strong>
						</td>
						<td class="muted">{vtranslate({$phonecont.phdescription}, $QUALIFIED_MODULE)}</td>
					</tr>
				{/foreach}	
						
				<tr>
					<td colspan="5" class="muted">{vtranslate('LBL_PHONE_INACTIVE_ALL', $QUALIFIED_MODULE)}	
						<input type="checkbox" id="phactive_all"  name="radiocheck" {$PHONECOUNT} />
				    </td>
      			</tr>
                 <input type="hidden" name="module" value="berliSoftphones"/>
				<input type="hidden" name="action" value="SaveAjax"/>
				<input type="hidden" name="parent" value="Settings"/>
				<input type="hidden" class="recordid" name="id" value="{$RECORD_ID}">
			</tbody>
		</table>
	</div>
</div>
<br>
<div class="span12 alert">
    {vtranslate('LBL_PHONE_HINT', $QUALIFIED_MODULE)}<br>
    {vtranslate('LBL_PHONE_HINT1', $QUALIFIED_MODULE)}<br><br>
    {vtranslate('LBL_PHONE_INBOUND_URL', $QUALIFIED_MODULE)}<br><br>
    {vtranslate('LBL_PHONE_HINT2', $QUALIFIED_MODULE)}<br>
    {vtranslate('LBL_PHONE_HINT3', $QUALIFIED_MODULE)}
</div>	
<br>	
<div class="span8 alert alert-danger">
    {vtranslate('LBL_PHONE_IMPORTANT', $QUALIFIED_MODULE)}<br>
    {vtranslate('LBL_PHONE_IMPORTANT1', $QUALIFIED_MODULE)}
</div>
{/strip}
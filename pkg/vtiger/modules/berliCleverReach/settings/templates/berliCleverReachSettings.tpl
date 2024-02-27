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
<form  method="POST" name="CleverReachSettings"  id="CleverReachSettings" >
<input type='hidden' name='module' value='berliCleverReach'>
<input type='hidden' name='action' value='saveberliCleverReachSettings'>

	<div id="CleverReachContainer" name="CleverReachContainer" class="container-fluid span12">
		<div class="widget_header row-fluid">
			<h3>{vtranslate('LBL_SETUP_MODULE',$MODULE)}</h3>
		</div>
		<hr>

		{if $WHOAMI}
			<input class="btn" id="deletecleverreachconfig" name="deletecleverreachconfig" type="button" title="{vtranslate('LBL_DELETE',$MODULE)}" style="float:right;margin-top:20px;" value="{vtranslate('LBL_DELETE',$MODULE)}">
			<h4 id='berliCleverReachApiState' class="padding20">{vtranslate('LBL_API_CONNECTED_TO',$MODULE)|sprintf:$WHOAMI->id:$WHOAMI->firstname:$WHOAMI->name}</h4>
		
		{else}
			<input class="btn" id="deletecleverreachconfig" name="deletecleverreachconfig" type="button" title="{vtranslate('LBL_DELETE',$MODULE)}" style="float:right;margin-top:20px;display:none;" value="{vtranslate('LBL_DELETE',$MODULE)}">
			<h4 id='berliCleverReachApiState' class="padding20" style="display:none;">&nbsp;</h4>
		{/if}

		{if $INVALIDTOKEN}
		<div class="alert alert-error">
			{vtranslate('LBL_TOKENREJECTED',$MODULE)}
		</div>
		{/if}
		
		<div class="contents row-fluid">

					<table class="table table-bordered table-condensed themeTableColor" id='berliCleverReachCredentials' {if $APICREDENTIALS.accesstoken !=""}style="display:none"{/if}>

					<tr class="blockHeader">
						<th class="medium" colspan="2">{vtranslate('LBL_ENTER_CREDENTIALS_FOR_NEW_TOKEN',$MODULE)}</th>
					</tr>
					
					<tr class="opacity" >
						<td class="textAlignLeft medium span4">
						
							<label class="menuItemLabel">{vtranslate('LBL_ENTER_CLEVERREACH_ID',$MODULE)}</label>
						</td>
						<td class="textAlignLeft medium" >
							<input class="span5" type="text" value="{$APICREDENTIALS.client_id}" name="customerid" id="customerid">
						</td>
					</tr>
					<tr class="opacity" >
						<td class="textAlignLeft medium span4">
							<label class="menuItemLabel">{vtranslate('LBL_ENTER_CLEVERREACH_USERNAME',$MODULE)}</label>
						</td>
						<td class="textAlignLeft medium" >
							<input class="span5" type="text" value="{$APICREDENTIALS.login}" name="customername" id="customername">
						</td>
					</tr>
					<tr class="opacity" >
						<td class="textAlignLeft medium span4">
							<label class="menuItemLabel">{vtranslate('LBL_ENTER_CLEVERREACH_PASSWORD',$MODULE)}</label>
						</td>
						<td class="textAlignLeft medium" >
							<input class="span5" type="password" value="" name="customerpassword" id="customerpassword">
						</td>
					</tr>
				</table>

			
			<br>	
				<table class="table table-bordered table-condensed themeTableColor">

					<tr class="blockHeader">
						<th class="medium" colspan="2">{vtranslate('LBL_CLEVERREACH_SETTINGS',$MODULE)}</th>
					</tr>

					<tr class="opacity">
						<td class="textAlignLeft medium span4">
							<label class="span3 menuItemLabel">{vtranslate('LBL_CREATE_AS',$MODULE)}</label>
						</td>
						{if $SUBSCRIBERTYPE eq 'lead'}
							<td class="small smalltxt">
								<input type="radio" name="newsubscriber" id="makeContact" value="contact"/><label for="makeContact">{vtranslate('LBL_CONTACTS',$MODULE)}</label>
								<input type="radio" name="newsubscriber" id="makeLead" value="lead" checked="true"  /><label for="makeLead">{vtranslate('LBL_LEADS',$MODULE)}</label>
							</td>
						{else}
							<td class="small smalltxt">
								<input type="radio" name="newsubscriber" id="makeContact" value="contact" checked="true" /><label for="makeContact">{vtranslate('LBL_CONTACTS',$MODULE)}</label>
								<input type="radio" name="newsubscriber" id="makeLead" value="lead" /><label for="makeLead">{vtranslate('LBL_LEADS',$MODULE)}</label>
							</td>
						{/if}
					</tr>
	

			</table>
		</div>
		<br>
		<table >
			<tr >
				<td class="small" align="right" >
					<input class="btn btn-success saveButton" id="savecleverreachconfig" name="savecleverreachconfig" title="{vtranslate('LBL_SAVE',$MODULE)}"  type="button" value="{vtranslate('LBL_SAVE',$MODULE)}">&nbsp;
				</td>
			</tr>
		</table>
	</div>
</form>
{/strip}
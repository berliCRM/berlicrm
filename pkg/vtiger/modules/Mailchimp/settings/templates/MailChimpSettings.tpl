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
<form  method="POST" name="mailchimpsettings"  id="mailchimpsettings" >
	<div id="MailchimpContainer" name="MailchimpContainer" class="container-fluid span12">
		<div class="widget_header row-fluid">
			<h3>{vtranslate('LBL_SETUP_MODULE',$MODULE)}</h3>
		</div>
		<hr>
		<input class="btn editButton" id="editmailchimpconfig" name="editmailchimpconfig"   type="button" title="{vtranslate('LBL_EDIT',$MODULE)}" style ="visibility:hidden"  value="{vtranslate('LBL_EDIT',$MODULE)}">
		<div class="contents row-fluid paddingTop20">
			<table class="table table-bordered table-condensed themeTableColor">
					<input type='hidden' name='module' value='Mailchimp'>
					<input type='hidden' name='action' value='saveMailchimpSettings'>
					<tr class="blockHeader">
						<th class="medium" colspan="3">{vtranslate('LBL_MAILCHIMP_SETTINGS',$MODULE)}</th>
					</tr>
					<tr class="opacity" >
						<td class="textAlignLeft medium span4" style="border-left: block;">
							<label class="span3 menuItemLabel">{vtranslate('LBL_ENTER_APP_KEY',$MODULE)}</label>
						</td>
						<td class="textAlignLeft medium" >
							<input class="span6" type="text" value="{$APIKEY}" name="apikey" id="apikey">
						</td>
					</tr>
					<tr class="opacity" >
						<td class="textAlignLeft medium" style="border-left: block;">
							<label class="span3 menuItemLabel">{vtranslate('LBL_CREATE_AS',$MODULE)}</label>
						</td>
						{if $SUBSCRIBERTYPE eq 'lead'}
							<td  class="small smalltxt">
								<input type="radio" name="newsubscriber" id="makeContact" value="contact"/><label for="makeContact">{vtranslate('LBL_CONTACTS',$MODULE)}</label>
								<input type="radio" name="newsubscriber" id="makeLead" value="lead" checked="true"  /><label for="makeLead">{vtranslate('LBL_LEADS',$MODULE)}</label>
							</td>
						{else}
							<td  class="small smalltxt">
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
					<input class="btn btn-success saveButton" id="savemailchimpconfig" name="savemailchimpconfig" title="{vtranslate('LBL_SAVE',$MODULE)}"  type="button" value="{vtranslate('LBL_SAVE',$MODULE)}">&nbsp;
				</td>
			</tr>
		</table>
	</div>
</form>
{/strip}
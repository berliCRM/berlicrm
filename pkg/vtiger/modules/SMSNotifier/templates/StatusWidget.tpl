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
<div>
	<table width="100%" cellpadding="3" cellspacing="1" border="0" class="lvt small">
	
		{assign var="_TRSTARTED" value=false}
		{foreach item=RESULT from=$RECORD->get('messagedetails') name=NUMBERSECTION}
			{assign var="PROCESS" value="false"}
			{assign var="TIME" value="false"}
			{assign var="ERROR" value="false"}
			{if $smarty.foreach.NUMBERSECTION.index % 4 == 0}
			
				{* Close the tr if it was started last *}		
				{if $_TRSTARTED}
					</tr>
					{assign var="_TRSTARTED" value=false}
				{/if}
				
				<tr class="lvtColData" onmouseover="this.className='lvtColDataHover'" onmouseout="this.className='lvtColData'" >
				{assign var="_TRSTARTED" value=true}
			{/if}
			
			{assign var="_TDBGCOLOR" value="#FFFFFF"}
			{if $RESULT.timestamp  != ''}
				{assign var="TIME" value=Vtiger_Time_UIType::getDisplayTimeValue($RESULT.timestamp)}
			{/if}
			{if $RESULT.status == 'Processing'}
				{*  yellow  *}
				{assign var="_TDBGCOLOR" value="#FFFF00"}
				{assign var="PROCESS" value="true"}
			{elseif $RESULT.status == 'Dispatched'}
				{*  green - ok  *}
				{assign var="_TDBGCOLOR" value="#BDF97D"}			
			{elseif $RESULT.status == 'delivered'}
				{*  green - ok  *}
				{assign var="_TDBGCOLOR" value="#BDF97D"}			
			{elseif $RESULT.status neq 'Dispatched'}
				{*  Error = red colored *}
				{assign var="_TDBGCOLOR" value="#FF3322"}
				{assign var="ERROR" value="true"}
			{else}
				{*  Provider is not Clickatell *}
				{assign var="_TDBGCOLOR" value="#99FFEE"}
				{assign var="OTHER" value="true"}
			{/if}
			{if $PROCESS == 'true'}
				<td nowrap="nowrap" bgcolor="{$_TDBGCOLOR}" width="25%">{$RESULT.tonumber}&nbsp;&nbsp;{if $RESULT.timestamp  != ''}{$TIME}&nbsp;GMT{/if}&nbsp;&nbsp;&nbsp;{vtranslate('LBL_PROVIDER_MESSAGE',$MODULE_NAME)}:&nbsp;&nbsp;{$RESULT.status}&nbsp;&nbsp;</td>
			{elseif $ERROR == 'true'}
				<td nowrap="nowrap" bgcolor="{$_TDBGCOLOR}" width="25%">{$RESULT.tonumber}&nbsp;&nbsp;{if $RESULT.timestamp  != ''}{$TIME}&nbsp;GMT{/if}&nbsp;&nbsp;&nbsp;{vtranslate('LBL_PROVIDER_ERROR_MESSAGE',$MODULE_NAME)}:&nbsp;&nbsp;{$RESULT.status}&nbsp;{$RESULT.statusmessage}&nbsp;&nbsp;</td>
			{elseif $OTHER == 'true'}
				<td nowrap="nowrap" bgcolor="{$_TDBGCOLOR}" width="25%">{$RESULT.tonumber}&nbsp;&nbsp;{if $RESULT.timestamp  != ''}{$TIME}&nbsp;GMT{/if}&nbsp;&nbsp;&nbsp;{$RESULT.status}&nbsp;&nbsp;{$CMOD.LBL_OTHER_PROVIDER_MESSAGE}&nbsp;&nbsp;</td>
			{else}
				<td nowrap="nowrap" bgcolor="{$_TDBGCOLOR}" width="25%">{$RESULT.tonumber}&nbsp;&nbsp;{if $RESULT.timestamp  != ''}{$TIME}&nbsp;GMT{/if}&nbsp;&nbsp;&nbsp;{vtranslate('LBL_MESSAGE_SENT',$MODULE_NAME)}&nbsp;&nbsp;</td>

			{/if}
			
		{/foreach}
	
		{* Close the tr if it was started last *}		
		{if $_TRSTARTED}
			</tr>
			{assign var="_TRSTARTED" value=false}
		{/if}
	</table>
</div>
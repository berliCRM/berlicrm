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
<table style="table-layout:fixed;width:100%;">
	{foreach item=RELATED_RECORD from=$RELATED_RECORDS}
		<tr style="border-bottom:1px solid #dadada">
			<td style="padding:2px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;">
                <a href="{$RELATED_RECORD->getDetailViewUrl()}" id="{$MODULE}_{$RELATED_MODULE}_Related_Record_{$RELATED_RECORD->get('id')}">
                   <b>{$RELATED_RECORD->getDisplayValue('lastname')}</b>, {$RELATED_RECORD->getDisplayValue('firstname')}
                </a>
            </td>
            <td style="padding:2px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;width:10em;font-size:80%">{$RELATED_RECORD->getDisplayValue('title')}</td>
            <td style="padding:2px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis">{$RELATED_RECORD->getDisplayValue('email')}</td>
 								{assign var=SOFTPHONE value=berliSoftphones_Record_Model::getSoftphonePrefix()}
								{if $SOFTPHONE and $RELATED_RECORD->getDisplayValue('phone')}
									{assign var=PHONE_FIELD_VALUE value=$RELATED_RECORD->getDisplayValue('phone')}
									{assign var=PHONE_NUMBER value=$PHONE_FIELD_VALUE|regex_replace:"/[-()\s]/":""}
									<td style="padding:2px;white-space:nowrap;width:11em"><a class="phoneField" data-value="{$PHONE_NUMBER}" record="{$RELATED_RECORD->getId()}" href="{$SOFTPHONE}{$PHONE_NUMBER}">{$RELATED_RECORD->getDisplayValue('phone')}</a></td>
                                 {else}
                                    <td style="padding:2px;white-space:nowrap;width:11em">{$RELATED_RECORD->getDisplayValue('phone')}</td>  
 								{/if}
		</tr>
	{/foreach}
</table>
	{assign var=NUMBER_OF_RECORDS value=count($RELATED_RECORDS)}
	{if $NUMBER_OF_RECORDS eq 10}
		<div class="row-fluid">
			<div class="pull-right">
				<a class="moreRecentContacts cursorPointer">{vtranslate('LBL_MORE',$MODULE_NAME)}</a>
			</div>
		</div>
	{/if}
{/strip}

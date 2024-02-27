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
    <tr style="border-bottom:1px solid #c9c9c9">
    <th style='text-align:left'>{vtranslate('Project Task Name',$MODULE)}
    <th style='text-align:left;width:10em'>{vtranslate('Project Task Status',$MODULE)}
    <th style='text-align:right;width:6em'>{vtranslate('Progress',$MODULE)}
    </tr>
	{foreach item=RELATED_RECORD from=$RELATED_RECORDS}
        <tr style="border-bottom:1px solid #dadada">
        <td style="padding:2px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;"><a href="{$RELATED_RECORD->getDetailViewUrl()}" id="{$MODULE}_{$RELATED_MODULE}_Related_Record_{$RELATED_RECORD->get('id')}">
                {$RELATED_RECORD->getDisplayValue('projecttaskname')}
			</a>
        <td style="padding:2px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;">{$RELATED_RECORD->getDisplayValue('projecttaskstatus')}
        <td class="pull-right">{$RELATED_RECORD->getDisplayValue('projecttaskprogress')}
        </tr>
	{/foreach}
    </table>
	{assign var=NUMBER_OF_RECORDS value=count($RELATED_RECORDS)}
	{if $NUMBER_OF_RECORDS eq 5}
		<div class="row-fluid">
			<div class="pull-right">
				<a class="moreRecentTasks cursorPointer">{vtranslate('LBL_MORE',$MODULE)}</a>
			</div>
		</div>
	{/if}
{/strip}
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
	<div class="recordDetails">
		<div>
			<h4>{vtranslate('LBL_RECORD_SUMMARY',$MODULE_NAME)}</h4>
			<hr>
		</div>
        {foreach item=SUMMARY_CATEGORY from=$SUMMARY_INFORMATION}
            <div class="row-fluid textAlignCenter roundedCorners">
                {foreach key=FIELD_NAME item=FIELD_VALUE from=$SUMMARY_CATEGORY}
                    <div class="well squeezedWell span3" style="line-height:2em">
                    {if ($FIELD_NAME == "LBL_TASKS_OPEN" || $FIELD_NAME=="LBL_TASKS_DUE" || $FIELD_NAME=="LBL_TASKS_COMPLETED" ) && $FIELD_VALUE >0}
                    <a href="index.php?module=Project&relatedModule=ProjectTask&view=Detail&mode=showRelatedList&tab_label=Project%20Tasks&record={$RECORD->getId()}">
                    {/if}
                        {vtranslate($FIELD_NAME,$MODULE_NAME)}
                        <br><span style="font-size:150%">
                        {$FIELD_VALUE|default:0}
                        </span>
                    {if ($FIELD_NAME == "LBL_TASKS_OPEN" || $FIELD_NAME=="LBL_TASKS_DUE" || $FIELD_NAME=="LBL_TASKS_COMPLETED" ) && $FIELD_VALUE >0}
                    </a>
                    {/if}
                    </div>
                {/foreach}
            </div>
        {/foreach}
		{include file='SummaryViewContents.tpl'|@vtemplate_path}
	</div>
{/strip}
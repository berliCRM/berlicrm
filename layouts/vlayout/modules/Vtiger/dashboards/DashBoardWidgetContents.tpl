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
{if count($DATA) gt 0 }
	<input class="widgetData" type='hidden' value='{$DATA|@json_encode:JSON_HEX_APOS}'>
    {if $smarty.request.name == "LeadsByStatus" || $smarty.request.name == "LeadsByIndustry" || $smarty.request.name == "LeadsBySource" || $smarty.request.name == "FunnelAmount"}
        <div class="widgetChartContainer" style="height:{$WIDGET->getHeight()*250-20}px;width:98%;"></div>
    {else}
        <div class="widgetChartContainer" style="height:{$WIDGET->getHeight()*250}px;width:98%;"></div>
    {/if}
{else}
	<span class="noDataMsg">
		{vtranslate('LBL_EQ_ZERO')} {vtranslate($MODULE_NAME, $MODULE_NAME)} {vtranslate('LBL_MATCHED_THIS_CRITERIA')}
	</span>
{/if}
{/strip}
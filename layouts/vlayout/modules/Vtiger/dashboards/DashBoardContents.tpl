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

<div class="grid-stack">
	{assign var=COLUMNS value=2}
	{assign var=ROW value=1}
	{foreach from=$WIDGETS item=WIDGET name=count}
		{assign var=WIDGETDOMID value=$WIDGET->get('linkid')}
		{if $WIDGET->getName() eq 'MiniList'}
			{assign var=WIDGETDOMID value=$WIDGET->get('linkid')|cat:'-':$WIDGET->get('widgetid')}
		{elseif $WIDGET->getName() eq 'Notebook'}
			{assign var=WIDGETDOMID value=$WIDGET->get('linkid')|cat:'-':$WIDGET->get('widgetid')}
		{/if}
		<div class="grid-stack-item" 
		gs-w="{$WIDGET->getWidth()}" 
		gs-h="{$WIDGET->getHeight()}" 
		gs-y="{$WIDGET->getPositionCol($COLCOUNT)}" 
		gs-x="{$WIDGET->getPositionRow($ROW)}">
			<div id="{$WIDGETDOMID}" 
			{assign var=COLCOUNT value=($smarty.foreach.count.index % $COLUMNS)+1} 
			data-sizex="{$WIDGET->getWidth()}" 
			data-sizey="{$WIDGET->getHeight()}"
			class="grid-stack-item-content dashboardWidget dashboardWidget_{$smarty.foreach.count.index}" 
			data-url="{$WIDGET->getUrl()}" 
			data-mode="open" 
			data-name="{$WIDGET->getName()}">
			</div>
		</div>
	{/foreach}
	<input type="hidden" id=row value="{$ROWCOUNT}" />
	<input type="hidden" id=col value="{$COLCOUNT}" />
	<input type="hidden" id="userDateFormat" value="{$CURRENT_USER->get('date_format')}" />
</div>	
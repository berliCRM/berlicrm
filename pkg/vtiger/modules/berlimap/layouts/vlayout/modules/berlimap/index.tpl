 {*<!--/************************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/-->*}

{strip}
<input id="iconpath" value="modules/berlimap/icons/blueIcon.png" type="hidden">
<input id="myiconpath" value="modules/berlimap/icons/red-dot.png" type="hidden">
<style>
      .map {
        height: 100%;
        width: 100%;
      }
      .ol-popup {
        position: absolute;
        background-color: white;
        -webkit-filter: drop-shadow(0 1px 4px rgba(0,0,0,0.2));
        filter: drop-shadow(0 1px 4px rgba(0,0,0,0.2));
        padding: 15px;
        border-radius: 10px;
        border: 1px solid #cccccc;
        bottom: 12px;
        left: -50px;
        min-width: 280px;
      }
      .ol-popup:after, .ol-popup:before {
        top: 100%;
        border: solid transparent;
        content: " ";
        height: 0;
        width: 0;
        position: absolute;
        pointer-events: none;
      }
      .ol-popup:after {
        border-top-color: white;
        border-width: 10px;
        left: 48px;
        margin-left: -10px;
      }
      .ol-popup:before {
        border-top-color: #cccccc;
        border-width: 11px;
        left: 48px;
        margin-left: -11px;
      }
      .ol-popup-closer {
        text-decoration: none;
        position: absolute;
        top: 2px;
        right: 8px;
      }
      .ol-popup-closer:after {
        content: "✖";
      }
</style>
<input type="hidden" id="geoapikey" value="{$GEOAPIKEY}" />
<div >
<br>
<table class="summary-table" style="width:60%;">
	<tr class="summaryViewEntries">
		<td>
			<select id="modulefilter" style="width:350px;">
				{foreach key=GROUP_LABEL item=GROUP_CUSTOM_VIEWS from=$CUSTOMVIEWSBYMODULE}
					<optgroup label="{vtranslate($GROUP_LABEL)}">
						{foreach item="CUSTOM_VIEW" from=$GROUP_CUSTOM_VIEWS}
							<option  data-editurl="{$CUSTOM_VIEW->getEditUrl()}" data-module="{$GROUP_LABEL}" data-public="{$CUSTOM_VIEW->isPublic() && $CURRENT_USER_MODEL->isAdminUser()}" id="filterOptionId_{$CUSTOM_VIEW->get('cvid')}" value="{$CUSTOM_VIEW->get('cvid')}" data-id="{$CUSTOM_VIEW->get('cvid')}" {if $VIEWID neq '' && $VIEWID neq '0'  && $VIEWID == $CUSTOM_VIEW->getId()} selected="selected" {elseif ($VIEWID == '' or $VIEWID == '0')&& $CUSTOM_VIEW->isDefault() eq 'true'} selected="selected" {/if} class="filterOptionId_{$CUSTOM_VIEW->get('cvid')}">{if $CUSTOM_VIEW->get('viewname') eq 'All'}{vtranslate($CUSTOM_VIEW->get('viewname'), $MODULE)} {else}{vtranslate($CUSTOM_VIEW->get('viewname'), $MODULE)}{/if}{if $CUSTOM_VIEW->get('viewname') neq 'All'} [ {$CUSTOM_VIEW->getOwnerName()} ]  {/if}</option>
						{/foreach}
					</optgroup>
				{/foreach}
			</select>
		<td>
	</tr>
<tr>
	<td class="fieldLabel" style="width:20%">
		<button class="btn btn-success" id='showButton'  name='showButton'><strong>{vtranslate('LBL_VIEW_MAP', $MODULE)}</strong></button>
	</td>
</tr>
 </table>
<div id="map" class="map"></div>
<div id="map1" class="map1"></div>
<div id="popup" class="ol-popup" style="display:none" >
      <a href="#" id="popup-closer" class="ol-popup-closer"></a>
      <div id="popup-content"></div>
</div>
{/strip}
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
<div class="listViewPageDiv">
	<div class="showPanelBg" style="padding:20px;margin:20px;">
 		<div class="listViewTopMenuDiv noprint">
 			<div class="listViewActionsDiv row-fluid">
				<span class="span6 btn-toolbar">
				</span>
				<div class="listViewContentDiv" id="listViewContents">
					<span >
						<h1>{vtranslate('LBL_CALLER_INFORMATION', $MODULE_NAME)}</h1>
					</span>
					<br><br>
					{if $CALLERPHONE neq ''}
						<span class="span6 margin0px">
							<span >
								<h2>{vtranslate('LBL_GIVEN_NUMBER', $MODULE_NAME)}&nbsp;{$CALLERPHONE}</h2>
							</span>
						</span>
						<span class="span7">
							<div style="position:relative;display:inline;">
								{if count($RECORDS) > 0 }
								{foreach key=RECORDID item=RECORD from=$RECORDS}
								<span >
									<span class="recordLabel "><h3>{vtranslate({$RECORD->getModuleName()}, $MODULE_NAME)}</h3>
								   </span>
								</span>
								<div style="vertical-align:top;line-height:24px;display:inline-block;width:300px;min-height:40px;padding:10px;margin:5px;border:1px solid #ccc;border-radius:3px;background:url(themes/softed/images/layerPopupBg.gif);">
								   <span class="fieldLabel " title="{$RECORD->getName()}">{$RECORD->getName()}</span>
									<br>
									<a class="fieldLabel" title="{$RECORD->getName()}" href ="{$RECORD->getDetailViewUrl()}" target="_blank">{vtranslate('LBL_VIEW_DETAILS', $MODULE_NAME)}</a>
									<br>
								</div>
								{/foreach}
								{else}
								<span >
									<span class="recordLabel "><h3>{vtranslate('LBL_NO_ENTRY', $MODULE_NAME)}</h3>
								   </span>
								</span>
								{/if}
							</div>
						</span> 
					{else}
						<span >
							<span class="recordLabel "><h3>{vtranslate('LBL_NO_PHONE', $MODULE_NAME)}</h3>
							</span>
						</span>
					{/if}
				</div>
			</div>
		</div>
	</div>
	<div class="container">
		<div class="center">
			<button class="btn btn-success" onclick="javascript:window.history.back();"><strong>{vtranslate('LBL_GO_BACK', 'Vtiger')}</strong></button>
		</div>
	</div>
</div>

{/strip}

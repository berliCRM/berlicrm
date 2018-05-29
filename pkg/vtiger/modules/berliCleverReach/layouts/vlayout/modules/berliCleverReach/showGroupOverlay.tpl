{*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Modified and improved by crm-now.de
 * 27.7. bb			renamed mcgrouplist to crgrouplist, removed "groups", renamed list
 ************************************************************************************}
{strip}

<div class='modelContainer modal basicCreateView'>
	<div class="modal-header">
		<button data-dismiss="modal" class="close" title="{vtranslate('LBL_CLOSE')}">x</button>
		<h3>{vtranslate('LBL_LISTE', $MODULE)}&nbsp;</h3>
	</div>
	
	{if $ERRORCODE =="noauth"}
		<div class="modal-body">
		<h3>{vtranslate('LBL_API_AUTH_ERROR', $MODULE)}</h3>
		<br>
		<a href='index.php?module=berliCleverReach&parent=Settings&view=Index'>{vtranslate('LBL_API_AUTH_ERROR_ACT', $MODULE)}</a>
		</div>
	{elseif $ERRORCODE =="timeout"}
		<div class="modal-body">
		<h3>{vtranslate('LBL_API_TIMEOUT', $MODULE)}</h3>
		<br>
		{vtranslate('LBL_API_TIMEOUT_ACT', $MODULE)}
		</div>
	{else}
		
	<div class="modal-body">
		<form class="form-horizontal">		
			<div class="control-group">
				<div class="control-label">{vtranslate('LBL_LISTE',$MODULE)}
				</div>
				<div class="controls">	
					<div id="selW5D_chzn" class="chzn-container chzn-container-single">	
						<select id="crgrouplist" name="crgrouplist" >
							<option value="">{vtranslate('LBL_NONE',$MODULE)}</option>
							{foreach key=key item=data from=$APILISTE}
								<option value="{$data.id}">{$data.name|escape:"html"}</option>
							{/foreach}
						</select>
					</div>
				</div>	
			</div>
		</form>
	</div>

	{include file='GroupSyncFooter.tpl'|@vtemplate_path:$MODULE}
	{/if}
</div>

{/strip}


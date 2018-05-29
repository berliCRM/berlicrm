{*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Modified and improved by crm-now.de
 ************************************************************************************}
{strip}

<div class='modelContainer modal basicCreateView'>
	<div class="modal-header">
		<button data-dismiss="modal" class="close" title="{vtranslate('LBL_CLOSE')}">x</button>
		<h3>{vtranslate('LBL_LISTE', $MODULE)}&nbsp;</h3>
	</div>
	<div class="modal-body">
		<form class="form-horizontal">		
			<div class="control-group">
				<div class="control-label">{vtranslate('LBL_LISTE',$MODULE)}
				</div>
				<div class="controls">	
					<div id="selW5D_chzn" class="chzn-container chzn-container-single">	
						<select id="mcgrouplist" name="mcgrouplist" >
							<option value="">{vtranslate('LBL_NONE',$MODULE)}</option>
							{foreach key=key item=data from=$APILISTE}
								<option value="{$data.id}">{$data.name}</option>
							{/foreach}
						</select>
					</div>
				</div>
				<div class="control-label" id="groupname">{vtranslate('LBL_GROUPE',$MODULE)}
				</div>
				<div class="controls">	
					<div id="groups_row" class="chzn-container chzn-container-single">	
						<input id="groups" type="text" value="" disabled="disabled">
					</div>
				</div>
				<div class="control-label" id="new_groupname">{vtranslate('LBL_NEW_GROUP',$MODULE)}
				</div>
				<div class="controls">	
					<div id="newgroupname_row" class="chzn-container chzn-container-single">	
						<input type="text" value="" id="newGroupName" disabled="disabled">
					</div>
				</div>
			</div>
		</form>
	</div>

	{include file='GroupSyncFooter.tpl'|@vtemplate_path:$MODULE}
</div>

{/strip}


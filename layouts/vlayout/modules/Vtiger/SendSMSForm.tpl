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
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<div id="sendSmsContainer" class='modelContainer'>
	<div class="modal-header contentsBackground">
        <button data-dismiss="modal" class="close" title="{vtranslate('LBL_CLOSE')}">&times;</button>
		<h3>{vtranslate('LBL_SEND', $MODULE_NAME)}&nbsp;<font color="red">{$SELECTED_IDS|count}</font>&nbsp;{vtranslate('LBL_SMS_TO_SELECTED_NUMBERS', $MODULE_NAME)}</h3>
	</div>
	<form class="form-horizontal" id="massSMS" method="post" action="index.php" content="text/html;charset=UTF-8">
		<input type="hidden" id="smsModuleName" name="smsModuleName" value="{$MODULE}" />
		<input type="hidden" name="source_module" value="{$SOURCE_MODULE}" />
		<input type="hidden" name="action" value="" />
		<input type="hidden" name="module" value="{$MODULE}" />
		{if $SINGLE_RECORD neq ''}
			<input type="hidden" name="view" value="Detail" />
		{else}
			<input type="hidden" name="view" value="List" />
		{/if}
		<input type="hidden" name="record" value="{$RECORD}" />
		<input type="hidden" name="selected_ids" value={ZEND_JSON::encode($SELECTED_IDS)}>
		<input type="hidden" name="excluded_ids" value={ZEND_JSON::encode($EXCLUDED_IDS)}>
        <input type="hidden" name="search_key" value= "{$SEARCH_KEY}" />
        <input type="hidden" name="operator" value="{$OPERATOR}" />
        <input type="hidden" name="search_value" value="{$ALPHABET_VALUE}" />
        <input type="hidden" name="search_params" value='{ZEND_JSON::encode($SEARCH_PARAMS)}' />
               
		<div class="modal-body tabbable">
			<div>
				<span><strong>{vtranslate('LBL_STEP_1',$MODULE_NAME)}</strong></span>
				&nbsp;:&nbsp;
				{vtranslate('LBL_SELECT_THE_PHONE_NUMBER_FIELDS_TO_SEND',$MODULE_NAME)}
			</div>
			<select name="smsFields[]" id="smsFields" data-placeholder="{vtranslate('LBL_ADD_MORE_FIELDS',$MODULE_NAME)}" multiple class="chzn-select" data-validation-engine="validate[required]">
				<optgroup>
					{foreach item=PHONE_FIELD from=$PHONE_FIELDS}
						{assign var=PHONE_FIELD_NAME value=$PHONE_FIELD->get('name')}
						<option value="{$PHONE_FIELD_NAME}">
							{if !empty($SINGLE_RECORD)}
								{assign var=FIELD_VALUE value=$SINGLE_RECORD->get($PHONE_FIELD_NAME)}
							{/if}
							{vtranslate($PHONE_FIELD->get('label'), $SOURCE_MODULE)}{if !empty($FIELD_VALUE)} ({$FIELD_VALUE}){/if}
						</option>
					{/foreach}
				</optgroup>
			</select>
			<hr>
			<div>
				<span><strong>{vtranslate('LBL_STEP_2',$MODULE_NAME)}</strong></span>
				&nbsp;:&nbsp;
				{vtranslate('LBL_TYPE_THE_MESSAGE',$MODULE_NAME)}&nbsp;(&nbsp;{vtranslate('LBL_SMS_MAX_CHARACTERS_ALLOWED',$MODULE_NAME)}&nbsp;)
			</div>
			<textarea class="input-xxlarge" name="smsMessage" id="smsMessage" placeholder="{vtranslate('LBL_WRITE_YOUR_MESSAGE_HERE', $MODULE_NAME)}" data-validation-engine="validate[required]"></textarea>
		</div>
		<div class="modal-footer">
			<div class=" pull-right cancelLinkContainer">
				<a class="cancelLink" type="reset" data-dismiss="modal">{vtranslate('LBL_CANCEL', $MODULE_NAME)}</a>
			</div>
			<button class="btn btn-success" type="submit" name="saveButton"><strong>{vtranslate('LBL_SEND', $MODULE_NAME)}</strong></button>
		</div>
	</form>
</div>
{/strip}

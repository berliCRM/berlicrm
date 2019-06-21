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
{assign var="SPECIAL_VALIDATOR" value=$FIELD_MODEL->getValidator()}
<input id="{$MODULE}_editView_fieldName_{$FIELD_NAME}" 
	type="text" 
	class="input-medium numberField" 
	data-validation-engine="validate[{if $FIELD_MODEL->isMandatory()} required,{/if}funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" 
	name="{$FIELD_MODEL->getFieldName()}"
	data-fieldinfo='{$FIELD_MODEL->getFieldInfo()|@json_encode:JSON_HEX_APOS}' 
	value="{$FIELD_MODEL->getEditViewDisplayValue($FIELD_MODEL->get('fieldvalue'))}" 
	{if !empty($SPECIAL_VALIDATOR)}
		data-validator='{$SPECIAL_VALIDATOR|@json_encode:JSON_HEX_APOS}'
	{/if} 
	data-decimal-separator="{$USER_MODEL->get('currency_decimal_separator')}" 
	data-group-separator="{$USER_MODEL->get('currency_grouping_separator')}" 
	data-number-of-decimal-places="{$USER_MODEL->get('no_of_currency_decimals')}">
{/strip}
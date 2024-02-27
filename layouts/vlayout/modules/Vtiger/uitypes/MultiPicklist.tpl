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
{assign var="FIELD_VALUE_LIST" value=explode(' |##| ',$FIELD_MODEL->get('fieldvalue'))}
<input type="hidden" name="{$FIELD_MODEL->getFieldName()}" value="" />
{assign var=VIEW_NAME value={getPurifiedSmartyParameters('view')}}
<select id="{$MODULE}_{$VIEW_NAME}_fieldName_{$FIELD_MODEL->get('name')}" multiple class="select2" name="{$FIELD_MODEL->getFieldName()}[]" data-fieldinfo='{$FIELD_MODEL->getFieldInfo()|@json_encode:JSON_HEX_APOS}' {if $FIELD_MODEL->isMandatory() eq true} data-validation-engine="validate[required,funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" {if !empty($SPECIAL_VALIDATOR)}data-validator='{Zend_Json::encode($SPECIAL_VALIDATOR)}'{/if} {/if} style="width: 60%">
    {foreach item=PICKLIST_VALUE key=PICKLIST_NAME from=$FIELD_MODEL->getPicklistValues()}
        <option value="{$PICKLIST_NAME|escape}" {if in_array($PICKLIST_NAME, $FIELD_VALUE_LIST)} selected{/if}>{$PICKLIST_VALUE|escape}</option>
    {/foreach}
</select>
{/strip}
{if $smarty.request.view == "MassActionAjax"}
<input type="checkbox" id="add[{$FIELD_MODEL->getFieldName()}]" checked="checked" name="add[{$FIELD_MODEL->getFieldName()}]"><label for="add[{$FIELD_MODEL->getFieldName()}]" style="display:inline;vertical-align:middle"> {vtranslate('LBL_MASSOP_APPEND_MULTIPICKLIST')}</label>
{/if}
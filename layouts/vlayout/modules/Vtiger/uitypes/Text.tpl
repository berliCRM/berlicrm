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
{assign var="FIELD_NAME" value=$FIELD_MODEL->get('name')}



{if $FIELD_MODEL->get('uitype') eq '19'} 
    <textarea id="{$MODULE}_editView_fieldName_{$FIELD_NAME}" class="span11 {if $FIELD_MODEL->isNameField()}nameField{/if}" name="{$FIELD_MODEL->getFieldName()}" {if $FIELD_NAME eq "notecontent"}id="{$FIELD_NAME}"{/if} data-validation-engine="validate[{if $FIELD_MODEL->isMandatory() eq true}required,{/if}funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" data-fieldinfo='{$FIELD_MODEL->getFieldInfo()|@json_encode:JSON_HEX_APOS}' {if !empty($SPECIAL_VALIDATOR)}data-validator='{$SPECIAL_VALIDATOR|@json_encode:JSON_HEX_APOS}'}{/if}>
        {$FIELD_MODEL->get('fieldvalue')}
    </textarea>
{/if}   


{if $FIELD_MODEL->get('uitype') eq '20'}
    <textarea id="{$MODULE}_editView_fieldName_{$FIELD_NAME}" class="span11 {if $FIELD_MODEL->isNameField()}nameField{/if}" name="{$FIELD_MODEL->getFieldName()}" {if $FIELD_NAME eq "notecontent"}id="{$FIELD_NAME}"{/if} data-validation-engine="validate[{if $FIELD_MODEL->isMandatory() eq true}required,{/if}funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" data-fieldinfo='{$FIELD_MODEL->getFieldInfo()|@json_encode:JSON_HEX_APOS}' {if !empty($SPECIAL_VALIDATOR)}data-validator='{$SPECIAL_VALIDATOR|@json_encode:JSON_HEX_APOS}'}{/if}>
        {$FIELD_MODEL->get('fieldvalue')}
    </textarea>
{/if}

{if $FIELD_MODEL->get('uitype') eq '21'}
    {if $FIELD_MODEL->get('name') eq 'signature'}
        
        <script type="text/javascript" src="libraries/jquery/ckeditor/ckeditor.js">
        </script>
        
        <textarea id="{$MODULE}_editView_fieldName_{$FIELD_NAME}" class="span11 {if $FIELD_MODEL->isNameField()}nameField{/if}" name="{$FIELD_MODEL->getFieldName()}" {if $FIELD_NAME eq "notecontent"}id="{$FIELD_NAME}"{/if} data-validation-engine="validate[{if $FIELD_MODEL->isMandatory() eq true}required,{/if}funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" data-fieldinfo='{$FIELD_MODEL->getFieldInfo()|@json_encode:JSON_HEX_APOS}' {if !empty($SPECIAL_VALIDATOR)}data-validator='{$SPECIAL_VALIDATOR|@json_encode:JSON_HEX_APOS}'}{/if}>
            {$FIELD_MODEL->get('fieldvalue')}
        </textarea>

        <script type="text/javascript">
            CKEDITOR.replace( 'signature');
        </script>
        
    {else}
        <textarea id="{$MODULE}_editView_fieldName_{$FIELD_NAME}" class="span11 {if $FIELD_MODEL->isNameField()}nameField{/if}" name="{$FIELD_MODEL->getFieldName()}" {if $FIELD_NAME eq "notecontent"}id="{$FIELD_NAME}"{/if} data-validation-engine="validate[{if $FIELD_MODEL->isMandatory() eq true}required,{/if}funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" data-fieldinfo='{$FIELD_MODEL->getFieldInfo()|@json_encode:JSON_HEX_APOS}' {if !empty($SPECIAL_VALIDATOR)}data-validator='{$SPECIAL_VALIDATOR|@json_encode:JSON_HEX_APOS}'}{/if}>
            {$FIELD_MODEL->get('fieldvalue')}
        </textarea>
    {/if}
{/if}


{if $FIELD_MODEL->get('uitype') neq '19' && $FIELD_MODEL->get('uitype') neq '20' && $FIELD_MODEL->get('uitype') neq '21' }
    <textarea id="{$MODULE}_editView_fieldName_{$FIELD_NAME}" class="row-fluid {if $FIELD_MODEL->isNameField()}nameField{/if}" name="{$FIELD_MODEL->getFieldName()}" data-validation-engine="validate[{if $FIELD_MODEL->isMandatory() eq true}required,{/if}funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" data-fieldinfo='{$FIELD_MODEL->getFieldInfo()|@json_encode:JSON_HEX_APOS}' {if !empty($SPECIAL_VALIDATOR)}data-validator='{$SPECIAL_VALIDATOR|@json_encode:JSON_HEX_APOS}'{/if}>
        {$FIELD_MODEL->get('fieldvalue')}  
    </textarea>
{/if}



{/strip}
{if $smarty.request.view == "MassActionAjax"}
<br><input type="checkbox" id="add[{$FIELD_NAME}]"  checked="checked" name="add[{$FIELD_NAME}]"><label for="add[{$FIELD_NAME}]" style="display:inline;vertical-align:middle"> {vtranslate('LBL_MASSOP_APPEND_TEXT')}</label>
{/if}
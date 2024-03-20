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
    {assign var=SEARCH_VALUES value=explode(',',$SEARCH_INFO['searchValue'])}
    <div class="row-fluid">
        <select class="select2 listSearchContributor span9" name="{$FIELD_MODEL->get('name')}" multiple data-fieldinfo='{$FIELD_MODEL->getFieldInfo()|@json_encode:JSON_HEX_APOS}'>
            {foreach item=PICKLIST_LABEL key=PICKLIST_KEY from=$FIELD_MODEL->getPicklistValues()}
                <option value="{$PICKLIST_KEY|escape}" {if in_array($PICKLIST_KEY,$SEARCH_VALUES) && ($PICKLIST_KEY neq "")} selected{/if}>{$PICKLIST_LABEL|escape}</option>
            {/foreach}
        </select>
    </div>
{/strip}
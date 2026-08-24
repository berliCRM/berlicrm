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
    {assign var="FIELD_INFO" value=Zend_Json::encode($FIELD_MODEL->getFieldInfo())}
    <div class="row-fluid">
    {assign var=ASSIGNED_USER_ID value=$FIELD_MODEL->get('name')}
    {assign var=PICKLIST_VALUES value=$FIELD_MODEL->getUserPicklistValues()}
    {assign var=SEARCH_VALUES value=explode(',',$SEARCH_INFO['searchValue'])}
    {assign var=SEARCH_VALUES value=array_map("trim",$SEARCH_VALUES)}

	{assign var=ACCESSIBLE_USER_LIST value=$USER_MODEL->getAccessibleUsersForModule($MODULE)}
	{assign var=ACCESSIBLE_GROUP_LIST value=$USER_MODEL->getAccessibleGroupForModule($MODULE)}

	<select class="select2 listSearchContributor span10 {$ASSIGNED_USER_ID}" name="{$ASSIGNED_USER_ID}" multiple style="width:150px;" data-fieldinfo='{$FIELD_INFO|escape}'>
		<optgroup label="{vtranslate('LBL_USERS')}">
			{foreach $PICKLIST_VALUES AS $USER_ID => $USER_NAME}
				<option value="{$USER_ID}" data-picklistvalue= '{$USER_ID}'{if in_array($USER_ID, $SEARCH_VALUES)} selected{/if}>
					{$USER_NAME}
				</option>
			{/foreach}
		</optgroup>
	</select>
    </div>
{/strip}
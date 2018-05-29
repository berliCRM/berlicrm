{*<!--
/* +***********************************************************************************************************************************
 * The contents of this file are subject to the berliCRM Public License Version 1.0 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is from the crm-now GmbH.
 * The Initial Developer of the Original Code is crm-now. Portions created by crm-now are Copyright (C) www.crm-now.de. 
 * Portions created by vtiger are Copyright (C) www.vtiger.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */
-->*}
{strip}
	<select name="coloredFieldsList" id="coloredFieldsList" class="select2-choice">
		<option selected value="">{vtranslate('LBL_SELECT_OPTION',$QUALIFIED_MODULE)}</option>
			{foreach from=$MODULE_FIELDS item=item key=key}
				<option value="{$item['fieldid']}" id="{$item['fieldid']}" name="{$item['fieldname']}" >{vtranslate($item['fieldlabel'],$item['sourceModule'])}</option>
			{/foreach}
	</select>
{/strip}

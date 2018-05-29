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
{assign var="ModulesEntity" value=$MODULE_MODEL->getModulesEntity()}
<div>
	<div class="widget_header row-fluid">
		<div class="col-md-10"><h3>{vtranslate($MODULE, $QUALIFIED_MODULE)}</h3>{vtranslate('LBL_COLORED_LISTVIEW_DESCRIPTION', $QUALIFIED_MODULE)}</div>
		<div class="col-md-2"></div>
	</div>
	<hr>

	<div class="contents row-fluid">
		<table class="table table-bordered table-condensed themeTableColor">
			<tr class="blockHeader">
				<th class="" colspan="2">
					<span class="alignMiddle">{vtranslate('LBL_SELECT_DESCRIPTION', $QUALIFIED_MODULE)}</span>
				</th>
			</tr>
			<tr>
				<td class="">
					<label class="muted pull-right marginRight10px">{vtranslate('LBL_SELECT_MODULE', $QUALIFIED_MODULE)}</label>
				</td>
				<td style="border-left: none;" class="">
					<select name="pickListModules" id="pickListModules" class="select2-choice" disabled>
						<option selected="selected" value="">{vtranslate('LBL_SELECT_MODULE',$QUALIFIED_MODULE)}</option>
							{foreach from=$ModulesEntity item=item key=key}
							<option value="{$item['modulename']}" >{vtranslate($item['modulename'],$item['modulename'])}</option>
							{/foreach}
					</select>
				</td>
			</tr>
			<tr>
				<td>
					<label class="muted pull-right marginRight10px">{vtranslate('LBL_SELECT_FIELD', $QUALIFIED_MODULE)}</label>
				</td>
				<td style="border-left: none;" class="">
					<div id="FieldChoiceContainer">
						<select name="coloredFieldsList" id="coloredFieldsList" class="select2-choice">
								<option selected value="">{vtranslate('LBL_SELECT_MODULE_FIRST',$QUALIFIED_MODULE)}</option>
						</select>
					</div>
				</td>
			</tr>
		</table>
	</div>		
	<div id="colorFieldsValuesContainer">
	</div>
</div>
{/strip}
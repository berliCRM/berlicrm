{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
********************************************************************************/
-->*}
{strip}
    <div class="container-fluid" id="layoutEditorContainer">
        <input id="selectedModuleName" type="hidden" value="{$SELECTED_MODULE_NAME}" />
        <div class="widget_header row-fluid">
            <div class="span8">
                <h3>{vtranslate('LBL_FIELDS_AND_LAYOUT_EDITOR', $QUALIFIED_MODULE)}</h3>
            </div>
            <div class="span4">
                <div class="pull-right">
                    <select class="select2 span3" name="layoutEditorModules">
                        {foreach item=MODULE_NAME from=$SUPPORTED_MODULES}
                            <option value="{$MODULE_NAME}" {if $MODULE_NAME eq $SELECTED_MODULE_NAME} selected {/if}>{vtranslate($MODULE_NAME, $QUALIFIED_MODULE)}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
        </div>
        <hr>

        <div class="contents tabbable">
            <ul class="nav nav-tabs fontBold massEditTabs">
                <li class="active"><a data-toggle="tab" href="#detailViewLayout">{vtranslate('LBL_DETAILVIEW_LAYOUT', $QUALIFIED_MODULE)}</a></li>
                <li class="relatedListTab"><a data-toggle="tab" href="#relatedTabOrder">{vtranslate('LBL_ARRANGE_RELATED_LIST', $QUALIFIED_MODULE)}</a></li>
            </ul>
            <div class="tab-content layoutContent padding20 themeTableColor overflowVisible">
                <div class="tab-pane active" id="detailViewLayout">
                    {assign var=FIELD_TYPE_INFO value=$SELECTED_MODULE_MODEL->getAddFieldTypeInfo()}
                    {assign var=IS_SORTABLE value=$SELECTED_MODULE_MODEL->isSortableAllowed()}
                    {assign var=IS_BLOCK_SORTABLE value=$SELECTED_MODULE_MODEL->isBlockSortableAllowed()}
                    {assign var=ALL_BLOCK_LABELS value=[]}
                    {if $IS_SORTABLE}
                        <div class="btn-toolbar">
                            <button class="btn addButton addCustomBlock" type="button">
                                <i class="icon-plus"></i>&nbsp;
                                <strong>{vtranslate('LBL_ADD_CUSTOM_BLOCK', $QUALIFIED_MODULE)}</strong>
                            </button>
                            <span class="pull-right">
                                <button class="btn btn-success saveFieldSequence hide" type="button">
                                    <strong>{vtranslate('LBL_SAVE_FIELD_SEQUENCE', $QUALIFIED_MODULE)}</strong>
                                </button>
                            </span>
                        </div>
                    {/if}
                    <div id="moduleBlocks">
                        {foreach key=BLOCK_LABEL_KEY item=BLOCK_MODEL from=$BLOCKS}
                            {assign var=FIELDS_LIST value=$BLOCK_MODEL->getLayoutBlockActiveFields()}
                            {assign var=BLOCK_ID value=$BLOCK_MODEL->get('id')}
                            {$ALL_BLOCK_LABELS[$BLOCK_ID] = $BLOCK_LABEL_KEY}
                            <div id="block_{$BLOCK_ID}" class="editFieldsTable block_{$BLOCK_ID} marginBottom10px border1px {if $IS_BLOCK_SORTABLE} blockSortable{/if}" data-block-id="{$BLOCK_ID}" data-sequence="{$BLOCK_MODEL->get('sequence')}" style="border-radius: 4px 4px 0px 0px;background: white;">
                                <div class="row-fluid layoutBlockHeader">
                                    <div class="blockLabel span5 padding10 marginLeftZero">
                                        <img class="alignMiddle" src="{vimage_path('drag.png')}" />&nbsp;&nbsp;
                                        {assign var=BLOCK_LABEL_TRANS value=vtranslate($BLOCK_LABEL_KEY, $SELECTED_MODULE_NAME)}
                                        <strong>{$BLOCK_LABEL_TRANS}</strong>
					{if $BLOCK_LABEL_TRANS != $BLOCK_LABEL_KEY}
						<span class="no-drag" style="opacity:0.50;">&nbsp;&nbsp;({$BLOCK_LABEL_KEY})</span>
					{/if}
                                    </div>
                                    <div class="span6 marginLeftZero" style="float:right !important;"><div class="pull-right btn-toolbar blockActions" style="margin: 4px;">
                                            {if $BLOCK_MODEL->isAddCustomFieldEnabled()}
                                                <div class="btn-group">
                                                    <button class="btn addCustomField" type="button">
                                                        <strong>{vtranslate('LBL_ADD_CUSTOM_FIELD', $QUALIFIED_MODULE)}</strong>
                                                    </button>
                                                </div>
                                            {/if}
                                            {if $BLOCK_MODEL->isActionsAllowed()}
                                                <div class="btn-group"><button class="btn dropdown-toggle" data-toggle="dropdown">
                                                        <strong>{vtranslate('LBL_ACTIONS', $QUALIFIED_MODULE)}</strong>&nbsp;&nbsp;
                                                        <i class="caret"></i>
                                                    </button>
                                                    <ul class="dropdown-menu pull-right">
                                                        <li class="blockVisibility" data-visible="{if !$BLOCK_MODEL->isHidden()}1{else}0{/if}" data-block-id="{$BLOCK_MODEL->get('id')}">
                                                            <a href="javascript:void(0)">
                                                                <i class="icon-ok {if $BLOCK_MODEL->isHidden()} hide {/if}"></i>&nbsp;
                                                                {vtranslate('LBL_SHOW_ACTIVE', $QUALIFIED_MODULE)}
                                                            </a>
                                                        </li>
                                                        <li class="inActiveFields">
                                                            <a href="javascript:void(0)">{vtranslate('LBL_SHOW_INACTIVE', $QUALIFIED_MODULE)}</a>
                                                        </li>
                                                        {if $BLOCK_MODEL->isCustomized()}
                                                            <li class="deleteCustomBlock">
                                                                <a href="javascript:void(0)">{vtranslate('LBL_DELETE_BLOCK', $QUALIFIED_MODULE)}</a>
                                                            </li>
                                                        {/if}
                                                        {if $BLOCK_MODEL->isBlockRenamingEnabled()}
								                        <li class="renameBlock">
															<a href="javascript:void(0)">{vtranslate('LBL_RENAME_BLOCK', $QUALIFIED_MODULE)}</a>
														</li>
                                                        {/if}
                                                    </ul>
                                                </div>
                                            {/if}
                                        </div>
                                    </div>
                                </div>
                                <div class="blockFieldsList {if $SELECTED_MODULE_MODEL->isFieldsSortableAllowed($BLOCK_LABEL_KEY)}blockFieldsSortable {/if} row-fluid" style="padding:5px;min-height: 27px">
                                    <ul name="sortable1" class="connectedSortable span6" style="list-style-type: none; float: left;min-height: 1px;padding:2px;">
                                        {foreach item=FIELD_MODEL from=$FIELDS_LIST name=fieldlist}
                                            {assign var=FIELD_INFO value=$FIELD_MODEL->getFieldInfo()}
                                            {if $smarty.foreach.fieldlist.index % 2 eq 0}
                                                <li>
                                                    <div class="opacity editFields marginLeftZero border1px" data-block-id="{$BLOCK_ID}" data-field-id="{$FIELD_MODEL->get('id')}" data-sequence="{$FIELD_MODEL->get('sequence')}">
                                                        <div class="row-fluid padding1per">
                                                            {assign var=IS_MANDATORY value=$FIELD_MODEL->isMandatory()}
                                                            <span class="span1">&nbsp;
                                                                {if $FIELD_MODEL->isEditable()}
                                                                    <a>
                                                                        <img src="{vimage_path('drag.png')}" border="0" title="{vtranslate('LBL_DRAG',$QUALIFIED_MODULE)}"/>
                                                                    </a>
                                                                {/if}
                                                            </span>
                                                            <div class="span11 marginLeftZero" style="word-wrap: break-word;">
                                                                <span class="fieldLabel no-drag">{vtranslate($FIELD_MODEL->get('label'), $SELECTED_MODULE_NAME)}&nbsp;
                                                                {if $IS_MANDATORY}<span class="redColor">*</span>{/if}</span>
																
                                                            <span class="btn-group pull-right actions">
                                                                {if $FIELD_MODEL->isEditable()}
                                                                    <a href="javascript:void(0)" class="dropdown-toggle editFieldDetails" data-toggle="dropdown">
                                                                        <i class="icon-pencil alignMiddle" title="{vtranslate('LBL_EDIT', $QUALIFIED_MODULE)}"></i>
                                                                    </a>
                                                                    <div class="basicFieldOperations pull-right hide" style="width: 290px;">
                                                                        <form class="form-horizontal fieldDetailsForm" method="POST">
                                                                            <div class="modal-header contentsBackground">
                                                                                <strong>{vtranslate($FIELD_MODEL->get('label'), $SELECTED_MODULE_NAME)}</strong>
                                                                                <div class="pull-right"><a href="javascript:void(0)" class='cancel'>X</a></div>
                                                                            </div>
																			
																			<div style="margin:5px;padding:5px;">
																				{assign var=uilbl value=$FIELD_MODEL->uitype}
																				{assign var=datatype value=explode("~",$FIELD_MODEL->typeofdata)}
																				{assign var=sqltype value=$SQLTYPES[$FIELD_MODEL->getName()]}
																				{vtranslate("LBL_UITYPE",$QUALIFIED_MODULE)} {vtranslate("LBL_UI$uilbl",$QUALIFIED_MODULE)} <br>
																				{if $FIELD_MODEL->displaytype == 2}
																					{vtranslate("LBL_NOEDITVIEW",$QUALIFIED_MODULE)}
																					<br>
																				{/if}

																				{if !in_array($uilbl,array(10,51,53,56,57,59,73,75,76,77,78,80,81,83,117))}
																					{if substr($sqltype,0,7) == "varchar"}
																						{assign var=maxchars value=substr($sqltype,8,-1)}
																						{vtranslate('LBL_MAXLEN',$QUALIFIED_MODULE)}: {$maxchars} {vtranslate('LBL_CHARS',$QUALIFIED_MODULE)}
																					{elseif $sqltype == "text"}
																						{vtranslate('LBL_MAXLEN',$QUALIFIED_MODULE)}: 64kB
																					{elseif $sqltype == "mediumtext"}
																						{vtranslate('LBL_MAXLEN',$QUALIFIED_MODULE)}: 16MB
																					{/if}
																					{if $datatype[0] == "N" || $datatype[0] == "I" || $datatype[0] == "NN"}
																						{if substr($sqltype,0,7) == "decimal"}
																							{assign var=digits value=explode(",",substr($sqltype,8,-1))}
																							
																							{vtranslate("LBL_NUMFLOAT",$QUALIFIED_MODULE)} {$digits[0]} {vtranslate("LBL_NUMDIGITS",$QUALIFIED_MODULE)} {$digits[1]} {vtranslate("LBL_NUMDECIMALS",$QUALIFIED_MODULE)}
																						{elseif substr($sqltype,0,6) == "bigint"}
																							{vtranslate("LBL_NUMBIGINT",$QUALIFIED_MODULE)}
																						{else}
																							{vtranslate("LBL_NUMINT",$QUALIFIED_MODULE)}
																						{/if}
																					{/if}
																				{/if}
																			</div>
																			
                                                                            <div style="padding-bottom: 5px;">
                                                                                <span>
                                                                                    <input type="hidden" name="mandatory" value="O" />
                                                                                    <label class="checkbox" style="padding-left: 25px; padding-top: 5px;">
                                                                                        <input type="checkbox" name="mandatory" {if $IS_MANDATORY} checked {/if}
                                                                                        {if $FIELD_MODEL->isMandatoryOptionDisabled()} readonly="readonly" {/if} value="M" />&nbsp;
                                                                                    {vtranslate('LBL_MANDATORY_FIELD', $QUALIFIED_MODULE)}
                                                                                </label>
                                                                            </span>
                                                                            <span>
                                                                                <input type="hidden" name="presence" value="1" />
                                                                                <label class="checkbox" style="padding-left: 25px; padding-top: 5px;">
                                                                                    <input type="checkbox" name="presence" {if $FIELD_MODEL->isViewable()} checked {/if}
                                                                                {if $FIELD_MODEL->isActiveOptionDisabled()} readonly="readonly" class="optionDisabled"{/if} {if $IS_MANDATORY} readonly="readonly" {/if} value="2" />&nbsp;
                                                                            {vtranslate('LBL_ACTIVE', $QUALIFIED_MODULE)}
                                                                        </label>
                                                                    </span>
                                                                    <span>
                                                                        <input type="hidden" name="quickcreate" value="1" />
                                                                        <label class="checkbox" style="padding-left: 25px; padding-top: 5px;">
                                                                            <input type="checkbox" name="quickcreate" {if $FIELD_MODEL->isQuickCreateEnabled()} checked {/if}
                                                                        {if $FIELD_MODEL->isQuickCreateOptionDisabled()} readonly="readonly" class="optionDisabled"{/if} {if $IS_MANDATORY} readonly="readonly" {/if} value="2" />&nbsp;
                                                                    {vtranslate('LBL_QUICK_CREATE', $QUALIFIED_MODULE)}
                                                                </label>
                                                            </span>
                                                            <span>
                                                                <input type="hidden" name="summaryfield" value="0"/>
                                                                <label class="checkbox" style="padding-left: 25px; padding-top: 5px;">
                                                                    <input type="checkbox" name="summaryfield" {if $FIELD_MODEL->isSummaryField()} checked {/if}
                                                                    {if $FIELD_MODEL->isSummaryFieldOptionDisabled()} readonly="readonly" class="optionDisabled"{/if} value="1" />&nbsp;
                                                                {vtranslate('LBL_SUMMARY_FIELD', $QUALIFIED_MODULE)}
                                                            </label>
                                                        </span>
                                                        <span>
                                                            <input type="hidden" name="masseditable" value="2" />
                                                            <label class="checkbox" style="padding-left: 25px; padding-top: 5px;">
                                                                <input type="checkbox" name="masseditable" {if $FIELD_MODEL->isMassEditable()} checked {/if}
                                                                {if $FIELD_MODEL->isMassEditOptionDisabled()} readonly="readonly" {/if} value="1" />&nbsp;
                                                            {vtranslate('LBL_MASS_EDIT', $QUALIFIED_MODULE)}
                                                        </label>
                                                    </span>
                                                    <span>
                                                        <input type="hidden" name="defaultvalue" value="" />
                                                        <label class="checkbox" style="padding-left: 25px; padding-top: 5px;">
                                                            <input type="checkbox" name="defaultvalue" {if $FIELD_MODEL->hasDefaultValue()} checked {/if}
                                                            {if $FIELD_MODEL->isDefaultValueOptionDisabled()} readonly="readonly" {/if} value="" />&nbsp;
                                                        {vtranslate('LBL_DEFAULT_VALUE', $QUALIFIED_MODULE)}
                                                    </label>
                                                    <div class="padding1per defaultValueUi {if !$FIELD_MODEL->hasDefaultValue()} zeroOpacity {/if}" style="padding : 0px 10px 0px 25px;{if !$FIELD_MODEL->hasDefaultValue()} display:none {/if}">
                                                        {if $FIELD_MODEL->isDefaultValueOptionDisabled() neq "true"}
                                                            {if $FIELD_MODEL->getFieldDataType() eq "picklist"}
                                                                {assign var=PICKLIST_VALUES value=$FIELD_MODEL->getPicklistValues()}
                                                                <select class="span2" name="fieldDefaultValue" {if !$FIELD_MODEL->hasDefaultValue()} disabled="" {/if} data-validation-engine="validate[required,funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" data-fieldinfo='{Vtiger_Util_Helper::toSafeHTML(ZEND_JSON::encode($FIELD_INFO))}'>
                                                                    {foreach item=PICKLIST_VALUE key=PICKLIST_NAME from=$PICKLIST_VALUES}
                                                                        <option value="{Vtiger_Util_Helper::toSafeHTML($PICKLIST_NAME)}" {if decode_html($FIELD_MODEL->get('defaultvalue')) eq $PICKLIST_NAME} selected {/if}>{vtranslate($PICKLIST_VALUE, $SELECTED_MODULE_NAME)}</option>
                                                                    {/foreach}
                                                                </select>
                                                            {elseif $FIELD_MODEL->getFieldDataType() eq "multipicklist"}
                                                                {assign var=PICKLIST_VALUES value=$FIELD_MODEL->getPicklistValues()}
                                                                {assign var="FIELD_VALUE_LIST" value=explode(' |##| ',$FIELD_MODEL->get('defaultvalue'))}
                                                                <select multiple class="span2" data-validation-engine="validate[required,funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" {if !$FIELD_MODEL->hasDefaultValue()} disabled="" {/if}  name="fieldDefaultValue" data-fieldinfo='{Vtiger_Util_Helper::toSafeHTML(ZEND_JSON::encode($FIELD_INFO))}'>
                                                                    {foreach item=PICKLIST_VALUE from=$PICKLIST_VALUES}
                                                                        <option value="{Vtiger_Util_Helper::toSafeHTML($PICKLIST_VALUE)}" {if in_array(Vtiger_Util_Helper::toSafeHTML($PICKLIST_VALUE), $FIELD_VALUE_LIST)} selected {/if}>{vtranslate($PICKLIST_VALUE, $SELECTED_MODULE_NAME)}</option>
                                                                    {/foreach}
                                                                </select>
                                                            {elseif $FIELD_MODEL->getFieldDataType() eq "boolean"}
                                                                <input type="hidden" name="fieldDefaultValue" value="" />
                                                                <input type="checkbox" name="fieldDefaultValue" value="1"
                                                                {if $FIELD_MODEL->get('defaultvalue') eq 1} checked {/if} data-fieldinfo='{ZEND_JSON::encode($FIELD_INFO)}' />
                                                        {elseif $FIELD_MODEL->getFieldDataType() eq "time"}
                                                            <div class="input-append time">
                                                                <input type="text" class="input-small" data-format="{$USER_MODEL->get('hour_format')}" data-validation-engine="validate[required,funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" {if !$FIELD_MODEL->hasDefaultValue()} disabled="" {/if} data-toregister="time" value="{$FIELD_MODEL->get('defaultvalue')}" name="fieldDefaultValue" data-fieldinfo='{ZEND_JSON::encode($FIELD_INFO)}'/>
                                                                <span class="add-on cursorPointer">
                                                                    <i class="icon-time"></i>
                                                                </span>
                                                            </div>
                                                        {elseif $FIELD_MODEL->getFieldDataType() eq "date"}
                                                            <div class="input-append date">
                                                                {assign var=FIELD_NAME value=$FIELD_MODEL->get('name')}
                                                                <input type="text" class="input-medium" data-validation-engine="validate[required,funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" {if !$FIELD_MODEL->hasDefaultValue()} disabled="" {/if} name="fieldDefaultValue" data-toregister="date" data-date-format="{$USER_MODEL->get('date_format')}" data-fieldinfo='{ZEND_JSON::encode($FIELD_INFO)}'
                                                                       value="{$FIELD_MODEL->getEditViewDisplayValue($FIELD_MODEL->get('defaultvalue'))}" />
                                                                <span class="add-on">
                                                                    <i class="icon-calendar"></i>
                                                                </span>
                                                            </div>
                                                        {elseif $FIELD_MODEL->getFieldDataType() eq "percentage"}
                                                            <div class="input-append">
                                                                <input type="number" data-validation-engine="validate[required,funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" {if !$FIELD_MODEL->hasDefaultValue()} disabled="" {/if}  class="input-medium" name="fieldDefaultValue"
                                                                       value="{$FIELD_MODEL->get('defaultvalue')}" data-fieldinfo='{ZEND_JSON::encode($FIELD_INFO)}' step="any" />
                                                                <span class="add-on">%</span>
                                                            </div>
                                                        {elseif $FIELD_MODEL->getFieldDataType() eq "currency"}
                                                            <div class="input-prepend">
                                                                <span class="add-on">{$USER_MODEL->get('currency_symbol')}</span>
                                                                <input type="text" data-validation-engine="validate[required,funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" {if !$FIELD_MODEL->hasDefaultValue()} disabled="" {/if}  class="input-medium" name="fieldDefaultValue"
                                                                       data-fieldinfo='{ZEND_JSON::encode($FIELD_INFO)}' value="{$FIELD_MODEL->getEditViewDisplayValue($FIELD_MODEL->get('defaultvalue'))}"
                                                                       data-decimal-separator='{$USER_MODEL->get('currency_decimal_separator')}' data-group-separator='{$USER_MODEL->get('currency_grouping_separator')}' />
                                                            </div>
                                                        {else if $FIELD_MODEL->getFieldName() eq "terms_conditions" && $FIELD_MODEL->get('uitype') == 19}
                                                            {assign var=INVENTORY_TERMS_AND_CONDITIONS_MODEL value= Settings_Vtiger_MenuItem_Model::getInstance("INVENTORYTERMSANDCONDITIONS")}
                                                          <a href="{$INVENTORY_TERMS_AND_CONDITIONS_MODEL->getUrl()}" target="_blank">{vtranslate('LBL_CLICK_HERE_TO_EDIT', $QUALIFIED_MODULE)}</a>
                                                       {else if $FIELD_MODEL->get('uitype') eq 19}
                                                             <textarea class="input-medium" data-validation-engine="validate[required,funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" {if !$FIELD_MODEL->hasDefaultValue()} disabled="" {/if}  name="fieldDefaultValue" value="{$FIELD_MODEL->get('defaultvalue')}" data-fieldinfo='{ZEND_JSON::encode($FIELD_INFO)}'></textarea>
                                                       {else}
                                                            <input type="text" class="input-medium" data-validation-engine="validate[required,funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" {if !$FIELD_MODEL->hasDefaultValue()} disabled="" {/if}  name="fieldDefaultValue" value="{$FIELD_MODEL->get('defaultvalue')}" data-fieldinfo='{ZEND_JSON::encode($FIELD_INFO)}'/>
                                                        {/if}
                                                    {/if}
                                                </div>
                                            </span>
											<span>
												<input type="hidden" name="helptext" value="" />
												<label class="checkbox" style="padding-left: 25px; padding-top: 5px;">
													<input type="checkbox" {if $FIELD_MODEL->hasHelpinfo()} checked {/if}
														name="helptext" value="" />&nbsp;
													{vtranslate('LBL_HELPTEXT_VALUE', $QUALIFIED_MODULE)}
												</label>
												<div class="padding1per defaultValueUi {if !$FIELD_MODEL->hasHelpinfo()} zeroOpacity {/if}" style="padding : 0px 10px 0px 25px;{if !$FIELD_MODEL->hasHelpinfo()} display:none {/if}">
													<textarea class="input-medium" {if !$FIELD_MODEL->hasHelpinfo()} disabled="" {/if}  name="helptextValue" value="{$FIELD_MODEL->get('helpinfo')}">{$FIELD_MODEL->get('helpinfo')}</textarea>
												</div>
											</span>
											
											<span>
												<input type="hidden" name="changelabel" value="" />
												<label class="checkbox" style="padding-left: 25px; padding-top: 5px;">
													<input type="checkbox" name="changelabel" />&nbsp;
													Label ändern
												</label>
												<div class="padding1per newlabelUI zeroOpacity display:none">
													<input type="text" maxlength="50" name="newlabelValue" value="">
												</div>
											</span>
											
											</div>
                                        <div class="modal-footer" style="padding: 0px;">
                                            <span class="pull-right">
                                                <div class="pull-right"><a href="javascript:void(0)" style="margin: 5px;color:#AA3434;margin-top:10px;" class='cancel'>{vtranslate('LBL_CANCEL', $QUALIFIED_MODULE)}</a></div>
                                                <button class="btn btn-success saveFieldDetails" data-field-id="{$FIELD_MODEL->get('id')}" type="submit" style="margin: 5px;">
                                                    <strong>{vtranslate('LBL_SAVE', $QUALIFIED_MODULE)}</strong>
                                                </button>
                                            </span>
                                        </div>
                                    </form>
                                </div>
                            {/if}
                            {if $FIELD_MODEL->isCustomField() eq 'true'}
                                <a href="javascript:void(0)" class="deleteCustomField" data-field-id="{$FIELD_MODEL->get('id')}">
                                    <i class="icon-trash alignMiddle" title="{vtranslate('LBL_DELETE', $QUALIFIED_MODULE)}"></i>
                                </a>
                            {/if}
                        </span>
						<span class="pull-right no-drag" style="opacity:0.50;">({$FIELD_MODEL->getName()})</span>
                    </div>
                </div>
            </div>
        </li>
    {/if}
{/foreach}
</ul>
<ul {if $SELECTED_MODULE_MODEL->isFieldsSortableAllowed($BLOCK_LABEL_KEY)}name="sortable2"{/if} class="connectedSortable span6" style="list-style-type: none; margin: 0; float: left;min-height: 1px;padding:2px;">
    {foreach item=FIELD_MODEL from=$FIELDS_LIST name=fieldlist1}
        {assign var=FIELD_INFO value=$FIELD_MODEL->getFieldInfo()}
        {if $smarty.foreach.fieldlist1.index % 2 neq 0}
            <li>
                <div class="opacity editFields marginLeftZero border1px" data-block-id="{$BLOCK_ID}" data-field-id="{$FIELD_MODEL->get('id')}" data-sequence="{$FIELD_MODEL->get('sequence')}">
                    <div class="row-fluid padding1per">
                        {assign var=IS_MANDATORY value=$FIELD_MODEL->isMandatory()}
                        <span class="span1">&nbsp;
                            {if $FIELD_MODEL->isEditable()}
                                <a>
                                    <img src="{vimage_path('drag.png')}" border="0" title="{vtranslate('LBL_DRAG',$QUALIFIED_MODULE)}"/>
                                </a>
                            {/if}
                        </span>
                        <div class="span11 marginLeftZero" style="word-wrap: break-word;">
                            <span class="fieldLabel no-drag">
                                {if $IS_MANDATORY}
                                    <span class="redColor">*</span>
                                {/if}
                                {vtranslate($FIELD_MODEL->get('label'), $SELECTED_MODULE_NAME)}&nbsp;
                            </span>
                            <span class="btn-group pull-right actions">
                                {if $FIELD_MODEL->isEditable()}
                                    <a href="javascript:void(0)" class="dropdown-toggle editFieldDetails" data-toggle="dropdown">
                                        <i class="icon-pencil alignMiddle" title="{vtranslate('LBL_EDIT', $QUALIFIED_MODULE)}"></i>
                                    </a>
                                    <div class="basicFieldOperations pull-right hide" style="width : 290px;">
                                        <form class="form-horizontal fieldDetailsForm" method="POST">
                                            <div class="modal-header contentsBackground">
                                                <strong>{vtranslate($FIELD_MODEL->get('label'), $SELECTED_MODULE_NAME)}</strong>
                                                <div class="pull-right"><a href="javascript:void(0)" class="cancel">X</a></div>
                                            </div>
					
												<div style="margin:5px;padding:5px;">
													{assign var=uilbl value=$FIELD_MODEL->uitype}
													{assign var=datatype value=explode("~",$FIELD_MODEL->typeofdata)}
													{assign var=sqltype value=$SQLTYPES[$FIELD_MODEL->getName()]}
													{vtranslate("LBL_UITYPE",$QUALIFIED_MODULE)} {vtranslate("LBL_UI$uilbl",$QUALIFIED_MODULE)} <br>
													{if $FIELD_MODEL->displaytype == 2}
														{vtranslate("LBL_NOEDITVIEW",$QUALIFIED_MODULE)}
														<br>
													{/if}

													{if !in_array($uilbl,array(10,51,53,56,57,59,73,75,76,77,78,80,81,83,117))}
														{if substr($sqltype,0,7) == "varchar"}
															{assign var=maxchars value=substr($sqltype,8,-1)}
															{vtranslate('LBL_MAXLEN',$QUALIFIED_MODULE)}: {$maxchars} {vtranslate('LBL_CHARS',$QUALIFIED_MODULE)}
														{elseif $sqltype == "text"}
															{vtranslate('LBL_MAXLEN',$QUALIFIED_MODULE)}: 64kB
														{elseif $sqltype == "mediumtext"}
															{vtranslate('LBL_MAXLEN',$QUALIFIED_MODULE)}: 16MB
														{/if}
														{if $datatype[0] == "N" || $datatype[0] == "I" || $datatype[0] == "NN"}
															{if substr($sqltype,0,7) == "decimal"}
																{assign var=digits value=explode(",",substr($sqltype,8,-1))}
																
																{vtranslate("LBL_NUMFLOAT",$QUALIFIED_MODULE)} {$digits[0]} {vtranslate("LBL_NUMDIGITS",$QUALIFIED_MODULE)} {$digits[1]} {vtranslate("LBL_NUMDECIMALS",$QUALIFIED_MODULE)}
															{elseif substr($sqltype,0,6) == "bigint"}
																{vtranslate("LBL_NUMBIGINT",$QUALIFIED_MODULE)}
															{else}
																{vtranslate("LBL_NUMINT",$QUALIFIED_MODULE)}
															{/if}
														{/if}
													{/if}
												</div>
																			
                                            <div style="padding-bottom: 5px;">
                                                <span>
                                                    <input type="hidden" name="mandatory" value="O" /><label class="checkbox" style="padding-left: 25px; padding-top: 5px;">
                                                        <input type="checkbox" name="mandatory" {if $IS_MANDATORY} checked {/if}
                                                        {if $FIELD_MODEL->isMandatoryOptionDisabled()} readonly="readonly" {/if} value="M" />&nbsp;
                                                    {vtranslate('LBL_MANDATORY_FIELD', $QUALIFIED_MODULE)}
                                                </label>
                                            </span>
                                            <span>
                                                <input type="hidden" name="presence" value="1" />
                                                <label class="checkbox" style="padding-left: 25px; padding-top: 5px;">
                                                    <input type="checkbox" name="presence" {if $FIELD_MODEL->isViewable()} checked {/if}
                                                {if $FIELD_MODEL->isActiveOptionDisabled()} readonly="readonly" class="optionDisabled"{/if} {if $IS_MANDATORY} readonly="readonly" {/if} value="2" />&nbsp;
                                            {vtranslate('LBL_ACTIVE', $QUALIFIED_MODULE)}
                                        </label>
                                    </span>
                                    <span>
                                        <input type="hidden" name="quickcreate" value="1" />
                                        <label class="checkbox" style="padding-left: 25px; padding-top: 5px;">
                                            <input type="checkbox" name="quickcreate" {if $FIELD_MODEL->isQuickCreateEnabled()} checked {/if}
                                        {if $FIELD_MODEL->isQuickCreateOptionDisabled()} readonly="readonly" class="optionDisabled"{/if} {if $IS_MANDATORY} readonly="readonly" {/if} value="2" />&nbsp;
                                    {vtranslate('LBL_QUICK_CREATE', $QUALIFIED_MODULE)}
                                </label>
                            </span>
                            <span>
                                <input type="hidden" name="summaryfield" value="0"/>
                                <label class="checkbox" style="padding-left: 25px; padding-top: 5px;">
                                    <input type="checkbox" name="summaryfield" {if $FIELD_MODEL->isSummaryField()} checked {/if}
                                    {if $FIELD_MODEL->isSummaryFieldOptionDisabled()} readonly="readonly" class="optionDisabled"{/if} value="1" />&nbsp;
                                {vtranslate('LBL_SUMMARY_FIELD', $QUALIFIED_MODULE)}
                            </label>
                        </span>
                        <span>
                            <input type="hidden" name="masseditable" value="2" />
                            <label class="checkbox" style="padding-left: 25px; padding-top: 5px;">
                                <input type="checkbox" name="masseditable" {if $FIELD_MODEL->isMassEditable()} checked {/if}
                                {if $FIELD_MODEL->isMassEditOptionDisabled()} readonly="readonly" {/if} value="1" />&nbsp;
                            {vtranslate('LBL_MASS_EDIT', $QUALIFIED_MODULE)}
                        </label>
                    </span>
                    <span>
                        <input type="hidden" name="defaultvalue" value="" />
                        <label class="checkbox" style="padding-left: 25px; padding-top: 5px;">
                            <input type="checkbox" name="defaultvalue" {if $FIELD_MODEL->hasDefaultValue()} checked {/if}
                            {if $FIELD_MODEL->isDefaultValueOptionDisabled()} readonly="readonly" {/if} value="" />&nbsp;
                        {vtranslate('LBL_DEFAULT_VALUE', $QUALIFIED_MODULE)}
                    </label>
                    <div class="padding1per defaultValueUi {if !$FIELD_MODEL->hasDefaultValue()} zeroOpacity {/if}" style="padding : 0px 10px 0px 25px;{if !$FIELD_MODEL->hasDefaultValue()} display:none {/if}">
                        {if $FIELD_MODEL->isDefaultValueOptionDisabled() neq "true"}
                            {if $FIELD_MODEL->getFieldDataType() eq "picklist"}
                                {assign var=PICKLIST_VALUES value=$FIELD_MODEL->getPicklistValues()}
                                <select class="span2" name="fieldDefaultValue" {if !$FIELD_MODEL->hasDefaultValue()} disabled="" {/if} data-validation-engine="validate[required,funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" data-fieldinfo='{Vtiger_Util_Helper::toSafeHTML(ZEND_JSON::encode($FIELD_INFO))}'>
                                    {foreach item=PICKLIST_VALUE key=PICKLIST_NAME from=$PICKLIST_VALUES}
                                        <option value="{Vtiger_Util_Helper::toSafeHTML($PICKLIST_NAME)}" {if decode_html($FIELD_MODEL->get('defaultvalue')) eq $PICKLIST_NAME} selected {/if}>{vtranslate($PICKLIST_VALUE, $SELECTED_MODULE_NAME)}</option>
                                    {/foreach}
                                </select>
                            {elseif $FIELD_MODEL->getFieldDataType() eq "multipicklist"}
                                {assign var=PICKLIST_VALUES value=$FIELD_MODEL->getPicklistValues()}
                                {assign var="FIELD_VALUE_LIST" value=explode(' |##| ',$FIELD_MODEL->get('defaultvalue'))}
                                <select multiple class="span2" data-validation-engine="validate[required,funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" {if !$FIELD_MODEL->hasDefaultValue()} disabled="" {/if}  name="fieldDefaultValue" data-fieldinfo='{Vtiger_Util_Helper::toSafeHTML(ZEND_JSON::encode($FIELD_INFO))}'>
                                    {foreach item=PICKLIST_VALUE from=$PICKLIST_VALUES}
                                        <option value="{Vtiger_Util_Helper::toSafeHTML($PICKLIST_VALUE)}" {if in_array(Vtiger_Util_Helper::toSafeHTML($PICKLIST_VALUE), $FIELD_VALUE_LIST)} selected {/if}>{vtranslate($PICKLIST_VALUE, $SELECTED_MODULE_NAME)}</option>
                                    {/foreach}
                                </select>
                            {elseif $FIELD_MODEL->getFieldDataType() eq "boolean"}
                                <input type="hidden" name="fieldDefaultValue" value="" />
                                <input type="checkbox" name="fieldDefaultValue" value="1"
                                {if $FIELD_MODEL->get('defaultvalue') eq 1} checked {/if} data-fieldinfo='{ZEND_JSON::encode($FIELD_INFO)}' />
                        {elseif $FIELD_MODEL->getFieldDataType() eq "time"}
                            <div class="input-append time">
                                <input type="text" class="input-small" data-format="{$USER_MODEL->get('hour_format')}" data-validation-engine="validate[required,funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" {if !$FIELD_MODEL->hasDefaultValue()} disabled="" {/if} data-toregister="time" value="{$FIELD_MODEL->get('defaultvalue')}" name="fieldDefaultValue" data-fieldinfo='{ZEND_JSON::encode($FIELD_INFO)}'/>
                                <span class="add-on cursorPointer">
                                    <i class="icon-time"></i>
                                </span>
                            </div>
                        {elseif $FIELD_MODEL->getFieldDataType() eq "date"}
                            <div class="input-append date">
                                {assign var=FIELD_NAME value=$FIELD_MODEL->get('name')}
                                <input type="text" class="input-medium" data-validation-engine="validate[required,funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" {if !$FIELD_MODEL->hasDefaultValue()} disabled="" {/if} name="fieldDefaultValue" data-toregister="date" data-date-format="{$USER_MODEL->get('date_format')}" data-fieldinfo='{ZEND_JSON::encode($FIELD_INFO)}'
                                       value="{$FIELD_MODEL->getEditViewDisplayValue($FIELD_MODEL->get('defaultvalue'))}" />
                                <span class="add-on">
                                    <i class="icon-calendar"></i>
                                </span>
                            </div>
                        {elseif $FIELD_MODEL->getFieldDataType() eq "percentage"}
                            <div class="input-append">
                                <input type="number" data-validation-engine="validate[required,funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" {if !$FIELD_MODEL->hasDefaultValue()} disabled="" {/if}  class="input-medium" name="fieldDefaultValue"
                                       value="{$FIELD_MODEL->get('defaultvalue')}" data-fieldinfo='{ZEND_JSON::encode($FIELD_INFO)}' step="any" />
                                <span class="add-on">%</span>
                            </div>
                        {elseif $FIELD_MODEL->getFieldDataType() eq "currency"}
                            <div class="input-prepend">
                                <span class="add-on">{$USER_MODEL->get('currency_symbol')}</span>
                                <input type="text" data-validation-engine="validate[required,funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" {if !$FIELD_MODEL->hasDefaultValue()} disabled="" {/if}  class="input-medium" name="fieldDefaultValue"
                                       data-fieldinfo='{ZEND_JSON::encode($FIELD_INFO)}' value="{$FIELD_MODEL->getEditViewDisplayValue($FIELD_MODEL->get('defaultvalue'))}"
                                       data-decimal-separator='{$USER_MODEL->get('currency_decimal_separator')}' data-group-separator='{$USER_MODEL->get('currency_grouping_separator')}' />
                            </div>
                        {else}
                            <input type="text" class="input-medium" data-validation-engine="validate[required,funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" {if !$FIELD_MODEL->hasDefaultValue()} disabled="" {/if}  name="fieldDefaultValue" value="{$FIELD_MODEL->get('defaultvalue')}" data-fieldinfo='{ZEND_JSON::encode($FIELD_INFO)}'/>
                        {/if}
                    {/if}
                </div>
            </span>
			<span>
				<input type="hidden" name="helptext" value="" />
				<label class="checkbox" style="padding-left: 25px; padding-top: 5px;">
					<input type="checkbox" {if $FIELD_MODEL->hasHelpinfo()} checked {/if}
						name="helptext" value="" />&nbsp;
					{vtranslate('LBL_HELPTEXT_VALUE', $QUALIFIED_MODULE)}
				</label>
				<div class="padding1per defaultValueUi {if !$FIELD_MODEL->hasHelpinfo()} zeroOpacity {/if}" style="padding : 0px 10px 0px 25px;{if !$FIELD_MODEL->hasHelpinfo()} display:none {/if}">
					<textarea class="input-medium" {if !$FIELD_MODEL->hasHelpinfo()} disabled="" {/if}  name="helptextValue" value="{$FIELD_MODEL->get('helpinfo')}">{$FIELD_MODEL->get('helpinfo')}</textarea>
				</div>
			</span>
			
			<span>
				<input type="hidden" name="changelabel" value="" />
				<label class="checkbox" style="padding-left: 25px; padding-top: 5px;">
					<input type="checkbox" name="changelabel" />&nbsp;
					Label ändern
				</label>
				<div class="padding1per newlabelUI zeroOpacity display:none">
					<input type="text" maxlength="50" name="newlabelValue" value="">
				</div>
			</span>
			
        </div>
        <div class="modal-footer" style="padding: 0px;">
            <span class="pull-right">
                <div class="pull-right"><a href="javascript:void(0)" style="margin: 5px;color:#AA3434;margin-top:10px;" class="cancel">{vtranslate('LBL_CANCEL', $QUALIFIED_MODULE)}</a></div>
                <button class="btn btn-success saveFieldDetails" data-field-id="{$FIELD_MODEL->get('id')}" type="submit" style="margin: 5px;">
                    <strong>{vtranslate('LBL_SAVE', $QUALIFIED_MODULE)}</strong>
                </button>
            </span>
        </div>
    </form>
</div>
{/if}
{if $FIELD_MODEL->isCustomField() eq 'true'}
    <a href="javascript:void(0)" class="deleteCustomField" data-field-id="{$FIELD_MODEL->get('id')}">
        <i class="icon-trash alignMiddle" title="{vtranslate('LBL_DELETE', $QUALIFIED_MODULE)}"></i>
    </a>
{/if}
</span>
<span class="pull-right no-drag" style="opacity:0.50;">({$FIELD_MODEL->getName()})</span>
</div>
</div>
</div>
</li>
{/if}
{/foreach}
</ul>
</div>
</div>
{/foreach}
</div>
<input type="hidden" class="inActiveFieldsArray" value='{ZEND_JSON::encode($IN_ACTIVE_FIELDS)}' />

<div class="newCustomBlockCopy hide marginBottom10px border1px {if $IS_BLOCK_SORTABLE}blockSortable {/if}" data-block-id="" data-sequence="" style="border-radius: 4px;">
    <div class="row-fluid layoutBlockHeader">
        <div class="span6 blockLabel padding10">
            <img class="alignMiddle" src="{vimage_path('drag.png')}" />&nbsp;&nbsp;
        </div>
        <div class="span6 marginLeftZero">
            <div class="pull-right btn-toolbar blockActions" style="margin: 4px;">
                <div class="btn-group">
                    <button class="btn addCustomField hide" type="button">
                        <strong>{vtranslate('LBL_ADD_CUSTOM_FIELD', $QUALIFIED_MODULE)}</strong>
                    </button>
                </div>
                <div class="btn-group">
                    <button class="btn dropdown-toggle" data-toggle="dropdown">
                        <strong>{vtranslate('LBL_ACTIONS', $QUALIFIED_MODULE)}</strong>&nbsp;&nbsp;
                        <i class="caret"></i>
                    </button>
                    <ul class="dropdown-menu pull-right">
                        <li class="blockVisibility" data-visible="1" data-block-id="">
                            <a href="javascript:void(0)">
                                <i class="icon-ok"></i>&nbsp;{vtranslate('LBL_SHOW_ACTIVE', $QUALIFIED_MODULE)}
                            </a>
                        </li>
                        <li class="inActiveFields">
                            <a href="javascript:void(0)">{vtranslate('LBL_SHOW_INACTIVE', $QUALIFIED_MODULE)}</a>
                        </li>
                        <li class="deleteCustomBlock">
                            <a href="javascript:void(0)">{vtranslate('LBL_DELETE_BLOCK', $QUALIFIED_MODULE)}</a>
                        </li>
                        <li class="renameBlock">
                            <a href="javascript:void(0)">{vtranslate('LBL_RENAME_BLOCK', $QUALIFIED_MODULE)}</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="blockFieldsList row-fluid blockFieldsSortable" style="padding:5px;min-height: 27px">
        <ul class="connectedSortable span6 ui-sortable" style="list-style-type: none; float: left;min-height:1px;padding:2px;" name="sortable1"></ul>
        <ul class="connectedSortable span6 ui-sortable" style="list-style-type: none; margin: 0;float: left;min-height:1px;padding:2px;" name="sortable2"></ul>
    </div>
</div>

<li class="newCustomFieldCopy hide">
    <div class="marginLeftZero border1px" data-field-id="" data-sequence="">
        <div class="row-fluid padding1per">
            <span class="span1">&nbsp;
                {if $IS_SORTABLE}
                    <a>
                        <img src="{vimage_path('drag.png')}" border="0" title="{vtranslate('LBL_DRAG',$QUALIFIED_MODULE)}"/>
                    </a>
                {/if}
            </span>
            <div class="span11 marginLeftZero" style="word-wrap: break-word;">
                <span class="fieldLabel no-drag"></span>
                <span class="btn-group pull-right actions">
                    {if $IS_SORTABLE}
                        <a href="javascript:void(0)" class="dropdown-toggle editFieldDetails" data-toggle="dropdown">
                            <i class="icon-pencil alignMiddle" title="{vtranslate('LBL_EDIT', $QUALIFIED_MODULE)}"></i>
                        </a>
                        <div class="basicFieldOperations hide pull-right" style="width: 250px;">
                            <form class="form-horizontal fieldDetailsForm" method="POST">
                                <div class="modal-header contentsBackground">
                                </div>
                                <div style="padding-bottom: 5px;">
                                    <span>
                                        <input type="hidden" name="mandatory" value="O" />
                                        <label class="checkbox" style="padding-left: 25px; padding-top: 5px;">
                                            <input type="checkbox" name="mandatory" value="M" />&nbsp;{vtranslate('LBL_MANDATORY_FIELD', $QUALIFIED_MODULE)}
                                        </label>
                                    </span>
                                    <span>
                                        <input type="hidden" name="presence" value="1" />
                                        <label class="checkbox" style="padding-left: 25px; padding-top: 5px;">
                                            <input type="checkbox" name="presence" value="2" />&nbsp;{vtranslate('LBL_ACTIVE', $QUALIFIED_MODULE)}
                                        </label>
                                    </span>
                                    <span>
                                        <input type="hidden" name="quickcreate" value="1" />
                                        <label class="checkbox" style="padding-left: 25px; padding-top: 5px;">
                                            <input type="checkbox" name="quickcreate" value="2" />&nbsp;{vtranslate('LBL_QUICK_CREATE', $QUALIFIED_MODULE)}
                                        </label>
                                    </span>
                                    <span>
                                        <input type="hidden" name="summaryfield" value="0"/>
                                        <label class="checkbox" style="padding-left: 25px; padding-top: 5px;">
                                            <input type="checkbox" name="summaryfield" value="1" />&nbsp;{vtranslate('LBL_SUMMARY_FIELD', $QUALIFIED_MODULE)}
                                        </label>
                                    </span>
                                    <span>
                                        <input type="hidden" name="masseditable" value="2" />
                                        <label class="checkbox" style="padding-left: 25px; padding-top: 5px;">
                                            <input type="checkbox" name="masseditable" value="1" />&nbsp;{vtranslate('LBL_MASS_EDIT', $QUALIFIED_MODULE)}
                                        </label>
                                    </span>
                                    <span>
                                        <input type="hidden" name="defaultvalue" value="" />
                                        <label class="checkbox" style="padding-left: 25px; padding-top: 5px;">
                                            <input type="checkbox" name="defaultvalue" value="" />&nbsp;
                                            {vtranslate('LBL_DEFAULT_VALUE', $QUALIFIED_MODULE)}</label>
                                        <div class="padding1per defaultValueUi" style="padding:0 10px 0 25px;display:none"></div>
                                    </span>
                                    <span>
                                        <input type="hidden" name="helptext" value="" />
                                        <label class="checkbox" style="padding-left: 25px; padding-top: 5px;">
                                            <input type="checkbox" name="helptext" value="" />&nbsp;
                                            {vtranslate('LBL_HELPTEXT_VALUE', $QUALIFIED_MODULE)}</label>
                                        <div class="padding1per helptextUi" style="padding:0 10px 0 25px;display:none">
                                            <textarea class="input-medium" name="helptextValue"></textarea>
                                        </div>
                                    </span>
                                </div>
                                <div class="modal-footer">
                                    <span class="pull-right">
                                        <div class="pull-right"><a href="javascript:void(0)" style="margin-top: 5px;margin-left: 10px;color:#AA3434;" class='cancel'>{vtranslate('LBL_CANCEL', $QUALIFIED_MODULE)}</a></div>
                                        <button class="btn btn-success saveFieldDetails" data-field-id="" type="submit"><strong>{vtranslate('LBL_SAVE', $QUALIFIED_MODULE)}</strong></button>
                                    </span>
                                </div>
                            </form>
                        </div>
                    {/if}
                    <a href="javascript:void(0)" class="deleteCustomField" data-field-id=""><i class="icon-trash alignMiddle" title="{vtranslate('LBL_DELETE', $QUALIFIED_MODULE)}"></i></a>
                </span>
            </div>
        </div>
    </div>
</li>

<div class="modal renameBlockModal hide">
    <div class="modal-header contentsBackground">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h3>{vtranslate('LBL_RENAME_BLOCK', $QUALIFIED_MODULE)}</h3>
    </div>
	<form class="form-horizontal renameBlockForm" method="POST">
        <div class="modal-body">
            <div class="control-group">
                <span class="control-label">
                    <span class="redColor">*</span>
                    <span>{vtranslate('LBL_BLOCK_NAME', $QUALIFIED_MODULE)}</span>
                </span>
                <div class="controls">
				<input type="hidden" name="blockid" value="">
                    <input id='blockname' type="text" name="label" class="span3" data-validation-engine="validate[required]" />
                </div>
            </div>
        </div>
		{include file='ModalFooter.tpl'|@vtemplate_path:'Vtiger'}
    </form>
</div>

<div class="modal addBlockModal hide">
    <div class="modal-header contentsBackground">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h3>{vtranslate('LBL_ADD_CUSTOM_BLOCK', $QUALIFIED_MODULE)}</h3>
    </div>
    <form class="form-horizontal addCustomBlockForm" method="POST">
        <div class="modal-body">
            <div class="control-group">
                <span class="control-label">
                    <span class="redColor">*</span>
                    <span>{vtranslate('LBL_BLOCK_NAME', $QUALIFIED_MODULE)}</span>
                </span>
                <div class="controls">
                    <input type="text" name="label" class="span3" data-validation-engine="validate[required]" />
                </div>
            </div>
            <div class="control-group">
                <span class="control-label">
                    {vtranslate('LBL_ADD_AFTER', $QUALIFIED_MODULE)}
                </span>
                <div class="controls">
                    <span class="row-fluid">
                        <select class="span8" name="beforeBlockId">
                            {foreach key=BLOCK_ID item=BLOCK_LABEL from=$ALL_BLOCK_LABELS}
                                <option value="{$BLOCK_ID}" data-label="{$BLOCK_LABEL}">{vtranslate($BLOCK_LABEL, $SELECTED_MODULE_NAME)}</option>
                            {/foreach}
                        </select>
                    </span>
                </div>
            </div>
        </div>
        {include file='ModalFooter.tpl'|@vtemplate_path:'Vtiger'}
    </form>
</div>

<div class="modal createFieldModal hide">
    <div class="modal-header contentsBackground">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h3>{vtranslate('LBL_CREATE_CUSTOM_FIELD', $QUALIFIED_MODULE)}</h3>
    </div>
    <form class="form-horizontal createCustomFieldForm" method="POST">
        <div class="modal-body">
            <div class="control-group">
                <span class="control-label">
                    {vtranslate('LBL_SELECT_FIELD_TYPE', $QUALIFIED_MODULE)}
                </span>
                <div class="controls">
                    <select class="fieldTypesList" name="fieldType">
                        {foreach item=FIELD_TYPE from=$ADD_SUPPORTED_FIELD_TYPES}
                            <option value="{$FIELD_TYPE}"
                                {foreach key=TYPE_INFO item=TYPE_INFO_VALUE from=$FIELD_TYPE_INFO[$FIELD_TYPE]}
                                    data-{$TYPE_INFO}="{$TYPE_INFO_VALUE}"
                                {/foreach}>
                                {vtranslate($FIELD_TYPE, $QUALIFIED_MODULE)}
                            </option>
                        {/foreach}
                    </select>
                </div>
            </div>
            <div class="control-group">
                <span class="control-label">
                    <span class="redColor">*</span>&nbsp;
                    {vtranslate('LBL_LABEL_NAME', $QUALIFIED_MODULE)}
                </span>
                <div class="controls">
                    <input type="text" maxlength="50" name="fieldLabel" value="" data-validation-engine="validate[required, funcCall[Vtiger_Base_Validator_Js.invokeValidation]]"
                           data-validator={Zend_Json::encode([['name'=>'FieldLabel']])} />
                </div>
            </div>
            <div class="control-group supportedType lengthsupported">
                <span class="control-label">
                    <span class="redColor">*</span>&nbsp;
                    {vtranslate('LBL_LENGTH', $QUALIFIED_MODULE)}
                </span>
                <div class="controls">
                    <input type="text" name="fieldLength" value="" data-validation-engine="validate[required, funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" />
                </div>
            </div>
            <div class="control-group supportedType decimalsupported hide">
                <span class="control-label">
                    <span class="redColor">*</span>&nbsp;
                    {vtranslate('LBL_DECIMALS', $QUALIFIED_MODULE)}
                </span>
                <div class="controls">
                    <input type="text" name="decimal" value="" data-validation-engine="validate[required, funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" />
                </div>
            </div>
            <div class="control-group supportedType preDefinedValueExists hide">
                <span class="control-label">
                    <span class="redColor">*</span>&nbsp;
                    {vtranslate('LBL_PICKLIST_VALUES', $QUALIFIED_MODULE)}
                </span>
                <div class="controls">
                    <div class="row-fluid">
                        <input type="hidden" id="picklistUi" class="span7 select2" name="pickListValues"
                               placeholder="{vtranslate('LBL_ENTER_PICKLIST_VALUES', $QUALIFIED_MODULE)}" data-validation-engine="validate[required, funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" data-validator={Zend_Json::encode([['name'=>'PicklistFieldValues']])} />
                    </div>
                </div>
            </div>
            <div class="control-group supportedType picklistOption hide">
                <span class="control-label">
                    &nbsp;
                </span>
                <div class="controls">
                    <label class="checkbox span3" style="margin-left: 0px;">
                        <input type="checkbox" class="checkbox" name="isRoleBasedPickList" value="1" checked>&nbsp;{vtranslate('LBL_ROLE_BASED_PICKLIST',$QUALIFIED_MODULE)}
                    </label>
                    <div class="alert alert-info" style="clear:both">
                        {vtranslate('LBL_PICKLIST_ROLE_HELP',$QUALIFIED_MODULE)}
                    </div>
                </div>
            </div>
        </div>
        {include file='ModalFooter.tpl'|@vtemplate_path:'Vtiger'}
    </form>
</div>


<div class="modal inactiveFieldsModal hide">
    <div class="modal-header contentsBackground">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h3>{vtranslate('LBL_INACTIVE_FIELDS', $QUALIFIED_MODULE)}</h3>
    </div>
    <form class="form-horizontal inactiveFieldsForm" method="POST">
        <div class="modal-body">
            <div class="row-fluid inActiveList"></div>
        </div>
        <div class="modal-footer">
            <div class=" pull-right cancelLinkContainer">
                <a class="cancelLink" type="reset" data-dismiss="modal">{vtranslate('LBL_CANCEL', $QUALIFIED_MODULE)}</a>
            </div>
            <button class="btn btn-success" type="submit" name="reactivateButton">
                <strong>{vtranslate('LBL_REACTIVATE', $QUALIFIED_MODULE)}</strong>
            </button>
        </div>
    </form>
</div>
</div>
<div class="tab-pane" id="relatedTabOrder">

</div>
</div>
</div>
</div>
{/strip}

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
    <div class="relatedContainer listViewPageDiv">
        {assign var=RELATED_MODULE_NAME value=$RELATED_MODULE->get('name')}
        <input type="hidden" name="emailEnabledModules" value=1>
        <input type="hidden" name="currentPageNum" value="{$PAGING->getCurrentPage()}" />
        <input type="hidden" name="relatedModuleName" class="relatedModuleName" value="{$RELATED_MODULE->get('name')}" />
        <input type="hidden" value="{$ORDER_BY}" id="orderBy">
        <input type="hidden" value="{$SORT_ORDER}" id="sortOrder">
        <input type="hidden" value="{$RELATED_ENTIRES_COUNT}" id="noOfEntries">
        <input type='hidden' value="{$PAGING->getPageLimit()}" id='pageLimit'>
            <input type="hidden" id="recordsCount" value=""/>
            <input type="hidden" id="selectedIds" name="selectedIds" data-selected-ids={ZEND_JSON::encode($SELECTED_IDS)} />
            <input type="hidden" id="excludedIds" name="excludedIds" data-excluded-ids={ZEND_JSON::encode($EXCLUDED_IDS)} />                                                             
        <input type='hidden' value="{$TOTAL_ENTRIES}" id='totalCount'>
        <div class="relatedHeader ">
            <div class="btn-toolbar row-fluid">
                <div class="span2">
                    <div class="btn-group"><button class="btn dropdown-toggle" data-toggle="dropdown"><strong>{vtranslate('LBL_ACTIONS', $MODULE)}</strong>&nbsp;&nbsp;<i class="caret"></i></button>
                        <ul class="dropdown-menu">
                            <li id="addEntries"><a href="#">{vtranslate('LBL_ADD_CONTACTS', $MODULE)}</a></li>
                            <li id="removeEntries"><a href="#">{vtranslate('LBL_DELETE_SELECTED', $MODULE)}</a></li>
                            <li id="findDuplicates"><a href="#">{vtranslate('LBL_FIND_DUPLICATES', $MODULE)}</a></li> 
                        </ul>
                    </div>  
                </div> 
<div class="span3">
    <span class="customFilterMainSpan row-fluid">
        {if is_array($CUSTOM_VIEWS) AND $CUSTOM_VIEWS|@count gt 0}
            <select id="recordsFilter" class="span12" data-placeholder="{vtranslate('LBL_SELECT_TO_LOAD_LIST', $RELATED_MODULE_NAME)}">
                <option></option>
                {foreach key=GROUP_LABEL item=GROUP_CUSTOM_VIEWS from=$CUSTOM_VIEWS}
                    <optgroup label=' {if $GROUP_LABEL eq 'Mine'} &nbsp; {else if} {vtranslate($GROUP_LABEL)} {/if}' >
                        {foreach item="CUSTOM_VIEW" from=$GROUP_CUSTOM_VIEWS}
                            <option id="filterOptionId_{$CUSTOM_VIEW->get('cvid')}" value="{$CUSTOM_VIEW->get('cvid')}" class="filterOptionId_{$CUSTOM_VIEW->get('cvid')}" data-id="{$CUSTOM_VIEW->get('cvid')}">{if $CUSTOM_VIEW->get('viewname') eq 'All'}{vtranslate($CUSTOM_VIEW->get('viewname'), $RELATED_MODULE_NAME)} {vtranslate($RELATED_MODULE_NAME, $RELATED_MODULE_NAME)}{else}{vtranslate($CUSTOM_VIEW->get('viewname'), $RELATED_MODULE_NAME)}{/if}{if $GROUP_LABEL neq 'Mine'} [ {$CUSTOM_VIEW->getOwnerName()} ] {/if}</option>
                        {/foreach}
                    </optgroup>
                {/foreach}
            </select>
            <img class="filterImage" src="{'filter.png'|vimage_path}" style="display:none;height:13px;margin-right:2px;vertical-align: middle;">
        {else}
            <input type="hidden" value="0" id="customFilter" />
        {/if}
    </span>
</div>

<div class="span3">
    <span class="customFilterMainSpan row-fluid">
    <select id="loadVerteiler" class="span12" data-placeholder="{vtranslate('LBL_SELECT_TO_LOAD_VERTEILER', $MODULE)}">
        <option></option>
        {foreach key=VERTEILER_ID item=VERTEILER_NAME from=$OTHER_VERTEILER}
            <option value="{$VERTEILER_ID}">{$VERTEILER_NAME|escape:"html"}</option>
        {/foreach}
    </select>
    </span>
</div>

<div class="span2">
<input type="text" id="relatedFilter" style="vertical-align:baseline" value="{$smarty.request.filter|escape:"html"}" placeholder="{vtranslate('LBL_PLACEHOLDER_SEARCH', $MODULE)}">
</div>

<div class="span2">
    <div class="pull-right">
    {$RELATED_ENTIRES_COUNT}&nbsp;{vtranslate('LBL_ENTRIES', $MODULE)}&nbsp;{if $smarty.request.filter !=""}({vtranslate('LBL_FILTERED', $MODULE)}){/if}
    </div>
</div> 
{*
<div class="span3">
    <div class="pull-right">
        <span class="pageNumbers">
            <span class="pageNumbersText">{if !empty($RELATED_RECORDS)} {$PAGING->getRecordStartRange()} {vtranslate('LBL_to', $RELATED_MODULE->get('name'))} {$PAGING->getRecordEndRange()}{else}<span>&nbsp;</span>{/if}</span>
            <span class="icon-refresh totalNumberOfRecords cursorPointer{if empty($RELATED_RECORDS)} hide{/if}"></span>
        </span>
        <span class="btn-group">
            <button class="btn" id="relatedListPreviousPageButton" {if !$PAGING->isPrevPageExists()} disabled {/if} type="button"><span class="icon-chevron-left"></span></button>
            <button class="btn dropdown-toggle" type="button" id="relatedListPageJump" data-toggle="dropdown" {if $PAGE_COUNT eq 1} disabled {/if}>
                <i class="vtGlyph vticon-pageJump" title="{vtranslate('LBL_LISTVIEW_PAGE_JUMP',$moduleName)}"></i>
            </button>
            <ul class="listViewBasicAction dropdown-menu" id="relatedListPageJumpDropDown">
                <li>
                    <span class="row-fluid">
                        <span class="span3"><span class="pull-right">{vtranslate('LBL_PAGE',$moduleName)}</span></span>
                        <span class="span4">
                            <input type="text" id="pageToJump" class="listViewPagingInput" value="{$PAGING->getCurrentPage()}"/>
                        </span>
                        <span class="span2 textAlignCenter">
                            {vtranslate('LBL_OF',$moduleName)}
                        </span>
                        <span class="span3" id="totalPageCount">{$PAGE_COUNT}</span>
                    </span>
                </li>
            </ul>
            <button class="btn" id="relatedListNextPageButton" {if (!$PAGING->isNextPageExists()) or ($PAGE_COUNT eq 1)} disabled {/if} type="button"><span class="icon-chevron-right"></span></button>
        </span>
    </div>
</div>
*}
</div>
</div>

<div class="contents-topscroll">
    <div class="topscroll-div">
        &nbsp;
    </div>
</div>

<div class="relatedContents contents-bottomscroll">
    <div class="bottomscroll-div">
        {assign var=WIDTHTYPE value=$USER_MODEL->get('rowheight')}
        <table class="table table-bordered listViewEntriesTable">
            <thead>
                <tr class="listViewHeaders">
                    <th width="4%">
                        <input type="checkbox" id="listViewEntriesMainCheckBox"/>
                    </th>                                   
                    {foreach item=HEADER_FIELD from=$RELATED_HEADERS}
                        <th nowrap>
                            {if $HEADER_FIELD->get('column') eq 'access_count' or $HEADER_FIELD->get('column') eq 'idlists' }
                                <a href="javascript:void(0);" class="noSorting">{vtranslate($HEADER_FIELD->get('label'), $RELATED_MODULE->get('name'))}</a>
                            {elseif $HEADER_FIELD->get('column') eq 'time_start'}
                            {else}
                                <a href="javascript:void(0);" class="relatedListHeaderValues" data-nextsortorderval="{if $COLUMN_NAME eq $HEADER_FIELD->get('column')}{$NEXT_SORT_ORDER}{else}ASC{/if}" data-fieldname="{$HEADER_FIELD->get('column')}">{vtranslate($HEADER_FIELD->get('label'), $RELATED_MODULE->get('name'))}
                                    &nbsp;&nbsp;{if $COLUMN_NAME eq $HEADER_FIELD->get('column')}<img class="{$SORT_IMAGE}">{/if}
                                </a>
                            {/if}
                        </th>
                    {/foreach}
                    
                    <th nowrap>
                    {ASSIGN var=STATUSCOLUMNFIELD value="added_by_user_name"}
                    <a href="javascript:void(0);" class="relatedListHeaderValues" data-nextsortorderval="{if $COLUMN_NAME eq $STATUSCOLUMNFIELD}{$NEXT_SORT_ORDER}{else}ASC{/if}" data-fieldname="{$STATUSCOLUMNFIELD}">{vtranslate("LBL_ADDEDBY", "Verteiler")}
                                    &nbsp;&nbsp;{if $COLUMN_NAME eq $STATUSCOLUMNFIELD}<img class="{$SORT_IMAGE}">{/if}
                    </th>
                    
                    <th nowrap colspan="2">
                    {ASSIGN var=STATUSCOLUMNFIELD value="parent"}
                    <a href="javascript:void(0);" class="relatedListHeaderValues" data-nextsortorderval="{if $COLUMN_NAME eq $STATUSCOLUMNFIELD}{$NEXT_SORT_ORDER}{else}ASC{/if}" data-fieldname="{$STATUSCOLUMNFIELD}">{vtranslate("LBL_PARENT", "Verteiler")}
                                    &nbsp;&nbsp;{if $COLUMN_NAME eq $STATUSCOLUMNFIELD}<img class="{$SORT_IMAGE}">{/if}
                    </th>
                    
                    
                </tr>
            </thead>
            {foreach item=RELATED_RECORD from=$RELATED_RECORDS}
                <tr class="listViewEntries" data-id='{$RELATED_RECORD->getId()}' data-recordUrl='{$RELATED_RECORD->getDetailViewUrl()}'>
                     <td width="4%" class="{$WIDTHTYPE}">
                        <input type="checkbox" value="{$RELATED_RECORD->getId()}" class="listViewEntriesCheckBox"/>
                    </td>                        
                    {foreach item=HEADER_FIELD from=$RELATED_HEADERS}
                        {assign var=RELATED_HEADERNAME value=$HEADER_FIELD->get('name')}
                        <td nowrap class="{$WIDTHTYPE}">
                            {if $HEADER_FIELD->isNameField() eq true or $HEADER_FIELD->get('uitype') eq '4'}
                                <a href="{$RELATED_RECORD->getDetailViewUrl()}">{$RELATED_RECORD->getDisplayValue($RELATED_HEADERNAME)}</a>
                            {elseif $RELATED_HEADERNAME eq 'access_count'}
                                {$RELATED_RECORD->getAccessCountValue($PARENT_RECORD->getId())}
                            {elseif $RELATED_HEADERNAME eq 'time_start'}
                            {else}
                                {$RELATED_RECORD->getDisplayValue($RELATED_HEADERNAME)}
                            {/if}
                        </td>
                    {/foreach}
                    
                    <td nowrap class="{$WIDTHTYPE}">
                        <span class="addedByUser btn-group" data-id='{$RELATED_RECORD->get('added_by_user_id')}'>
                            {$RELATED_RECORD->get('added_by_user_name')}
                        </span>
                    </td>
                    <td nowrap class="{$WIDTHTYPE}">
                        <span class="addedByUser btn-group" data-id='{$RELATED_RECORD->get('parent')}'>
                            {$RELATED_RECORD->get('parent')}
                        </span>
                    </td>
                    
                    <td nowrap class="{$WIDTHTYPE}">
                        <div class="pull-right actions">
                            <span class="actionImages">
                                <a href="{$RELATED_RECORD->getFullDetailViewUrl()}"><i title="{vtranslate('LBL_SHOW_COMPLETE_DETAILS', $MODULE)}" class="icon-th-list alignMiddle"></i></a>&nbsp;
                                {if $IS_EDITABLE}
                                    <a href='{$RELATED_RECORD->getEditViewUrl()}'><i title="{vtranslate('LBL_EDIT', $MODULE)}" class="icon-pencil alignMiddle"></i></a>
                                {/if}
                                {if $IS_DELETABLE}
                                    <a class="relationDelete"><i title="{vtranslate('LBL_DELETE', $MODULE)}" class="icon-trash alignMiddle"></i></a>
                                {/if}
                            </span>
                        </div>
                    </td>
                </tr>
            {/foreach}
        </table>
    </div>
</div>
</div>
{/strip}

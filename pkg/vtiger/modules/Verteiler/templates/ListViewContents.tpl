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
<input type="hidden" id="view" value="{$VIEW}" />
<input type="hidden" id="pageStartRange" value="{$PAGING_MODEL->getRecordStartRange()}" />
<input type="hidden" id="pageEndRange" value="{$PAGING_MODEL->getRecordEndRange()}" />
<input type="hidden" id="previousPageExist" value="{$PAGING_MODEL->isPrevPageExists()}" />
<input type="hidden" id="nextPageExist" value="{$PAGING_MODEL->isNextPageExists()}" />
<input type="hidden" id="alphabetSearchKey" value= "{$MODULE_MODEL->getAlphabetSearchField()}" />
<input type="hidden" id="Operator" value="{$OPERATOR}" />
<input type="hidden" id="alphabetValue" value="{$ALPHABET_VALUE}" />
<input type="hidden" id="totalCount" value="{$LISTVIEW_COUNT}" />
<input type='hidden' value="{$PAGE_NUMBER}" id='pageNumber'>
<input type='hidden' value="{$PAGING_MODEL->getPageLimit()}" id='pageLimit'>
<input type="hidden" value="{$LISTVIEW_ENTRIES_COUNT}" id="noOfEntries">

{assign var = ALPHABETS_LABEL value = vtranslate('LBL_ALPHABETS', 'Vtiger')}
{assign var = ALPHABETS value = ','|explode:$ALPHABETS_LABEL}

<div class="alphabetSorting noprint">
	<table width="100%" class="table-bordered" style="border: 1px solid #ddd;table-layout: fixed">
		<tbody>
			<tr>
			{foreach item=ALPHABET from=$ALPHABETS}
				<td class="alphabetSearch textAlignCenter cursorPointer {if $ALPHABET_VALUE eq $ALPHABET} highlightBackgroundColor {/if}" style="padding : 0px !important"><a id="{$ALPHABET}" href="#">{$ALPHABET}</a></td>
			{/foreach}
			</tr>
		</tbody>
	</table>
</div>
<div id="selectAllMsgDiv" class="alert-block msgDiv noprint">
	<strong><a id="selectAllMsg">{vtranslate('LBL_SELECT_ALL',$MODULE)}&nbsp;{vtranslate($MODULE ,$MODULE)}&nbsp;(<span id="totalRecordsCount"></span>)</a></strong>
</div>
<div id="deSelectAllMsgDiv" class="alert-block msgDiv noprint">
	<strong><a id="deSelectAllMsg">{vtranslate('LBL_DESELECT_ALL_RECORDS',$MODULE)}</a></strong>
</div>
<div class="contents-topscroll noprint">
	<div class="topscroll-div">
		&nbsp;
	 </div>
</div>
<div class="listViewEntriesDiv contents-bottomscroll">
	<div class="bottomscroll-div">
	<input type="hidden" value="{$ORDER_BY}" id="orderBy">
	<input type="hidden" value="{$SORT_ORDER}" id="sortOrder">
	<span class="listViewLoadingImageBlock hide modal noprint" id="loadingListViewModal">
		<img class="listViewLoadingImage" src="{vimage_path('loading.gif')}" alt="no-image" title="{vtranslate('LBL_LOADING', $MODULE)}"/>
		<p class="listViewLoadingMsg">{vtranslate('LBL_LOADING_LISTVIEW_CONTENTS', $MODULE)}........</p>
	</span>
	{assign var=WIDTHTYPE value=$CURRENT_USER_MODEL->get('rowheight')}
	<table class="table table-bordered listViewEntriesTable">
		<thead>
			<tr class="listViewHeaders">
				<th width="5%">
					<input type="checkbox" id="listViewEntriesMainCheckBox" />
				</th>
				{foreach key=ENTRY_INDEX item=LISTVIEW_HEADER from=$LISTVIEW_HEADERS}
				<th nowrap>
                
                    {if $LISTVIEW_HEADER->block->module->name neq null AND $LISTVIEW_HEADER->block->module->name != $MODULE} {*crmnow *}
                        <a href="javascript:void(0);" class="listViewHeaderValues" data-nextsortorderval="{if $COLUMN_NAME eq $ENTRY_INDEX}{$NEXT_SORT_ORDER}{else}ASC{/if}" data-columnname="{$ENTRY_INDEX}">{vtranslate($LISTVIEW_HEADER->get('label'), $MODULE)} 
						<span style='font-size:80%;font-weight:normal;color:#888;'> {if {vtranslate({$LISTVIEW_HEADER->block->module->name})} neq ''} ({vtranslate({$LISTVIEW_HEADER->block->module->name})}) {/if}</span>
						&nbsp;&nbsp;{if $COLUMN_NAME eq $ENTRY_INDEX}<img class="{$SORT_IMAGE} icon-white">{/if}</a>
                    {else}
                        <a href="javascript:void(0);" class="listViewHeaderValues" data-nextsortorderval="{if $COLUMN_NAME eq $LISTVIEW_HEADER->get('column')}{$NEXT_SORT_ORDER}{else}ASC{/if}" data-columnname="{$LISTVIEW_HEADER->get('column')}">{vtranslate($LISTVIEW_HEADER->get('label'), $MODULE)} 
                        &nbsp;&nbsp;{if $COLUMN_NAME eq $LISTVIEW_HEADER->get('column')}<img class="{$SORT_IMAGE} icon-white">{/if}</a>
                    {/if}
				</th>
				{/foreach}
                <th nowrap>
                    <a href="javascript:void(0);" class="listViewHeaderValues" data-nextsortorderval="{if $COLUMN_NAME == {vtranslate('LBL_ENTRIES', $MODULE)}}{$NEXT_SORT_ORDER}{else}ASC{/if}" data-columnname="{vtranslate('LBL_ENTRIES', $MODULE)}">{vtranslate('LBL_ENTRIES', $MODULE)}
                    &nbsp;&nbsp;{if $COLUMN_NAME eq {vtranslate('LBL_ENTRIES', $MODULE)}}<img class="{$SORT_IMAGE} icon-white">{/if}</a>
                </th>
			</tr>
		</thead>
        {if $MODULE_MODEL->isQuickSearchEnabled()}
        <tr>
            <td></td>
			{foreach key=ENTRY_INDEX item=LISTVIEW_HEADER from=$LISTVIEW_HEADERS}
             <td>
                 {assign var=FIELD_UI_TYPE_MODEL value=$LISTVIEW_HEADER->getUITypeModel()}
                {include file=vtemplate_path($FIELD_UI_TYPE_MODEL->getListSearchTemplateName(),$MODULE_NAME)
                    FIELD_MODEL= $LISTVIEW_HEADER SEARCH_INFO=$SEARCH_DETAILS[$LISTVIEW_HEADER->getName()] USER_MODEL=$CURRENT_USER_MODEL}
             </td>
			{/foreach}
			<td>
				<button class="btn" data-trigger="listSearch">{vtranslate('LBL_SEARCH', $MODULE )}</button>
			</td>
        </tr>
        {/if}
		{if $URL_SEARCH_PARAMETERS}
			<input type="hidden" id="url_search_parameters" value='{$URL_SEARCH_PARAMETERS}'>
		{/if}
		{foreach item=LISTVIEW_ENTRY from=$LISTVIEW_ENTRIES name=listview}
		<tr style="background: linear-gradient({$LISTVIEW_ENTRY->getListViewColor()});" class="listViewEntries" data-id='{$LISTVIEW_ENTRY->getId()}' data-recordUrl='{$LISTVIEW_ENTRY->getDetailViewUrl()}' id="{$MODULE}_listView_row_{$smarty.foreach.listview.index+1}">
           <td  width="5%" class="{$WIDTHTYPE}">
				<input type="checkbox" value="{$LISTVIEW_ENTRY->getId()}" class="listViewEntriesCheckBox"/>
			</td>
			{foreach key=ENTRY_INDEX item=LISTVIEW_HEADER from=$LISTVIEW_HEADERS}
			{assign var=LISTVIEW_HEADERNAME value=$ENTRY_INDEX}
			<td class="listViewEntryValue {$WIDTHTYPE}" data-field-type="{$LISTVIEW_HEADER->getFieldDataType()}" nowrap>
				{if ($LISTVIEW_HEADER->isNameField() eq true or $LISTVIEW_HEADER->get('uitype') eq '4') and $MODULE_MODEL->isListViewNameFieldNavigationEnabled() eq true }
					<a href="{$LISTVIEW_ENTRY->getDetailViewUrl()}">{$LISTVIEW_ENTRY->get($LISTVIEW_HEADERNAME)}</a>
				{else if $LISTVIEW_HEADER->get('uitype') eq '72'}
					{assign var=CURRENCY_SYMBOL_PLACEMENT value={$CURRENT_USER_MODEL->get('currency_symbol_placement')}}
					{if $CURRENCY_SYMBOL_PLACEMENT eq '1.0$'}
						{$LISTVIEW_ENTRY->get($LISTVIEW_HEADERNAME)}{$LISTVIEW_ENTRY->get('currencySymbol')}
					{else}
						{$LISTVIEW_ENTRY->get('currencySymbol')}{$LISTVIEW_ENTRY->get($LISTVIEW_HEADERNAME)}
					{/if}
				{else}
                    {if $LISTVIEW_HEADER->getFieldDataType() eq 'double'}
                        {decimalFormat($LISTVIEW_ENTRY->get($LISTVIEW_HEADERNAME))}
                    {elseif $LISTVIEW_HEADER->getFieldDataType() eq 'percentage'}
                        {decimalFormat($LISTVIEW_ENTRY->get($LISTVIEW_HEADERNAME))}%
                    {else}
                        {$LISTVIEW_ENTRY->get($LISTVIEW_HEADERNAME)}
                    {/if}
				{/if}
			</td>
				{if $LISTVIEW_HEADER@last}
				<td nowrap class="{$WIDTHTYPE}">
				<div class="actions pull-right">{$LISTVIEW_ENTRY->get('totalrelated')}&nbsp;
					<span class="actionImages">
						<a href="{$LISTVIEW_ENTRY->getFullDetailViewUrl()}"><i title="{vtranslate('LBL_SHOW_COMPLETE_DETAILS', $MODULE)}" class="icon-th-list alignMiddle"></i></a>&nbsp;
						{if $IS_MODULE_EDITABLE}
							<a href='{$LISTVIEW_ENTRY->getEditViewUrl()}'><i title="{vtranslate('LBL_EDIT', $MODULE)}" class="icon-pencil alignMiddle"></i></a>&nbsp;
						{/if}
						{if $IS_MODULE_DELETABLE}
							<a class="deleteRecordButton"><i title="{vtranslate('LBL_DELETE', $MODULE)}" class="icon-trash alignMiddle"></i></a>
						{/if}
					</span>
				</div></td>
				{/if}
			{/foreach}
		</tr>
		{/foreach}
	</table>

<!--added this div for Temporarily -->
{if $LISTVIEW_ENTRIES_COUNT eq '0'}
	<table class="emptyRecordsDiv">
		<tbody>
			<tr>
				<td>
					{assign var=SINGLE_MODULE value="SINGLE_$MODULE"}
					{vtranslate('LBL_EQ_ZERO')} {vtranslate($SINGLE_MODULE, $MODULE)} {vtranslate('LBL_FOUND')}.{if $IS_RECORD_CREATABLE} {vtranslate('LBL_CREATE')} <a href="{$MODULE_MODEL->getCreateRecordUrl()}">{vtranslate($SINGLE_MODULE, $MODULE)}</a>{/if}
				</td>
			</tr>
		</tbody>
	</table>
{/if}
</div>
</div>
{/strip}

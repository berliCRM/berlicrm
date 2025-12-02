{************************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************}
<div style='padding:4%;padding-top: 0;margin-bottom: 2%'>

	{* Comupte the nubmer of columns required *}
	{assign var="SPANSIZE" value=12}
	{if $MINILIST_WIDGET_MODEL->getHeaderCount()}
		{assign var="SPANSIZE" value=12/$MINILIST_WIDGET_MODEL->getHeaderCount()}
	{/if}

	<div class="row-fluid" style="padding:5px">
		{foreach item=FIELD from=$MINILIST_WIDGET_MODEL->getHeaders()}
		<div class="span{$SPANSIZE}"><strong>{vtranslate($FIELD->get('label'),$BASE_MODULE)}</strong></div>
		{/foreach}
	</div>

	{assign var="MINILIST_WIDGET_RECORDS" value=$MINILIST_WIDGET_MODEL->getRecords()}

	{foreach item=RECORD from=$MINILIST_WIDGET_RECORDS}
	<div class="row-fluid" style="padding:5px">

	{foreach item=FIELD from=$MINILIST_WIDGET_MODEL->getHeaders() name="minilistWidgetModelRowHeaders"}
		{assign var=fieldName value=$FIELD->get('name')}
		{assign var=fieldValue value=$RECORD->get($fieldName)}

		{* <div class="span{$SPANSIZE}"> *}
		<div class="span{$SPANSIZE} textOverflowEllipsis" title="{strip_tags($RECORD->get($FIELD->get('name')))}">

			{if $fieldName eq 'filename'}
				{assign var=recordId value=$RECORD->getId()}
				{assign var=fileDetails value=$RECORD->getFileDetails()}
				{assign var=previewUrl value="index.php?module=Documents&action=DownloadFile&record=`$recordId`&fileid=`$fileDetails.attachmentsid`&mode=preview"}

				<a href="{$previewUrl}"
				class="pdf-link"
				data-pdf-preview="{$previewUrl}">
					{$fieldValue}
				</a>
			{else}
				{$fieldValue}
			{/if}
			{if $smarty.foreach.minilistWidgetModelRowHeaders.last}
					<a href="{$RECORD->getDetailViewUrl()}" class="pull-right"><i title="{vtranslate('LBL_SHOW_COMPLETE_DETAILS',$MODULE_NAME)}" class="icon-th-list alignMiddle"></i></a>
			{/if}			
		</div>
	{/foreach}

	<script src="layouts/vlayout/modules/Vtiger/resources/List.js"></script>
	{literal}
	<script>
	jQuery(function() {
		const listInstance = Vtiger_List_Js.getInstance();

		if (typeof listInstance.registerPreviewEvents === 'function') {
			listInstance.registerPreviewEvents();
		} else {
			console.warn('registerPreviewEvents nicht verfügbar');
		}
	});
	</script>
	{/literal}

	</div>
	{/foreach}

	{if count($MINILIST_WIDGET_RECORDS) >= $MINILIST_WIDGET_MODEL->getRecordLimit()}
	<div class="row-fluid" style="padding:5px;padding-bottom:10px;">
		<a class="pull-right" href="index.php?module={$MINILIST_WIDGET_MODEL->getTargetModule()}&view=List&mode=showListViewRecords&viewname={$WIDGET->get('filterid')}">{vtranslate('LBL_MORE')}</a>
	</div>
	{/if}

</div>
{*<!--/************************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/-->*}

{strip}
<div class="span2 row-fluid">
	<div id="_quicklinks_mainuidiv_" class="quickWidgetContainer accordion">
		{include file="modules/MailManager/MainuiQuickLinks.tpl"}

		<div class="clearfix">&nbsp;
			<input type="hidden" id="isMailBoxExists" value="{if $MAILBOX->exists()}1{else}0{/if}"/>
		</div>
		<div class="quickWidget">
		<div class="accordion-heading accordion-toggle quickWidgetHeader" onclick="MailManager.getFoldersList();">
			<span class="pull-left">
				<img class="imageElement" data-rightimage="{vimage_path('rightArrowWhite.png')}" data-downimage="{vimage_path('downArrowWhite.png')}" src="{vimage_path('rightArrowWhite.png')}" />
			</span>&nbsp;
			<h5 class="title widgetTextOverflowEllipsis pull-right">{vtranslate('LBL_Folders',$MODULE)}</h5>
		</div>

		<div class="widgetContainer accordion-body collapse in" id="folders">
			<input type=hidden name="mm_selected_folder" id="mm_selected_folder">
			<input type="hidden" name="_folder" id="mailbox_folder">
		</div>
	</div>
		<div id="_mainfolderdiv_" class="quickWidgetContainer accordion"></div>
	</div>
</div>

<div class="contentsDiv span10 marginLeftZero">
	<div id='_progress_' style='float: right; display: none; position: absolute; right: 35px; font-weight: bold;'>
		<span id='_progressmsg_'>...</span><img src="{'vtbusy.gif'|@vimage_path}" border='0' align='absmiddle'>
	</div>
	<span id="_messagediv_">{if $ERROR}<p>{$ERROR}</p>{/if}</span>
	<div id="_contentdiv_"></div>
    <div id="_contentdiv2_" class="container-fluid"></div>
	<div id="_settingsdiv_"></div>
	<div id="_relationpopupdiv_" style="display:none;position:absolute;width:800px;z-index:80000;"></div>
	<div id="_replydiv_" style="display:none;"></div>
	<div id="replycontentdiv" style="display:none;"></div>
</div>
<div id = '__vtiger__'></div>

<script type='text/javascript'>
	{literal}
		jQuery(function(){MailManager.mainui()});
	{/literal}
</script>
<input type="hidden" name="module" value="MailManager">
{/strip}
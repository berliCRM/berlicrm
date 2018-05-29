{*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************}
 
{if empty($smarty.request.ajax)}
<table class="small" border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
<td colspan="4" class="dvInnerHeader">
	<div style="float: left; font-weight: bold;">
	<div style="float: left;">
	<a href="javascript:showHideStatus('tbl{$UIKEY}','aid{$UIKEY}','$IMAGE_PATH');"><img id="aid{$UIKEY}" src="{'activate.gif'|@vtiger_imageurl:$THEME}" style="border: 0px solid rgb(0, 0, 0);" alt="Hide" title="Hide"></a>
	</div><b>&nbsp;{$WIDGET_TITLE}</b></div>
	<span style="float: right;">
		<img src="themes/images/vtbusy.gif" border=0 id="indicator{$UIKEY}" style="display:none;">
	</span>
	</td>
</tr>
</table>
{/if}

<div id="tbl{$UIKEY}">
	
	<table class="small" border="0" cellpadding="0" cellspacing="0" width="100%">
	
	<tr style="height: 25px;">
		<td colspan="4" align="left" class="dvtCellInfo" >
		<div id="contentwrap_{$UIKEY}" style="overflow: auto; height: 420px; width: 100%;">
			
		</div>
		</td>
	</tr>
	</table>
</div>

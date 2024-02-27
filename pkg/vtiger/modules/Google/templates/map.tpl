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
<script type="text/javascript" src="layouts/vlayout/modules/Google/resources/map.js"></script>

<span id="map_record" class="hide">{$RECORD}</span>
<span id="map_module" class="hide">{$SOURCE_MODULE}</span>

{if $MAPAPIKEY neq ''}
	<div id="map_canvas" style="text-align:center">
		<span id="map_address" class="hide"></span>
		<img id="map_link" class="pull-right icon-share cursorPointer hide" style="margin-right:4px"></img>
	</div>
{else}
	<div class="recordNamesList">
		<div class="row-fluid">
			<div>
				<ul class="nav nav-list">
					<li>
						{if $USER_MODEL->get('is_admin') eq 'on'}
							<a href="{$SETMENUEURL}" data-id="5">{vtranslate('LBL_MISSING_MAP_KEY_ADMIN',$SOURCE_MODULE)}</a>
						{else}
							{vtranslate('LBL_MISSING_MAP_KEY',$SOURCE_MODULE)}</a>
						{/if}
					</li>
				</ul>
			</div>
		</div>
	</div>
{/if}
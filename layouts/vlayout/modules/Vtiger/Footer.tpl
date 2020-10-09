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

		<input id='activityReminder' class='hide noprint' type="hidden" value="{$ACTIVITY_REMINDER}"/>

		{if isset($CURRENT_USER_MODEL)}
		<footer class="noprint">
            <div class="vtFooter">
			<p>
				berliCRM&nbsp;{$SVN_TAG|replace:"berlicrm-":" "}&nbsp;&nbsp;
				&copy; 2004 - {date('Y')}&nbsp;&nbsp;
				{if $CURRENT_USER_MODEL->language eq 'de_de'}
				<a href="#" onclick="window.open('copyright.html','copyright', 'height=115,width=575').moveTo(210,620)">Copyright</a>
				{else}
				<a href="#" onclick="window.open('copyright_en.html','copyright', 'height=115,width=575').moveTo(210,620)">Copyright</a>
				{/if}
			</p>
            </div>
		</footer>
		{/if}
		
		{* javascript files *}
		{include file='JSResources.tpl'|@vtemplate_path}
		</div>
		
	</body>
</html>
{/strip}

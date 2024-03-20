{*<!--/************************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/-->*}

{strip}
<br>
<div class="quickWidget">
	<div class="accordion-heading accordion-toggle quickWidgetHeader">
		<table width="100%" cellspacing="0" cellpadding="0">
			<tbody>
				<tr>
					<td class="span5">
						<div class="dashboardTitle textOverflowEllipsis" title="{vtranslate('LBL_Mailbox', 'MailManager')}">
							<h5 class="title widgetTextOverflowEllipsis">{vtranslate('LBL_Mailbox', 'MailManager')}</h5>
						</div>
					</td>
					<td class="widgeticons span5" align="right">
						<div class="box pull-right">
							<a href='#Reload' id="_mailfolder_mm_reload" onclick="MailManager.reload_now();">
								<i alt="Refresh" title="{vtranslate('LBL_Refresh', 'MailManager')}" align="absmiddle" border="0" hspace="2" class="icon-refresh"></i>
							</a>
							<a href='#Settings' id="_mailfolder_mm_settings" onclick="MailManager.open_settings_detail();">
								<i alt="Settings" title="{vtranslate('LBL_SETTINGS', 'MailManager')}" align="absmiddle" border="0" hspace="2" class="icon-cog"></i>
							</a>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
		<div class="clearfix"></div>
	</div>
	<div class="defaultContainer {if $MAILBOX->exists() eq false}hide{/if}">
		<div class="widgetContainer accordion-body collapse in">
			<input type=hidden name="mm_selected_folder" id="mm_selected_folder">
			<input type="hidden" name="_folder" id="mailbox_folder">
			<div class="row-fluid">
				<div class="span12">
					<ul class="nav nav-list">
						<li>
							<a href="javascript:void(0);" onclick="MailManager.mail_compose();">{vtranslate('LBL_Compose','MailManager')}</a>
						</li>
						<li>
							<a href="#Drafts" id="_mailfolder_mm_drafts" onclick="MailManager.folder_drafts(0);">{vtranslate('LBL_Drafts','MailManager')}</a>
						</li>
					</ul>
				</div>
			</div>
		</div>
	</div>
</div>
{/strip}

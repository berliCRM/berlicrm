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
    {assign var=MAIL_TO value=$COMMENT->get('mailto')}
    {if $MAIL_TO neq NULL && $MAIL_TO neq ''}
        {assign var=MAIL_FROM value=$COMMENT->get('mailfrom')}
        {assign var=MAIL_CC value=$COMMENT->get('carboncopy')}
        {assign var=MAIL_BCC value=$COMMENT->get('blindcarboncopy')}
        {assign var=EMAIL_RECORD_ID value=$COMMENT->get('emailid')}
        <span class="js-comment-mail-metadata muted cursorPointer" tabindex="0" title="{vtranslate('LBL_EMAIL_RECORD', 'ModComments')}" style="display:inline-block;margin-left:4px;vertical-align:middle;">
			<i class="icon-envelope"></i>
		</span>
        <span class="js-comment-mail-popover-content hide">
			<span style="display:block;min-width:220px;max-width:320px;line-height:18px;">
				{if $MAIL_FROM neq NULL && $MAIL_FROM neq ''}
                    <span style="display:table;width:100%;table-layout:fixed;margin-bottom:3px;">
						<span style="display:table-cell;width:42px;font-weight:bold;vertical-align:top;">{vtranslate('LBL_FROM', 'Emails')}:</span>
						<span style="display:table-cell;word-break:break-word;overflow-wrap:anywhere;">{$MAIL_FROM|escape:'html'}</span>
					</span>
                {/if}
				<span style="display:table;width:100%;table-layout:fixed;margin-bottom:3px;">
					<span style="display:table-cell;width:42px;font-weight:bold;vertical-align:top;">{vtranslate('LBL_TO', 'Emails')}:</span>
					<span style="display:table-cell;word-break:break-word;overflow-wrap:anywhere;">{$MAIL_TO|escape:'html'}</span>
				</span>
				{if $MAIL_CC neq NULL && $MAIL_CC neq ''}
                    <span style="display:table;width:100%;table-layout:fixed;margin-bottom:3px;">
						<span style="display:table-cell;width:42px;font-weight:bold;vertical-align:top;">{vtranslate('LBL_CC', 'Emails')}:</span>
						<span style="display:table-cell;word-break:break-word;overflow-wrap:anywhere;">{$MAIL_CC|escape:'html'}</span>
					</span>
                {/if}
                {if $MAIL_BCC neq NULL && $MAIL_BCC neq ''}
                    <span style="display:table;width:100%;table-layout:fixed;margin-bottom:3px;">
						<span style="display:table-cell;width:42px;font-weight:bold;vertical-align:top;">{vtranslate('LBL_BCC', 'Emails')}:</span>
						<span style="display:table-cell;word-break:break-word;overflow-wrap:anywhere;">{$MAIL_BCC|escape:'html'}</span>
					</span>
                {/if}
                {if $EMAIL_RECORD_ID neq NULL && $EMAIL_RECORD_ID neq ''}
                    <span style="display:block;margin-top:8px;padding-top:6px;border-top:1px solid #e5e5e5;">
						<a href="javascript:void(0);"
                           name="emailsRelatedRecord"
                           data-id="{$EMAIL_RECORD_ID|escape:'html'}">
							{vtranslate('LBL_OPEN_EMAIL_RECORD', 'ModComments')}
						</a>
					</span>
                {/if}
			</span>
		</span>
    {/if}
{/strip}

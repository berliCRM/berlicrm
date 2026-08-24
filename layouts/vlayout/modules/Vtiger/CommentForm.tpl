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
    {if !isset($COMMENT_TEXTAREA_DEFAULT_ROWS)}
        {assign var="COMMENT_TEXTAREA_DEFAULT_ROWS" value="2"}
    {/if}
    <div class="{if $COMMENT_FORM_IS_HIDDEN}hide {/if}{$COMMENT_FORM_BLOCK_CLASS}"{if !empty($COMMENT_FORM_STYLE)} style="{$COMMENT_FORM_STYLE}"{/if}>
        {if $COMMENT_FORM_SHOW_REASON}
            <div class="row-fluid">
                <span class="span1">&nbsp;</span>
                <div class="span11">
                    <input type="text" name="reasonToEdit"
                           placeholder="{vtranslate('LBL_REASON_FOR_CHANGING_COMMENT', $MODULE_NAME)}"
                           class="input-block-level" />
                </div>
            </div>
        {/if}

        {if $COMMENT_FORM_IS_NESTED}
            <div class="row-fluid">
                <span class="span1">&nbsp;</span>
                <div class="span11">
				<textarea class="{$COMMENT_FORM_TEXTAREA_CLASS}" name="commentcontent"
                          rows="{$COMMENT_TEXTAREA_DEFAULT_ROWS}"{if !empty($COMMENT_FORM_PLACEHOLDER)}
				          placeholder="{vtranslate($COMMENT_FORM_PLACEHOLDER, $MODULE_NAME)}"{/if}></textarea>
                </div>
            </div>
        {else}
            <div>
			<textarea name="commentcontent" class="{$COMMENT_FORM_TEXTAREA_CLASS}"
                      placeholder="{vtranslate($COMMENT_FORM_PLACEHOLDER, $MODULE_NAME)}"
                      rows="{$COMMENT_TEXTAREA_DEFAULT_ROWS}"></textarea>
            </div>
        {/if}

        {if $MODULE_NAME == 'HelpDesk' && $COMMENT_FORM_HELPDESK_FIELDS eq 'status'}
            <div class="row-fluid">
                <span class="span1">&nbsp;</span>
                <div class="span11">
                    {include file='CommentTicketStatusSelect.tpl'|@vtemplate_path:$MODULE_NAME}
                </div>
            </div>
        {/if}

        <div class="pull-right">
            {if $MODULE_NAME == 'HelpDesk' && $COMMENT_FORM_SHOW_SEND_MAIL}
                <button class="{$COMMENT_FORM_SEND_MAIL_BUTTON_CLASS}" type="button"
                        data-mode="sendMail"><strong>{vtranslate('LBL_SEND_MAIL_AND_POST', $MODULE_NAME)}</strong></button>
            {/if}
            <button class="{$COMMENT_FORM_POST_BUTTON_CLASS}" type="button"
                    data-mode="{$COMMENT_FORM_MODE}"><strong>{vtranslate('LBL_POST', $MODULE_NAME)}</strong></button>
            {if $COMMENT_FORM_SHOW_CANCEL}
                <a class="cursorPointer closeCommentBlock{if !empty($COMMENT_FORM_CANCEL_CLASS)} {$COMMENT_FORM_CANCEL_CLASS}{/if}"
                   type="reset">{vtranslate('LBL_CANCEL', $MODULE_NAME)}</a>
            {/if}
        </div>

        {if $MODULE_NAME == 'HelpDesk' && ($COMMENT_FORM_HELPDESK_FIELDS eq 'full' || $COMMENT_FORM_HELPDESK_FIELDS eq 'external' || $COMMENT_FORM_HELPDESK_FIELDS eq 'external_time')}
            <div{if $COMMENT_FORM_HELPDESK_FIELDS eq 'external'} style="display:inline-block; margin-right:20px;"{/if}>
                <input type="checkbox" id="externalComment" name="externalComment"{if $COMMENT_FORM_HELPDESK_FIELDS eq 'external'} class="alignTop"{/if}>&nbsp;
                <label for="externalComment"
                       style="display:inline;">{vtranslate('LBL_EXTERNAL_COMMENT', $MODULE_NAME)}</label>
            </div>
        {/if}

        {if $MODULE_NAME == 'HelpDesk' && ($COMMENT_FORM_HELPDESK_FIELDS eq 'full' || $COMMENT_FORM_HELPDESK_FIELDS eq 'external_time')}
            <div class="input-append time pushDown">
                <label for="timeNeeded">{vtranslate('LBL_TIME_NEEDED', $MODULE_NAME)}:</label>
                <input id="timeNeeded" type="text" data-format="24" class="timepicker-default input-small" value="{$COMMENT_FORM_TIME_NEEDED_VALUE}" name="timeNeeded"
                       data-validation-engine="validate[funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" />
                <span class="add-on cursorPointer">
				<i class="icon-time"></i>
			</span>
                {if $COMMENT_FORM_HELPDESK_FIELDS eq 'full'}
                    {include file='CommentTicketStatusSelect.tpl'|@vtemplate_path:$MODULE_NAME}
                {/if}
            </div>
        {/if}
    </div>
{/strip}

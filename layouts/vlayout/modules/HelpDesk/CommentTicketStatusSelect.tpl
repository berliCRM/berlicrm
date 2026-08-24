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
    {if !empty($TICKET_STATUS_PICKLIST_VALUES)}
        <div class="ticketStatusCommentControl pull-right">
            <label for="comment_ticketstatus" style="display:inline-block; margin-right:6px;">
                {vtranslate('LBL_SET_NEW_STATUS', $MODULE_NAME)}:
            </label>
            <select id="comment_ticketstatus" name="comment_ticketstatus" class="input-max">
                <option value="" selected>{vtranslate('LBL_STATUS_UNCHANGED', $MODULE_NAME)}</option>
                {foreach key=TICKET_STATUS item=TICKET_STATUS_LABEL from=$TICKET_STATUS_PICKLIST_VALUES}
                    <option value="{$TICKET_STATUS}">{$TICKET_STATUS_LABEL}</option>
                {/foreach}
            </select>
        </div>
    {/if}
{/strip}

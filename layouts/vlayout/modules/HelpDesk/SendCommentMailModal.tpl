{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*
********************************************************************************/
-->*}
{strip}
<form class="form-horizontal js-send-mail-comment-form">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h3>{vtranslate('LBL_SEND_MAIL_AND_POST', $MODULE)}</h3>
	</div>
	<div class="modal-body">
		<p class="muted">{vtranslate('LBL_HELPDESK_COMMENT_MAIL_NOTICE', $MODULE)}</p>
		<div class="control-group">
			<label class="control-label">{vtranslate('LBL_CC', $MODULE)}</label>
			<div class="controls">
				<input class="input-block-level js-comment-mail-cc" type="text" value="" placeholder="name@example.com, other@example.com" />
			</div>
		</div>
		<div class="control-group">
			<label class="control-label">{vtranslate('LBL_BCC', $MODULE)}</label>
			<div class="controls">
				<input class="input-block-level js-comment-mail-bcc" type="text" value="" placeholder="name@example.com, other@example.com" />
			</div>
		</div>
		<div class="control-group">
			<label class="control-label">{vtranslate('LBL_ATTACHMENT', $MODULE)}</label>
			<div class="controls">
				<div class="js-comment-mail-dropzone" style="border: 2px dashed #b5bcc4; border-radius: 6px; padding: 18px; text-align: center; background: #fafbfd; cursor: pointer;">
					<div>{vtranslate('LBL_HELPDESK_DROP_FILES_HERE', $MODULE)}</div>
					<div class="muted small">{vtranslate('LBL_HELPDESK_OR_CLICK_TO_SELECT', $MODULE)}</div>
				</div>
				<input class="js-comment-mail-files" type="file" multiple style="display: none;" />
				<ul class="unstyled js-comment-mail-file-list" style="margin-top: 12px;"></ul>
			</div>
		</div>
	</div>
	<div class="modal-footer">
		<div class="pull-right cancelLinkContainer" style="margin-top: 0;">
			<a class="cancelLink" type="reset" data-dismiss="modal">{vtranslate('LBL_CANCEL', $MODULE)}</a>
		</div>
		<button class="btn btn-success js-submit-send-mail-comment" type="button">
			<strong>{vtranslate('LBL_SEND_MAIL_AND_POST', $MODULE)}</strong>
		</button>
	</div>
</form>
{/strip}

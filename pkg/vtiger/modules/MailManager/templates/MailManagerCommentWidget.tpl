{*<!--/************************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/-->*}

{strip}
<div class="modelContainer" id="commentWidget">
	<div class="modal-header contentsBackground">
        <button data-dismiss="modal" class="close" title="{vtranslate('LBL_CLOSE')}">&times;</button>
		<h3>{vtranslate('LBL_MAILMANAGER_ADD_ModComments', 'MailManager')}</h3>
	</div>
	<div class="modal-body tabbable">
		<textarea class="input-block-level span6" name="commentcontent" data-validation-engine="validate[required]" id="commentcontent" placeholder="{vtranslate('LBL_WRITE_YOUR_COMMENT_HERE', 'MailManager')}"></textarea>
	</div>
	<input type=hidden name="_mlinkto" value="{$PARENT}">
	<input type=hidden name="_mlinktotype" value="{$LINKMODULE}">
	<input type=hidden name="_msgno" value="{$MSGNO}">
	<input type=hidden name="_folder" value="{$FOLDER}">
	{include file='ModalFooter.tpl'|@vtemplate_path:$MODULE}
</div>
{/strip}
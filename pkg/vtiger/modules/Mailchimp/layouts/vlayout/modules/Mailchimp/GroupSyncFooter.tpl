{*<!--
/*********************************************************************************
  ** The contents of this file are subject to the vtiger CRM Public License Version 1.0
   * ("License"); You may not use this file except in compliance with the License
   * The Original Code is:  vtiger CRM Open Source
   * The Initial Developer of the Original Code is vtiger.
   * Portions created by vtiger are Copyright (C) vtiger.
   * All Rights Reserved.
   * Modified and improved by crm-now.de
  *
 ********************************************************************************/
-->*}
{strip}
	<div class="modal-footer">
		<div class="pull-right cancelLinkContainer" style="margin-top:0px;">
			<a class="cancelLink" type="reset" data-dismiss="modal">{vtranslate('LBL_CANCEL', $MODULE)}</a>
		</div>
		<button class="btn btn-success" type="submit" onclick="if(MailChimpCommon.sync({$ID}))MailChimpCommon.hide();else return false;"  name="saveButton"><strong>{vtranslate('LBL_SYNC', $MODULE)}</strong></button>
	</div>
{/strip}
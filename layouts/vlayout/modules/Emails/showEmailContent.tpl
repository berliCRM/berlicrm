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
<div class="emailSubstitute" name="emailSubstitutePreview">
	<div class="well well-large zeroPaddingAndMargin">
		<div class="modal-header blockHeader emailPreviewHeader" style="height:30px">
			<h3 class='pull-left'>{vtranslate('LBL_SHOW_EMAIL', $MODULE)}</h3>
			<div class='pull-right'>
				<span class="btn-toolbar">
					<span class="btn-group">
						<button class="btn btn-primary" type="reset" data-dismiss="modal">
							<strong>{vtranslate('LBL_CLOSE',$MODULE)}</strong>
						</button>
					</span>
				</span>
			</div>
		</div>
		<div class="modal-body">
			<div style="float:left;width:100%;text-align: left;font-family:'Lucida Grande';font-size:15px">
				{vtranslate('LBL_EMAILHTML_COMMENT',$MODULE)}
			</div>
			<p>&nbsp;</p>
			<div style="width:100%;height:450px;overflow:auto;overflow-y: scroll;padding:5px;border:1px solid lightgray;">
				{$EMAIL_CONTENT}
			</div>
			<div class="clear-both"></div>
		</div>
	</div>
</div>

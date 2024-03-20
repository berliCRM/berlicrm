{strip}
	<div class="modal-header">
		<button class="close" data-dismiss="modal" title="{vtranslate('LBL_CLOSE')}">x</button>
		<h3>
			{vtranslate('LBL_CONTACTDETAILS', $MODULE)}
		</h3>
	</div>
	<div class="modal-body">
		<div id ="transferPopupScroll" style="margin-right: 8px;">
			<table class="table table-bordered">
				<tr class="blockHeader">
					<td>
						<table width="99%">
							<tr>
								<td class="small">
									<textarea style="width:608px;border:1px solid #aaa;height:200px;padding:10px;" id="copy-text">{$COPYPASTESTRING}</textarea>
								</td>
							</tr>
							<tr>
								<td>
									<button id="copy-button">{vtranslate('LBL_COPY', $MODULE)}</button>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</div>
	</div>
</div>
<div class="alert ui-pnotify-container alert-info ui-pnotify-shadow" style="min-height: 16px;" id="copied">
    <div class="ui-pnotify-closer" style="cursor: pointer; visibility: hidden;">
        <span class="icon-remove"></span>
    </div>
    <div class="ui-pnotify-sticker" style="cursor: pointer; visibility: hidden; display: none;">
        <span class="icon-pause"></span>
    </div>
    <div class="ui-pnotify-text">{vtranslate('LBL_TEXT_COPIED', $MODULE)}</div>
</div>
<div id="transferPopupContainer" class="modelContainer" style="min-width:450px;height: auto;">
{/strip}
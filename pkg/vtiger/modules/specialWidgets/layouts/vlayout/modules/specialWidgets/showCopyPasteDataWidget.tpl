{strip}
<script type="text/javascript" src="layouts/vlayout/modules/specialWidgets/resources/popupMenueCopyAndPaste.js"></script>
<div class="CopyPasteContainer" id="CopyPasteContainer">
	<input type="hidden" name="sourcemodule" id="sourcemodule" value='{$SOURCEMODULE}' />
	<input type="hidden" name="recordid" id="recordid" value='{$RECORD}' />

    <div id="copypasteButton">
        <div class="modal-header contentsBackground">
            <div class="row-fluid">
				<input class="btn span" id="showcontactdetails" value="{vtranslate('LBL_SHOW_CONTACTDETAILS', $MODULE)}" type="button"> 
 			</div>
       </div>
    </div>
</div>
<script type="text/javascript">	
	// initialize button
	$(document).ajaxComplete(function() {
		 specialWidgets_popupMenueCopyAndPaste_Js.registerEvents();
	});

</script>
{/strip}

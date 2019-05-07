<div class='modelContainer modal basicCreateView'>
	<div class="modal-header">
		<button data-dismiss="modal" class="close" title="{vtranslate('LBL_CLOSE')}">x</button>
		<h3>{vtranslate('LBL_ADD_TO_TREE', $MODULE)}&nbsp;</h3>
	</div>
    
    <div class="modal-body">	
        <div class="control-group">
            <select id="verteilerlist" name="verteilerlist" style="width:500px" data-placeholder="{vtranslate('LBL_SELECT_VERTEILER',$MODULE)}">
                <option></option>
                {foreach key=key item=name from=$VERTEILER}
                    <option value="{$key}">{$name|escape:"html"}</option>
                {/foreach}
            </select>
        </div>
	</div>
    
    <div class="modal-footer">
		<div class="pull-right cancelLinkContainer" style="margin-top:0px;">
			<a class="cancelLink" type="reset" data-dismiss="modal">{vtranslate('LBL_CANCEL', $MODULE)}</a>
		</div>
		<button class="btn btn-success" type="submit" onclick="" id="addContacs" disabled><strong>{vtranslate('LBL_ADD_CONTACTS', $MODULE)}</strong></button>
	</div>
</div>
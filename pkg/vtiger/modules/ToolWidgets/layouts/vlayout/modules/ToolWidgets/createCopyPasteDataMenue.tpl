{strip}
<div class="modal-header">
    <button class="close" data-dismiss="modal" title="{vtranslate('LBL_CLOSE')}">x</button>
    <h3>{vtranslate('LBL_CONTACTDETAILS', $MODULE)}</h3>
</div>
<div class="modal-body">
    <div id="transferPopupScroll" class="scrollable-container">
        <table class="table table-bordered">
            <thead>
                <tr class="blockHeader">
				</tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div class="textarea-container">
                            <textarea 
                                id="copy-text" 
                                class="form-control" 
                                style="height: 200px;width:608px;border:1px solid #aaa;padding:10px;" 
                                readonly>{$COPYPASTESTRING}</textarea>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="text-center">
                        <button id="copy-button" class="btn btn-primary">{vtranslate('LBL_COPY', $MODULE)}</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
<div id="copied" class="alert alert-info ui-pnotify-container" style="display: none;" role="alert" aria-hidden="true">
    <span class="ui-pnotify-text">{vtranslate('LBL_TEXT_COPIED', $MODULE)}</span>
</div>
{/strip}
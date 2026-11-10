{strip}
{assign var=COMMENTS_EXPANDED value=Users_CommentDisplay_Model::isExpanded()}
<div class="row-fluid commentDisplayControls">
	<button type="button" class="btn btn-small toggleAllComments"
		data-expanded="{if $COMMENTS_EXPANDED}1{else}0{/if}"
		data-expand-label="{vtranslate('LBL_EXPAND_ALL_COMMENTS', 'Vtiger')|escape:'html'}"
		data-collapse-label="{vtranslate('LBL_COLLAPSE_ALL_COMMENTS', 'Vtiger')|escape:'html'}"
		data-save-error="{vtranslate('LBL_COMMENT_DISPLAY_SAVE_FAILED', 'Vtiger')|escape:'html'}"
		aria-expanded="{if $COMMENTS_EXPANDED}true{else}false{/if}">
		{if $COMMENTS_EXPANDED}{vtranslate('LBL_COLLAPSE_ALL_COMMENTS', 'Vtiger')}{else}{vtranslate('LBL_EXPAND_ALL_COMMENTS', 'Vtiger')}{/if}
	</button>
</div>
{/strip}

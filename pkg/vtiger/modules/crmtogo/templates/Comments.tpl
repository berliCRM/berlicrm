{foreach item=_COMMENT from=$_COMMENTS}
	<div class="ui-grid-a">
		<div class="ui-block-a">
			{$_COMMENT.commentcontent}<p />
			<font size="2">
				{vtranslate('LBL_AUTHOR', 'crmtogo')}: {$_COMMENT.assigned_user_id} {vtranslate('LBL_ON_DATE', 'crmtogo')} {$_COMMENT.createdtime}  
			</font>
			<hr />
		</div>
	</div>
{/foreach}
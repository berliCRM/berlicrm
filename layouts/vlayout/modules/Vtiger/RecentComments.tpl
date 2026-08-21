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
{strip}

	<div class="commentContainer recentComments">
		<div class="commentTitle row-fluid">
			{assign var=CREATE_PERMISSION value=$COMMENTS_MODULE_MODEL->isPermitted('CreateView')}
			{assign var=EDIT_PERMISSION value=$COMMENTS_MODULE_MODEL->isPermitted('EditView')}
			{if $CREATE_PERMISSION}
				{include file='CommentForm.tpl'|@vtemplate_path
				COMMENT_FORM_BLOCK_CLASS='addCommentBlock'
				COMMENT_FORM_IS_HIDDEN=false
				COMMENT_FORM_IS_NESTED=false
				COMMENT_FORM_MODE='add'
				COMMENT_FORM_TEXTAREA_CLASS='commentcontent'
				COMMENT_FORM_PLACEHOLDER='LBL_ADD_YOUR_COMMENT_HERE'
				COMMENT_FORM_POST_BUTTON_CLASS='btn btn-success detailViewSaveComment'
				COMMENT_FORM_SHOW_SEND_MAIL=true
				COMMENT_FORM_SEND_MAIL_BUTTON_CLASS='btn saveButton detailViewSaveComment'
				COMMENT_FORM_SHOW_CANCEL=false
				COMMENT_FORM_CANCEL_CLASS=''
				COMMENT_FORM_SHOW_REASON=false
				COMMENT_FORM_STYLE=''
				COMMENT_FORM_HELPDESK_FIELDS='full'
				COMMENT_FORM_TIME_NEEDED_VALUE='00:00'}
			{/if}
		</div>
		<hr><br>
		<div class="commentsList commentsBody">
			{include file='CommentsList.tpl'|@vtemplate_path
			COMMENT_MODULE_MODEL=$COMMENTS_MODULE_MODEL
			PARENT_COMMENTS=$COMMENTS
			EXPAND_COMMENT_THREADS=true
			SHOW_DETAIL_VIEW_THREAD_LINK=true}
		</div>
		{if $PAGING_MODEL->isNextPageExists()}
			<div class="row-fluid">
				<div class="pull-right">
					<a href="javascript:void(0)" class="moreRecentComments">{vtranslate('LBL_MORE',$MODULE_NAME)}..</a>
				</div>
			</div>
		{/if}
		{if $CREATE_PERMISSION}
			{include file='CommentForm.tpl'|@vtemplate_path
			COMMENT_FORM_BLOCK_CLASS='basicAddCommentBlock'
			COMMENT_FORM_IS_HIDDEN=true
			COMMENT_FORM_IS_NESTED=true
			COMMENT_FORM_MODE='add'
			COMMENT_FORM_TEXTAREA_CLASS='commentcontenthidden fullWidthAlways'
			COMMENT_FORM_PLACEHOLDER='LBL_ADD_YOUR_COMMENT_HERE'
			COMMENT_FORM_POST_BUTTON_CLASS='btn btn-success detailViewSaveComment'
			COMMENT_FORM_SHOW_SEND_MAIL=false
			COMMENT_FORM_SEND_MAIL_BUTTON_CLASS=''
			COMMENT_FORM_SHOW_CANCEL=true
			COMMENT_FORM_CANCEL_CLASS='cancelLink'
			COMMENT_FORM_SHOW_REASON=false
			COMMENT_FORM_STYLE=''
			COMMENT_FORM_HELPDESK_FIELDS=''
			COMMENT_FORM_TIME_NEEDED_VALUE='00:00'}
		{/if}
		{if $EDIT_PERMISSION}
			{include file='CommentForm.tpl'|@vtemplate_path
			COMMENT_FORM_BLOCK_CLASS='basicEditCommentBlock'
			COMMENT_FORM_IS_HIDDEN=true
			COMMENT_FORM_IS_NESTED=true
			COMMENT_FORM_MODE='edit'
			COMMENT_FORM_TEXTAREA_CLASS='commentcontenthidden fullWidthAlways'
			COMMENT_FORM_PLACEHOLDER=''
			COMMENT_FORM_POST_BUTTON_CLASS='btn btn-success detailViewSaveComment'
			COMMENT_FORM_SHOW_SEND_MAIL=false
			COMMENT_FORM_SEND_MAIL_BUTTON_CLASS=''
			COMMENT_FORM_SHOW_CANCEL=true
			COMMENT_FORM_CANCEL_CLASS='cancelLink'
			COMMENT_FORM_SHOW_REASON=true
			COMMENT_FORM_STYLE='min-height: 150px;'
			COMMENT_FORM_HELPDESK_FIELDS='full'
			COMMENT_FORM_TIME_NEEDED_VALUE='00:00'}
		{/if}
	</div>
{/strip}

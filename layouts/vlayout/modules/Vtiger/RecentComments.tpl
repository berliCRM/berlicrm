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
		<div class="commentsBody">
			{if !empty($COMMENTS)}
				{foreach key=index item=COMMENT from=$COMMENTS}
					{assign var=COMMENT_TYPE value=$COMMENT->getCommentType()}
					{if !isset($COMMENTS_COLORS) || empty($COMMENTS_COLORS)}
						{$COMMENTS_COLORS = ['customer' => 'red', 'outgoing' => 'green', 'internal' => 'yellow']}
					{/if}
					<div class="commentDetails" style="border: 1px solid {if isset($COMMENTS_COLORS[$COMMENT_TYPE])}{$COMMENTS_COLORS[$COMMENT_TYPE]}{/if};">
						<div class="commentDiv">
							<div class="singleComment">
								<div class="commentInfoHeader row-fluid" data-commentid="{$COMMENT->getId()}"
								     data-parentcommentid="{$COMMENT->get('parent_comments')}">
									<div class="commentTitle">
										{assign var=PARENT_COMMENT_MODEL value=$COMMENT->getParentCommentModel()}
										{assign var=CHILD_COMMENTS_MODEL value=$COMMENT->getChildComments()}
										<div class="row-fluid">
											<div class="span1">
												{assign var=IMAGE_PATH value=$COMMENT->getImagePath()}
												<img class="alignMiddle pull-left"
												     src="{if !empty($IMAGE_PATH)}{$IMAGE_PATH}{else}{vimage_path('DefaultUserIcon.png')}{/if}">
											</div>
											<div class="span11 commentorInfo">
												{assign var=COMMENTOR value=$COMMENT->getCommentedByModel()}
												<div class="inner">
													<span class="commentorName">
														<strong>{if $COMMENTOR}{$COMMENTOR->getName()}{else}{vtranslate('LBL_DELETED')}{/if}</strong>&nbsp;
															{include file='CommentMailInfo.tpl'|@vtemplate_path COMMENT=$COMMENT}
													</span>
													<span class="pull-right">
														<p class="muted"><small
																	title="{Vtiger_Util_Helper::formatDateTimeIntoDayString($COMMENT->getCommentedTime())}">{Vtiger_Util_Helper::formatDateDiffInStrings($COMMENT->getCommentedTime())}&nbsp;&nbsp;
																({Vtiger_Util_Helper::convertDateTimeIntoUsersDisplayFormat($COMMENT->getCommentedTime())})</small>
														</p>
												        {assign var=TIMENEEDED value=Vtiger_Util_Helper::convertTimeIntoUsersDisplayFormat($COMMENT->get('timeneeded'))}
														{if $TIMENEEDED neq "00:00:00" and $TIMENEEDED neq false}
															<p class="muted">
                                                                <small class="pull-right">
                                                                    {vtranslate('LBL_TIME_NEEDED', $MODULE_NAME)}:&nbsp{Vtiger_Util_Helper::convertTimeIntoUsersDisplayFormat($COMMENT->get('timeneeded'))}
                                                                </small>
                                                            </p>
														{/if}
													</span>
													<div class="clearfix"></div>
												</div>
												<div class="commentInfoContent">
													{nl2br($COMMENT->get('commentcontent'))}
												</div>
											</div>
										</div>
									</div>
								</div>
								<div class="row-fluid commentActionsContainer">
									{if $MODULE_NAME == 'HelpDesk'}
										{assign var=EXTERNAL_COMMENT value=$COMMENT->get('external')}
										{assign var=TIME_NEEDED value=$COMMENT->get('timeneeded')}
										<input type="hidden" name="external" value="{$EXTERNAL_COMMENT}">
										<input type="hidden" name="timeNeeded" value="{$TIME_NEEDED}">
									{/if}

									{assign var="REASON_TO_EDIT" value=$COMMENT->get('reasontoedit')}
									<div class="row-fluid editStatus" name="editStatus">
										<span class="span6{if empty($REASON_TO_EDIT)} hide{/if}">
											<p class="muted">
												<small>
													[ {vtranslate('LBL_EDIT_REASON',$MODULE_NAME)} ] :
													<span name="editReason"
													      class="textOverflowEllipsis">{nl2br($REASON_TO_EDIT)}</span>
												</small>
											</p>
										</span>
										{if $COMMENT->getCommentedTime() neq $COMMENT->getModifiedTime()}
											<span class="{if empty($REASON_TO_EDIT)}row-fluid{else} span6{/if}">
												<p class="muted pull-right">
													<small><em>{vtranslate('LBL_MODIFIED',$MODULE_NAME)}</em></small>&nbsp;
													<small
															title="{Vtiger_Util_Helper::formatDateTimeIntoDayString($COMMENT->getModifiedTime())}"
															class="commentModifiedTime">{Vtiger_Util_Helper::formatDateDiffInStrings($COMMENT->getModifiedTime())}&nbsp;&nbsp;
														({Vtiger_Util_Helper::convertDateTimeIntoUsersDisplayFormat($COMMENT->getModifiedTime())})</small>
												</p>
											</span>
										{/if}
									</div>
									<div class="row-fluid">
										<div class="pull-right commentActions">
											<span>
												{if $CREATE_PERMISSION}
													<a class="cursorPointer replyComment feedback">
														<i class="icon-share-alt"></i>{vtranslate('LBL_REPLY',$MODULE_NAME)}
													</a>
												{/if}
												{if $CURRENTUSER->getId() eq $COMMENT->get('userid') && $EDIT_PERMISSION}
													{if $CREATE_PERMISSION}&nbsp;<span>|</span>&nbsp;{/if}
													<a class="cursorPointer editComment feedback">
														{vtranslate('LBL_EDIT',$MODULE_NAME)}
													</a>
												{/if}
											</span>
											<span>
												{if $PARENT_COMMENT_MODEL neq false or $CHILD_COMMENTS_MODEL neq null}
													{if $CREATE_PERMISSION || $EDIT_PERMISSION}&nbsp;<span>|</span>&nbsp;{/if}
													<a href="javascript:void(0);"
													   class="cursorPointer detailViewThread">{vtranslate('LBL_VIEW_THREAD',$MODULE_NAME)}</a>
												{/if}
											</span>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				{/foreach}
			{else}
				{include file="NoComments.tpl"|@vtemplate_path}
			{/if}
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
			COMMENT_FORM_HELPDESK_FIELDS='external_time'
			COMMENT_FORM_TIME_NEEDED_VALUE='00:00'}
		{/if}
	</div>
{/strip}

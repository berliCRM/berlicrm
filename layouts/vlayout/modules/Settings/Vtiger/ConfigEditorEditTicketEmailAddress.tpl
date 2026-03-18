{* ----------------------------- *
 * Ticket Mail Sender Config Edit Block
 * ----------------------------- *}

{strip}
{assign var=WIDTHTYPE value=$CURRENT_USER_MODEL->get('rowheight')}
{assign var=FIELD_DATA value=$TICKETMAIL_DATA}

<div class="container-fluid" id="ConfigTicketMailSenderEdit"> 
	<div class="contents">

		<form id="ConfigTicketMailSenderForm"
			  class="form-horizontal"
			  method="POST"
			  action="index.php">

			{* Hidden routing fields *}
			<input type="hidden" name="module" value="{$QUALIFIED_MODULE}" />
			<input type="hidden" name="action" value="SaveConfigTicketMail" />
			<input type="hidden" name="parent" value="Settings" />

			<div class="widget_header row-fluid">
				<div class="span8">
					<h3>{vtranslate('LBL_CONFIGTICKETMAIL_EDITOR', $QUALIFIED_MODULE)}</h3>
				</div>
				<div class="span4 btn-toolbar">
					<div class="pull-right">
						<button class="btn btn-success" type="submit" title="{vtranslate('LBL_SAVE', $QUALIFIED_MODULE)}">
							<strong>{vtranslate('LBL_SAVE', $QUALIFIED_MODULE)}</strong>
						</button>

						<a class="btn"
						   href="{$MODEL->getDetailViewUrl()}"
						   title="{vtranslate('LBL_CANCEL', $QUALIFIED_MODULE)}">
							{vtranslate('LBL_CANCEL', $QUALIFIED_MODULE)}
						</a>
					</div>
				</div>
			</div>

			<div class="col-md-10">
				{vtranslate('LBL_HEADLINE_CONFIGTICKETMAIL_EDITOR', $QUALIFIED_MODULE)}
			</div>

			<hr>

			<table class="table table-bordered table-condensed themeTableColor">
				<thead>
					<tr class="blockHeader">
						<th colspan="2" class="{$WIDTHTYPE}">
							<span class="alignMiddle">{vtranslate('LBL_CONFIGTICKETMAIL_EDITOR', $QUALIFIED_MODULE)}</span>
						</th>
					</tr>
				</thead>
				<tbody>

					{* enabled checkbox (optional) *}
					<tr>
						<td width="30%" class="{$WIDTHTYPE}">
							<label class="muted pull-right marginRight10px">
								{vtranslate('LBL_CONFIGTICKETMAIL_FIELD0_EDITOR', $QUALIFIED_MODULE)}
							</label>
						</td>
						<td style="border-left:none;" class="row-fluid {$WIDTHTYPE}">
							<input type="hidden" name="enabled" value="0" />
							<input type="checkbox" name="enabled" value="1"
							{if !empty($FIELD_DATA.enabled)}checked="checked"{/if} />
						</td>
					</tr>

					{* Sender name (text) *}
					<tr>
						<td width="30%" class="{$WIDTHTYPE}">
							<label class="muted pull-right marginRight10px">
								{vtranslate('LBL_CONFIGTICKETMAIL_FIELD1_EDITOR', $QUALIFIED_MODULE)}
							</label>
						</td>
						<td style="border-left:none;" class="row-fluid {$WIDTHTYPE}">
							<input type="text"
								   name="sender_name"
								   class="input-xxlarge"
								   value="{$FIELD_DATA.sender_name|escape:'html'}" />
						</td>
					</tr>

					{* Sender email (email) *}
					<tr>
						<td width="30%" class="{$WIDTHTYPE}">
							<label class="muted pull-right marginRight10px">
								{vtranslate('LBL_CONFIGTICKETMAIL_FIELD2_EDITOR', $QUALIFIED_MODULE)}
							</label>
						</td>
						<td style="border-left:none;" class="row-fluid {$WIDTHTYPE}">
							<input type="email"
								   name="sender_email"
								   class="input-xxlarge"
								   value="{$FIELD_DATA.sender_email|escape:'html'}" />
						</td>
					</tr>
<!-- 
					{* Reply-To name (text) *}
					<tr>
						<td width="30%" class=" $WIDTHTYPE}">
							<label class="muted pull-right marginRight10px">
								 vtranslate('LBL_CONFIGTICKETMAIL_FIELD3_EDITOR', $QUALIFIED_MODULE)}
							</label>
						</td>
						<td style="border-left:none;" class="row-fluid   $WIDTHTYPE}">
							<input type="text"
								   name="reply_to_name"
								   class="input-xxlarge"
								   value=" $FIELD_DATA.reply_to_name|escape:'html'}" />
						</td>
					</tr>

					{* Reply-To email (email) *}
					<tr>
						<td width="30%" class=" $WIDTHTYPE}">
							<label class="muted pull-right marginRight10px">
								 vtranslate('LBL_CONFIGTICKETMAIL_FIELD4_EDITOR', $QUALIFIED_MODULE)}
							</label>
						</td>
						<td style="border-left:none;" class="row-fluid  $WIDTHTYPE}">
							<input type="email"
								   name="reply_to_email"
								   class="input-xxlarge"
								   value=" $FIELD_DATA.reply_to_email|escape:'html'}" />
						</td>
					</tr>
 -->
				</tbody>
			</table>
		</form>
	</div>
</div>
{/strip}
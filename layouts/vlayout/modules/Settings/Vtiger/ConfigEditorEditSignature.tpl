{* ----------------------------- *
 * Signature Config Edit Block
 * ----------------------------- *}
<script type="text/javascript">
	// MUST be defined BEFORE ckeditor.js is loaded
	window.CKEDITOR_BASEPATH = 'libraries/jquery/ckeditor/';
</script>

<script type="text/javascript" src="libraries/jquery/ckeditor/ckeditor.js"></script> 

<script type="text/javascript">
jQuery(function () {
  if (!window.CKEDITOR) return;

  if (CKEDITOR.instances.description) {
    CKEDITOR.instances.description.destroy(true);
  }

  CKEDITOR.replace('description', {
    height: 650,
    fullPage: false
  });

  jQuery('#ConfigSignatureEditorForm').on('submit', function () {
    if (CKEDITOR.instances.description) {
      CKEDITOR.instances.description.updateElement();
    }
  });
});
</script>

{assign var=WIDTHTYPE value=$CURRENT_USER_MODEL->get('rowheight')}
{assign var=FIELD_DATA value=$SIGNATURE_DATA}

<div class="container-fluid" id="ConfigSignatureEditorEdit">
	<div class="contents">

		<form id="ConfigSignatureEditorForm"
			  class="form-horizontal"
			  method="POST"
			  action="index.php">
			{* Hidden routing fields *}
			<input type="hidden" name="module" value="{$QUALIFIED_MODULE}" />
			<input type="hidden" name="action" value="SaveConfigSignature" />
			<input type="hidden" name="parent" value="Settings" />

			<div class="widget_header row-fluid">
				<div class="span8">
					<h3>{vtranslate('LBL_CONFIGSIGNATURE_EDITOR', $QUALIFIED_MODULE)}</h3>
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
				{vtranslate('LBL_HEADLINE_CONF_SIG', $QUALIFIED_MODULE)}
			</div>

			<hr>

			<table class="table table-bordered table-condensed themeTableColor">
				<thead>
					<tr class="blockHeader">
						<th colspan="2" class="{$WIDTHTYPE}">
							<span class="alignMiddle">{vtranslate('LBL_CONFIGSIGNATURE_EDITOR', $QUALIFIED_MODULE)}</span>
						</th>
					</tr>
				</thead>
				<tbody>
					{* enabled checkbox *}
					<tr>
						<td width="30%" class="{$WIDTHTYPE}">
							<label class="muted pull-right marginRight10px">
								{vtranslate('LBL_FIELD1_CONF_SIG', $QUALIFIED_MODULE)}
							</label>
						</td>
						<td style="border-left:none;" class="row-fluid {$WIDTHTYPE}">
							<input type="hidden" name="enabled" value="0" />
							<input type="checkbox" name="enabled" value="1"
							{if !empty($FIELD_DATA.enabled)}checked="checked"{/if} />
						</td>
					</tr>

					{* CKeditor: must be name="description" and id="description" *}
					<tr>
						<td width="30%" class="{$WIDTHTYPE}">
							<label class="muted pull-right marginRight10px">{vtranslate('LBL_FIELD2_CONF_SIG', $QUALIFIED_MODULE)}</label>
						</td>
						<td style="border-left:none;" class="row-fluid {$WIDTHTYPE}">
							<textarea name="description" id="description" rows="18" class="input-xxlarge">
								{$FIELD_DATA.signature_html}
							</textarea>
						</td>
					</tr>
				</tbody>
			</table>
		</form>
	</div>
</div>
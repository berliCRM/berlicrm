<link rel="stylesheet" href="libraries/jquery/chosen/chosen.css" media="screen">
<script type="text/javascript" src="libraries/jquery/chosen/chosen.jquery.min.js"></script>
<style>
div.content {
    position:absolute;
}

.chosen-container .chosen-drop {
  border-bottom: 0;
  border-top: 1px solid #aaa;
  top: auto;
  bottom: 40px;
}
.chosen-container.chosen-with-drop .chosen-single {
  border-top-left-radius: 0px;
  border-top-right-radius: 0px;
  border-bottom-left-radius: 5px;
  border-bottom-right-radius: 5px;
  background-image: none;
}
.chosen-container.chosen-with-drop .chosen-drop {
  border-bottom-left-radius: 0px;
  border-bottom-right-radius: 0px;
  border-top-left-radius: 5px;
  border-top-right-radius: 5px;
  box-shadow: none;
  margin-bottom: -16px;
}

</style>	

<div id="transferPopupContainer" class="modelContainer" style='min-width:450px'>
	<div class="modal-header">
		<button class="close" data-dismiss="modal" title="{vtranslate('LBL_CLOSE')}">x</button>
		<h3>{vtranslate('LBL_EXPORT_OPTION', $MODULE)}</h3>
	</div>
	<div class="modal-body">
 			<table class="table table-bordered">
				<tr class="blockHeader">
					<td>
						<table width="99%">
							<tr>
								<td>
									{vtranslate('LBL_EXPORT_DESCRIPTION',$MODULE)}
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td>
						<div class="modal-body">
							{vtranslate('LBL_EXPORT_STEP1',$MODULE)}
						</div>
						<div class="modal-body">
							<select id="exportdestination" class="chosen" style="max-width:90%;" data-placeholder='{vtranslate("LBL_CHOSEN_MODUL", $MODULE)}'>
								<option value=""></option>
								{foreach key=key item=modulename from=$DESTINATION_MODULES}
									<option value="{$modulename}">{vtranslate($modulename, $modulename)}</option>
								{/foreach}
							</select>
						</div>
						<div class="modal-body">
							{vtranslate('LBL_EXPORT_STEP2',$MODULE)}
						</div>
						<div id ="transferPopupScroll" style="margin-right: 8px;">
							<div class="modal-body" id="result_Campaigns" name="result_Campaigns"  style="display:none;">
									<select id="campaignlist"  class="chosen" data-placeholder='{vtranslate("LBL_CHOSEN_ENTRY", $MODULE)}'>
										<option value=""></option>
										{foreach key=key item=data from=$CAMPAIGNLIST}
											<option value="{$key}">{$data}</option>
										{/foreach}
								</select>
							</div>
							<div class="modal-body" id="result_Mailchimp" name="result_Mailchimp"  style="display:none;">
									<select id="maichimplist"  class="chosen" data-placeholder='{vtranslate("LBL_CHOSEN_ENTRY", $MODULE)}'>
										<option value=""></option>
										{foreach key=key item=data from=$MAILCHIMPLIST}
											<option value="{$key}">{$data}</option>
										{/foreach}
									</select>
							</div>
							<div class="modal-body" id="result_berliCleverReach" name="result_berliCleverReach"  style="display:none;">
									<select id="cleverreachlist"  class="chosen" data-placeholder='{vtranslate("LBL_CHOSEN_ENTRY", $MODULE)}'>
										<option value=""></option>
										{foreach key=key item=data from=$CLEVERREACHLIST}
											<option value="{$key}">{$data}</option>
										{/foreach}
									</select>
							</div>
						</div>
					</td>
				</tr>
				<tr>
					<td class='lvtColData' colspan="3" align="center" >
						<button class="btn btn-success" id="exportbutton" type="submit" disabled data-dismiss="modal"><strong>{vtranslate('LBL_EXPORT_BUTTON', $MODULE)}</strong></button>
					</td>
				</tr>
			</table>
		</div>
        <div class="modal-footer">
            <div class=" pull-right cancelLinkContainer">
                <button class="btn btn-primary" type="reset" data-dismiss="modal"><strong>{vtranslate('LBL_CLOSE', $MODULE)}</strong></button>
            </div>
		</div>
    </div>
</div>

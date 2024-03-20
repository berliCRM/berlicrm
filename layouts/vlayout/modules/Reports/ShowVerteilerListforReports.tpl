<div id="transferPopupContainer" class="modelContainer" style='min-width:450px'>
	<div class="modal-header">
		<button class="close" data-dismiss="modal" title="{vtranslate('LBL_CLOSE')}">x</button>
		<h3>{vtranslate('LBL_VERTEILER', $MODULE)}</h3>
	</div>
	<div class="modal-body">
		{if $NUMVELIST == 0}
			{vtranslate('LBL_NO_VERTEILER_LIST',$MODULE)}
		{else}
            <div id ="transferPopupScroll" style="margin-right: 8px;">
			<table class="table table-bordered">
				<tr class="blockHeader">
					<td>
						<table width="99%">
							<tr>
								<td>
									{vtranslate('LBL_V_COMMENT1_CONTACT',$MODULE)}
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td>
						<table  width="99%" class="table table-bordered">
							<tr>
								<td class="contents">
									{if $NUMVELIST < '20'}
									<select id="verteilerlist" >
									{else}
									<select id="verteilerlist"  size="20">
									{/if}
										{foreach key=key item=data from=$VERTEILERLIST}
											<option value="{$key}">{$data}</option>
										{/foreach}
									</select>
								</td>
							</tr>
							<tr>
								<td class="small">
									&nbsp;
								</td>
							</tr>
							<tr>
								<td class='lvtColData' colspan="3" align="center">
									<button class="btn btn-success" type="submit" data-dismiss="modal" onclick="if(callVerteilerList.create('{$REPORTID}','{$MODULENAME}'));else return false;"><strong>{vtranslate('LBL_SELECT_VERTEILER_CONTACT', $MODULE)}</strong></button>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
			</div>
		{/if}
		</div>
        <div class="modal-footer">
            <div class=" pull-right cancelLinkContainer">
                <button class="btn btn-primary" type="reset" data-dismiss="modal"><strong>{vtranslate('LBL_CLOSE', $MODULE)}</strong></button>
            </div>
		</div>
    </div>
</div>

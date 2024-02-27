<div id="transferPopupContainer" class="modelContainer" style='min-width:450px'>
	<div class="modal-header">
		<button class="close" data-dismiss="modal" title="{vtranslate('LBL_CLOSE')}">x</button>
		<h3>{vtranslate('LBL_CR_LISTS', $MODULE)}</h3>
	</div>
	<div class="modal-body">
		{if $NUMMCLIST == 0}
			{vtranslate('LBL_NO_CR_LIST',$MODULE)}
		{else}
            <div id ="transferPopupScroll" style="margin-right: 8px;">
			
			<table class="table table-bordered">
				<tr class="blockHeader">
					<td>
						<table width="99%">
							<tr>
								<td>
									{if $MODULENAME eq 'Contacts'}
										{vtranslate('LBL_CR_COMENT1_CONTACT',$MODULE)}
									{else}
										{vtranslate('LBL_CR_COMENT1_LEAD',$MODULE)}
									{/if}
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
									{if $NUMMCLIST < '20'}
									<select id="cleverreachlist" >
									{else}
									<select id="cleverreachlist"  size="20">
									{/if}
										{foreach key=key item=data from=$CLEVERREACHLIST}
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
									{if $MODULENAME eq 'Contacts'}
										<button class="btn btn-success" type="submit" data-dismiss="modal" onclick="if(callCleverReachList.create('{$REPORTID}','{$MODULENAME}'));else return false;"><strong>{vtranslate('LBL_SELECT_CR_CONTACT', $MODULE)}</strong></button>
									{else}
										<button class="btn btn-success" type="submit" data-dismiss="modal" onclick="if(callCleverReachList.create('{$REPORTID}','{$MODULENAME}'));else return false;"><strong>{vtranslate('LBL_SELECT_CR_LEAD', $MODULE)}</strong></button>
									{/if}
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

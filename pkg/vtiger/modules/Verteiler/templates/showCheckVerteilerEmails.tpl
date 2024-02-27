<body>
	{if $ALLVALID eq 0}
		<div id="transferPopupContainer" style='min-width:450px'>
			<div class="modal-header">
				<button class="close" data-dismiss="modal" title="{vtranslate('LBL_CANCEL', $MODULE)}">x</button>
				<h3>{vtranslate('LBL_CHECK_E-MAIL_HEADER', $MODULE)}</h3>
			</div>

			<div class="modal-body">
 				<table class="table table-bordered">
					<tbody>
						<tr class="blockHeader">
							<td>
								<table width="99%">
									<tbody>
										<tr>
											<td>
												<div class="info">{vtranslate('LBL_CHECK_E-MAIL_TRUE', $MODULE)}</div>
            	                			</td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		
			<div class="modal-footer">
				<button class="btn btn-primary" type="reset" data-dismiss="modal">
					<strong>{vtranslate('LBL_CHECK_E-MAIL_CLOSE', $MODULE)}</strong>
				</button>
			</div>
		</div>

	{else}
		<div id="transferPopupContainer" style='min-width:450px'>
			<div class="modal-header">
				<button class="close" data-dismiss="modal" title="{vtranslate('LBL_CANCEL', $MODULE)}">x</button>
				<h3>{vtranslate('LBL_CHECK_E-MAIL_HEADER', $MODULE)}</h3>
			</div>

			<div class="modal-body">
 				<table class="table table-bordered">
					<tbody>
						<tr class="blockHeader">
							<td>
								<table width="99%">
									<tbody>
										<tr>
											<td>
												<div class="info">{vtranslate('LBL_CHECK_E-MAIL_DESCRIPTION', $MODULE)}</div>
            	                			</td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
						<tr>
							<td>
								<div class="modal-body" style ="overflow-y: auto; max-height: 200px;">
									{vtranslate('LBL_CHECK_E-MAIL_FALSE', $MODULE)}<br><br>
									<div class="counter" style= "display: none;">{count($FALSEEMAILS)}</div>
	                    				{foreach key=contactId item=falseemailaddress from=$FALSEEMAILS}
										<div class="text">
											{$falseemailaddress}
											<a href ="index.php?module=Contacts&view=Detail&record={$contactId}" target="_blank"><i title="{vtranslate('LBL_CHECK_E-MAIL_ALL-DETAIL', $MODULE)}" class="icon-th-list alignMiddle" style="float: right;"></i> </a><br> 
										</div>
										{/foreach}
									</div>
								</div>	
							</td>	
						</tr>
					</tbody>
				</table>
			</div>
		
			<div class="modal-footer">
				<button class="btn btn-primary" type="reset" data-dismiss="modal">
					<strong>{vtranslate('LBL_CHECK_E-MAIL_CLOSE', $MODULE)}</strong>
				</button>
			</div>
		</div>
	{/if}
</body>
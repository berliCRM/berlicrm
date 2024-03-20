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
<script language="JavaScript" type="text/javascript" src="modules/Pdfsettings/languages/{$CURRENT_USER_MODEL->get('language')}/{$CURRENT_USER_MODEL->get('language')}.lang.js"></script>
<script language="JavaScript" type="text/javascript" src="modules/Pdfsettings/third-party/js/tab-view.js"></script>
<link rel="stylesheet" href="modules/Pdfsettings/third-party/js/tab-view.css" type="text/css">
{strip}
<input type="hidden" id="sourceModule" value="{$MODULE}" />
<div class="listViewEntriesDiv">
<div class="PdfsettingsContainer">
<form enctype="multipart/form-data" action="index.php?" id="pdfsettings" name="pdfsettings" method="post" >
	<input type="hidden" name="module" value="Pdfsettings">
	<input type="hidden" name="parenttab" value="Tools">
	<input type="hidden" name="fld_module" id="fld_module" value="{$MODULEVIEW}">
	<input type="hidden" name="action" id="action" value="UpdatePDFSettings">
	<table align="center" border="0" cellpadding="0" cellspacing="0" width="98%">
		<tr>
	        <td class="showPanelBg" style="padding: 10px;" valign="top" width="100%"><span  name="progressIndicator" style="height:30px;">&nbsp;</span>
				<br>
				<div align='left'>
					<table border=0 cellspacing=0 cellpadding=5 width="100%" class="settingsSelUITopLine">
							<tr>
								<td colspan=2 class="blockHeader" valign=bottom><b>{vtranslate('LBL_PDF_CONFIGURATOR',$MODULE)}</b></td>
								<td rowspan=2 class="title_label" align=right>&nbsp;</td>
							</tr>
							<tr>
								<td valign="top" class="title_label">{vtranslate('LBL_PDFCONFIGURATOR_DESCRIPTION',$MODULE)}</td>
								<td rowspan=2 class="title_label" align=right>&nbsp;</td>
							</tr>
					</table>
					{if $MODULEVIEW==1} 
					<table border=0 cellspacing=0 cellpadding=5 width=100%>
						<tr>
							<td class="small" align="left">
								<input id="edit" name="edit" class="btn" title="{vtranslate('LBL_EDIT')}"  onclick="enableFields(pdfsettings);" type="button" style="visibility:hidden" value="{vtranslate('LBL_EDIT')}">
							</td>
						</tr>
					</table>
					{/if} 
					<table width="100%" border="0" cellpadding="5" cellspacing="0" class="listTableTopButtons">
			                <tr>
				        		<td  style="padding-left:5px;" class="big">{vtranslate('LBL_SELECT_MODULE',$MODULE)}&nbsp;&nbsp;  
									<select name="displaymodul" id="displaymodul" class="detailedViewTextBox" style="width:30%;"  onchange="tableswitch(this.value);">
										{foreach item=module from=$FIELD_INFO}
											{if $module == $DEF_MODULE}
												<option selected value='{$module}'>{vtranslate($module,$MODULE)}</option>
											{else}		
												<option value='{$module}' >{vtranslate($module,$MODULE)}</option>
											{/if} 
										{/foreach}
									</select>
						    	</td>
			                </tr>
					</table>
					<!-- Quotes start here -->
					<div id="Quotes" style="display:box" class="box">
						<table align="center" border="0" cellpadding="0" cellspacing="0" width="98%">
							<tr>
								<td class="showPanelBg" style="padding: 10px;" valign="top" width="100%">
									<br>
									<div align=center>
										<!-- DISPLAY -->
										<table border=0 cellspacing=0 cellpadding=5 width="100%" >
											<tr>
												<td>
													<div id="configurationtabs">
														<div class="dhtmlgoodies_aTab"> 
															<table border=0 cellspacing=0 cellpadding=10 width="100%" >
																<tr>
																	<td valign="top" align="left" class="bigtxt">{vtranslate('LBL_PDFCONFIGURATOR_QUOTES',$MODULE)}</td>
																</tr>
																<tr>
																	<td align="left">
																		<table border=0 cellspacing=0 cellpadding=0 width="100%" >
																			<tr>
																				<td valign="top"  class="smalltxt">{$PDFMODULLANGUAGEQUOTES.LBL_LANGUAGES}</td>
																			</tr>
																			<tr>
																				<td class="small" valign="top" >
																					<table width="100%"  border="0" cellspacing="0" cellpadding="5">
																						<tr valign="top">
																							<td class="smalltxt" width="50%">
																								<select class="detailedViewTextBox" style="width:30%;" id="Quotes_pdflang_qv" name="Quotes_pdflang_qv" {$CHANGEPERMISSION.Quotes.pdflang}>
																									{html_options values=$LANGUAGEKEYS.Quotes output=$LANGUAGES.Quotes selected=$LANGSELECTED.Quotes}
																								</select>
																							</td>
																							{if $MODULEVIEW == 1} 
																							<td class="smalltxt" align="right" >
																								<input type="checkbox" id="Quotes_pdflang_perm"  name="Quotes_pdflang_perm" {$EDITPERMISSION.Quotes.pdflang} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																							</td>
																							{/if}
																						</tr>
																					</table>
																				</td>
																			</tr>
																		</table>
																		<table border=0 cellspacing=0 cellpadding=0 width="100%" >
																			<tr>
																				<td valign="top"  class="smalltxt">{$PDFMODULLANGUAGEQUOTES.LBL_PAPERFORMAT}</td>
																			</tr>
																			<tr>
																				<td class="small" valign="top" >
																					<table width="100%"  border="0" cellspacing="0" cellpadding="5">
																						<tr valign="top">
																							<td class="smalltxt" width="50%">
																								<select class="detailedViewTextBox" style="width:30%;" id="Quotes_paperf_qv" name="Quotes_paperf_qv" {$CHANGEPERMISSION.Quotes.paperf}>
																									{html_options values=$PAPERFORMAT.Quotes output=$PAPERFORMAT.Quotes selected=$PAPERSELECTED.Quotes}
																								</select>
																							</td>
																							{if $MODULEVIEW== 1} 
																							<td class="smalltxt" align="right" >
																								<input type="checkbox" id="Quotes_paperf_perm"  name="Quotes_paperf_perm" {$EDITPERMISSION.Quotes.paperf} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																							</td>
																							{/if}
																						</tr>
																					</table>
																				</td>
																			</tr>
																		</table>
																		<table border=0 cellspacing=0 cellpadding=0 width="100%" >
																			<tr>
																				<td valign="top" class="smalltxt">{$PDFMODULLANGUAGEQUOTES.LBL_PDF_CONFIGURATOR_FONTS}</td>
																			</tr>
																			<tr>
																				<td class="small" valign="top" width="100%">
																					<table width="100%"  border="0" cellspacing="0" cellpadding="5">
																						<tr valign="top">
																							<td  class="smalltxt" width="80%">
																								<select id="Quotes_fontid_qv" name="Quotes_fontid_qv" class="detailedViewTextBox"  style="width:40%;" {$CHANGEPERMISSION.Quotes.fontid}>
																									{html_options selected=$SELECTEDFONTID.Quotes size=1 values=$FONTIDS.Quotes output=$FONTLIST.Quotes }
																								</select>
																							</td>
																							{if $MODULEVIEW==1} 
																							<td class="smalltxt" align="right" width="20%">
																								<input type="checkbox" name="Quotes_fontid_perm" id="Quotes_fontid_perm" {$EDITPERMISSION.Quotes.fontid} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																							</td>
																							{/if}
																						</tr>
																					</table>
																				</td>
																			</tr>
																		</table>
																		<table border=0 cellspacing=0 cellpadding=0 width="100%" class="listRow">
																			<tr>
																				<td align="left" valign="top" class="smalltxt">{$PDFMODULLANGUAGEQUOTES.LBL_PDF_CONFIGURATOR_FONTSSIZE}</td>
																			</tr>
																			<tr>
																				<td align='left'>
																					<table width="100%"  border="0" cellspacing="0" cellpadding="5">
																						<tr>
																							<td valign="top" class="smalltxt"><b>{$PDFMODULLANGUAGEQUOTES.LBL_PDF_CONFIGURATOR_FONTSSIZE_HEADER}</b>&nbsp;&nbsp;
																							{if $MODULEVIEW==1} 
																								<input type="checkbox" id="Quotes_fontsizeheader_perm" name="Quotes_fontsizeheader_perm" {$EDITPERMISSION.Quotes.fontsizeheader} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																							{/if}
																							</td>
																							<td valign="top" class="smalltxt"><b>{$PDFMODULLANGUAGEQUOTES.LBL_PDF_CONFIGURATOR_FONTSSIZE_ADDRESS}</b>&nbsp;&nbsp;
																							{if $MODULEVIEW==1} 
																								<input type="checkbox" id="Quotes_fontsizeaddress_perm" name="Quotes_fontsizeaddress_perm" {$EDITPERMISSION.Quotes.fontsizeaddress} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																							{/if}
																							</td>
																							<td valign="top" class="smalltxt"><b>{$PDFMODULLANGUAGEQUOTES.LBL_PDF_CONFIGURATOR_FONTSSIZE_BODY}</b>&nbsp;&nbsp;
																							{if $MODULEVIEW==1} 
																								<input type="checkbox" id="Quotes_fontsizebody_perm" name="Quotes_fontsizebody_perm"{$EDITPERMISSION.Quotes.fontsizebody} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																							{/if}
																							</td>
																							<td valign="top" class="smalltxt"><b>{$PDFMODULLANGUAGEQUOTES.LBL_PDF_CONFIGURATOR_FONTSSIZE_FOOTER}</b>&nbsp;&nbsp;
																							{if $MODULEVIEW==1} 
																								<input type="checkbox" id="Quotes_fontsizefooter_perm" name="Quotes_fontsizefooter_perm" {$EDITPERMISSION.Quotes.fontsizefooter} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																							{/if}
																							</td>
																						</tr>
																						<tr valign="top">
																								<td class="smalltxt">
																									<select name="Quotes_fontsizeheader_qv" class="detailedViewTextBox"  style="width:25%;" id="Quotes_fontsizeheader_qv" {$CHANGEPERMISSION.Quotes.fontsizeheader}>
																										{html_options values=$FONTSIZEAVAILABLE output=$FONTSIZEAVAILABLE selected=$FONTSIZEHEADER.Quotes}
																									</select>
																								</td>
																								<td  class="smalltxt">
																									<select name="Quotes_fontsizeaddress_qv" class="detailedViewTextBox"  style="width:25%;" id="Quotes_fontsizeaddress_qv" {$CHANGEPERMISSION.Quotes.fontsizeaddress}>
																										{html_options values=$FONTSIZEAVAILABLE output=$FONTSIZEAVAILABLE selected=$FONTSIZEADDRESS.Quotes}
																									</select>
																								</td>
																								<td  class="smalltxt">
																									<select name="Quotes_fontsizebody_qv" class="detailedViewTextBox"  style="width:25%;" id="Quotes_fontsizebody_qv" {$CHANGEPERMISSION.Quotes.fontsizebody}>
																										{html_options values=$FONTSIZEAVAILABLE output=$FONTSIZEAVAILABLE selected=$FONTSIZEBODY.Quotes}
																									</select>
																								</td>
																								<td  class="smalltxt">
																									<select name="Quotes_fontsizefooter_qv" class="detailedViewTextBox" style="width:25%;" id="Quotes_fontsizefooter_qv"{$CHANGEPERMISSION.Quotes.fontsizefooter}>
																										{html_options values=$FONTSIZEAVAILABLE output=$FONTSIZEAVAILABLE selected=$FONTSIZEFOOTER.Quotes}
																									</select>
																								</td>
																						</tr>
																					</table>
																				</td>
																			</tr>
																		</table>
																	</td>
													            </tr>
													        </table>
															<table border=0 cellspacing=0 cellpadding=10 width="100%" class="listRow">
																<tr>
																	<td align="left">
																		<table align="" border=0 cellspacing=0 cellpadding=0 width="100%" >
																			<tr>
																				<td valign="top" width="80%" class="smalltxt">{$PDFMODULLANGUAGEQUOTES.LBL_PRINT_LOGO}&nbsp;&nbsp;
																					<input type="checkbox" id="Quotes_logoradio_qc" name="Quotes_logoradio_qc" {$LOGORADIO.Quotes} {$CHANGEPERMISSION.Quotes.logoradio}> 
																				</td>
																				{if $MODULEVIEW==1} 
																				<td class="smalltxt" width="20%" align="right">
																					<input type="checkbox" id="Quotes_logoradio_perm" name="Quotes_logoradio_perm" {$EDITPERMISSION.Quotes.logoradio} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top"  width="80%" class="smalltxt">{$PDFMODULLANGUAGEQUOTES.LBL_PDF_DATE}&nbsp;&nbsp;
																					<select name="Quotes_dateused_qv" class="detailedViewTextBox"  style="width:20%;" id="Quotes_dateused_qv" {$CHANGEPERMISSION.Quotes.dateused}>
																						{html_options values=$DATEUSED.Quotes output= $DATEUSEDNAME selected=$DATEUSEDSELECTED.Quotes}
																					</select>
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt"  width="20%" align="right">
																					<input type="checkbox" name="Quotes_dateused_perm" id="Quotes_dateused_perm" {$EDITPERMISSION.Quotes.dateused} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top" width="80%" class="smalltxt">{$PDFMODULLANGUAGEQUOTES.LBL_OWNER}&nbsp;&nbsp;
																					<input type="checkbox" id="Quotes_owner_qc" name="Quotes_owner_qc" {$OWNER.Quotes} {$CHANGEPERMISSION.Quotes.owner}> 
																				</td>
																				{if $MODULEVIEW==1} 
																				<td class="smalltxt" width="20%" align="right">
																					<input type="checkbox" id="Quotes_owner_perm" name="Quotes_owner_perm" {$EDITPERMISSION.Quotes.owner} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top" width="80%" class="smalltxt">{$PDFMODULLANGUAGEQUOTES.LBL_OWNER_PH}&nbsp;&nbsp;
																					<input type="checkbox" id="Quotes_ownerphone_qc" name="Quotes_ownerphone_qc" {$OWNERPHONE.Quotes} {$CHANGEPERMISSION.Quotes.ownerphone}> 
																				</td>
																				{if $MODULEVIEW==1} 
																				<td class="smalltxt" width="20%" align="right">
																					<input type="checkbox" id="Quotes_ownerphone_perm" name="Quotes_ownerphone_perm" {$EDITPERMISSION.Quotes.ownerphone} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top"  width="80%" class="smalltxt">{$PDFMODULLANGUAGEQUOTES.LBL_PDF_CONFIGURATOR_SPACE_HEADER}&nbsp;&nbsp;
																					<select name="Quotes_spaceheadline_qv" class="detailedViewTextBox"   style="width:7%;" id="Quotes_spaceheadline_qv" {$CHANGEPERMISSION.Quotes.spaceheadline}>
																						{html_options values=$HEADERSPACE output=$HEADERSPACE selected=$HEADERSPACESELECTED}
																					</select>
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt"  width="20%" align="right">
																					<input type="checkbox" name="Quotes_spaceheadline_perm" id="Quotes_spaceheadline_perm"  {$EDITPERMISSION.Quotes.spaceheadline} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top"  width="80%" class="smalltxt">{$PDFMODULLANGUAGEQUOTES.LBL_PRINT_FOOTER}&nbsp;&nbsp;
																					<input type="checkbox" name="Quotes_footerradio_qc" id="Quotes_footerradio_qc" {$FOOTERRADIO.Quotes} {$CHANGEPERMISSION.Quotes.footerradio}> 
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt"  width="20%" align="right">
																					<input type="checkbox" name="Quotes_footerradio_perm" id="Quotes_footerradio_perm"  {$EDITPERMISSION.Quotes.footerradio} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top"  width="80%" class="smalltxt">{$PDFMODULLANGUAGEQUOTES.LBL_PRINT_FOOTERPAGE}&nbsp;&nbsp;
																					<input type="checkbox" name="Quotes_pageradio_qc" id="Quotes_pageradio_qc" {$FOOTERPAGERADIO.Quotes} {$CHANGEPERMISSION.Quotes.pageradio}> 
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt" align="right">
																					<input type="checkbox" name="Quotes_pageradio_perm" id="Quotes_pageradio_perm"  {$EDITPERMISSION.Quotes.pageradio} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																		</table>
																	</td>
													            </tr>
													        </table>
															<table border=0 cellspacing=0 cellpadding=10 width="100%" class="listRow">
																<tr>
																	<td align="left">
																		<table align="" border=0 cellspacing=0 cellpadding=0 width="100%" >
																			<tr>
																				<td valign="top" class="smalltxt">{$PDFMODULLANGUAGEQUOTES.LBL_PDF_CONFIGURATOR_SUMMARY}&nbsp;&nbsp;
																					<input type="checkbox" name="Quotes_summaryradio_qc" id="Quotes_summaryradio_qc" {$SUMMARYRADIO.Quotes} {$CHANGEPERMISSION.Quotes.summaryradio}> 
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt" align="right">
																					<input type="checkbox" name="Quotes_summaryradio_perm" id="Quotes_summaryradio_perm"  {$EDITPERMISSION.Quotes.summaryradio} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																		</table>
																	</td>
													            </tr>
													        </table>
														</div>
														<div class="dhtmlgoodies_aTab"> 
															<table border=0 cellspacing=0 cellpadding=10 width="100%" class="listRow">
																<tr>
																	<td align="left">
																		<table border=0 cellspacing=0 cellpadding=0 width="100%">
																			<tr>
																				<td valign="top" class="smalltxt">{$PDFMODULLANGUAGEQUOTES.LBL_PDF_CONFIGURATOR_ROWS}</td>
																			</tr>
																		</table><br>
																		<table border=0 cellspacing=0 cellpadding=0 width="100%" >
																			<tr valign="top">
																				<td  valign="top" class="smalltxt">{$PDFLANGUAGEARRAYQUOTES.TAX_GROUP}</td>
																			</tr>
																			<tr valign="top">
																				<td width="90%">
																					<table border=0 cellspacing=0 cellpadding=3 frame="below" width="100%" class="table thread th">
																						<tr >
																							{foreach key =checkboxtype item =grouptax from=$COLUMNCONFIGURATIONGROUP.Quotes} 
																								<td  class="bigtxt" valign="top" id="Quotes.{$grouptax.taxtype}.{$checkboxtype}">
																									{if ($grouptax.selected == 'checked="checked"' and $grouptax.enabled == '1')}
																										{$PDFLANGUAGEARRAYQUOTES.$checkboxtype}
																									{/if}
																								</td>
																								<td>&nbsp;</td>
																							{/foreach}
																							{if $MODULEVIEW==1}
																							<td  class="smalltxt" align="right">
																								<input type="checkbox" name="Quotes_gcolumns_perm" id="Quotes_gcolumns_perm"  {$EDITPERMISSION.Quotes.gcolumns} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																							</td>
																							{/if}
																						</tr>
																					</table>
																				</td>
																				<td id="qcheckboxes_group"  valign="top" class="smalltxt" width="20%">
																					{foreach key =checkboxtype item =grouptax from=$COLUMNCONFIGURATIONGROUP.Quotes}
																						{if $grouptax.enabled == '1'}
																							<input type="checkbox" name="Quotes.{$grouptax.taxtype}.{$checkboxtype}" id="{$checkboxtype}" {$grouptax.active} {$grouptax.selected} {$CHANGEPERMISSION.Quotes.gcolumns} onclick="preview(this,'Quotes.{$grouptax.taxtype}.{$checkboxtype}','{$PDFLANGUAGEARRAYQUOTES.$checkboxtype}');">&nbsp;{$PDFLANGUAGEARRAYQUOTES.$checkboxtype}<br>
																						{/if}
																					{/foreach}
																				</td>
																			</tr>
																		</table>
																	</td>
																</tr>
															</table>
															<table border=0 cellspacing=0 cellpadding=10 width="100%" class="listRow">
																<tr>
																	<td align="left">
																		<table align="" border=0 cellspacing=0 cellpadding=0 width="100%" >
																			<tr>
																				<td valign="top" class="smalltxt">{$PDFMODULLANGUAGEQUOTES.LBL_PDF_DESCRIPTION_CONTENT}
																				</td>
																			</tr>
																			<tr>
																				<td valign="top" class="smalltxt">
																					{$PDFMODULLANGUAGEQUOTES.LBL_PDF_DESCRIPTION_CONTENT_NAME}&nbsp;&nbsp; 
																					<input type="checkbox" name="Quotes_gprodname_qc"  id="Quotes_gprodname_qc"  {$GPRODDETAILS.Quotes.0} {$CHANGEPERMISSION.Quotes.gprodname}>
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt" align="right">
																					<input type="checkbox" name="Quotes_gprodname_perm" id="Quotes_gprodname_perm"  {$EDITPERMISSION.Quotes.gprodname} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top" class="smalltxt">
																					{$PDFMODULLANGUAGEQUOTES.LBL_PDF_DESCRIPTION_CONTENT_DESCRIPTION}&nbsp;&nbsp;
																					<input type="checkbox" name="Quotes_gproddes_qc"  id="Quotes_gproddes_qc"  {$GPRODDETAILS.Quotes.1} {$CHANGEPERMISSION.Quotes.gproddes}>
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt" align="right">
																					<input type="checkbox" name="Quotes_gproddes_perm" id="Quotes_gproddes_perm"  {$EDITPERMISSION.Quotes.gproddes} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top" class="smalltxt">
																					{$PDFMODULLANGUAGEQUOTES.LBL_PDF_DESCRIPTION_CONTENT_COMMENT}&nbsp;&nbsp;
																					<input type="checkbox" name="Quotes_gprodcom_qc"  id="Quotes_gprodcom_qc"  {$GPRODDETAILS.Quotes.2} {$CHANGEPERMISSION.Quotes.gprodcom}>
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt" align="right">
																					<input type="checkbox" name="Quotes_gprodcom_perm" id="Quotes_gprodcom_perm"  {$EDITPERMISSION.Quotes.gprodcom} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																		</table>
																	</td>
													            </tr>
													        </table>
														</div>
														<div class="dhtmlgoodies_aTab"> 
															<table border=0 cellspacing=0 cellpadding=10 width="100%" class="listRow">
																<tr>
																	<td align="left">
																		<table border=0 cellspacing=0 cellpadding=0 width="100%" >
																			<tr>
																				<td valign="top" class="smalltxt">{$PDFMODULLANGUAGEQUOTES.LBL_PDF_CONFIGURATOR_ROWS}</td>
																			</tr>
																		</table><br>
																		<table border=0 cellspacing=0 cellpadding=0 width="100%" >
																			<tr valign="top">
																				<td valign="top" class="smalltxt">{$PDFLANGUAGEARRAYQUOTES.TAX_INDIVIDUAL}</td>
																			</tr>
																			<tr valign="top">
																				<td width="90%">
																					<table border=0 cellspacing=0 cellpadding=3 frame="below" width="100%" class="table thread th">
																						<tr >
																							{foreach key =checkboxtypei item =indivitax from=$COLUMNCONFIGURATIONINDIVIDUAL.Quotes}
																								<td  class="bigtxt" valign="top" id="Quotes.{$indivitax.taxtype}.{$checkboxtypei}">
																									{if ($indivitax.selected == 'checked="checked"' and $indivitax.enabled == '1')}
																										{$PDFLANGUAGEARRAYQUOTES.$checkboxtypei}
																									{/if}
																								</td>
																								<td>&nbsp;</td>
																							{/foreach}
																							{if $MODULEVIEW==1}
																							<td class="smalltxt" align="right">
																								<input type="checkbox" name="Quotes_icolumns_perm" id="Quotes_icolumns_perm"  {$EDITPERMISSION.Quotes.icolumns} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																							</td>
																							{/if}
																						</tr>
																					</table>
																				</td>
																				<td id="Quotes_checkboxes_individual"  valign="top" class="smalltxt" width="20%">
																					{foreach key =checkboxtypei item =indivitax from=$COLUMNCONFIGURATIONINDIVIDUAL.Quotes}
																						{if $indivitax.enabled == '1'}
																							<input type="checkbox" name="Quotes.{$indivitax.taxtype}.{$checkboxtypei}" id="{$checkboxtypei}" {$indivitax.active} {$indivitax.selected} {$CHANGEPERMISSION.Quotes.icolumns} onclick="preview(this,'Quotes.{$indivitax.taxtype}.{$checkboxtypei}','{$PDFLANGUAGEARRAYQUOTES.$checkboxtypei}');">&nbsp;{$PDFLANGUAGEARRAYQUOTES.$checkboxtypei}<br>
																						{/if}
																					{/foreach}
																				</td>
																			</tr>
																		</table>
																	</td>
																</tr>
															</table>
															<table border=0 cellspacing=0 cellpadding=10 width="100%" class="listRow">
																<tr>
																	<td align="left">
																		<table align="" border=0 cellspacing=0 cellpadding=0 width="100%" >
																			<tr>
																				<td valign="top" class="smalltxt">{$PDFMODULLANGUAGEQUOTES.LBL_PDF_DESCRIPTION_CONTENT}
																				</td>
																			</tr>
																			<tr>
																				<td valign="top" class="smalltxt">
																					{$PDFMODULLANGUAGEQUOTES.LBL_PDF_DESCRIPTION_CONTENT_NAME}&nbsp;&nbsp;
																					<input type="checkbox" name="Quotes_iprodname_qc"  id="Quotes_iprodname_qc"  {$IPRODDETAILS.Quotes.0} {$CHANGEPERMISSION.Quotes.iprodname}>
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt" align="right">
																					<input type="checkbox" name="Quotes_iprodname_perm" id="Quotes_iprodname_perm"  {$EDITPERMISSION.Quotes.iprodname} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top" class="smalltxt">
																					{$PDFMODULLANGUAGEQUOTES.LBL_PDF_DESCRIPTION_CONTENT_DESCRIPTION}&nbsp;&nbsp;
																					<input type="checkbox" name="Quotes_iproddes_qc"  id="Quotes_iproddes_qc"  {$IPRODDETAILS.Quotes.1} {$CHANGEPERMISSION.Quotes.iproddes}>
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt" align="right">
																					<input type="checkbox" name="Quotes_iproddes_perm" id="Quotes_iproddes_perm" {$EDITPERMISSION.Quotes.iproddes} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top" class="smalltxt">
																					{$PDFMODULLANGUAGEQUOTES.LBL_PDF_DESCRIPTION_CONTENT_COMMENT}&nbsp;&nbsp;
																					<input type="checkbox" name="Quotes_iprodcom_qc"  id="Quotes_iprodcom_qc"  {$IPRODDETAILS.Quotes.2} {$CHANGEPERMISSION.Quotes.iprodcom}>
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt" align="right">
																					<input type="checkbox" name="Quotes_iprodcom_perm" id="Quotes_iprodcom_perm"  {$EDITPERMISSION.Quotes.iprodcom} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																		</table>
																	</td>
													            </tr>
													        </table>
														</div>
													</div>
												</td>
											</tr>
										</table>
										<br><br><br>
										<table>
											<tr>
												<td class="small" align='left' nowrap width="100%">
													<input class="btn  btn-success" id="saveg" name="saveg" title="{vtranslate('LBL_SAVE',$MODULE)}"  type="submit" value="{vtranslate('LBL_SAVE',$MODULE)}">&nbsp;
													<input class="btn" id="cancelg" name="cancelg" title="{vtranslate('LBL_CANCEL',$MODULE)}" onclick="disableFields(pdfsettings);" type="button"  value="{vtranslate('LBL_CANCEL',$MODULE)}">
												</td>
											</tr>
										</table>
										<script type="text/javascript">
											initTabs('configurationtabs',Array(pdfconfig_arr.TAB_GENERAL,pdfconfig_arr.TAB_GROUP,pdfconfig_arr.TAB_INDIVIDUAL),0,850,600);
										</script>
									</div>
								</td>
							</tr>
						</table>
						<br>
						<br>
					</div>
					<!-- Invoice start here -->
					<div id="Invoice" style="display:none" class="box">
						<table align="center" border="0" cellpadding="0" cellspacing="0" width="98%">
							<tr>
								<td class="showPanelBg" style="padding: 10px;" valign="top" width="100%">
								<br>
									<div align=center>
										<!-- DISPLAY -->
										<table border=0 cellspacing=0 cellpadding=5 width="100%" >
											<tr>
												<td>
													<div id="configurationtabs_invoice">
														<div class="dhtmlgoodies_aTab">
															<table border=0 cellspacing=0 cellpadding=10 width="100%" >
																<tr>
																	<td valign="top"  align="left"  class="bigtxt">{vtranslate('LBL_PDFCONFIGURATOR_INVOICES',$MODULE)}</td>
																</tr>
																<tr>
																	<td  align="left" >
																		<table border=0 cellspacing=0 cellpadding=0 width="100%" >
																			<tr>
																				<td valign="top" class="smalltxt">{$PDFMODULLANGUAGEINVOICES.LBL_LANGUAGES}</td>
																			</tr>
																			<tr>
																				<td class="small" align="left" valign="top" >
																					<table width="100%"  border="0" cellspacing="0" cellpadding="5">
																						<tr valign="top">
																							<td class="smalltxt"  align="left" width="50%" >
																								<select class="detailedViewTextBox" style="width:30%;" name="Invoice_pdflang_iv" id="Invoice_pdflang_iv" {$CHANGEPERMISSION.Invoice.pdflang}>
																									{html_options values=$LANGUAGEKEYS.Invoice output=$LANGUAGES.Invoice selected=$LANGSELECTED.Invoice}
																								</select>
																							</td>
																							{if $MODULEVIEW==1}
																							<td class="smalltxt" align="right"  width="50%" >
																								<input type="checkbox" name="Invoice_pdflang_perm" id="Invoice_pdflang_perm" {$EDITPERMISSION.Invoice.pdflang} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																							</td>
																							{/if}
																						</tr>
																					</table>
																				</td>
																			</tr>
																		</table>
																		<table border=0 cellspacing=0 cellpadding=0 width="100%" >
																			<tr>
																				<td valign="top"  class="smalltxt">{$PDFMODULLANGUAGEINVOICES.LBL_PAPERFORMAT}</td>
																			</tr>
																			<tr>
																				<td class="small" valign="top" >
																					<table width="100%"  border="0" cellspacing="0" cellpadding="5">
																						<tr valign="top">
																							<td class="smalltxt" width="50%">
																								<select class="detailedViewTextBox" style="width:30%;" id="Invoice_paperf_iv" name="Invoice_paperf_iv" {$CHANGEPERMISSION.Invoice.paperf}>
																									{html_options values=$PAPERFORMAT.Invoice output=$PAPERFORMAT.Invoice selected=$PAPERSELECTED.Invoice}
																								</select>
																							</td>
																							{if $MODULEVIEW==1} 
																							<td class="smalltxt" align="right" >
																								<input type="checkbox" id="Invoice_paperf_perm"  name="Invoice_paperf_perm" {$EDITPERMISSION.Invoice.paperf} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																							</td>
																							{/if}
																						</tr>
																					</table>
																				</td>
																			</tr>
																		</table>
																		<table border=0 cellspacing=0 cellpadding=0 width="100%" >
																			<tr>
																				<td valign="top" class="smalltxt">{$PDFMODULLANGUAGEINVOICES.LBL_PDF_CONFIGURATOR_FONTS}</td>
																			</tr>
																			<tr>
																				<td class="small" valign="top" >
																					<table width="100%"  border="0" cellspacing="0" cellpadding="5">
																						<tr valign="top">
																							<td class="smalltxt" width="80%">
																								<select name="Invoice_fontid_iv" class="detailedViewTextBox" id="Invoice_fontid_iv" style="width:40%;" >
																									{html_options  selected=$SELECTEDFONTID.Invoice values=$FONTIDS.Invoice output=$FONTLIST.Invoice}
																								</select>
																							</td>
																							{if $MODULEVIEW==1}
																							<td class="smalltxt" align="right" width="20%">
																								<input type="checkbox" name="Invoice_fontid_perm" id="Invoice_fontid_perm" {$EDITPERMISSION.Invoice.fontid} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																							</td>
																							{/if}
																						</tr>
																					</table>
																				</td>
																			</tr>
																		</table>
																		<table border=0 cellspacing=0 cellpadding=0 width="100%" class="listRow">
																			<tr>
																				<td valign="top" class="smalltxt">{$PDFMODULLANGUAGEINVOICES.LBL_PDF_CONFIGURATOR_FONTSSIZE}</td>
																			</tr>
																			<tr>
																				<td>
																					<table width="100%"  border="0" cellspacing="0" cellpadding="5">
																						<tr>
																							<td valign="top" class="smalltxt"><b>{$PDFMODULLANGUAGEINVOICES.LBL_PDF_CONFIGURATOR_FONTSSIZE_HEADER}</b>&nbsp;&nbsp;
																							{if $MODULEVIEW==1}
																								<input type="checkbox" name="Invoice_fontsizeheader_perm" id="Invoice_fontsizeheader_perm" {$EDITPERMISSION.Invoice.fontsizeheader} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																							{/if}
																							</td>
																							<td valign="top" class="smalltxt"><b>{$PDFMODULLANGUAGEINVOICES.LBL_PDF_CONFIGURATOR_FONTSSIZE_ADDRESS}</b>&nbsp;&nbsp;
																							{if $MODULEVIEW==1}
																								<input type="checkbox" name="Invoice_fontsizeaddress_perm" id="Invoice_fontsizeaddress_perm" {$EDITPERMISSION.Invoice.fontsizeaddress} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																							{/if}
																							</td>
																							<td valign="top" class="smalltxt"><b>{$PDFMODULLANGUAGEINVOICES.LBL_PDF_CONFIGURATOR_FONTSSIZE_BODY}</b>&nbsp;&nbsp;
																							{if $MODULEVIEW==1}
																								<input type="checkbox" name="Invoice_fontsizebody_perm" id="Invoice_fontsizebody_perm"  {$EDITPERMISSION.Invoice.fontsizebody} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																							{/if}
																							</td>
																							<td valign="top" class="smalltxt"><b>{$PDFMODULLANGUAGEINVOICES.LBL_PDF_CONFIGURATOR_FONTSSIZE_FOOTER}</b>&nbsp;&nbsp;
																							{if $MODULEVIEW==1}
																								<input type="checkbox" name="Invoice_fontsizefooter_perm" id="Invoice_fontsizefooter_perm"  {$EDITPERMISSION.Invoice.fontsizefooter} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																							{/if}
																							</td>
																						</tr>
																						<tr valign="top">
																							<td class="smalltxt">
																								<select name="Invoice_fontsizeheader_iv" class="detailedViewTextBox"  style="width:25%;" id="Invoice_fontsizeheader_iv" >
																									{html_options values=$FONTSIZEAVAILABLE output=$FONTSIZEAVAILABLE selected=$FONTSIZEHEADER.Invoice}
																								</select>
																							</td>
																							<td  class="smalltxt">
																								<select name="Invoice_fontsizeaddress_iv" class="detailedViewTextBox"  style="width:25%;" id="Invoice_fontsizeaddress_iv" >
																									{html_options values=$FONTSIZEAVAILABLE output=$FONTSIZEAVAILABLE selected=$FONTSIZEADDRESS.Invoice}
																								</select>
																							</td>
																							<td  class="smalltxt">
																								<select name="Invoice_fontsizebody_iv" class="detailedViewTextBox"  style="width:25%;" id="Invoice_fontsizebody_iv" >
																									{html_options values=$FONTSIZEAVAILABLE output=$FONTSIZEAVAILABLE selected=$FONTSIZEBODY.Invoice}
																								</select>
																							</td>
																							<td  class="smalltxt">
																								<select name="Invoice_fontsizefooter_iv" class="detailedViewTextBox" style="width:25%;" id="Invoice_fontsizefooter_iv">
																									{html_options values=$FONTSIZEAVAILABLE output=$FONTSIZEAVAILABLE selected=$FONTSIZEFOOTER.Invoice}
																								</select>
																							</td>
																						</tr>
																					</table>
																				</td>
																			</tr>
																		</table>
																	</td>
													            </tr>
													        </table>
															<table border=0 cellspacing=0 cellpadding=10 width="100%" class="listRow">
																<tr>
																	<td  align="left" >
																		<table align="" border=0 cellspacing=0 cellpadding=0 width="100%" >
																			<tr>
																				<td valign="top" class="smalltxt">{$PDFMODULLANGUAGEINVOICES.LBL_PRINT_LOGO}&nbsp;&nbsp;
																					<input type="checkbox" name="Invoice_logoradio_ic" id="Invoice_logoradio_ic" {$LOGORADIO.Invoice} > 
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt" align="right">
																					<input type="checkbox" name="Invoice_logoradio_perm" id="Invoice_logoradio_perm"  {$EDITPERMISSION.Invoice.logoradio} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top" width="80%" class="smalltxt">{$PDFMODULLANGUAGEQUOTES.LBL_OWNER}&nbsp;&nbsp;
																					<input type="checkbox" id="Invoice_owner_ic" name="Invoice_owner_ic" {$OWNER.Invoice} {$CHANGEPERMISSION.Invoice.owner}> 
																				</td>
																				{if $MODULEVIEW==1} 
																				<td class="smalltxt" width="20%" align="right">
																					<input type="checkbox" id="Invoice_owner_perm" name="Invoice_owner_perm" {$EDITPERMISSION.Invoice.owner} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top" width="80%" class="smalltxt">{$PDFMODULLANGUAGEQUOTES.LBL_OWNER_PH}&nbsp;&nbsp;
																					<input type="checkbox" id="Invoice_ownerphone_ic" name="Invoice_ownerphone_ic" {$OWNERPHONE.Invoice} {$CHANGEPERMISSION.Invoice.ownerphone}> 
																				</td>
																				{if $MODULEVIEW==1} 
																				<td class="smalltxt" width="20%" align="right">
																					<input type="checkbox" id="Invoice_ownerphone_perm" name="Invoice_ownerphone_perm" {$EDITPERMISSION.Invoice.ownerphone} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top" width="80%" class="smalltxt">{$PDFMODULLANGUAGEINVOICES.LBL_PDF_PONAME}&nbsp;&nbsp;
																					<input type="checkbox" name="Invoice_poname_ic" id="Invoice_poname_perm"  {$PONAME.Invoice} {$CHANGEPERMISSION.Invoice.poname}> 
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt" width="20%" align="right">
																					<input type="checkbox" name="Invoice_poname_perm" id="Invoice_poname_perm"  {$EDITPERMISSION.Invoice.poname} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top" width="80%" class="smalltxt">{$PDFMODULLANGUAGEINVOICES.LBL_PDF_CLIENTID}&nbsp;&nbsp;
																					<input type="checkbox" name="Invoice_clientid_ic" id="Invoice_clientid_perm"  {$CLIENTID.Invoice} {$CHANGEPERMISSION.Invoice.clientid}> 
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt" width="20%" align="right">
																					<input type="checkbox" name="Invoice_clientid_perm" id="Invoice_clientid_perm"  {$EDITPERMISSION.Invoice.clientid} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top" width="80%" class="smalltxt">{$PDFMODULLANGUAGEINVOICES.LBL_PDF_CONFIGURATOR_SPACE_HEADER}&nbsp;&nbsp;
																					<select name="Invoice_spaceheadline_iv" class="detailedViewTextBox"   style="width:7%;" id="Invoice_spaceheadline_iv" >
																						{html_options values=$HEADERSPACE output=$HEADERSPACE selected=$HEADERSPACESELECTED}
																					</select>
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt" width="20%" align="right">
																					<input type="checkbox" name="Invoice_spaceheadline_perm" id="Invoice_spaceheadline_perm"  {$EDITPERMISSION.Invoice.spaceheadline} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top" width="80%" class="smalltxt">{$PDFMODULLANGUAGEINVOICES.LBL_PRINT_FOOTER}&nbsp;&nbsp;
																					<input type="checkbox" name="Invoice_footerradio_ic" id="Invoice_footerradio_ic" {$FOOTERRADIO.Invoice} {$CHANGEPERMISSION.Invoice.footerradio}> 
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt" width="20%" align="right">
																					<input type="checkbox" name="Invoice_footerradio_perm" id="Invoice_footerradio_perm"  {$EDITPERMISSION.Invoice.footerradio} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top" width="80%" class="smalltxt">{$PDFMODULLANGUAGEINVOICES.LBL_PRINT_FOOTERPAGE}&nbsp;&nbsp;
																					<input type="checkbox" name="Invoice_pageradio_ic" id="Invoice_pageradio_ic" {$FOOTERPAGERADIO.Invoice} {$CHANGEPERMISSION.Invoice.pageradio}> 
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt" width="20%" align="right">
																					<input type="checkbox" name="Invoice_pageradio_perm" id="Invoice_pageradio_perm" {$EDITPERMISSION.Invoice.pageradio} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																		</table>
																	</td>
													            </tr>
													        </table>
															<table border=0 cellspacing=0 cellpadding=10 width="100%" class="listRow">
																<tr>
																	<td  align="left" >
																		<table align="" border=0 cellspacing=0 cellpadding=0 width="100%" >
																			<tr>
																				<td valign="top" class="smalltxt">{$PDFMODULLANGUAGEINVOICES.LBL_PDF_CONFIGURATOR_SUMMARY}&nbsp;&nbsp;
																					<input type="checkbox" name="Invoice_summaryradio_ic" id="Invoice_summaryradio_ic" {$SUMMARYRADIO.Invoice} {$CHANGEPERMISSION.Invoice.summaryradio} > 
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt" align="right">
																					<input type="checkbox" name="Invoice_summaryradio_perm" id="Invoice_summaryradio_perm"  {$EDITPERMISSION.Invoice.summaryradio} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																		</table>
																	</td>
													            </tr>
													        </table>
														</div>
														<div class="dhtmlgoodies_aTab">
															<table border=0 cellspacing=0 cellpadding=10 width="100%" class="listRow">
																<tr>
																	<td  align="left" >
																		<table border=0 cellspacing=0 cellpadding=0 width="100%">
																			<tr>
																				<td valign="top" class="smalltxt">{$PDFMODULLANGUAGEINVOICES.LBL_PDF_CONFIGURATOR_ROWS}</td>
																			</tr>
																		</table><br>
																		<table border=0 cellspacing=0 cellpadding=0 width="100%" >
																			<tr valign="top">
																				<td  valign="top" class="smalltxt">{$PDFLANGUAGEARRAYINVOICES.TAX_GROUP}</td>
																			</tr>
																			<tr valign="top">
																				<td width="90%">
																					<table border=0 cellspacing=0 cellpadding=3 frame="below" width="100%" class="table thread th">
																						<tr>
																							{foreach key =checkboxtype item =grouptax from=$COLUMNCONFIGURATIONGROUP.Invoice} 
																								<td  class="bigtxt" valign="top" id="Invoice.{$grouptax.taxtype}.{$checkboxtype}">
																									{if ($grouptax.selected == 'checked="checked"' and $grouptax.enabled == '1')}
																										{$PDFLANGUAGEARRAYINVOICES.$checkboxtype}
																									{/if}
																								</td>
																								<td>&nbsp;</td>
																							{/foreach}
																							{if $MODULEVIEW==1}
																							<td class="smalltxt" align="right">
																								<input type="checkbox" name="Invoice_gcolumns_perm" id="Invoice_gcolumns_perm"  {$EDITPERMISSION.Invoice.gcolumns} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																							</td>
																							{/if}
																						</tr>
																					</table>
																				</td>
																				<td id="Invoice_checkboxes_group"  valign="top" class="smalltxt" width="20%">
																					{foreach key =checkboxtype item =grouptax from=$COLUMNCONFIGURATIONGROUP.Invoice}
																						{if $grouptax.enabled == '1'}
																							<input type="checkbox" name="Invoice.{$grouptax.taxtype}.{$checkboxtype}" id="{$checkboxtype}"  {$grouptax.active} {$grouptax.selected} onclick="preview(this,'Invoice.{$grouptax.taxtype}.{$checkboxtype}','{$PDFLANGUAGEARRAYINVOICES.$checkboxtype}');">&nbsp;{$PDFLANGUAGEARRAYINVOICES.$checkboxtype}<br>
																						{/if}
																					{/foreach}
																				</td>
																			</tr>
																		</table>
																	</td>
																</tr>
															</table>
															<table border=0 cellspacing=0 cellpadding=10 width="100%" class="listRow">
																<tr>
																	<td  align="left" >
																		<table align="" border=0 cellspacing=0 cellpadding=0 width="100%" >
																			<tr>
																				<td valign="top" class="smalltxt">{$PDFMODULLANGUAGEINVOICES.LBL_PDF_DESCRIPTION_CONTENT}
																				</td>
																			</tr>
																			<tr>
																				<td valign="top" class="smalltxt">
																					{$PDFMODULLANGUAGEINVOICES.LBL_PDF_DESCRIPTION_CONTENT_NAME}&nbsp;&nbsp;
																					<input type="checkbox" name="Invoice_gprodname_ic"  id="Invoice_gprodname_ic"  {$GPRODDETAILS.Invoice.0}>
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt" align="right">
																					<input type="checkbox" name="Invoice_gprodname_perm" id="Invoice_gprodname_perm"  {$EDITPERMISSION.Invoice.gprodname} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top" class="smalltxt">
																					{$PDFMODULLANGUAGEINVOICES.LBL_PDF_DESCRIPTION_CONTENT_DESCRIPTION}&nbsp;&nbsp;
																					<input type="checkbox" name="Invoice_gproddes_ic"  id="Invoice_gproddes_ic"  {$GPRODDETAILS.Invoice.1}>
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt" align="right">
																					<input type="checkbox" name="Invoice_gproddes_perm" id="Invoice_gproddes_perm"  {$EDITPERMISSION.Invoice.gproddes} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top" class="smalltxt">
																					{$PDFMODULLANGUAGEINVOICES.LBL_PDF_DESCRIPTION_CONTENT_COMMENT}&nbsp;&nbsp;
																					<input type="checkbox" name="Invoice_gprodcom_ic"  id="Invoice_gprodcom_ic"  {$GPRODDETAILS.Invoice.2}>
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt" align="right">
																					<input type="checkbox" name="Invoice_gprodcom_perm" id="Invoice_gprodcom_perm" {$EDITPERMISSION.Invoice.gprodcom} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																		</table>
																	</td>
													            </tr>
													        </table>
														</div>
														<div class="dhtmlgoodies_aTab">
															<table border=0 cellspacing=0 cellpadding=10 width="100%" class="listRow">
																<tr>
																	<td  align="left" >
																		<table border=0 cellspacing=0 cellpadding=0 width="100%" >
																			<tr>
																				<td valign="top" class="smalltxt">{$PDFMODULLANGUAGEINVOICES.LBL_PDF_CONFIGURATOR_ROWS}</td>
																			</tr>
																		</table><br>
																		<table border=0 cellspacing=0 cellpadding=0 width="100%" >
																			<tr valign="top">
																				<td valign="top" class="smalltxt">{$PDFLANGUAGEARRAYINVOICES.TAX_INDIVIDUAL}</td>
																			</tr>
																			<tr valign="top">
																				<td width="90%">
																					<table border=0 cellspacing=0 cellpadding=3 frame="below" width="100%" class="table thread th">
																						<tr >
																							{foreach key =checkboxtypei item =indivitax from=$COLUMNCONFIGURATIONINDIVIDUAL.Invoice}
																								<td class="bigtxt" valign="top" id="Invoice.{$indivitax.taxtype}.{$checkboxtypei}">
																									{if ($indivitax.selected == 'checked="checked"' and $indivitax.enabled == '1')}
																										{$PDFLANGUAGEARRAYINVOICES.$checkboxtypei}
																									{/if}
																								</td>
																								<td>&nbsp;</td>
																							{/foreach}
																							{if $MODULEVIEW==1}
																							<td class="smalltxt" align="right">
																								<input type="checkbox" name="Invoice_icolumns_perm" id="Invoice_icolumns_perm" {$EDITPERMISSION.Invoice.icolumns} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																							</td>
																							{/if}
																						</tr>
																					</table>
																				</td>
																				<td id="Invoice_checkboxes_individual"  valign="top" class="smalltxt" width="15%">
																					{foreach key =checkboxtypei item =indivitax from=$COLUMNCONFIGURATIONINDIVIDUAL.Invoice}
																						{if $indivitax.enabled == '1'}
																							<input type="checkbox" name="Invoice.{$indivitax.taxtype}.{$checkboxtypei}" id="{$checkboxtypei}" {$indivitax.active} {$indivitax.selected} onclick="preview(this,'Invoice.{$indivitax.taxtype}.{$checkboxtypei}','{$PDFLANGUAGEARRAYINVOICES.$checkboxtypei}');">&nbsp;{$PDFLANGUAGEARRAYINVOICES.$checkboxtypei}<br>
																						{/if}
																					{/foreach}
																				</td>
																			</tr>
																		</table>
																	</td>
																</tr>
															</table>
															<table border=0 cellspacing=0 cellpadding=10 width="100%" class="listRow">
																<tr>
																	<td  align="left" >
																		<table align="" border=0 cellspacing=0 cellpadding=0 width="100%" >
																			<tr>
																				<td valign="top" class="smalltxt">{$PDFMODULLANGUAGEINVOICES.LBL_PDF_DESCRIPTION_CONTENT}
																				</td>
																			</tr>
																			<tr>
																				<td valign="top" class="smalltxt">
																					{$PDFMODULLANGUAGEINVOICES.LBL_PDF_DESCRIPTION_CONTENT_NAME}&nbsp;&nbsp;
																					<input type="checkbox" name="Invoice_iprodname_ic"  id="Invoice_iprodname_ic"  {$IPRODDETAILS.Invoice.0}>
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt" align="right">
																					<input type="checkbox" name="Invoice_iprodname_perm" id="Invoice_iprodname_perm" {$EDITPERMISSION.Invoice.iprodname} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top" class="smalltxt">
																					{$PDFMODULLANGUAGEINVOICES.LBL_PDF_DESCRIPTION_CONTENT_DESCRIPTION}&nbsp;&nbsp;
																					<input type="checkbox" name="Invoice_iproddes_ic"  id="Invoice_iproddes_ic"  {$IPRODDETAILS.Invoice.1}>
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt" align="right">
																					<input type="checkbox" name="Invoice_iproddes_perm" id="Invoice_iproddes_perm" {$EDITPERMISSION.Invoice.iproddes} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top" class="smalltxt">
																					{$PDFMODULLANGUAGEINVOICES.LBL_PDF_DESCRIPTION_CONTENT_COMMENT}&nbsp;&nbsp;
																					<input type="checkbox" name="Invoice_iprodcom_ic"  id="Invoice_iprodcom_ic"  {$IPRODDETAILS.Invoice.2}>
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt" align="right">
																					<input type="checkbox" name="Invoice_iprodcom_perm" id="Invoice_iprodcom_perm" {$EDITPERMISSION.Invoice.iprodcom} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																		</table>
																	</td>
													            </tr>
													        </table>
														</div>
													</div>
												</td>
											</tr>
										</table>
										<br><br><br>
										<table>
											<tr>
												<td class="small" align='left' nowrap>
													<input class="btn  btn-success" id="savei" name="savei" title="{vtranslate('LBL_SAVE',$MODULE)}"   type="submit"  value="{vtranslate('LBL_SAVE',$MODULE)}">&nbsp;
													<input class="btn" id="canceli" name="canceli" title="{vtranslate('LBL_CANCEL',$MODULE)}"  onclick="disableFields(pdfsettings);" type="button"  value="{vtranslate('LBL_CANCEL',$MODULE)}">
												</td>
											</tr>
										</table>
										<script type="text/javascript">
											initTabs('configurationtabs_invoice',Array(pdfconfig_arr.TAB_GENERAL,pdfconfig_arr.TAB_GROUP,pdfconfig_arr.TAB_INDIVIDUAL),0,850,600);
										</script>
									</div>
									<br>
								</td>
							</tr>
						</table>
						<br>
						<br>
					</div>
					<!-- Sales Order start here -->
					<div id="SalesOrder" style="display:none" class="box">
						<table align="center" border="0" cellpadding="0" cellspacing="0" width="98%">
							<tr>
								<td class="showPanelBg" style="padding: 10px;" valign="top" width="100%">
									<br>
									<div align=center>
										<!-- DISPLAY -->
										<table border=0 cellspacing=0 cellpadding=5 width="100%" >
											<tr>
												<td>
													<div id="configurationtabs_so">
														<div class="dhtmlgoodies_aTab"> 
															<table border=0 cellspacing=0 cellpadding=10 width="100%" >
																<tr>
																	<td valign="top" align="left" class="bigtxt">{vtranslate('LBL_PDFCONFIGURATOR_SO',$MODULE)}</td>
																</tr>
																<tr>
																	<td align="left">
																		<table border=0 cellspacing=0 cellpadding=0 width="100%" >
																			<tr>
																				<td valign="top"  class="smalltxt">{$PDFMODULLANGUAGESO.LBL_LANGUAGES}</td>
																			</tr>
																			<tr>
																				<td class="small" valign="top" >
																					<table width="100%"  border="0" cellspacing="0" cellpadding="5">
																						<tr valign="top">
																							<td class="smalltxt" width="50%">
																								<select class="detailedViewTextBox" style="width:30%;" id="SalesOrder_pdflang_sv" name="SalesOrder_pdflang_sv" {$CHANGEPERMISSION.SalesOrder.pdflang}>
																									{html_options values=$LANGUAGEKEYS.SalesOrder output=$LANGUAGES.SalesOrder selected=$LANGSELECTED.SalesOrder}
																								</select>
																							</td>
																							{if $MODULEVIEW==1} 
																							<td class="smalltxt" align="right" >
																								<input type="checkbox" id="SalesOrder_pdflang_perm"  name="SalesOrder_pdflang_perm" {$EDITPERMISSION.SalesOrder.pdflang} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																							</td>
																							{/if}
																						</tr>
																					</table>
																				</td>
																			</tr>
																		</table>
																		<table border=0 cellspacing=0 cellpadding=0 width="100%" >
																			<tr>
																				<td valign="top"  class="smalltxt">{$PDFMODULLANGUAGESO.LBL_PAPERFORMAT}</td>
																			</tr>
																			<tr>
																				<td class="small" valign="top" >
																					<table width="100%"  border="0" cellspacing="0" cellpadding="5">
																						<tr valign="top">
																							<td class="smalltxt" width="50%">
																								<select class="detailedViewTextBox" style="width:30%;" id="SalesOrder_paperf_sv" name="SalesOrder_paperf_sv" {$CHANGEPERMISSION.SalesOrder.paperf}>
																									{html_options values=$PAPERFORMAT.SalesOrder output=$PAPERFORMAT.SalesOrder selected=$PAPERSELECTED.SalesOrder}
																								</select>
																							</td>
																							{if $MODULEVIEW==1} 
																							<td class="smalltxt" align="right" >
																								<input type="checkbox" id="SalesOrder_paperf_perm"  name="SalesOrder_paperf_perm" {$EDITPERMISSION.SalesOrder.paperf} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																							</td>
																							{/if}
																						</tr>
																					</table>
																				</td>
																			</tr>
																		</table>
																		<table border=0 cellspacing=0 cellpadding=0 width="100%" >
																			<tr>
																				<td valign="top" class="smalltxt">{$PDFMODULLANGUAGESO.LBL_PDF_CONFIGURATOR_FONTS}</td>
																			</tr>
																			<tr>
																				<td class="small" valign="top" width="100%">
																					<table width="100%"  border="0" cellspacing="0" cellpadding="5">
																						<tr valign="top">
																							<td  class="smalltxt" width="80%">
																								<select id="SalesOrder_fontid_sv" name="SalesOrder_fontid_sv" class="detailedViewTextBox"  style="width:40%;" {$CHANGEPERMISSION.SalesOrder.fontid}>
																									{html_options selected=$SELECTEDFONTID.SalesOrder size=1 values=$FONTIDS.SalesOrder output=$FONTLIST.SalesOrder }
																								</select>
																							</td>
																							{if $MODULEVIEW==1} 
																							<td class="smalltxt" align="right" width="20%">
																								<input type="checkbox" name="SalesOrder_fontid_perm" id="SalesOrder_fontid_perm" {$EDITPERMISSION.SalesOrder.fontid} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																							</td>
																							{/if}
																						</tr>
																					</table>
																				</td>
																			</tr>
																		</table>
																		<table border=0 cellspacing=0 cellpadding=0 width="100%" class="listRow">
																			<tr>
																				<td align="left" valign="top" class="smalltxt">{$PDFMODULLANGUAGESO.LBL_PDF_CONFIGURATOR_FONTSSIZE}</td>
																			</tr>
																			<tr>
																				<td align='left'>
																					<table width="100%"  border="0" cellspacing="0" cellpadding="5">
																						<tr>
																							<td valign="top" class="smalltxt"><b>{$PDFMODULLANGUAGESO.LBL_PDF_CONFIGURATOR_FONTSSIZE_HEADER}</b>&nbsp;&nbsp;
																							{if $MODULEVIEW==1} 
																								<input type="checkbox" id="SalesOrder_fontsizeheader_perm" name="SalesOrder_fontsizeheader_perm" {$EDITPERMISSION.SalesOrder.fontsizeheader} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																							{/if}
																							</td>
																							<td valign="top" class="smalltxt"><b>{$PDFMODULLANGUAGESO.LBL_PDF_CONFIGURATOR_FONTSSIZE_ADDRESS}</b>&nbsp;&nbsp;
																							{if $MODULEVIEW==1} 
																								<input type="checkbox" id="SalesOrder_fontsizeaddress_perm" name="SalesOrder_fontsizeaddress_perm" {$EDITPERMISSION.SalesOrder.fontsizeaddress} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																							{/if}
																							</td>
																							<td valign="top" class="smalltxt"><b>{$PDFMODULLANGUAGESO.LBL_PDF_CONFIGURATOR_FONTSSIZE_BODY}</b>&nbsp;&nbsp;
																							{if $MODULEVIEW==1} 
																								<input type="checkbox" id="SalesOrder_fontsizebody_perm" name="SalesOrder_fontsizebody_perm"{$EDITPERMISSION.SalesOrder.fontsizebody} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																							{/if}
																							</td>
																							<td valign="top" class="smalltxt"><b>{$PDFMODULLANGUAGESO.LBL_PDF_CONFIGURATOR_FONTSSIZE_FOOTER}</b>&nbsp;&nbsp;
																							{if $MODULEVIEW==1} 
																								<input type="checkbox" id="SalesOrder_fontsizefooter_perm" name="SalesOrder_fontsizefooter_perm" {$EDITPERMISSION.SalesOrder.fontsizefooter} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																							{/if}
																							</td>
																						</tr>
																						<tr valign="top">
																								<td class="smalltxt">
																									<select name="SalesOrder_fontsizeheader_sv" class="detailedViewTextBox"  style="width:25%;" id="SalesOrder_fontsizeheader_sv" {$CHANGEPERMISSION.SalesOrder.fontsizeheader}>
																										{html_options values=$FONTSIZEAVAILABLE output=$FONTSIZEAVAILABLE selected=$FONTSIZEHEADER.SalesOrder}
																									</select>
																								</td>
																								<td  class="smalltxt">
																									<select name="SalesOrder_fontsizeaddress_sv" class="detailedViewTextBox"  style="width:25%;" id="SalesOrder_fontsizeaddress_sv" {$CHANGEPERMISSION.SalesOrder.fontsizeaddress}>
																										{html_options values=$FONTSIZEAVAILABLE output=$FONTSIZEAVAILABLE selected=$FONTSIZEADDRESS.SalesOrder}
																									</select>
																								</td>
																								<td  class="smalltxt">
																									<select name="SalesOrder_fontsizebody_sv" class="detailedViewTextBox"  style="width:25%;" id="SalesOrder_fontsizebody_sv" {$CHANGEPERMISSION.SalesOrder.fontsizebody}>
																										{html_options values=$FONTSIZEAVAILABLE output=$FONTSIZEAVAILABLE selected=$FONTSIZEBODY.SalesOrder}
																									</select>
																								</td>
																								<td  class="smalltxt">
																									<select name="SalesOrder_fontsizefooter_sv" class="detailedViewTextBox" style="width:25%;" id="SalesOrder_fontsizefooter_sv"{$CHANGEPERMISSION.SalesOrder.fontsizefooter}>
																										{html_options values=$FONTSIZEAVAILABLE output=$FONTSIZEAVAILABLE selected=$FONTSIZEFOOTER.SalesOrder}
																									</select>
																								</td>
																						</tr>
																					</table>
																				</td>
																			</tr>
																		</table>
																	</td>
													            </tr>
													        </table>
															<table border=0 cellspacing=0 cellpadding=10 width="100%" class="listRow">
																<tr>
																	<td align="left">
																		<table align="" border=0 cellspacing=0 cellpadding=0 width="100%" >
																			<tr>
																				<td valign="top" width="80%" class="smalltxt">{$PDFMODULLANGUAGESO.LBL_PRINT_LOGO}&nbsp;&nbsp;
																					<input type="checkbox" id="SalesOrder_logoradio_sc" name="SalesOrder_logoradio_sc" {$LOGORADIO.SalesOrder} {$CHANGEPERMISSION.SalesOrder.logoradio}> 
																				</td>
																				{if $MODULEVIEW==1} 
																				<td class="smalltxt" width="20%" align="right">
																					<input type="checkbox" id="SalesOrder_logoradio_perm" name="SalesOrder_logoradio_perm" {$EDITPERMISSION.SalesOrder.logoradio} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top"  width="80%" class="smalltxt">{$PDFMODULLANGUAGESO.LBL_PDF_DATE}&nbsp;&nbsp;
																					<select name="SalesOrder_dateused_sv" class="detailedViewTextBox"  style="width:20%;" id="SalesOrder_dateused_sv" {$CHANGEPERMISSION.SalesOrder.dateused}>
																						{html_options values=$DATEUSED.SalesOrder output= $DATEUSEDNAME selected=$DATEUSEDSELECTED.SalesOrder}
																					</select>
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt"  width="20%" align="right">
																					<input type="checkbox" name="SalesOrder_dateused_perm" id="SalesOrder_dateused_perm" {$EDITPERMISSION.SalesOrder.dateused} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top" width="80%" class="smalltxt">{$PDFMODULLANGUAGESO.LBL_OWNER}&nbsp;&nbsp;
																					<input type="checkbox" id="SalesOrder_owner_sc" name="SalesOrder_owner_sc" {$OWNER.SalesOrder} {$CHANGEPERMISSION.SalesOrder.owner}> 
																				</td>
																				{if $MODULEVIEW==1} 
																				<td class="smalltxt" width="20%" align="right">
																					<input type="checkbox" id="SalesOrder_owner_perm" name="SalesOrder_owner_perm" {$EDITPERMISSION.SalesOrder.owner} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top" width="80%" class="smalltxt">{$PDFMODULLANGUAGESO.LBL_OWNER_PH}&nbsp;&nbsp;
																					<input type="checkbox" id="SalesOrder_ownerphone_sc" name="SalesOrder_ownerphone_sc" {$OWNERPHONE.SalesOrder} {$CHANGEPERMISSION.SalesOrder.ownerphone}> 
																				</td>
																				{if $MODULEVIEW==1} 
																				<td class="smalltxt" width="20%" align="right">
																					<input type="checkbox" id="SalesOrder_ownerphone_perm" name="SalesOrder_ownerphone_perm" {$EDITPERMISSION.SalesOrder.ownerphone} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top" width="80%" class="smalltxt">{$PDFMODULLANGUAGESO.LBL_CUSTSIGN}&nbsp;&nbsp;
																					<input type="checkbox" id="SalesOrder_clientid_sc" name="SalesOrder_clientid_sc" {$CLIENTID.SalesOrder} {$CHANGEPERMISSION.SalesOrder.clientid}> 
																				</td>
																				{if $MODULEVIEW==1} 
																				<td class="smalltxt" width="20%" align="right">
																					<input type="checkbox" id="SalesOrder_clientid_perm" name="SalesOrder_clientid_perm" {$EDITPERMISSION.SalesOrder.clientid} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top"  width="80%" class="smalltxt">{$PDFMODULLANGUAGESO.LBL_PDF_CONFIGURATOR_SPACE_HEADER}&nbsp;&nbsp;
																					<select name="SalesOrder_spaceheadline_sv" class="detailedViewTextBox"   style="width:7%;" id="SalesOrder_spaceheadline_sv" {$CHANGEPERMISSION.SalesOrder.spaceheadline}>
																						{html_options values=$HEADERSPACE output=$HEADERSPACE selected=$HEADERSPACESELECTED}
																					</select>
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt"  width="20%" align="right">
																					<input type="checkbox" name="SalesOrder_spaceheadline_perm" id="SalesOrder_spaceheadline_perm"  {$EDITPERMISSION.SalesOrder.spaceheadline} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top"  width="80%" class="smalltxt">{$PDFMODULLANGUAGESO.LBL_PRINT_FOOTER}&nbsp;&nbsp;
																					<input type="checkbox" name="SalesOrder_footerradio_sc" id="SalesOrder_footerradio_sc" {$FOOTERRADIO.SalesOrder} {$CHANGEPERMISSION.SalesOrder.footerradio}> 
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt"  width="20%" align="right">
																					<input type="checkbox" name="SalesOrder_footerradio_perm" id="SalesOrder_footerradio_perm"  {$EDITPERMISSION.SalesOrder.footerradio} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top"  width="80%" class="smalltxt">{$PDFMODULLANGUAGESO.LBL_PRINT_FOOTERPAGE}&nbsp;&nbsp;
																					<input type="checkbox" name="SalesOrder_pageradio_sc" id="SalesOrder_pageradio_sc" {$FOOTERPAGERADIO.SalesOrder} {$CHANGEPERMISSION.SalesOrder.pageradio}> 
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt" align="right">
																					<input type="checkbox" name="SalesOrder_pageradio_perm" id="SalesOrder_pageradio_perm"  {$EDITPERMISSION.SalesOrder.pageradio} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																		</table>
																	</td>
													            </tr>
													        </table>
															<table border=0 cellspacing=0 cellpadding=10 width="100%" class="listRow">
																<tr>
																	<td align="left">
																		<table align="" border=0 cellspacing=0 cellpadding=0 width="100%" >
																			<tr>
																				<td valign="top" class="smalltxt">{$PDFMODULLANGUAGESO.LBL_PDF_CONFIGURATOR_SUMMARY}&nbsp;&nbsp;
																					<input type="checkbox" name="SalesOrder_summaryradio_sc" id="SalesOrder_summaryradio_sc" {$SUMMARYRADIO.SalesOrder} {$CHANGEPERMISSION.SalesOrder.summaryradio}> 
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt" align="right">
																					<input type="checkbox" name="SalesOrder_summaryradio_perm" id="SalesOrder_summaryradio_perm"  {$EDITPERMISSION.SalesOrder.summaryradio} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																		</table>
																	</td>
													            </tr>
													        </table>
														</div>
														<div class="dhtmlgoodies_aTab"> 
															<table border=0 cellspacing=0 cellpadding=10 width="100%" class="listRow">
																<tr>
																	<td align="left">
																		<table border=0 cellspacing=0 cellpadding=0 width="100%">
																			<tr>
																				<td valign="top" class="smalltxt">{$PDFMODULLANGUAGESO.LBL_PDF_CONFIGURATOR_ROWS}</td>
																			</tr>
																		</table><br>
																		<table border=0 cellspacing=0 cellpadding=0 width="100%" >
																			<tr valign="top">
																				<td  valign="top" class="smalltxt">{$PDFLANGUAGEARRAYSO.TAX_GROUP}</td>
																			</tr>
																			<tr valign="top">
																				<td width="90%">
																					<table border=0 cellspacing=0 cellpadding=3 frame="below" width="100%" class="table thread th">
																						<tr >
																							{foreach key =checkboxtype item =grouptax from=$COLUMNCONFIGURATIONGROUP.SalesOrder} 
																								<td  class="bigtxt" valign="top" id="SalesOrder.{$grouptax.taxtype}.{$checkboxtype}">
																									{if ($grouptax.selected == 'checked="checked"' and $grouptax.enabled == '1')}
																										{$PDFLANGUAGEARRAYSO.$checkboxtype}
																									{/if}
																								</td>
																								<td>&nbsp;</td>
																							{/foreach}
																							{if $MODULEVIEW==1}
																							<td  class="smalltxt" align="right">
																								<input type="checkbox" name="SalesOrder_gcolumns_perm" id="SalesOrder_gcolumns_perm"  {$EDITPERMISSION.SalesOrder.gcolumns} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																							</td>
																							{/if}
																						</tr>
																					</table>
																				</td>
																				<td id="qcheckboxes_group"  valign="top" class="smalltxt" width="20%">
																					{foreach key =checkboxtype item =grouptax from=$COLUMNCONFIGURATIONGROUP.SalesOrder}
																						{if $grouptax.enabled == '1'}
																							<input type="checkbox" name="SalesOrder.{$grouptax.taxtype}.{$checkboxtype}" id="{$checkboxtype}" {$grouptax.active} {$grouptax.selected} {$CHANGEPERMISSION.SalesOrder.gcolumns} onclick="preview(this,'SalesOrder.{$grouptax.taxtype}.{$checkboxtype}','{$PDFLANGUAGEARRAYSO.$checkboxtype}');">&nbsp;{$PDFLANGUAGEARRAYSO.$checkboxtype}<br>
																						{/if}
																					{/foreach}
																				</td>
																			</tr>
																		</table>
																	</td>
																</tr>
															</table>
															<table border=0 cellspacing=0 cellpadding=10 width="100%" class="listRow">
																<tr>
																	<td align="left">
																		<table align="" border=0 cellspacing=0 cellpadding=0 width="100%" >
																			<tr>
																				<td valign="top" class="smalltxt">{$PDFMODULLANGUAGESO.LBL_PDF_DESCRIPTION_CONTENT}
																				</td>
																			</tr>
																			<tr>
																				<td valign="top" class="smalltxt">
																					{$PDFMODULLANGUAGESO.LBL_PDF_DESCRIPTION_CONTENT_NAME}&nbsp;&nbsp; 
																					<input type="checkbox" name="SalesOrder_gprodname_sc"  id="SalesOrder_gprodname_sc"  {$GPRODDETAILS.SalesOrder.0} {$CHANGEPERMISSION.SalesOrder.gprodname}>
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt" align="right">
																					<input type="checkbox" name="SalesOrder_gprodname_perm" id="SalesOrder_gprodname_perm"  {$EDITPERMISSION.SalesOrder.gprodname} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top" class="smalltxt">
																					{$PDFMODULLANGUAGESO.LBL_PDF_DESCRIPTION_CONTENT_DESCRIPTION}&nbsp;&nbsp;
																					<input type="checkbox" name="SalesOrder_gproddes_sc"  id="SalesOrder_gproddes_sc"  {$GPRODDETAILS.SalesOrder.1} {$CHANGEPERMISSION.SalesOrder.gproddes}>
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt" align="right">
																					<input type="checkbox" name="SalesOrder_gproddes_perm" id="SalesOrder_gproddes_perm"  {$EDITPERMISSION.SalesOrder.gproddes} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top" class="smalltxt">
																					{$PDFMODULLANGUAGESO.LBL_PDF_DESCRIPTION_CONTENT_COMMENT}&nbsp;&nbsp;
																					<input type="checkbox" name="SalesOrder_gprodcom_sc"  id="SalesOrder_gprodcom_sc"  {$GPRODDETAILS.SalesOrder.2} {$CHANGEPERMISSION.SalesOrder.gprodcom}>
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt" align="right">
																					<input type="checkbox" name="SalesOrder_gprodcom_perm" id="SalesOrder_gprodcom_perm"  {$EDITPERMISSION.SalesOrder.gprodcom} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																		</table>
																	</td>
													            </tr>
													        </table>
														</div>
														<div class="dhtmlgoodies_aTab"> 
															<table border=0 cellspacing=0 cellpadding=10 width="100%" class="listRow">
																<tr>
																	<td align="left">
																		<table border=0 cellspacing=0 cellpadding=0 width="100%" >
																			<tr>
																				<td valign="top" class="smalltxt">{$PDFMODULLANGUAGESO.LBL_PDF_CONFIGURATOR_ROWS}</td>
																			</tr>
																		</table><br>
																		<table border=0 cellspacing=0 cellpadding=0 width="100%" >
																			<tr valign="top">
																				<td valign="top" class="smalltxt">{$PDFLANGUAGEARRAYSO.TAX_INDIVIDUAL}</td>
																			</tr>
																			<tr valign="top">
																				<td width="90%">
																					<table border=0 cellspacing=0 cellpadding=3 frame="below" width="100%" class="table thread th">
																						<tr >
																							{foreach key =checkboxtypei item =indivitax from=$COLUMNCONFIGURATIONINDIVIDUAL.SalesOrder}
																								<td  class="bigtxt" valign="top" id="SalesOrder.{$indivitax.taxtype}.{$checkboxtypei}">
																									{if ($indivitax.selected == 'checked="checked"' and $indivitax.enabled == '1')}
																										{$PDFLANGUAGEARRAYSO.$checkboxtypei}
																									{/if}
																								</td>
																								<td>&nbsp;</td>
																							{/foreach}
																							{if $MODULEVIEW==1}
																							<td class="smalltxt" align="right">
																								<input type="checkbox" name="SalesOrder_icolumns_perm" id="SalesOrder_icolumns_perm"  {$EDITPERMISSION.SalesOrder.icolumns} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																							</td>
																							{/if}
																						</tr>
																					</table>
																				</td>
																				<td id="SalesOrder_checkboxes_individual"  valign="top" class="smalltxt" width="20%">
																					{foreach key =checkboxtypei item =indivitax from=$COLUMNCONFIGURATIONINDIVIDUAL.SalesOrder}
																						{if $indivitax.enabled == '1'}
																							<input type="checkbox" name="SalesOrder.{$indivitax.taxtype}.{$checkboxtypei}" id="{$checkboxtypei}" {$indivitax.active} {$indivitax.selected} {$CHANGEPERMISSION.SalesOrder.icolumns} onclick="preview(this,'SalesOrder.{$indivitax.taxtype}.{$checkboxtypei}','{$PDFLANGUAGEARRAYSO.$checkboxtypei}');">&nbsp;{$PDFLANGUAGEARRAYSO.$checkboxtypei}<br>
																						{/if}
																					{/foreach}
																				</td>
																			</tr>
																		</table>
																	</td>
																</tr>
															</table>
															<table border=0 cellspacing=0 cellpadding=10 width="100%" class="listRow">
																<tr>
																	<td align="left">
																		<table align="" border=0 cellspacing=0 cellpadding=0 width="100%" >
																			<tr>
																				<td valign="top" class="smalltxt">{$PDFMODULLANGUAGESO.LBL_PDF_DESCRIPTION_CONTENT}
																				</td>
																			</tr>
																			<tr>
																				<td valign="top" class="smalltxt">
																					{$PDFMODULLANGUAGESO.LBL_PDF_DESCRIPTION_CONTENT_NAME}&nbsp;&nbsp;
																					<input type="checkbox" name="SalesOrder_iprodname_sc"  id="SalesOrder_iprodname_sc"  {$IPRODDETAILS.SalesOrder.0} {$CHANGEPERMISSION.SalesOrder.iprodname}>
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt" align="right">
																					<input type="checkbox" name="SalesOrder_iprodname_perm" id="SalesOrder_iprodname_perm"  {$EDITPERMISSION.SalesOrder.iprodname} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top" class="smalltxt">
																					{$PDFMODULLANGUAGESO.LBL_PDF_DESCRIPTION_CONTENT_DESCRIPTION}&nbsp;&nbsp;
																					<input type="checkbox" name="SalesOrder_iproddes_sc"  id="SalesOrder_iproddes_sc"  {$IPRODDETAILS.SalesOrder.1} {$CHANGEPERMISSION.SalesOrder.iproddes}>
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt" align="right">
																					<input type="checkbox" name="SalesOrder_iproddes_perm" id="SalesOrder_iproddes_perm" {$EDITPERMISSION.SalesOrder.iproddes} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top" class="smalltxt">
																					{$PDFMODULLANGUAGESO.LBL_PDF_DESCRIPTION_CONTENT_COMMENT}&nbsp;&nbsp;
																					<input type="checkbox" name="SalesOrder_iprodcom_sc"  id="SalesOrder_iprodcom_sc"  {$IPRODDETAILS.SalesOrder.2} {$CHANGEPERMISSION.SalesOrder.iprodcom}>
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt" align="right">
																					<input type="checkbox" name="SalesOrder_iprodcom_perm" id="SalesOrder_iprodcom_perm"  {$EDITPERMISSION.SalesOrder.iprodcom} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																		</table>
																	</td>
													            </tr>
													        </table>
														</div>
													</div>
												</td>
											</tr>
										</table>
										<br><br><br>
										<table>
											<tr>
												<td class="small" align='left' nowrap width="100%">
													<input class="btn  btn-success" id="saveso" name="saveso" title="{vtranslate('LBL_SAVE',$MODULE)}"   type="submit"  value="{vtranslate('LBL_SAVE',$MODULE)}">&nbsp;
													<input class="btn" id="cancelso" name="cancelso" title="{vtranslate('LBL_CANCEL',$MODULE)}"  onclick="disableFields(pdfsettings);" type="button"  value="{vtranslate('LBL_CANCEL',$MODULE)}">
												</td>
											</tr>
										</table>
										<script type="text/javascript">
											initTabs('configurationtabs_so',Array(pdfconfig_arr.TAB_GENERAL,pdfconfig_arr.TAB_GROUP,pdfconfig_arr.TAB_INDIVIDUAL),0,850,600);
										</script>
									</div>
								</td>
							</tr>
						</table>
						<br>
						<br>
					</div>
					<!-- Purchase Order start here -->
					<div id="PurchaseOrder" style="display:none" class="box">
						<table align="center" border="0" cellpadding="0" cellspacing="0" width="98%">
							<tr>
								<td class="showPanelBg" style="padding: 10px;" valign="top" width="100%">
									<br>
									<div align=center>
										<!-- DISPLAY -->
										<table border=0 cellspacing=0 cellpadding=5 width="100%" >
											<tr>
												<td>
													<div id="configurationtabs_po">
														<div class="dhtmlgoodies_aTab"> 
															<table border=0 cellspacing=0 cellpadding=10 width="100%" >
																<tr>
																	<td valign="top" align="left" class="bigtxt">{vtranslate('LBL_PDFCONFIGURATOR_PO',$MODULE)}</td>
																</tr>
																<tr>
																	<td align="left">
																		<table border=0 cellspacing=0 cellpadding=0 width="100%" >
																			<tr>
																				<td valign="top"  class="smalltxt">{$PDFMODULLANGUAGEPO.LBL_LANGUAGES}</td>
																			</tr>
																			<tr>
																				<td class="small" valign="top" >
																					<table width="100%"  border="0" cellspacing="0" cellpadding="5">
																						<tr valign="top">
																							<td class="smalltxt" width="50%">
																								<select class="detailedViewTextBox" style="width:30%;" id="PurchaseOrder_pdflang_pv" name="PurchaseOrder_pdflang_pv" {$CHANGEPERMISSION.PurchaseOrder.pdflang}>
																									{html_options values=$LANGUAGEKEYS.PurchaseOrder output=$LANGUAGES.PurchaseOrder selected=$LANGSELECTED.PurchaseOrder}
																								</select>
																							</td>
																							{if $MODULEVIEW==1} 
																							<td class="smalltxt" align="right" >
																								<input type="checkbox" id="PurchaseOrder_pdflang_perm"  name="PurchaseOrder_pdflang_perm" {$EDITPERMISSION.PurchaseOrder.pdflang} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																							</td>
																							{/if}
																						</tr>
																					</table>
																				</td>
																			</tr>
																		</table>
																		<table border=0 cellspacing=0 cellpadding=0 width="100%" >
																			<tr>
																				<td valign="top"  class="smalltxt">{$PDFMODULLANGUAGEPO.LBL_PAPERFORMAT}</td>
																			</tr>
																			<tr>
																				<td class="small" valign="top" >
																					<table width="100%"  border="0" cellspacing="0" cellpadding="5">
																						<tr valign="top">
																							<td class="smalltxt" width="50%">
																								<select class="detailedViewTextBox" style="width:30%;" id="PurchaseOrder_paperf_pv" name="PurchaseOrder_paperf_pv" {$CHANGEPERMISSION.PurchaseOrder.paperf}>
																									{html_options values=$PAPERFORMAT.PurchaseOrder output=$PAPERFORMAT.PurchaseOrder selected=$PAPERSELECTED.PurchaseOrder}
																								</select>
																							</td>
																							{if $MODULEVIEW==1} 
																							<td class="smalltxt" align="right" >
																								<input type="checkbox" id="PurchaseOrder_paperf_perm"  name="PurchaseOrder_paperf_perm" {$EDITPERMISSION.PurchaseOrder.paperf} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																							</td>
																							{/if}
																						</tr>
																					</table>
																				</td>
																			</tr>
																		</table>
																		<table border=0 cellspacing=0 cellpadding=0 width="100%" >
																			<tr>
																				<td valign="top" class="smalltxt">{$PDFMODULLANGUAGEPO.LBL_PDF_CONFIGURATOR_FONTS}</td>
																			</tr>
																			<tr>
																				<td class="small" valign="top" width="100%">
																					<table width="100%"  border="0" cellspacing="0" cellpadding="5">
																						<tr valign="top">
																							<td  class="smalltxt" width="80%">
																								<select id="PurchaseOrder_fontid_pv" name="PurchaseOrder_fontid_pv" class="detailedViewTextBox"  style="width:40%;" {$CHANGEPERMISSION.PurchaseOrder.fontid}>
																									{html_options selected=$SELECTEDFONTID.PurchaseOrder size=1 values=$FONTIDS.PurchaseOrder output=$FONTLIST.PurchaseOrder }
																								</select>
																							</td>
																							{if $MODULEVIEW==1} 
																							<td class="smalltxt" align="right" width="20%">
																								<input type="checkbox" name="PurchaseOrder_fontid_perm" id="PurchaseOrder_fontid_perm" {$EDITPERMISSION.PurchaseOrder.fontid} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																							</td>
																							{/if}
																						</tr>
																					</table>
																				</td>
																			</tr>
																		</table>
																		<table border=0 cellspacing=0 cellpadding=0 width="100%" class="listRow">
																			<tr>
																				<td align="left" valign="top" class="smalltxt">{$PDFMODULLANGUAGEPO.LBL_PDF_CONFIGURATOR_FONTSSIZE}</td>
																			</tr>
																			<tr>
																				<td align='left'>
																					<table width="100%"  border="0" cellspacing="0" cellpadding="5">
																						<tr>
																							<td valign="top" class="smalltxt"><b>{$PDFMODULLANGUAGEPO.LBL_PDF_CONFIGURATOR_FONTSSIZE_HEADER}</b>&nbsp;&nbsp;
																							{if $MODULEVIEW==1} 
																								<input type="checkbox" id="PurchaseOrder_fontsizeheader_perm" name="PurchaseOrder_fontsizeheader_perm" {$EDITPERMISSION.PurchaseOrder.fontsizeheader} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																							{/if}
																							</td>
																							<td valign="top" class="smalltxt"><b>{$PDFMODULLANGUAGEPO.LBL_PDF_CONFIGURATOR_FONTSSIZE_ADDRESS}</b>&nbsp;&nbsp;
																							{if $MODULEVIEW==1} 
																								<input type="checkbox" id="PurchaseOrder_fontsizeaddress_perm" name="PurchaseOrder_fontsizeaddress_perm" {$EDITPERMISSION.PurchaseOrder.fontsizeaddress} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																							{/if}
																							</td>
																							<td valign="top" class="smalltxt"><b>{$PDFMODULLANGUAGEPO.LBL_PDF_CONFIGURATOR_FONTSSIZE_BODY}</b>&nbsp;&nbsp;
																							{if $MODULEVIEW==1} 
																								<input type="checkbox" id="PurchaseOrder_fontsizebody_perm" name="PurchaseOrder_fontsizebody_perm"{$EDITPERMISSION.PurchaseOrder.fontsizebody} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																							{/if}
																							</td>
																							<td valign="top" class="smalltxt"><b>{$PDFMODULLANGUAGEPO.LBL_PDF_CONFIGURATOR_FONTSSIZE_FOOTER}</b>&nbsp;&nbsp;
																							{if $MODULEVIEW==1} 
																								<input type="checkbox" id="PurchaseOrder_fontsizefooter_perm" name="PurchaseOrder_fontsizefooter_perm" {$EDITPERMISSION.PurchaseOrder.fontsizefooter} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																							{/if}
																							</td>
																						</tr>
																						<tr valign="top">
																								<td class="smalltxt">
																									<select name="PurchaseOrder_fontsizeheader_pv" class="detailedViewTextBox"  style="width:25%;" id="PurchaseOrder_fontsizeheader_pv" {$CHANGEPERMISSION.PurchaseOrder.fontsizeheader}>
																										{html_options values=$FONTSIZEAVAILABLE output=$FONTSIZEAVAILABLE selected=$FONTSIZEHEADER.PurchaseOrder}
																									</select>
																								</td>
																								<td  class="smalltxt">
																									<select name="PurchaseOrder_fontsizeaddress_pv" class="detailedViewTextBox"  style="width:25%;" id="PurchaseOrder_fontsizeaddress_pv" {$CHANGEPERMISSION.PurchaseOrder.fontsizeaddress}>
																										{html_options values=$FONTSIZEAVAILABLE output=$FONTSIZEAVAILABLE selected=$FONTSIZEADDRESS.PurchaseOrder}
																									</select>
																								</td>
																								<td  class="smalltxt">
																									<select name="PurchaseOrder_fontsizebody_pv" class="detailedViewTextBox"  style="width:25%;" id="PurchaseOrder_fontsizebody_pv" {$CHANGEPERMISSION.PurchaseOrder.fontsizebody}>
																										{html_options values=$FONTSIZEAVAILABLE output=$FONTSIZEAVAILABLE selected=$FONTSIZEBODY.PurchaseOrder}
																									</select>
																								</td>
																								<td  class="smalltxt">
																									<select name="PurchaseOrder_fontsizefooter_pv" class="detailedViewTextBox" style="width:25%;" id="PurchaseOrder_fontsizefooter_pv"{$CHANGEPERMISSION.PurchaseOrder.fontsizefooter}>
																										{html_options values=$FONTSIZEAVAILABLE output=$FONTSIZEAVAILABLE selected=$FONTSIZEFOOTER.PurchaseOrder}
																									</select>
																								</td>
																						</tr>
																					</table>
																				</td>
																			</tr>
																		</table>
																	</td>
													            </tr>
													        </table>
															<table border=0 cellspacing=0 cellpadding=10 width="100%" class="listRow">
																<tr>
																	<td align="left">
																		<table align="" border=0 cellspacing=0 cellpadding=0 width="100%" >
																			<tr>
																				<td valign="top" width="80%" class="smalltxt">{$PDFMODULLANGUAGEPO.LBL_PRINT_LOGO}&nbsp;&nbsp;
																					<input type="checkbox" id="PurchaseOrder_logoradio_pc" name="PurchaseOrder_logoradio_pc" {$LOGORADIO.PurchaseOrder} {$CHANGEPERMISSION.PurchaseOrder.logoradio}> 
																				</td>
																				{if $MODULEVIEW==1} 
																				<td class="smalltxt" width="20%" align="right">
																					<input type="checkbox" id="PurchaseOrder_logoradio_perm" name="PurchaseOrder_logoradio_perm" {$EDITPERMISSION.PurchaseOrder.logoradio} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top"  width="80%" class="smalltxt">{$PDFMODULLANGUAGEPO.LBL_PDF_DATE}&nbsp;&nbsp;
																					<select name="PurchaseOrder_dateused_pv" class="detailedViewTextBox"  style="width:20%;" id="PurchaseOrder_dateused_pv" {$CHANGEPERMISSION.PurchaseOrder.dateused}>
																						{html_options values=$DATEUSED.PurchaseOrder output= $DATEUSEDNAME selected=$DATEUSEDSELECTED.PurchaseOrder}
																					</select>
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt"  width="20%" align="right">
																					<input type="checkbox" name="PurchaseOrder_dateused_perm" id="PurchaseOrder_dateused_perm" {$EDITPERMISSION.PurchaseOrder.dateused} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top" width="80%" class="smalltxt">{$PDFMODULLANGUAGEPO.LBL_OWNER}&nbsp;&nbsp;
																					<input type="checkbox" id="PurchaseOrder_owner_pc" name="PurchaseOrder_owner_pc" {$OWNER.PurchaseOrder} {$CHANGEPERMISSION.PurchaseOrder.owner}> 
																				</td>
																				{if $MODULEVIEW==1} 
																				<td class="smalltxt" width="20%" align="right">
																					<input type="checkbox" id="PurchaseOrder_owner_perm" name="PurchaseOrder_owner_perm" {$EDITPERMISSION.PurchaseOrder.owner} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top" width="80%" class="smalltxt">{$PDFMODULLANGUAGEPO.LBL_OWNER_PH}&nbsp;&nbsp;
																					<input type="checkbox" id="PurchaseOrder_ownerphone_pc" name="PurchaseOrder_ownerphone_pc" {$OWNERPHONE.PurchaseOrder} {$CHANGEPERMISSION.PurchaseOrder.ownerphone}> 
																				</td>
																				{if $MODULEVIEW==1} 
																				<td class="smalltxt" width="20%" align="right">
																					<input type="checkbox" id="PurchaseOrder_ownerphone_perm" name="PurchaseOrder_ownerphone_perm" {$EDITPERMISSION.PurchaseOrder.ownerphone} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top" width="80%" class="smalltxt">{$PDFMODULLANGUAGEPO.LBL_REQUISITION}&nbsp;&nbsp;
																					<input type="checkbox" id="PurchaseOrder_poname_pc" name="PurchaseOrder_poname_pc" {$PONAME.PurchaseOrder} {$CHANGEPERMISSION.PurchaseOrder.poname}> 
																				</td>
																				{if $MODULEVIEW==1} 
																				<td class="smalltxt" width="20%" align="right">
																					<input type="checkbox" id="PurchaseOrder_poname_perm" name="PurchaseOrder_poname_perm" {$EDITPERMISSION.PurchaseOrder.poname} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top" width="80%" class="smalltxt">{$PDFMODULLANGUAGEPO.LBL_CARRIER}&nbsp;&nbsp;
																					<input type="checkbox" id="PurchaseOrder_carrier_pc" name="PurchaseOrder_carrier_pc" {$CARRIER.PurchaseOrder} {$CHANGEPERMISSION.PurchaseOrder.carrier}> 
																				</td>
																				{if $MODULEVIEW==1} 
																				<td class="smalltxt" width="20%" align="right">
																					<input type="checkbox" id="PurchaseOrder_carrier_perm" name="PurchaseOrder_carrier_perm" {$EDITPERMISSION.PurchaseOrder.carrier} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top" width="80%" class="smalltxt">{$PDFMODULLANGUAGEPO.LBL_VENDORID}&nbsp;&nbsp;
																					<input type="checkbox" id="PurchaseOrder_clientid_pc" name="PurchaseOrder_clientid_pc" {$CLIENTID.PurchaseOrder} {$CHANGEPERMISSION.PurchaseOrder.clientid}> 
																				</td>
																				{if $MODULEVIEW==1} 
																				<td class="smalltxt" width="20%" align="right">
																					<input type="checkbox" id="PurchaseOrder_clientid_perm" name="PurchaseOrder_clientid_perm" {$EDITPERMISSION.PurchaseOrder.clientid} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top"  width="80%" class="smalltxt">{$PDFMODULLANGUAGEPO.LBL_PDF_CONFIGURATOR_SPACE_HEADER}&nbsp;&nbsp;
																					<select name="PurchaseOrder_spaceheadline_pv" class="detailedViewTextBox"   style="width:7%;" id="PurchaseOrder_spaceheadline_pv" {$CHANGEPERMISSION.PurchaseOrder.spaceheadline}>
																						{html_options values=$HEADERSPACE output=$HEADERSPACE selected=$HEADERSPACESELECTED}
																					</select>
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt"  width="20%" align="right">
																					<input type="checkbox" name="PurchaseOrder_spaceheadline_perm" id="PurchaseOrder_spaceheadline_perm"  {$EDITPERMISSION.PurchaseOrder.spaceheadline} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top"  width="80%" class="smalltxt">{$PDFMODULLANGUAGEPO.LBL_PRINT_FOOTER}&nbsp;&nbsp;
																					<input type="checkbox" name="PurchaseOrder_footerradio_pc" id="PurchaseOrder_footerradio_pc" {$FOOTERRADIO.PurchaseOrder} {$CHANGEPERMISSION.PurchaseOrder.footerradio}> 
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt"  width="20%" align="right">
																					<input type="checkbox" name="PurchaseOrder_footerradio_perm" id="PurchaseOrder_footerradio_perm"  {$EDITPERMISSION.PurchaseOrder.footerradio} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top"  width="80%" class="smalltxt">{$PDFMODULLANGUAGEPO.LBL_PRINT_FOOTERPAGE}&nbsp;&nbsp;
																					<input type="checkbox" name="PurchaseOrder_pageradio_pc" id="PurchaseOrder_pageradio_pc" {$FOOTERPAGERADIO.PurchaseOrder} {$CHANGEPERMISSION.PurchaseOrder.pageradio}> 
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt" align="right">
																					<input type="checkbox" name="PurchaseOrder_pageradio_perm" id="PurchaseOrder_pageradio_perm"  {$EDITPERMISSION.PurchaseOrder.pageradio} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																		</table>
																	</td>
													            </tr>
													        </table>
															<table border=0 cellspacing=0 cellpadding=10 width="100%" class="listRow">
																<tr>
																	<td align="left">
																		<table align="" border=0 cellspacing=0 cellpadding=0 width="100%" >
																			<tr>
																				<td valign="top" class="smalltxt">{$PDFMODULLANGUAGEPO.LBL_PDF_CONFIGURATOR_SUMMARY}&nbsp;&nbsp;
																					<input type="checkbox" name="PurchaseOrder_summaryradio_pc" id="PurchaseOrder_summaryradio_pc" {$SUMMARYRADIO.PurchaseOrder} {$CHANGEPERMISSION.PurchaseOrder.summaryradio}> 
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt" align="right">
																					<input type="checkbox" name="PurchaseOrder_summaryradio_perm" id="PurchaseOrder_summaryradio_perm"  {$EDITPERMISSION.PurchaseOrder.summaryradio} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																		</table>
																	</td>
													            </tr>
													        </table>
														</div>
														<div class="dhtmlgoodies_aTab"> 
															<table border=0 cellspacing=0 cellpadding=10 width="100%" class="listRow">
																<tr>
																	<td align="left">
																		<table border=0 cellspacing=0 cellpadding=0 width="100%">
																			<tr>
																				<td valign="top" class="smalltxt">{$PDFMODULLANGUAGEPO.LBL_PDF_CONFIGURATOR_ROWS}</td>
																			</tr>
																		</table><br>
																		<table border=0 cellspacing=0 cellpadding=0 width="100%" >
																			<tr valign="top">
																				<td  valign="top" class="smalltxt">{$PDFLANGUAGEARRAYPO.TAX_GROUP}</td>
																			</tr>
																			<tr valign="top">
																				<td width="90%">
																					<table border=0 cellspacing=0 cellpadding=3 frame="below" width="100%" class="table thread th">
																						<tr >
																							{foreach key =checkboxtype item =grouptax from=$COLUMNCONFIGURATIONGROUP.PurchaseOrder} 
																								<td  class="bigtxt" valign="top" id="PurchaseOrder.{$grouptax.taxtype}.{$checkboxtype}">
																									{if ($grouptax.selected == 'checked="checked"' and $grouptax.enabled == '1')}
																										{$PDFLANGUAGEARRAYPO.$checkboxtype}
																									{/if}
																								</td>
																								<td>&nbsp;</td>
																							{/foreach}
																							{if $MODULEVIEW==1}
																							<td  class="smalltxt" align="right">
																								<input type="checkbox" name="PurchaseOrder_gcolumns_perm" id="PurchaseOrder_gcolumns_perm"  {$EDITPERMISSION.PurchaseOrder.gcolumns} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																							</td>
																							{/if}
																						</tr>
																					</table>
																				</td>
																				<td id="qcheckboxes_group"  valign="top" class="smalltxt" width="20%">
																					{foreach key =checkboxtype item =grouptax from=$COLUMNCONFIGURATIONGROUP.PurchaseOrder}
																						{if $grouptax.enabled == '1'}
																							<input type="checkbox" name="PurchaseOrder.{$grouptax.taxtype}.{$checkboxtype}" id="{$checkboxtype}" {$grouptax.active} {$grouptax.selected} {$CHANGEPERMISSION.PurchaseOrder.gcolumns} onclick="preview(this,'PurchaseOrder.{$grouptax.taxtype}.{$checkboxtype}','{$PDFLANGUAGEARRAYPO.$checkboxtype}');">&nbsp;{$PDFLANGUAGEARRAYPO.$checkboxtype}<br>
																						{/if}
																					{/foreach}
																				</td>
																			</tr>
																		</table>
																	</td>
																</tr>
															</table>
															<table border=0 cellspacing=0 cellpadding=10 width="100%" class="listRow">
																<tr>
																	<td align="left">
																		<table align="" border=0 cellspacing=0 cellpadding=0 width="100%" >
																			<tr>
																				<td valign="top" class="smalltxt">{$PDFMODULLANGUAGEPO.LBL_PDF_DESCRIPTION_CONTENT}
																				</td>
																			</tr>
																			<tr>
																				<td valign="top" class="smalltxt">
																					{$PDFMODULLANGUAGEPO.LBL_PDF_DESCRIPTION_CONTENT_NAME}&nbsp;&nbsp; 
																					<input type="checkbox" name="PurchaseOrder_gprodname_pc"  id="PurchaseOrder_gprodname_pc"  {$GPRODDETAILS.PurchaseOrder.0} {$CHANGEPERMISSION.PurchaseOrder.gprodname}>
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt" align="right">
																					<input type="checkbox" name="PurchaseOrder_gprodname_perm" id="PurchaseOrder_gprodname_perm"  {$EDITPERMISSION.PurchaseOrder.gprodname} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top" class="smalltxt">
																					{$PDFMODULLANGUAGEPO.LBL_PDF_DESCRIPTION_CONTENT_DESCRIPTION}&nbsp;&nbsp;
																					<input type="checkbox" name="PurchaseOrder_gproddes_pc"  id="PurchaseOrder_gproddes_pc"  {$GPRODDETAILS.PurchaseOrder.1} {$CHANGEPERMISSION.PurchaseOrder.gproddes}>
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt" align="right">
																					<input type="checkbox" name="PurchaseOrder_gproddes_perm" id="PurchaseOrder_gproddes_perm"  {$EDITPERMISSION.PurchaseOrder.gproddes} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top" class="smalltxt">
																					{$PDFMODULLANGUAGEPO.LBL_PDF_DESCRIPTION_CONTENT_COMMENT}&nbsp;&nbsp;
																					<input type="checkbox" name="PurchaseOrder_gprodcom_pc"  id="PurchaseOrder_gprodcom_pc"  {$GPRODDETAILS.PurchaseOrder.2} {$CHANGEPERMISSION.PurchaseOrder.gprodcom}>
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt" align="right">
																					<input type="checkbox" name="PurchaseOrder_gprodcom_perm" id="PurchaseOrder_gprodcom_perm"  {$EDITPERMISSION.PurchaseOrder.gprodcom} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																		</table>
																	</td>
													            </tr>
													        </table>
														</div>
														<div class="dhtmlgoodies_aTab"> 
															<table border=0 cellspacing=0 cellpadding=10 width="100%" class="listRow">
																<tr>
																	<td align="left">
																		<table border=0 cellspacing=0 cellpadding=0 width="100%" >
																			<tr>
																				<td valign="top" class="smalltxt">{$PDFMODULLANGUAGEPO.LBL_PDF_CONFIGURATOR_ROWS}</td>
																			</tr>
																		</table><br>
																		<table border=0 cellspacing=0 cellpadding=0 width="100%" >
																			<tr valign="top">
																				<td valign="top" class="smalltxt">{$PDFLANGUAGEARRAYPO.TAX_INDIVIDUAL}</td>
																			</tr>
																			<tr valign="top">
																				<td width="90%">
																					<table border=0 cellspacing=0 cellpadding=3 frame="below" width="100%" class="table thread th">
																						<tr >
																							{foreach key =checkboxtypei item =indivitax from=$COLUMNCONFIGURATIONINDIVIDUAL.PurchaseOrder}
																								<td  class="bigtxt" valign="top" id="PurchaseOrder.{$indivitax.taxtype}.{$checkboxtypei}">
																									{if ($indivitax.selected == 'checked="checked"' and $indivitax.enabled == '1')}
																										{$PDFLANGUAGEARRAYPO.$checkboxtypei}
																									{/if}
																								</td>
																								<td>&nbsp;</td>
																							{/foreach}
																							{if $MODULEVIEW==1}
																							<td class="smalltxt" align="right">
																								<input type="checkbox" name="PurchaseOrder_icolumns_perm" id="PurchaseOrder_icolumns_perm"  {$EDITPERMISSION.PurchaseOrder.icolumns} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																							</td>
																							{/if}
																						</tr>
																					</table>
																				</td>
																				<td id="PurchaseOrder_checkboxes_individual"  valign="top" class="smalltxt" width="20%">
																					{foreach key =checkboxtypei item =indivitax from=$COLUMNCONFIGURATIONINDIVIDUAL.PurchaseOrder}
																						{if $indivitax.enabled == '1'}
																							<input type="checkbox" name="PurchaseOrder.{$indivitax.taxtype}.{$checkboxtypei}" id="{$checkboxtypei}" {$indivitax.active} {$indivitax.selected} {$CHANGEPERMISSION.PurchaseOrder.icolumns} onclick="preview(this,'PurchaseOrder.{$indivitax.taxtype}.{$checkboxtypei}','{$PDFLANGUAGEARRAYPO.$checkboxtypei}');">&nbsp;{$PDFLANGUAGEARRAYPO.$checkboxtypei}<br>
																						{/if}
																					{/foreach}
																				</td>
																			</tr>
																		</table>
																	</td>
																</tr>
															</table>
															<table border=0 cellspacing=0 cellpadding=10 width="100%" class="listRow">
																<tr>
																	<td align="left">
																		<table align="" border=0 cellspacing=0 cellpadding=0 width="100%" >
																			<tr>
																				<td valign="top" class="smalltxt">{$PDFMODULLANGUAGEPO.LBL_PDF_DESCRIPTION_CONTENT}
																				</td>
																			</tr>
																			<tr>
																				<td valign="top" class="smalltxt">
																					{$PDFMODULLANGUAGEPO.LBL_PDF_DESCRIPTION_CONTENT_NAME}&nbsp;&nbsp;
																					<input type="checkbox" name="PurchaseOrder_iprodname_pc"  id="PurchaseOrder_iprodname_pc"  {$IPRODDETAILS.PurchaseOrder.0} {$CHANGEPERMISSION.PurchaseOrder.iprodname}>
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt" align="right">
																					<input type="checkbox" name="PurchaseOrder_iprodname_perm" id="PurchaseOrder_iprodname_perm"  {$EDITPERMISSION.PurchaseOrder.iprodname} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top" class="smalltxt">
																					{$PDFMODULLANGUAGEPO.LBL_PDF_DESCRIPTION_CONTENT_DESCRIPTION}&nbsp;&nbsp;
																					<input type="checkbox" name="PurchaseOrder_iproddes_pc"  id="PurchaseOrder_iproddes_pc"  {$IPRODDETAILS.PurchaseOrder.1} {$CHANGEPERMISSION.PurchaseOrder.iproddes}>
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt" align="right">
																					<input type="checkbox" name="PurchaseOrder_iproddes_perm" id="PurchaseOrder_iproddes_perm" {$EDITPERMISSION.PurchaseOrder.iproddes} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																			<tr>
																				<td valign="top" class="smalltxt">
																					{$PDFMODULLANGUAGEPO.LBL_PDF_DESCRIPTION_CONTENT_COMMENT}&nbsp;&nbsp;
																					<input type="checkbox" name="PurchaseOrder_iprodcom_pc"  id="PurchaseOrder_iprodcom_pc"  {$IPRODDETAILS.PurchaseOrder.2} {$CHANGEPERMISSION.PurchaseOrder.iprodcom}>
																				</td>
																				{if $MODULEVIEW==1}
																				<td class="smalltxt" align="right">
																					<input type="checkbox" name="PurchaseOrder_iprodcom_perm" id="PurchaseOrder_iprodcom_perm"  {$EDITPERMISSION.PurchaseOrder.iprodcom} >&nbsp;{vtranslate('LBL_PDFCONFIGURATOR_ENABLE',$MODULE)}
																				</td>
																				{/if}
																			</tr>
																		</table>
																	</td>
													            </tr>
													        </table>
														</div>
													</div>
												</td>
											</tr>
										</table>
										<br><br><br>
										<table>
											<tr>
												<td class="small" align='left' nowrap width="100%">
													<input class="btn  btn-success" id="savepo" name="savepo" title="{vtranslate('LBL_SAVE',$MODULE)}"   type="submit" value="{vtranslate('LBL_SAVE',$MODULE)}">&nbsp;
													<input class="btn" id="cancelpo" name="cancelpo" title="{vtranslate('LBL_CANCEL',$MODULE)}"  onclick="disableFields(pdfsettings);" type="button"  value="{vtranslate('LBL_CANCEL',$MODULE)}">
												</td>
											</tr>
										</table>
										<script type="text/javascript">
											initTabs('configurationtabs_po',Array(pdfconfig_arr.TAB_GENERAL,pdfconfig_arr.TAB_GROUP,pdfconfig_arr.TAB_INDIVIDUAL),0,850,640);
										</script>
									</div>
								</td>
							</tr>
						</table>
						<br>
						<br>
					</div>
					<br />
				</div>
			</td>
		</tr>
	</table>
</form>
    </div>
</div>
<br />
<div class="feedFrame">
</div>
{/strip}
<script>
{literal}

function tableswitch(modules)
{
	var option=['Quotes','Invoice','SalesOrder','PurchaseOrder'];
	for(var i=0; i<option.length; i++) { 
		obj=document.getElementById(option[i]);
		obj.style.display=(option[i]==modules) && !(obj.style.display=="block")? "block" : "none"; 
	}
}
</script>
<script>
function preview(checkbox,id,columnname) {
	if(checkbox.checked == true) 	{
		document.getElementById(id).innerHTML=columnname;
		document.getElementById(id).checked='true';
		if (document.getElementById) { // DOM3 = IE5,6,7, NS6
			document.getElementById(id).style.display = 'block';
		}
		else {
			if (document.layers) { // Netscape 4
				document.id.display = 'block';
			}
			else { // IE 4
				document.all.id.style.display = 'block';
			}
		}
	}
	else {
		document.getElementById(id).innerHTML=' ';
		document.getElementById(id).checked='false';
		if (document.getElementById) { // DOM3 = IE5, NS6
			document.getElementById(id).style.display = 'none';
		}
		else {
			if (document.layers) { // Netscape 4
				document.id.display = 'none';
			}
			else { // IE 4
					document.all.id.style.display = 'none';
			}
		}
	}
}

function enableFields(form_id) {
	var f = form_id.getElementsByTagName('select');
	for(var i=0;i<f.length;i++){
		f[i].removeAttribute('disabled');
	}
	var g = document.getElementsByTagName('input');
	for(var i=0;i<g.length;i++){
		if(g[i].getAttribute('type')=='checkbox'){
		var t = g[i].innerHTML;
		if (g[i].id != 'Description' & g[i].id != 'Qty' & g[i].id != 'UnitPrice' & g[i].id != 'LineTotal' & g[i].id != 'Tax')
			g[i].removeAttribute('disabled');
		}
	}
	document.getElementById('edit').style.visibility='hidden';
	document.getElementById('saveg').style.visibility='visible'; 
	document.getElementById('cancelg').style.visibility='visible';
	document.getElementById('savei').style.visibility='visible'; 
	document.getElementById('canceli').style.visibility='visible';
	document.getElementById('saveso').style.visibility='visible'; 
	document.getElementById('cancelso').style.visibility='visible';
	document.getElementById('savepo').style.visibility='visible'; 
	document.getElementById('cancelpo').style.visibility='visible';
}

function disableFields(form_id) {
	var f = form_id.getElementsByTagName('select');
	for(var i=0;i<f.length;i++){
		if (f[i]!=document.getElementById('displaymodul'))
		f[i].setAttribute('disabled',true)
	}
	var g = document.getElementsByTagName('input');
	for(var i=0;i<g.length;i++){
		if(g[i].getAttribute('type')=='checkbox'){
			g[i].setAttribute('disabled',true)
		}
	}
	document.getElementById('edit').style.visibility='visible';
	document.getElementById('saveg').style.visibility='hidden'; 
	document.getElementById('cancelg').style.visibility='hidden';
	document.getElementById('savei').style.visibility='hidden'; 
	document.getElementById('canceli').style.visibility='hidden';
	document.getElementById('saveso').style.visibility='hidden'; 
	document.getElementById('cancelso').style.visibility='hidden';
	document.getElementById('savepo').style.visibility='hidden'; 
	document.getElementById('cancelpo').style.visibility='hidden';
}

function setinitial(allmenues) {
	var option=['Quotes','Invoice','SalesOrder','PurchaseOrder']; // nach belieben fortsetzen ...
	for(var i=0; i<allmenues.length; i++) { 
		obj=allmenues.options[i];
		if (obj.selected==true) {
			tableswitch(option[i]);
		}
	}
}


</script>
{/literal}
{if $MODULEVIEW !=1}
{literal}
<script>window.onload = function() {eval(setinitial(document.getElementById('displaymodul')));}</script>
{/literal}
{/if}

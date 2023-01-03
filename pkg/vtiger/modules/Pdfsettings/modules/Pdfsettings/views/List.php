<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * Modified by crm-now, www.crm-now.de
 * All Rights Reserved.
 *************************************************************************************/
require_once 'modules/Pdfsettings/helpers/PDFutils.php';
/**
 * Pdfsettings ListView Model Class
 */

class Pdfsettings_List_View extends Vtiger_Index_View {
    
	function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);

		$currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		if(!$currentUserPriviligesModel->hasModulePermission($moduleModel->getId())) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
	}
	//do nothing as preProcess
	function preProcess(Vtiger_Request $request, $display=true) {
		parent::preProcess($request);
	}
    
    function preProcessTplName(Vtiger_Request $request) {
		return 'ListViewPreProcess.tpl';
	}
    
    function process (Vtiger_Request $request) {
		$viewer = $this->getViewer ($request);
		$moduleName = $request->getModule();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

		$this->initializeListViewContents($request, $viewer);
		
		$current_user = Users_Record_Model::getCurrentUserModel();
		$current_language = $current_user->get('language');
		
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('CURRENT_USER_MODEL', $current_user);
		$viewer->assign('LANGUAGE', $current_language);
		
		$viewer->view('ListViewContents.tpl', $moduleName);
	}

    function postProcess(Vtiger_Request $request) {
        $viewer = $this->getViewer ($request);
		$moduleName = $request->getModule();

		$viewer->view('ListViewPostProcess.tpl', $moduleName);
		parent::postProcess($request);
    }
    /*
	 * Function to initialize the required data in smarty to display the List View Contents
	 */
	public function initializeListViewContents(Vtiger_Request $request, Vtiger_Viewer $viewer) {
		global $default_language, $current_language, $current_user;
		$module = $request->getModule();
		$moduleModel = Vtiger_Module_Model::getInstance($module);
 		//get language file for PDF output
		$pdf_language_array_quotes = return_module_language_pdf($current_language, 'Quotes');
		$module_language_array_quotes = return_specific_language_pdf($current_language, 'Quotes');
		$pdf_language_array_invoices = return_module_language_pdf($current_language, 'Invoice');
		$module_language_array_invoices = return_specific_language_pdf($current_language, 'Invoice');
		$pdf_language_array_so = return_module_language_pdf($current_language, 'SalesOrder');
		$module_language_array_so = return_specific_language_pdf($current_language, 'SalesOrder');
		$pdf_language_array_po = return_module_language_pdf($current_language, 'PurchaseOrder');
		$module_language_array_po = return_specific_language_pdf($current_language, 'PurchaseOrder');

		$field_module=array('Quotes'=>'Quotes','Invoice'=>'Invoice','SalesOrder'=>'SalesOrder','PurchaseOrder'=>'PurchaseOrder');
		$allfields=Array();
		$adb = PearDatabase::getInstance();
		//get all the settings
		foreach($field_module as $fld_module) {
			//get all the settings for each module
			$language_strings[$fld_module] = return_module_language($current_language,$fld_module);
			$allfields[$fld_module] =getPDFFieldList($fld_module);
			//get PDF permissions from Settings
			$pdfsettings_query="select * from berli_pdfsettings where pdfmodul='".$fld_module."'";
			$pdfsettings = $adb->pquery($pdfsettings_query,array());
			$noofpickrows = $adb->num_rows($pdfsettings);
			for($j = 0; $j < $noofpickrows; $j++) {
				if ($adb->query_result($pdfsettings,$j,'pdfeditable')==1 and $current_user->is_admin !='on'){
					$pdfpermission[$fld_module][$adb->query_result($pdfsettings,$j,'pdffieldname')]= 'checked="checked"';
					$pdfactive[$fld_module][$adb->query_result($pdfsettings,$j,'pdffieldname')]= 'disabled';
				}
				else {
					$pdfpermission[$fld_module][$adb->query_result($pdfsettings,$j,'pdffieldname')]= '';
					$pdfactive[$fld_module][$adb->query_result($pdfsettings,$j,'pdffieldname')]= '';
				}
			}
			$pdf_details[$fld_module] = getAllPDFDetails($fld_module);
			
			//get the paper format settings
			$supported_formats = array('A4','US letter');
			$paperformat [$fld_module][0] = $supported_formats[0] ;
			$paperformat [$fld_module][1] = $supported_formats[1] ;
			$paperformat_sel[$fld_module] = $pdf_details[$fld_module]['paperf'];

			//language
			$pdflanguage_selected [$fld_module] = $pdf_details[$fld_module]['pdflang'];
			if ($pdflanguage_selected[$fld_module] =='') {
				$pdflanguage_selected[$fld_module] = 'de_de';
			}
			//get available languages based on existing language files
			$pdflanguages[$fld_module] = getAllPDFlanguages ($fld_module);
			$pdflanguage_keys[$fld_module] = array();
			foreach ($pdflanguages[$fld_module] as $key=>$value) {
				$pdflanguage_keys[$fld_module][] = $key;
			}
			// general information
			//font type
			$selected_fontid[$fld_module] = $pdf_details[$fld_module]['fontid'];
			// available fonts
			$pdf_fonts = getAllPDFFonts();
			for($j=0;$j<count($pdf_fonts);$j++) {
				$available_fontid[$fld_module][$j] = $pdf_fonts[$j]['fontid'];
				$available_tcpdfname[$fld_module][$j] = $pdf_fonts[$j]['tcpdfname'];
				$available_namedisplay[$fld_module][$j] = $pdf_fonts[$j]['namedisplay'];
			}
			//get font sizes: currently different font sizes for header, address in header, body and footer
			//the option do modify the font sizes is limited to 6 - 14
			$fontsizes_available = array(6,7,8,9,10,11,12,13,14);
			$fontsize_body[$fld_module] = $pdf_details[$fld_module]['fontsizebody'];
			If ($fontsize_body[$fld_module] =='') $fontsize_body[$fld_module]= 9;
			$fontsize_header[$fld_module] = $pdf_details[$fld_module]['fontsizeheader'];
			If ($fontsize_header[$fld_module] =='') $fontsize_header[$fld_module]= 9;
			$fontsize_footer[$fld_module] = $pdf_details[$fld_module]['fontsizefooter'];
			If ($fontsize_footer[$fld_module] =='') $fontsize_footer[$fld_module]= 9;
			$fontsize_address[$fld_module] = $pdf_details[$fld_module]['fontsizeaddress'];
			If ($fontsize_address[$fld_module] =='') $fontsize_address[$fld_module]= 9;


			//get date information 0->today, 1->created, 2->modified 
			$dateused_name = array ($module_language_array_quotes['today'],$module_language_array_quotes['created'],$module_language_array_quotes['modified']);
			$dateused_available[$fld_module] = array(0,1,2);
			$dateused_selected[$fld_module] = $pdf_details[$fld_module]['dateused'];
			If ($dateused_selected[$fld_module] =='') $dateused_selected[$fld_module]= 0;

			//get header space information
			$headerspace_available = array(0,1,2);
			$headerspace_selected[$fld_module] = $pdf_details[$fld_module]['space_headline'];

			//get logo information
			$logoradio[$fld_module] = $pdf_details[$fld_module]['logoradio'];
			If ($logoradio[$fld_module] =='') $logoradio[$fld_module]= 'true';
			If ($logoradio[$fld_module] =='true') $logo_selection[$fld_module] = 'checked="checked"';
			else $logo_selection[$fld_module] = "";

			//get owner information
			$owner[$fld_module] = $pdf_details[$fld_module]['owner'];
			If ($owner[$fld_module] =='') $owner[$fld_module]= 'true';
			If ($owner[$fld_module] =='true') $owner_selection[$fld_module] = 'checked="checked"';
			else $owner_selection[$fld_module] = "";

			//get oenerphone information
			$ownerphone[$fld_module] = $pdf_details[$fld_module]['ownerphone'];
			If ($ownerphone[$fld_module] =='') $ownerphone[$fld_module]= 'true';
			If ($ownerphone[$fld_module] =='true') $ownerphone_selection[$fld_module] = 'checked="checked"';
			else $ownerphone_selection[$fld_module] = "";

			//get clientid information
			$clientid[$fld_module] = $pdf_details[$fld_module]['clientid'];
			If ($clientid[$fld_module] =='') $clientid[$fld_module]= 'true';
			If ($clientid[$fld_module] =='true') $clientid_selection[$fld_module] = 'checked="checked"';
			else $clientid_selection[$fld_module] = '';

			//get po name  information
			$poname[$fld_module] = $pdf_details[$fld_module]['poname'];
			If ($poname[$fld_module] =='') $poname[$fld_module]= 'true';
			If ($poname[$fld_module] =='true') $poname_selection[$fld_module] = 'checked="checked"';
			else $poname_selection[$fld_module] = '';

			//get carrier information
			$carrier[$fld_module] = $pdf_details[$fld_module]['carrier'];
			If ($carrier[$fld_module] =='') $carrier[$fld_module]= 'true';
			If ($carrier[$fld_module] =='true') $carrier_selection[$fld_module] = 'checked="checked"';
			else $carrier_selection[$fld_module] = '';

			//get summary information
			$summaryratio[$fld_module] = $pdf_details[$fld_module]['summaryradio'];
			If ($summaryratio[$fld_module] =='') $summaryratio[$fld_module]= 'true';
			If ($summaryratio[$fld_module] =='true') $summary_selection[$fld_module] = 'checked="checked"';
			else $summary_selection[$fld_module] = '';

			//get footer information
			$footerradio[$fld_module] = $pdf_details[$fld_module]['footerradio'];
			If ($footerradio[$fld_module] =='') $footerradio[$fld_module]= 'true';
			If ($footerradio[$fld_module] =='true') $footer_selection[$fld_module] = 'checked="checked"';
			else $footer_selection[$fld_module] = '';

			//get footer page information
			$footerpageradio[$fld_module] = $pdf_details[$fld_module]['pageradio'];
			If ($footerpageradio[$fld_module] =='') $footerpageradio[$fld_module]= 'true';
			If ($footerpageradio[$fld_module] =='true') {
				$footerpage_selection[$fld_module] = 'checked="checked"';
			}
			else {
				$footerpage_selection[$fld_module] = '';
			}


			//get product description information
			//group
			$gproddetailarray[$fld_module] = array($pdf_details[$fld_module]['gprodname'],$pdf_details[$fld_module]['gproddes'],$pdf_details[$fld_module]['gprodcom']);
			foreach($gproddetailarray[$fld_module] as $value){
				if ($value=='true') {
					$gproddetails[$fld_module][]='checked="checked"';
				}
				else {
					$gproddetails[$fld_module][]='';
				}
			}
			//individual
			$iproddetailarray = array($pdf_details[$fld_module]['iprodname'],$pdf_details[$fld_module]['iproddes'],$pdf_details[$fld_module]['iprodcom']);
			foreach($iproddetailarray as $value){
				if ($value=='true') {
					$iproddetails[$fld_module][]='checked="checked"';
				}
				else {
					$iproddetails[$fld_module][]='';
				}
			}
			// get the column settings for body
			$pdf_column_settings =getAllPDFColums ($fld_module);
			$column_body_content_group_sel[$fld_module]= $pdf_column_settings[0];
			$column_body_content_individual_sel[$fld_module]= $pdf_column_settings[1];
		}

		$viewer->assign("DEF_MODULE",$_REQUEST['frommodule']);

		//assign general information
		$viewer->assign("MOD", return_module_language($current_language,$currentModule));
		$viewer->assign("APP", $app_strings);
		$viewer->assign("MODULES",$currentModule);
		$viewer->assign("PDFLANGUAGEARRAYQUOTES", $pdf_language_array_quotes);
		$viewer->assign("PDFMODULLANGUAGEQUOTES", $module_language_array_quotes);
		$viewer->assign("PDFLANGUAGEARRAYINVOICES", $pdf_language_array_invoices);
		$viewer->assign("PDFMODULLANGUAGEINVOICES", $module_language_array_invoices);
		$viewer->assign("PDFLANGUAGEARRAYSO", $pdf_language_array_so);
		$viewer->assign("PDFMODULLANGUAGESO", $module_language_array_so);
		$viewer->assign("PDFLANGUAGEARRAYPO", $pdf_language_array_po);
		$viewer->assign("PDFMODULLANGUAGEPO", $module_language_array_po);
		$viewer->assign("EDITPERMISSION", $pdfpermission);
		$viewer->assign("CHANGEPERMISSION", $pdfactive);

		//paperformat
		$viewer->assign("PAPERFORMAT", $paperformat);
		$viewer->assign("PAPERSELECTED", $paperformat_sel);
		$viewer->assign("PAPERKEYS", $paperformat);

		//language
		$viewer->assign("LANGUAGES", $pdflanguages);
		$viewer->assign("LANGSELECTED", $pdflanguage_selected);
		$viewer->assign("LANGUAGEKEYS", $pdflanguage_keys);

		//configuration tab content
		$viewer->assign('CONFIGTABS', array('general' => 'General', 'grouptax' => 'Group Tax Mode', 'individualtax' => 'Individual Tax Mode'));
		$viewer->assign('SELECTEDTAB', 'general');
		// Tab 1 general
		// assign font type information
		$viewer->assign("FONTIDS", $available_fontid);
		$viewer->assign("SELECTEDFONTID", $selected_fontid);
		$viewer->assign("FONTLIST",$available_namedisplay);
		// assign font size information
		$viewer->assign("FONTSIZEAVAILABLE", $fontsizes_available);
		$viewer->assign("FONTSIZEHEADER", $fontsize_header);
		$viewer->assign("FONTSIZEFOOTER", $fontsize_footer);
		$viewer->assign("FONTSIZEBODY",$fontsize_body);
		$viewer->assign("FONTSIZEADDRESS",$fontsize_address);
		// assign summary information
		$viewer->assign("SUMMARYRADIO",$summary_selection);
		// assign logo and other information
		$viewer->assign("LOGORADIO",$logo_selection);
		$viewer->assign("OWNER",$owner_selection);
		$viewer->assign("OWNERPHONE",$ownerphone_selection);
		$viewer->assign("FOOTERRADIO",$footer_selection);
		$viewer->assign("FOOTERPAGERADIO",$footerpage_selection);
		$viewer->assign("FOOTERPAGERADIO",$footerpage_selection);
		$viewer->assign("PONAME",$poname_selection);
		$viewer->assign("CLIENTID",$clientid_selection);
		$viewer->assign("CARRIER",$carrier_selection);
		// assign date used
		$viewer->assign("DATEUSED",$dateused_available);
		$viewer->assign("DATEUSEDNAME",$dateused_name);
		$viewer->assign("DATEUSEDSELECTED",$dateused_selected);
		// assign headerspace
		$viewer->assign("HEADERSPACE",$headerspace_available);
		$viewer->assign("HEADERSPACESELECTED",$headerspace_selected);
		// assign description content
		$viewer->assign("GPRODDETAILS",$gproddetails);
		$viewer->assign("IPRODDETAILS",$iproddetails);

		// Tab 2 tax mode
		// assign column content information
		$viewer->assign("COLUMNCONFIGURATIONGROUP", $column_body_content_group_sel);
		// Tab 3 individual tax mode
		// assign column content information
		$viewer->assign("COLUMNCONFIGURATIONINDIVIDUAL", $column_body_content_individual_sel);


		$viewer->assign("FIELD_INFO",$field_module);
		$viewer->assign("FIELD_LISTS",$allfields);
		$viewer->assign("CMOD", $mod_strings);


		//decide the view
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$viewer->assign("MODULEVIEW", $currentUser->isAdminUser());
		
		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE',$module);
        $linkParams = array('MODULE'=>$module, 'ACTION'=>$request->get('view'));
	}
    
    /**
	 * Function to get the list of Script models to be included
	 * @param Vtiger_Request $request
	 * @return <Array> - List of Vtiger_JsScript_Model instances
	 */
	function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = array(
			'modules.Vtiger.resources.List',
			"modules.$moduleName.resources.List",
			'modules.CustomView.resources.CustomView',
			"modules.$moduleName.resources.CustomView",
			"modules.Emails.resources.MassEdit",
			"modules.Vtiger.resources.CkEditor"
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
    }
    
}
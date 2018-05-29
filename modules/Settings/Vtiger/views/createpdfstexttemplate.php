<?php
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
*
 ********************************************************************************/

class Settings_Vtiger_createpdfstexttemplate_View extends Settings_Vtiger_Index_View {
	
	public function process(Vtiger_Request $request) {
	
		$qualifiedModuleName = $request->getModule(false);
		$moduleModel = Settings_Vtiger_listpdftexttemplates_Model::getInstance();

		$text_array_header=array('letter'=>vtranslate('LBL_MULTI_TEXT_SELECT_LETTER',$qualifiedModuleName),'conclusion'=>vtranslate('LBL_MULTI_TEXT_SELECT_CONCLUSION',$qualifiedModuleName));
		$text_array_list=array('letter'=>vtranslate('LBL_PDFTEXTTEMPLATES_LETTER',$qualifiedModuleName),'conclusion'=>vtranslate('LBL_PDFTEXTTEMPLATES_CONCLUSION',$qualifiedModuleName));
		$textrelation_array = $moduleModel->getTextRelations();
		
		$mode = $request->get('mode');
		if ($mode =='create') {
			$title = '';
			$text = '';
			$templateid = '';
		}
		else {
			$templateid = $request->get('templateid');
			$texttype = $request->get('texttype');
			if ($texttype =='letter') {
				$return_data = $moduleModel->getLetterText($templateid);
				$text = $return_data[0]['multistext'];
			}
			else {
				$return_data = $moduleModel->getConclusionText($templateid);
				$text = $return_data[0]['multietext'];
			}
			$title = $return_data[0]['templatename'];
		}
		
		$viewer = $this->getViewer($request);
		$viewer->assign("MODE", $mode);
		$viewer->assign("TEMPLATEID", $templateid);
		$viewer->assign("TEXTTYPE", $texttype);
		$viewer->assign("TEXTARRAYHEADER", $text_array_header);
		$viewer->assign("TEXTARRAYLIST", $text_array_list);
		$viewer->assign("TEXTMODULES", $textmodules);
		$viewer->assign("PARENTTAB", htmlspecialchars($_REQUEST['parenttab'],ENT_QUOTES,$default_charset));
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('ERROR_MESSAGE', $request->get('error'));
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('CURRENT_USER_MODEL', Users_Record_Model::getCurrentUserModel());
		
		$viewer->assign("STARTMODULE",'quotes');
		$viewer->assign("ALLMODULES",$tmplatemodules);
		$viewer->assign("TEXTYPES",$texttype_array);
		$viewer->assign("TEXTRELATIONS",$textrelation_array);
		$viewer->assign("TEMPLATESTITLE",$title);
		$viewer->assign("TEMPLATESTEXT",$text);
		$viewer->view('CreatePdfSTexttemplate.tpl', $qualifiedModuleName);
	}
	function getPageTitle(Vtiger_Request $request) {
		$qualifiedModuleName = $request->getModule(false);
		return vtranslate('LBL_PDF_TEMPLATES',$qualifiedModuleName);
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
			"modules.Settings.$moduleName.resources.CreatePdfSTexttemplate"
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

}
?>
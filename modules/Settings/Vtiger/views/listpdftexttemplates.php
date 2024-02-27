<?php
/*********************************************************************************
** The contents of this file is copyright crm-now
 * The Original Code is:  crm-now
 * The Initial Developer of the Original Code is crm-now
* All Rights Reserved.
 ********************************************************************************/

class Settings_Vtiger_listpdftexttemplates_View extends Settings_Vtiger_Index_View {


	public function process(Vtiger_Request $request) {
		global $adb;
		global $log;
		$qualifiedModuleName = $request->getModule(false);
		$moduleModel = Settings_Vtiger_listpdftexttemplates_Model::getInstance();
		// get existig entries

		$textrelation_array = $moduleModel->getTextRelations();
		$texttype_array = $moduleModel->getTextTypes();
		$templatemodules = $moduleModel->getTemplateModules();
		$return_data_letter = $moduleModel->getLetterText();
		$count_letter = count($return_data_letter);
		$return_data_conclusion = $moduleModel->getConclusionText();
		$count_conclusion = count($return_data_conclusion);
	
		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('ERROR_MESSAGE', $request->get('error'));
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('CURRENT_USER_MODEL', Users_Record_Model::getCurrentUserModel());
		
		$viewer->assign("STARTTEXTYPE",'LETTER');
		$viewer->assign("STARTMODULE",'quotes');
		$viewer->assign("ALLMODULES",$templatemodules);
		$viewer->assign("TEXTYPES",$texttype_array);
		$viewer->assign("TEXTRELATIONS",$textrelation_array);
		$viewer->assign("LETTERTEMPLATES",$return_data_letter);
		$viewer->assign("LETTERCOUNT",$count_letter);
		$viewer->assign("CONCLUSIONTEMPLATES",$return_data_conclusion);
		$viewer->assign("CONCLUSIONCOUNT",$count_conclusion);
		$viewer->view('ListPdfTextTemplates.tpl', $qualifiedModuleName);
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
			"modules.Settings.$moduleName.resources.ListPdfTextTemplates"
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

} 

?>
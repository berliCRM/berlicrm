<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

require_once 'modules/Settings/Vtiger/models/ConfigSignature.php';

/**
 * Class Settings_Vtiger_ConfigEditorEditSignature_View
 *
 * Settings view controller responsible for rendering the
 * edit view of the global email signature configuration.
 *
 * Loads:
 *  - ConfigModule model (for navigation / block / field context)
 *  - ConfigSignature model (signature data from DB)
 *
 * Renders template:
 *  - ConfigEditorEditSignature.tpl
 *
 * @package Settings
 * @subpackage Vtiger
 */
class Settings_Vtiger_ConfigEditorEditSignature_View extends Settings_Vtiger_Index_View {

	/**
	 * Process request and render the signature edit view.
	 *
	 * Assigns required models and data to the viewer:
	 *  - MODEL (ConfigModule)
	 *  - SIGNATURE_MODEL
	 *  - SIGNATURE_DATA (array for tpl usage)
	 *  - QUALIFIED_MODULE
	 *  - CURRENT_USER_MODEL
	 *
	 * @param Vtiger_Request $request
	 * @return void
	 */
	public function process(Vtiger_Request $request) {
		$qualifiedName = $request->getModule(false);

		// Config module model (used for block/field navigation)
		$moduleModel = Settings_Vtiger_ConfigModule_Model::getInstance();

		// Load signature configuration from database
		$signatureModel = Settings_Vtiger_ConfigSignature::getInstance();

		$viewer = $this->getViewer($request);
		$viewer->assign('MODEL', $moduleModel);
		$viewer->assign('SIGNATURE_MODEL', $signatureModel);
		$viewer->assign('SIGNATURE_DATA', $signatureModel->getData());

		$viewer->assign('QUALIFIED_MODULE', $qualifiedName);
		$viewer->assign('CURRENT_USER_MODEL', Users_Record_Model::getCurrentUserModel());

		$viewer->view('ConfigEditorEditSignature.tpl', $qualifiedName); 
	}

	/**
	 * Returns page title for the signature edit view.
	 *
	 * @param Vtiger_Request $request
	 * @return string
	 */
	public function getPageTitle(Vtiger_Request $request) {
		$qualifiedModuleName = $request->getModule(false);
		return vtranslate('LBL_CONFIG_EDITOR', $qualifiedModuleName);
	}

	/**
	 * Include required JavaScript resources for this view.
	 *
	 * Extends parent header scripts and adds:
	 *  - modules.Settings.<Module>.resources.ConfigEditor
	 *
	 * @param Vtiger_Request $request
	 * @return array List of Vtiger_JsScript_Model instances
	 */
	public function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);

		$jsFileNames = array(
			"modules.Settings." . $request->getModule() . ".resources.ConfigEditor"
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);

		return array_merge($headerScriptInstances, $jsScriptInstances);
	}
}
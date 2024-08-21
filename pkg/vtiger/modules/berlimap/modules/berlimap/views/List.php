<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License.
 * The Original Code is:  vtiger CRM Open Source.
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class berlimap_List_View extends Vtiger_Index_View {
	protected $listViewEntries = false;
	protected $listViewCount = false;
	protected $listViewLinks = false;
	protected $listViewHeaders = false;
	protected $googleCheck = true;

	public function __construct() {
		parent::__construct();
	}

	public function process(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$googleGeoApiKey = Settings_Google_Module_Model::getGoogleGeoApikey();

		// Check Google API Key
		if (!empty(trim($googleGeoApiKey))) {
			$this->googleCheck = Settings_Google_Module_Model::checkGoogleGeoApikey();
		}

		if (vtlib_isModuleActive('Google') && $this->googleCheck) {
			$this->handleActiveGoogleModule($request, $viewer, $googleGeoApiKey);
		} 
		else {
			$this->handleInactiveGoogleModule($viewer, $moduleName);
		}
	}

	private function handleActiveGoogleModule(Vtiger_Request $request, Vtiger_Viewer $viewer, string $googleGeoApiKey) {
		$moduleName = $request->getModule();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		$this->viewName = $request->get('viewname');

		$modules = ['Accounts', 'Contacts', 'Leads'];
		$customViews = [];
		foreach ($modules as $module) {
			$customViews[$module] = CustomView_Record_Model::getAll($module);
		}

		$viewer->assign('GEOAPIKEY', trim($googleGeoApiKey));
		$viewer->assign('CUSTOMVIEWSBYMODULE', $customViews);
		$viewer->assign('VIEW', $request->get('view'));
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('CURRENT_USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->view('index.tpl', $moduleName);
	}

	private function handleInactiveGoogleModule(Vtiger_Viewer $viewer, string $moduleName) {
		if (!$this->googleCheck) {
			$viewer->assign('GOOGLEKEY_ERROR', true);
		}
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->view('inactiveError.tpl', $moduleName);
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param Vtiger_Request $request
	 * @return array - List of Vtiger_JsScript_Model instances
	 */
	public function getHeaderScripts(Vtiger_Request $request): array {
		$headerScriptInstances = parent::getHeaderScripts($request);
		if ($this->googleCheck) {
			$moduleName = $request->getModule();
			$jsFileNames = [
				'modules.Vtiger.resources.List',
				"modules.$moduleName.resources.List",
				"modules.$moduleName.resources.ol",
			];

			$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
			$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		}
		return $headerScriptInstances;
	}

	public function getHeaderCss(Vtiger_Request $request): array {
		$headerCssInstances = parent::getHeaderCss($request);
		$moduleName = $request->getModule();

		$cssFileNames = [
			"~/layouts/vlayout/modules/$moduleName/resources/css/ol.css",
		];

		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		$headerCssInstances = array_merge($headerCssInstances, $cssInstances);

		return $headerCssInstances;
	}
}

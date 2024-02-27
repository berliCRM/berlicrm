<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class berlimap_List_View extends Vtiger_Index_View {
	protected $listViewEntries = false;
	protected $listViewCount = false;
	protected $listViewLinks = false;
	protected $listViewHeaders = false;
	protected $googlecheck = true;
	
	function __construct() {
		parent::__construct();
	}

	function process (Vtiger_Request $request) {
		$viewer = $this->getViewer ($request);
		$moduleName = $request->getModule();
		$GoogleGeoApiKey = Settings_Google_Module_Model::getGoogleGeoApikey();
		//check Google API Key
		if (trim($GoogleGeoApiKey) !='') {
			$this->googlecheck = Settings_Google_Module_Model::checkGoogleGeoApikey();
		}
		if(vtlib_isModuleActive('Google') AND $this->googlecheck == true ) {
			$moduleName = $request->getModule();
			$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
			$this->viewName = $request->get('viewname');
			$modules = array("Accounts","Contacts","Leads");
			foreach($modules as $module) {
				$CustomViews[$module] =  CustomView_Record_Model::getAll($module);
			}
			$viewer->assign('GEOAPIKEY', trim($GoogleGeoApiKey));
			$viewer->assign('CUSTOMVIEWSBYMODULE', $CustomViews);
			$viewer->assign('VIEW', $request->get('view'));
			$viewer->assign('MODULE_MODEL', $moduleModel);
			$viewer->assign('CURRENT_USER_MODEL', Users_Record_Model::getCurrentUserModel());
			$viewer->view('index.tpl', $moduleName);
		}
		else {
			if ($googlecheck == false) {
				$viewer->assign('GOOGLEKEY_ERROR', true);
			}
			$viewer->assign('MODULE_NAME', $moduleName);
			$viewer->view('inactiveError.tpl', $moduleName);
		}
	}


	/**
	 * Function to get the list of Script models to be included
	 * @param Vtiger_Request $request
	 * @return <Array> - List of Vtiger_JsScript_Model instances
	 */
	function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		if($this->googlecheck) {
			$moduleName = $request->getModule();
			$jsFileNames = array(
				'modules.Vtiger.resources.List',
				"modules.$moduleName.resources.List",
				"modules.$moduleName.resources.ol",
			);

			$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
			$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		}
		return $headerScriptInstances;
	}
	
	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		$moduleName = $request->getModule();

		$cssFileNames = array(
			"~/layouts/vlayout/modules/$moduleName/resources/css/ol.css",
		);
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		$headerCssInstances = array_merge($headerCssInstances, $cssInstances);

		return $headerCssInstances;
	}


}
<?php
/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class berliSoftphones_ListPhone_Action extends Vtiger_IndexAjax_View {
    
	public function validateRequest(Vtiger_Request $request) { 
            $request->validateReadAccess(); 
	}
	public function loginRequired() {
		return true;
	}
	public function checkPermission() { }
	
	protected function preProcessDisplay(Vtiger_Request $request) {
		$viewer = new Vtiger_Viewer();
		//$displayed = $viewer->view($this->preProcessTplName($request), $request->getModule());
		$menuModelsList = Vtiger_Menu_Model::getAll(true);
		$selectedModule = 'Contacts';
		$menuStructure = Vtiger_MenuStructure_Model::getInstanceFromMenuList($menuModelsList, $selectedModule);

		$companyDetails = Vtiger_CompanyDetails_Model::getInstanceById();
		$companyLogo = $companyDetails->getLogo();
		$currentDate  = Vtiger_Date_UIType::getDisplayDateValue(date('Y-n-j'));
		$headerScripts = $this->getHeaderScripts($request);
		$headerScripts = $this->getHeaderScripts($request);
        $viewer->assign('SCRIPTS', $this->getHeaderScripts($request));
		$viewer->assign('STYLES',$this->getHeaderCss($request));
		
		
		$viewer->assign('CURRENTDATE', $currentDate);
		$viewer->assign('MODULE', $selectedModule);
        $viewer->assign('MODULE_NAME', $selectedModule);
		$viewer->assign('QUALIFIED_MODULE', $selectedModule);
		$viewer->assign('PARENT_MODULE', $request->get('parent'));
		$viewer->assign('VIEW', 'List');

		// Order by pre-defined automation process for QuickCreate.
		uksort($menuModelsList, array('Vtiger_MenuStructure_Model', 'sortMenuItemsByProcess'));
                
		$viewer->assign('MENUS', $menuModelsList);
		$viewer->assign('MENU_STRUCTURE', $menuStructure);
		$viewer->assign('MENU_SELECTED_MODULENAME', $selectedModule);
		$viewer->assign('MENU_TOPITEMS_LIMIT', $menuStructure->getLimit());
		$viewer->assign('COMPANY_LOGO',$companyLogo);
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());

		$homeModuleModel = Vtiger_Module_Model::getInstance('Home');
		$viewer->assign('HOME_MODULE_MODEL', $homeModuleModel);
		$viewer->assign('HEADER_LINKS',Vtiger_Header_View::getHeaderLinks());
		$viewer->assign('ANNOUNCEMENT',Vtiger_Header_View::getAnnouncement());
		$viewer->assign('SEARCHABLE_MODULES', Vtiger_Module_Model::getSearchableModules());

		$viewer->assign('PAGETITLE', 'berliSoftphones');
		$viewer->view('BasicHeader.tpl','berliSoftphones');
	}
	
    /*
     * Override default preProcess
     */
	function preProcess(Vtiger_Request $request, $display=true) {
			$this->preProcessDisplay($request);
	}

	function postProcess(Vtiger_Request $request) {
		return true;
	}

	
	function process(Vtiger_Request $request) {
		$current_user =	Users_Record_Model::getCurrentUserModel();
		$moduleName = $request->getModule();
		$phonenumber = $request->get('phonenumber');
		
		$records = berliSoftphones_Record_Model:: getSoftphoneCaller($phonenumber,$current_user);
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		$viewer = new Vtiger_Viewer();
        $viewer->assign('RECORDS', $records);
        $viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('CALLERPHONE', $phonenumber);
		$viewer->assign('CURRENT_USER_MODEL', $current_user);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->view('showCallers.tpl', $moduleName);
	}
	

}

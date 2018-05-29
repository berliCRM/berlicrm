<?php
/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class berliSoftphones_List_View extends Vtiger_List_View{
    
    /**
     * Overrided to disable Ajax Edit option in Detail View of
     * PBXManager Record
     */
    function isAjaxEnabled($recordModel) {
		return true;
	}
 
    /*
     * Override default preProcess
     */
	function preProcess(Vtiger_Request $request, $display=true) {
		parent::preProcess($request, false);

		$viewer = $this->getViewer ($request);
		$moduleName = $request->getModule();

		$listViewModel = Vtiger_ListView_Model::getInstance($moduleName);
		$linkParams = array('MODULE'=>$moduleName, 'ACTION'=>$request->get('view'));
		$viewer->assign('CUSTOM_VIEWS', CustomView_Record_Model::getAllByGroup($moduleName));
		$this->viewName = $request->get('viewname');
		if(empty($this->viewName)){
			//If not view name exits then get it from custom view
			//This can return default view id or view id present in session
			$customView = new CustomView();
			$this->viewName = $customView->getViewId($moduleName);
		}

		$quickLinkModels = $listViewModel->getSideBarLinks($linkParams);
		$viewer->assign('QUICK_LINKS', $quickLinkModels);
		$this->initializeListViewContents($request, $viewer);
		$viewer->assign('VIEWID', $this->viewName);
		
        $viewer->assign('MODULE_MODEL', $listViewModel);

		if($display) {
			$this->preProcessDisplay($request);
		}
		$viewer = $this->getViewer ($request);
	}
	
	function process(Vtiger_Request $request) {
		$current_user =	Users_Record_Model::getCurrentUserModel();
		$moduleName = $request->getModule();
		$phonenumber = $request->get('phonenumber');
		
		$records = berliSoftphones_Record_Model:: getSoftphoneCaller($phonenumber,$current_user);
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		//$moduleModel->isPagingSupported(); 
		$viewer = $this->getViewer($request);
        $viewer->assign('RECORDS', $records);
        $viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('CALLERPHONE', $phonenumber);
		$viewer->assign('CURRENT_USER_MODEL', $current_user);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->view('showCallers.tpl', $moduleName);
	}
	
}

<?php

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class berliWidgets_dropDocument_View extends Vtiger_Detail_View {

	function checkPermission(Vtiger_Request $request) {
		$recordPermission = Users_Privileges_Model::isPermitted('Documents', 'CreateView');
		if(!$recordPermission) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
	}
	/**
	 * Function to get the list of Script models to be included
	 * @param Vtiger_Request $request
	 * @return <Array> - List of Vtiger_JsScript_Model instances
	 */
	public function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = array();
		$moduleName = $request->getModule();

		$jsFileNames = array(
			"modules.$moduleName.resources.Detail",
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

    /**
     * must be overriden
     * @param Vtiger_Request $request
     * @return boolean 
     */
    public function preProcess(Vtiger_Request $request, $display= true) {
        return true;
    }

    /**
     * must be overriden
     * @param Vtiger_Request $request
     * @return boolean 
     */
    public function postProcess(Vtiger_Request $request) {
        return true;
    }

    /**
     * called when the request is received.
     * if view type : detail then show related CRM entries
     * @param Vtiger_Request $request 
     */
    public function process(Vtiger_Request $request) {
		$this->showDragAnDropMenu($request);
    }

    /**
     * display the template.
     * @param Vtiger_Request $request 
     */
    public function showDragAnDropMenu(Vtiger_Request $request) {
		//document number
		$parentRecordId = $request->get('record');
		$moduleName = $request->getModule();

		$viewer = $this->getViewer($request);
 		$viewer->assign('SCRIPTS',$this->getHeaderScripts($request));
        $viewer->assign('RECORDID', $parentRecordId);
        $viewer->assign('MODULE', $moduleName);
        $viewer->view('showDocumentDropMenu.tpl', 'berliWidgets');
    }

}

?>

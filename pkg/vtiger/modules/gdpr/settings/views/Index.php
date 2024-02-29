<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Settings_gdpr_Index_View extends Settings_Vtiger_Index_View{
    
    function __construct() {
        $this->exposeMethod('gdprInfo');
    }

    public function process(Vtiger_Request $request) {
        $this->gdprInfo($request);
    }
    
    
    public function gdprInfo(Vtiger_Request $request){
 		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$recordModel = Settings_gdpr_Record_Model::getInstance();
		$recordModel = $recordModel->getGlobalSettingsParameters();

		// $moduleModel = Settings_gdpr_Module_Model::getCleanInstance();
		// $moduleModel = Settings_Vtiger_Module_Model::getInstance($qualifiedModuleName);
		// $modulesList = $moduleModel->getModulesList(); // lots of modules missing from this call (leads, f.e.), use Settings_ModuleManager_Module_Model::getAll() ??
 
        // $allModules = Settings_ModuleManager_Module_Model::getAll(array(0,2),array("Home")); // this returns too many modules, esp. non-entity ones

        $allModules = Settings_ModuleManager_Module_Model::getEntityModules(); // this appears ideal
 
        $moduleSettings = $recordModel->getModuleSettings();   
		$viewer = $this->getViewer($request);
        $viewer->assign('ALL_MODULES', $allModules);
        $viewer->assign('GDPR_GLOBAL_SETTINGS',$recordModel);
        $viewer->assign('RECORD_ID', $recordModel->get('id'));
        // $viewer->assign('MODULE_MODEL', $moduleModel);
        $viewer->assign('MODULE', $moduleName);
        $viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
        $viewer->assign('RECORD_MODEL', $recordModel);
		// $viewer->assign('MODULES_MODELS', $modulesList);
		$viewer->assign('MODULE_SETTINGS', $moduleSettings);
		$viewer->view('index.tpl',  $qualifiedModuleName);
    }
}

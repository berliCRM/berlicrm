<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Settings_EmailConfigurator_Edit_View extends Settings_Vtiger_Index_View {

	public function process(Vtiger_Request $request) {
        global $adb;
    
        $record = $request->get("record");
        
        $qualifiedName = $request->getModule(FALSE);
        $viewer = $this->getViewer($request);

        
        if (!empty($record)) {
        
            //get all stored Email addresses
            $sql = "SELECT * FROM crmnow_emailconfig WHERE emailid = ?";
            $result = $adb->pquery($sql, array($record));

            $emailaddress = $adb->fetchByAssoc($result,-1,false);
            
            $viewer->assign("EMAILADDRESS",$emailaddress);
        }
        
        $viewer->view('EmailConfiguratorSettingsEdit.tpl', $qualifiedName);
        
    }
}
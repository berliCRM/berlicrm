<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Modified and improved by crm-now.de
 ************************************************************************************/


class Settings_EmailConfigurator_Index_View extends Settings_Vtiger_Index_View {

	public function process(Vtiger_Request $request) {
        global $adb;
        
        $qualifiedName = $request->getModule(FALSE);
        
        //get all stored Email addresses
        $sql = "SELECT * FROM crmnow_emailconfig order by email_lastname ASC";
        $result = $adb->pquery($sql, array());

        $emailaddresses = array();
        
        while ($row = $adb->fetchByAssoc($result,-1,false)) {
            $emailaddresses[]=$row;
        }
        $viewer = $this->getViewer($request);
        $viewer->assign("EMAILADDRESSES",$emailaddresses);

        $viewer->view('EmailConfiguratorSettings.tpl', $qualifiedName);
    }
}
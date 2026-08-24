<?php
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Modified and improved by crm-now.de
 ********************************************************************************/

class Settings_EmailConfigurator_DeleteAjax_Action extends Settings_Vtiger_Index_Action {


    public function process(Vtiger_Request $request) {
        global $adb;
        
        $record = $request->get('record');
        
        if (!empty($record)) {
            $q = "DELETE FROM crmnow_emailconfig WHERE emailid = ?";
            $adb->pquery($q,array($record));
        }
    }
}
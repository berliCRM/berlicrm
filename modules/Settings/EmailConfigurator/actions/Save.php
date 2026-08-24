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

class Settings_EmailConfigurator_Save_Action extends Settings_Vtiger_Index_Action {

    public function process(Vtiger_Request $request) {
        global $adb;

        $record = $request->get("emailid");
        $firstname = $request->get("email_firstname");
        $lastname = $request->get("email_lastname");
        $email = $request->get("email_address");
        $desc = $request->get("email_desc");
        
        
        if (empty($record) && !empty($email) && !empty($desc)) {
            $q = "INSERT INTO crmnow_emailconfig SET email_address = ?, email_firstname = ?, email_lastname = ?, email_desc = ?";
            $adb->pquery($q,array($email,$firstname,$lastname,$desc));
        }
        elseif (!empty($email) && !empty($desc)) {
            $q = "UPDATE crmnow_emailconfig SET email_address = ?, email_firstname = ?, email_lastname = ?, email_desc = ? WHERE emailid = ?";
            $adb->pquery($q,array($email,$firstname,$lastname,$desc,$record));
        }

        header ("location: index.php?module=EmailConfigurator&view=Index&parent=Settings");
    }
}
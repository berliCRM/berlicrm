<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Settings_gdpr_Record_Model extends Settings_Vtiger_Record_Model {

    public function getId() {
        return $this->get('id');
    }

    public function getName() {
        return $this->getName();
    }

    static function getCleanInstance(){
        return new self;
    }
    
     public static function getInstance(){
        return new self();
    }
    
    public static function getGlobalSettingsParameters() {
		$db = PearDatabase::getInstance();
		$result = $db->pquery('SELECT * FROM berli_dsgvo_global', array());
		$recordModel = new self();
		$SettingsParameters = array();
		for ($i = 0; $i < $db->num_rows($result); $i++) {
			$recordModel->setData($db->query_result_rowdata($result,$i));
		}
		return $recordModel;
	}
    
    public static function getModuleSettings() {
        $db = PearDatabase::getInstance();
		$result = $db->pquery('SELECT * FROM berli_dsgvo_module WHERE setting_date = (SELECT MAX(setting_date) FROM berli_dsgvo_module)');
        while ($row = $db->fetchByAssoc($result)) {
            
            // no record of $moduleid == module does not contain personal data
            
            // deletion_mode == 0: no automatic deleteion
            // deletion_mode == 1: delete whole record
            // deletion_mode == 2: only delete selected fields of personal data
            
            // fieldids = semicolon separated values of fieldsids to contain personal data
            
            $moduleSettings[$row["tabid"]] = array ("deletion_mode" => $row["deletion_mode"],
                                                    "fields" => $row["fieldids"]!="" ? explode(";",$row["fieldids"]) : null,
                                                    "setting_date" => $row["setting_date"]
                                                    );
        }
        return $moduleSettings;
    }
}

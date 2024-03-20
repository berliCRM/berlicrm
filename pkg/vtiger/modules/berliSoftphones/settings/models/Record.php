<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Settings_berliSoftphones_Record_Model extends Settings_Vtiger_Record_Model {

    public function getId() {
        return $this->get('id');
    }

    public function getName() {
        return $this->getName();
    }
    
    public function getModule(){
        return new Settings_berliSoftphones_Module_Model;
    }
    
    static function getCleanInstance(){
        return new self;
    }
    
     public static function getInstance(){
        return new self();
    }
    
    public static function getSettingsParameters() {
		$db = PearDatabase::getInstance();
		$result = $db->pquery('SELECT * FROM berli_softphones', array());

		if ($db->num_rows($result)) {
			$recordModel = new self();
			$SettingsParameters = array();
			for ($i = 0; $i < $db->num_rows($result); $i++) {
				$rowData[$i] = $db->query_result_rowdata($result,$i);
			}
			$SettingsParameters = $recordModel->setData($rowData);
			return $SettingsParameters;
		}
		return false;
	}
}

<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Settings_gdpr_Module_Model extends Settings_Vtiger_Module_Model{
    
    /**
	 * Function to get the module model
	 * @return string
	 */
    public static function getCleanInstance(){
        return new self;
    }
	/**
	 * Function to get list of modules which may contain personalized information
	 * @return <Array> list of modules <Vtiger_Module_Model>
	 */
	public function getModulesList() {
		if (!$this->gdprModules) {
			$db = PearDatabase::getInstance();

			$query = "SELECT vtiger_customerportal_tabs.*, vtiger_customerportal_prefs.prefvalue, vtiger_tab.name FROM vtiger_customerportal_tabs
					INNER JOIN vtiger_customerportal_prefs ON vtiger_customerportal_prefs.tabid = vtiger_customerportal_tabs.tabid AND vtiger_customerportal_prefs.prefkey='showrelatedinfo'
					INNER JOIN vtiger_tab ON vtiger_customerportal_tabs.tabid = vtiger_tab.tabid AND vtiger_tab.presence = 0 ORDER BY vtiger_customerportal_tabs.sequence";

			$result = $db->pquery($query, array());
			$rows = $db->num_rows($result);

			for($i=0; $i<$rows; $i++) {
				$rowData = $db->query_result_rowdata($result, $i);
				$tabId = $rowData['tabid'];
				$moduleModel = Vtiger_Module_Model::getInstance($tabId);
				foreach ($rowData as $key => $value) {
					$moduleModel->set($key, $value);
				}
				$gdprModules[$tabId] = $moduleModel;
			}
			$this->gdprModules = $gdprModules;
		}
		return $this->gdprModules;
	}
  
}

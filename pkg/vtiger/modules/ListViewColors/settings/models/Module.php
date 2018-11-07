<?php
/* +***********************************************************************************************************************************
 * The contents of this file are subject to the berliCRM Public License Version 1.0 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is from the crm-now GmbH.
 * The Initial Developer of the Original Code is crm-now. Portions created by crm-now are Copyright (C) www.crm-now.de. 
 * Portions created by vtiger are Copyright (C) www.vtiger.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class Settings_ListViewColors_Module_Model extends Settings_Vtiger_Module_Model {

	public function getModulesEntity($tabid = false) {
		$log = vglobal('log');
		$log->debug("Entering Settings_ListViewColors_Module_Model::getModulesEntity() method ...");
		$adb = PearDatabase::getInstance();
		$sql = "SELECT * from vtiger_entityname where modulename not in ('Emails','PriceBooks','PBXManager','Users')";
		$params = array();
		if ($tabid) {
			$sql .= ' WHERE tabid = ?';
			$params[] = $tabid;
		}
		$result = $adb->pquery($sql, $params, true);
		$moduleEntity = array();
		for ($i = 0; $i < $adb->num_rows($result); $i++) {
			$row = $adb->query_result_rowdata($result, $i);
			if (vtlib_isModuleActive($row['modulename'])) {
				$moduleEntity[$row['tabid']] = $row;
			}
		}
		$log->debug("Exiting Settings_ListViewColors_Module_Model::getModulesEntity() method ...");
		return $moduleEntity;
	}
}
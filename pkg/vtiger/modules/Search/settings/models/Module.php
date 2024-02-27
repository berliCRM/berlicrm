<?php
/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * modified by crm-now
 * *********************************************************************************************************************************** */

class Settings_Search_Module_Model extends Settings_Vtiger_Module_Model {

	public static function getModulesEntity($tabid = false) {
		$log = vglobal('log');
		$log->debug("Entering Settings_Search_Module_Model::getModulesEntity() method ...");
		$adb = PearDatabase::getInstance();
		//this query is necessary to get new installed module into the search settings
		$adb->pquery("INSERT IGNORE INTO berli_globalsearch_settings (gstabid) (SELECT vtiger_entityname.tabid FROM vtiger_entityname LEFT JOIN berli_globalsearch_settings ON vtiger_entityname.tabid = berli_globalsearch_settings.gstabid where vtiger_entityname.modulename !='Users' AND vtiger_entityname.modulename !='PBXManager')");
		$sql = 'SELECT * from vtiger_entityname';
		$sql .= " INNER JOIN berli_globalsearch_settings ON vtiger_entityname.tabid = berli_globalsearch_settings.gstabid ";

		$params = array();
		if ($tabid) {
			$sql .= ' WHERE tabid = ?';
			$params[] = $tabid;
		}
		$sql .= ' ORDER BY sequence';
		$result = $adb->pquery($sql, $params, true);
		$moduleEntity = array();
		for ($i = 0; $i < $adb->num_rows($result); $i++) {
			$row = $adb->query_result_rowdata($result, $i);
			$moduleEntity[$row['tabid']] = $row;
		}

		return $moduleEntity;
		$log->debug("Exiting Settings_Search_Module_Model::getModulesEntity() method ...");
	}

	public static function getFieldFromModule() 	{
		$adb = PearDatabase::getInstance();
		$result = $adb->pquery("SELECT * from vtiger_field WHERE uitype NOT IN ('52','53','56','70','77','98', '99', '101', '104', '105', '106', '115', '116', '117', '156', '357')");
		$fields = array();
		while ($row = $adb->fetch_array($result)) {
			$fields[$row['tabid']][] = $row;
		}
		return $fields;
	}

	public static function compare_vale($actions, $item) 	{
		if (strpos($actions, ',')) {
			$actionsTab = explode(",", $actions);
			if (in_array($item, $actionsTab)) {
				$return = true;
			} else {
				$return = false;
			}
		} else {
			$return = $actions == $item ? true : false;
		}
		return $return;
	}

	public static function Save($params) {
		$adb = PearDatabase::getInstance();
		$name = $params['name'];

		if ($name == 'fieldname') {
			$value = implode(',', $params['value']);
			$adb->pquery("UPDATE berli_globalsearch_settings LEFT JOIN vtiger_entityname ON vtiger_entityname.tabid = berli_globalsearch_settings.gstabid SET displayfield = ? WHERE tabid = ?", array($value, (int) $params['tabid']));
		} 
		elseif ($name == 'searchcolumn') {
			$value = implode(',', $params['value']);
			$adb->pquery("UPDATE berli_globalsearch_settings LEFT JOIN vtiger_entityname ON vtiger_entityname.tabid = berli_globalsearch_settings.gstabid SET searchcolumn = ? WHERE tabid = ?", array($value, (int) $params['tabid']));
		}
		elseif ($name == 'turn_off') {
			$adb->pquery("UPDATE berli_globalsearch_settings LEFT JOIN vtiger_entityname ON vtiger_entityname.tabid = berli_globalsearch_settings.gstabid SET turn_off = ? WHERE tabid = ?", array($params['value'], (int) $params['tabid']));
		}
		elseif ($name == 'globalsearchall') {
			$adb->pquery("UPDATE berli_globalsearch_settings SET searchall = ? WHERE gstabid = ?", array($params['value'], (int) $params['tabid']));
		}

	}

	public static function UpdateLabels($params) {
		$log = vglobal('log');
		$log->debug("Entering Settings_Search_Module_Model::UpdateLabels(" . $params . ") method ...");
		$adb = PearDatabase::getInstance();
		$tabid = (int) $params['tabid'];
		$modulesEntity = self::getModulesEntity($tabid);
		$moduleEntity = $modulesEntity[$tabid];
		$modulename = $moduleEntity['modulename'];
		$tablename = $moduleEntity['tablename'];
		$entityidfield = $moduleEntity['entityidfield'];

		$primary = CRMEntity::getInstance($modulename);
		$moduleothertables = $primary->tab_name_index;
		unset($moduleothertables[$tablename], $moduleothertables['vtiger_crmentity']);		

		foreach ($moduleothertables as $othertable => $otherindex) {
			if (isset($moduleothertables)) {
				$otherquery .= " LEFT JOIN $othertable ON $othertable.$otherindex=$tablename.$entityidfield";
			} 
			else {
				$otherquery .= '';
			}
		}

		$fieldname = $moduleEntity['fieldname'];
		$searchcolumn = $moduleEntity['displayfield'];

        // use same default fields as template when displayfields empty
        if (empty($searchcolumn)) {
            $columns_search = explode(',', $fieldname);
        }
        else {
            $columns_search = explode(',', $searchcolumn);
        }
        
		$moduleInfo = Vtiger_Functions::getModuleFieldInfos($modulename);
		$columns_name = explode(',', $fieldname);
		$sql_ext = '';
		$sql_fieldname = '';
		$sql_searchcolumn = '';
		
		$moduleInfoExtend = [];
		foreach ($moduleInfo as $field => $fieldInfo) {
			$moduleInfoExtend[$fieldInfo['columnname']] = $fieldInfo;
		}
		
		foreach ($columns_name as $key => $columnName) {
			$fieldObiect = $moduleInfoExtend[$columnName];
			if (in_array($fieldObiect['uitype'], array(10, 51,73,76, 75, 81))) {
				$sql_ext .= " LEFT JOIN (SELECT extj_$key.crmid, extj_$key.label AS ext_$columnName FROM vtiger_crmentity extj_$key) ext_$key ON ext_$key.crmid = " . $fieldObiect['tablename'] . ".$columnName";
				$sql_fieldname .= ",ext_$columnName";
			} else {
				$sql_fieldname .= ",$columnName";
			}
		}
		foreach ($columns_search as $key => $columnName) {
			$fieldObiect = $moduleInfoExtend[$columnName];
			if (in_array($fieldObiect['uitype'], array(10, 51,73,76, 75, 81))) {
				$sql_ext2 = " LEFT JOIN (SELECT extj_$key.crmid, extj_$key.label AS ext_$columnName FROM vtiger_crmentity extj_$key) ext_$key ON ext_$key.crmid = " . $fieldObiect['tablename'] . ".$columnName";
				if (!strstr($sql_ext, $sql_ext2)) {
					$sql_ext .= $sql_ext2;
				}
				if ($sql_searchcolumn =='') {
					$sql_searchcolumn = "ext_".$columnName;
				}
				else {
					$sql_searchcolumn .= ",ext_".$columnName;
				}
			} else {
				if ($sql_searchcolumn =='') {
					$sql_searchcolumn = $columnName;
				}
				else {
					$sql_searchcolumn .= ",".$columnName;
				}
			}
		}

		$sql = "UPDATE vtiger_crmentity INNER JOIN berli_globalsearch_data ON vtiger_crmentity.crmid = berli_globalsearch_data.gscrmid";
		$sql .= " LEFT JOIN $tablename ON vtiger_crmentity.crmid = $tablename.$entityidfield ";
		$sql .= $sql_ext;
		$sql .= $otherquery;
		$sql .= " SET vtiger_crmentity.label = CONCAT_WS(' |', $sql_searchcolumn), berli_globalsearch_data.searchlabel = CONCAT_WS(' |', $sql_searchcolumn)";
		$sql .= " WHERE vtiger_crmentity.setype = '$modulename'";
		$adb->query($sql);
		$log->debug("Exiting Settings_Search_Module_Model::UpdateLabels() method ...");
	}

	public static function updateSequenceNumber($modulesSequence) {
		$log = vglobal('log');
		$log->debug("Entering Settings_Search_Module_Model::updateSequenceNumber(" . $modulesSequence . ") method ...");
		$tabIdList = array();
		$db = PearDatabase::getInstance();

		$query = 'UPDATE berli_globalsearch_settings ';
		$query .='LEFT JOIN vtiger_entityname ON vtiger_entityname.tabid = berli_globalsearch_settings.gstabid SET ';
		$query .=' sequence= CASE ';
		foreach ($modulesSequence as $newModuleSequence) {
			$tabId = $newModuleSequence['tabid'];
			$sequence = $newModuleSequence['sequence'];
			$tabIdList[] = $tabId;
			$query .= ' WHEN tabid=' . $tabId . ' THEN ' . $sequence;
		}

		$query .=' END ';

		$query .= ' WHERE tabid IN (' . generateQuestionMarks($tabIdList) . ')';
		$db->pquery($query, array($tabIdList));
		$log->debug("Exiting Settings_Search_Module_Model::updateSequenceNumber() method ...");
	}
}
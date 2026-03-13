<?php
/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */
require_once 'include/events/VTEventHandler.inc';

class Settings_Search_RecordSearchLabelUpdater_Handler extends VTEventHandler {

	function handleEvent($eventName, $data) {
		global $adb;

		if ($eventName == 'vtiger.entity.aftersave') {
            $module = $data->getModuleName();
            $id = $data->getId();
            if($module != "Users"){
                $labelInfo = self::computeCRMRecordLabelsForSearch($module, $id,true);
				if (count($labelInfo) > 0) {
					$label = decode_html($labelInfo[$id]['name']);
					$search = decode_html($labelInfo[$id]['search']);
                    $res = $adb->pquery('SELECT * FROM berli_globalsearch_data where gscrmid =?', array($id));
                    $rows = $adb->num_rows($res);
					if ($rows==0) {
							$adb->pquery('INSERT INTO `berli_globalsearch_data` (`gscrmid`, `searchlabel`) VALUES (?,?)', array($id,''));
					}
					if ($search!='') {
						$adb->pquery('UPDATE berli_globalsearch_data INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = berli_globalsearch_data.gscrmid SET searchlabel=? WHERE crmid=?', array($search, $id));
					}
					if ($label!='') {
						$adb->pquery('UPDATE berli_globalsearch_data INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = berli_globalsearch_data.gscrmid SET label=? WHERE crmid=?', array($label, $id));
					}
				}
            }
		}
	}

	public function computeCRMRecordLabelsForSearch($module, $ids) {
		$log = vglobal('log');
		$log->debug("Entering Settings_Search_Handlers_Model::computeCRMRecordLabelsForSearch() method ...");
		$adb = PearDatabase::getInstance();
		if (!is_array($ids))
			$ids = array($ids);

		if ($module == 'Events') {
			$module = 'Calendar';
		}

		if ($module) {
			$entityDisplay = array();
			if ($ids) {
				$moduleModel = Vtiger_Module_Model::getInstance($module);
				$moduleFields = $moduleModel->getFields();
				$idColumn = $moduleModel->basetableid;
				$tableName = $moduleModel->basetable;
				
				$sqlQuery ="SELECT searchcolumn, fieldname FROM vtiger_entityname
						    LEFT JOIN berli_globalsearch_settings ON berli_globalsearch_settings.gstabid = vtiger_entityname.tabid
							WHERE vtiger_entityname.modulename = ?;";

				$columnsResult = $adb->pquery($sqlQuery, array($module));
				if ($columnsResult && $adb->num_rows($columnsResult) > 0) {
					$colRow = $adb->getNextRow($columnsResult, false);
					$searchColumn = explode(',', $colRow['searchcolumn']);
					$fieldName = explode(',', $colRow['fieldname']);
				} else {
					return $entityDisplay;
				}
				$columns_search_for = array_merge($searchColumn, $fieldName);
				//remove empty entries
				$columns_search_for = array_map('trim', $columns_search_for);
				$columns = array_unique(array_filter($columns_search_for));
				// transform columns to fieldnames for QueryGenerator
				foreach ($columns AS &$value) {
					$tmp = $moduleModel->getFieldByColumn($value);
					if ($tmp) {
						$value = $tmp->get('name');
					}
				}

				$currentUserModel = Users_Record_Model::getCurrentUserModel();
				$queryGenerator = new QueryGenerator($module, $currentUserModel);
				$queryGenerator->setFields(array_merge(array('id'), $columns));
				$sql = $queryGenerator->getQuery()." AND $tableName.$idColumn IN(".generateQuestionMarks($ids).");";
				$result = $adb->pquery($sql, $ids);
				if (!$result) {
					syslog(LOG_DEBUG, __FILE__);
					syslog(LOG_DEBUG, serialize($sql));
					syslog(LOG_DEBUG, serialize($adb->database->errorMsg()));
					return $entityDisplay;
				}

				while ($row = $adb->getNextRow($result, false)) {
					$label_name = array();
					$label_search = array();
					foreach ($columns AS $fieldName) {
						if ($moduleFields[$fieldName]) {
							$columnName = $moduleFields[$fieldName]->get('column');
							if (in_array($moduleFields[$fieldName]->get('uitype'), array(10, 51,73,76, 75, 81))) {
								if ($row[$columnName] > 0) {
									//get module of the related record if exists
									$setype = 'SELECT setype FROM vtiger_crmentity WHERE crmid = ?';
									$setype_result = $adb->pquery($setype, array($row[$columnName]));
									$entityinfo = 'SELECT tablename, fieldname, entityidfield FROM vtiger_entityname WHERE modulename = ?';
									$entityinfo_result = $adb->pquery($entityinfo, array($adb->query_result($setype_result, 0, "setype")));
									$label_query = "Select ".$adb->query_result($entityinfo_result, 0, "fieldname")." from ".$adb->query_result($entityinfo_result, 0, "tablename")." where ".$adb->query_result($entityinfo_result, 0, "entityidfield")." =?";
									$label_result = $adb->pquery($label_query, array($row[$columnName]));
									$label_name[$columnName] = $adb->query_result($label_result, 0, $adb->query_result($entityinfo_result, 0, "fieldname"));
								}
							}
							else {
								$label_search[] = $row[$columnName];
							}
						}
					}
					$entityDisplay[$row[$idColumn]] = array('name' => implode(' |', array_filter($label_name)), 'search' => implode(' |', array_filter($label_search)));
				}
			}
			return $entityDisplay;
		}
		$log->debug("Exiting Settings_Search_Handlers_Model::computeCRMRecordLabelsForSearch() method ...");
	}
}
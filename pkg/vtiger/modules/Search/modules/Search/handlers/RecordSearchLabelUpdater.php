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
				if ($module == 'Groups') {
					$metainfo = array('tablename' => 'vtiger_groups', 'entityidfield' => 'groupid', 'fieldname' => 'groupname');
					/* } else if ($module == 'DocumentFolders') { 
					  $metainfo = array('tablename' => 'vtiger_attachmentsfolder','entityidfield' => 'folderid','fieldname' => 'foldername'); */
				} 
				else {
					$metainfo = Vtiger_Functions::getEntityModuleInfo($module);
				}
				$modulename = $metainfo['modulename'];
				$table = $metainfo['tablename'];
				$idcolumn = $metainfo['entityidfield'];
				$columns_name = $metainfo['fieldname'];
				$columns_name_arr = explode(',',$columns_name);

				$primary = CRMEntity::getInstance($modulename);
				$moduleothertables = $primary->tab_name_index;
				$moduleothertables = array_diff($moduleothertables, array('crmid'));
				$otherquery ='';
				foreach ($moduleothertables as $othertable => $otherindex) {
					if (isset($moduleothertables)) {
						$otherquery .= " LEFT JOIN $othertable ON $othertable.$otherindex=$table.$idcolumn";
					} 
					else {
						$otherquery .= '';
					}
				}
				$sqlquery ="SELECT searchcolumn, gstabid FROM  berli_globalsearch_settings LEFT JOIN vtiger_entityname ON vtiger_entityname.tabid = berli_globalsearch_settings.gstabid ";
				//$sqlquery .= $otherquery;
				$sqlquery .= " WHERE vtiger_entityname.modulename = '".$modulename."' ";

				$columns_search = $adb->pquery($sqlquery, array());
				$searchcolumn = $adb->query_result($columns_search, 0, 'searchcolumn');
				$gstabid = $adb->query_result($columns_search, 0, 'gstabid');
				if (empty ($searchcolumn)) {
						$entity_search_query = "SELECT fieldname FROM `vtiger_entityname` where tabid = ?";
						$entity_search = $adb->pquery($entity_search_query, array($gstabid));
						$fieldname= $adb->query_result($entity_search, 0, 'fieldname');
						$columns_search_for['searchcolumn'] = $fieldname;
				}
				else {
					$columns_search_for['searchcolumn'] = $searchcolumn;
				}
				$columns_search = explode(',', $columns_search_for['searchcolumn']);
				$columns = array_unique(array_merge($columns_name_arr, $columns_search));
				//remove empty entries
				$columns = array_filter($columns);

				$moduleothertableslim = $moduleothertables;
				unset($moduleothertableslim[$table], $moduleothertableslim['vtiger_crmentity']);

				foreach ($moduleothertableslim as $othertable => $otherindex) {
					if (isset($moduleothertableslim)) {
						$otherqueryslim .= " LEFT JOIN $othertable ON $othertable.$otherindex=$table.$idcolumn";
					} 
					else {
						$otherqueryslim .= '';
					}
				}

				$full_idcolumn = $table.'.'.$idcolumn;
				$sql = sprintf('SELECT ' . implode(',', array_filter($columns)) . ', %s AS id FROM %s %s WHERE %s IN (%s)', $full_idcolumn, $table, $otherqueryslim, $full_idcolumn, generateQuestionMarks($ids));
				$result = $adb->pquery($sql, $ids);

				$moduleInfo = Vtiger_Functions::getModuleFieldInfos($module);
				$moduleInfoExtend = [];
				if (count($moduleInfo) > 0) {
					foreach ($moduleInfo as $field => $fieldInfo) {
						$moduleInfoExtend[$fieldInfo['columnname']] = $fieldInfo;
					}
				}

				for ($i = 0; $i < $adb->num_rows($result); $i++) {
					$row = $adb->raw_query_result_rowdata($result, $i);
					$label_name = array();
					$label_search = array();
					foreach ($columns_search as $columnName) {
						if ($moduleInfoExtend && in_array($moduleInfoExtend[$columnName]['uitype'], array(10, 51,73,76, 75, 81))) {
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
					$entityDisplay[$row['id']] = array('name' => implode(' |', array_filter($label_name)), 'search' => implode(' |', array_filter($label_search)));
				}
			}
			return $entityDisplay;
		}
		$log->debug("Exiting Settings_Search_Handlers_Model::computeCRMRecordLabelsForSearch() method ...");
	}
}
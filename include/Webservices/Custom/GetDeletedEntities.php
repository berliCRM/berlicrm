<?php
/************************************************************************************************************************************************************ 
Description: Get Relations of an entity
  Parameters:
    - string modules: optional filter for modules (JSON)
	- string (date) searchSinceDate: optional filter for modifiedtime
  Returns:
    - array($moduleName => list of ids and modifiedtimes)
Copyright (C) crm-now GmbH
All Rights Reserved.
************************************************************************************************************************************************************ */

function berli_getDeletedEntities($modules, $searchSinceDate, $user) {
	$adb = PearDatabase::getInstance();
	
	$query = "SELECT vtiger_crmentity.crmid, vtiger_crmentity.setype, vtiger_crmentity.modifiedtime, vtiger_ws_entity.id FROM vtiger_crmentity
			  INNER JOIN vtiger_ws_entity ON vtiger_ws_entity.name = vtiger_crmentity.setype
			  WHERE vtiger_crmentity.deleted = 1";
	$params = array();
	
	if (!empty($modules)) {
		if (!is_array($modules)) {
			$modules = array($modules);
		}
		$query .= " AND setype IN(".generateQuestionMarks($modules).")";
		$params = $modules;
	}
	
	if (!empty($searchSinceDate)) {
		$query .= " AND modifiedtime >= ?";
		$params[] = $searchSinceDate;
	}
	$query .= " ORDER BY vtiger_crmentity.setype;";
	$res = $adb->pquery($query, $params);
	if (!$res) {
		throw new WebServiceException(WebServiceErrorCode::$QUERYSYNTAX, "Error with query: $query, parameters:".serialize($params));
	}
	
	
	$arrRet = array();
	while ($row = $adb->getNextRow($res, false)) {
		$arrRet[$row['setype']][] = array("{$row['id']}x{$row['crmid']}", $row['modifiedtime']);
		set_time_limit(0);
	}
	
	if (empty($modules) || $modules == 'Leads' || (is_array($modules) && in_array('Leads', $modules))) {
		$query = "SELECT vtiger_crmentity.crmid, vtiger_crmentity.setype, vtiger_crmentity.modifiedtime, vtiger_ws_entity.id FROM vtiger_crmentity
				  INNER JOIN vtiger_ws_entity ON vtiger_ws_entity.name = vtiger_crmentity.setype
				  INNER JOIN vtiger_leaddetails ON vtiger_leaddetails.leadid = vtiger_crmentity.crmid
				  WHERE vtiger_crmentity.deleted = 0 AND vtiger_leaddetails.converted = 1";
		if (!empty($searchSinceDate)) {
			$query .= " AND modifiedtime >= ?";
		}
		$res = $adb->pquery($query, array($searchSinceDate));
		if (!$res) {
			throw new WebServiceException(WebServiceErrorCode::$QUERYSYNTAX, "Error with query: $query");
		}
		while ($row = $adb->getNextRow($res, false)) {
			$arrRet[$row['setype']][] = array("{$row['id']}x{$row['crmid']}", $row['modifiedtime']);
			set_time_limit(0);
		}
	}
	
	return $arrRet;
}
<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Modified by crm-now GmbH, www.crm-now.com
 ************************************************************************************/

include_once dirname(__FILE__) . '/QueryWithGrouping.php';

class crmtogo_WS_RelatedRecords extends crmtogo_WS_QueryWithGrouping {
	
	function process(crmtogo_API_Request $request) {
		$db = PearDatabase::getInstance();
		$current_user = $this->getActiveUser();
		$response = new crmtogo_API_Response();

		$record = $request->get('record');
		$currentPage = $request->get('page', 0);
		
		// Input validation
		if (empty($record)) {
			$response->setError(1001, 'Record id is empty');
			return $response;
		}
		$recordid = vtws_getIdComponents($record);
		$recordid = $recordid[1];
	
		$module = crmtogo_WS_Utils::detectModulenameFromRecordId($record);
		
		//related module currently supported
		if($module == 'Accounts' || $module == 'Potentials') {
			$relatedmodule = Array ('Contacts','Potentials','HelpDesk','Documents','Assets', 'Calendar');
		}
		else {
			$relatedmodule = Array ('Contacts','Potentials','HelpDesk','Documents','Assets');
		}
		//make sure there is no reference to inactive modules
		$relatedTypes = vtws_relatedtypes($module, $current_user);
		$inactivemodules = array_diff ($relatedmodule, $relatedTypes['types']);
		$relatedmodule =  array_diff ($relatedmodule, $inactivemodules);
		
		$activemodule = $this->sessionGet('_MODULES');
		foreach($activemodule as $amodule) {
			if (in_array($amodule->name(), $relatedmodule)) {
				if ($amodule->name() != $module) {
				$active_related_module[] = $amodule->name();
				}
			}
		}
		// We obtain the active modules and compare them with the modules that we allow to display.
		foreach ($active_related_module as $relmod) {
			$functionHandler = crmtogo_WS_Utils::getRelatedFunctionHandler($module, $relmod); 
			$fieldmodel = new crmtogo_UI_FieldModel();

			if ($functionHandler) {
				$sourceFocus = CRMEntity::getInstance($module);
				// Leo - 28-03-2017 
				if($relmod == 'Calendar'){
					$relationResult = array();
					$id = explode('x', $record);
					$query = "SELECT vtiger_activity.activityid, vtiger_activity.activitytype FROM vtiger_activity INNER JOIN vtiger_seactivityrel on vtiger_seactivityrel.activityid = vtiger_activity.activityid
						INNER JOIN vtiger_crmentity on vtiger_seactivityrel.activityid = vtiger_crmentity.crmid
						WHERE vtiger_crmentity.deleted = 0 AND vtiger_activity.activitytype != 'Emails' AND vtiger_seactivityrel.crmid = ?";
					$result = $db->pquery($query, array($id[1]));
					for($i=0; $i < $db->num_rows($result); $i++){
						$id = $db->query_result($result, $i, 'activityid');
						if($db->query_result($result, $i, 'activitytype') == 'Task'){
							$wsid = crmtogo_WS_Utils::getEntityModuleWSId('Calendar');
							$query = 'SELECT * FROM Calendar WHERE id = '.$wsid.'x'.$id.' ;';
							$reg = vtws_query($query, $current_user);
							$relationResult[$i] = $reg[0];
						}
						else {
							$wsid = crmtogo_WS_Utils::getEntityModuleWSId('Events');
							$query = 'SELECT * FROM Events WHERE id = '.$wsid.'x'.$id.' ;';
							$reg = vtws_query($query, $current_user);
							$relationResult[$i]=$reg[0];
						}
					}
				// Fin
				}
				else{
					$relationResult = vtws_retrieve_related($record, $relmod, $relmod, $current_user);
				}
				if (!empty($relationResult)) {
					foreach ($relationResult as $relkey => $relvalue) {
						if (is_array ($relvalue)) {
							if($relmod != 'Calendar'){
								$trueidarr = explode('x', $relvalue['id']);
								$trueid = $trueidarr[1];
								$relatedRecords[$relmod][] = $trueid;
							}else{
								$relatedRecords[$relmod][] = $relvalue['id'];
							}
						}
					}
				}
				else {
					$relatedRecords[$relmod][]='0';
				}
				$response->setResult($relatedRecords);
			}
			else {
				$relatedRecords[$relmod][]='0';
			}
		}
		return $response;
	}
}
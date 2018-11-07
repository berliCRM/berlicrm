<?php
/************************************************************************************************************************************************************ 
Description: Set Product Relation
Update Document Relations for given Document id and related ids
  Parameters:
    -string productid: is the product id obtained by webservice call to product entity
    -string relids: is a JSON encoded string that contains the webservice ids the product will be relate to
	-string preserve: is a var that dictates the deletion behavior
  Returns:
    - array('relids'=>list of relids as provided)
  Comments:
    - this function respects the vtiger CRM profile privilege system and returns only entities accessible by the user accessing the REST functionality
Copyright (C) crm-now GmbH
All Rights Reserved.
************************************************************************************************************************************************************ */

function vtws_update_product_relations($productid, $relids, $preserve='true') {
	global $log, $adb, $current_user;
	if (!isset($preserve) || $preserve == "" || strtolower($preserve) == "true") {
		$preserve=true;
	}
	else {
		$preserve=false;
	}

	$webserviceObject = VtigerWebserviceObject::fromId($adb, $productid);
	$handlerPath = $webserviceObject->getHandlerPath();
	$handlerClass = $webserviceObject->getHandlerClass();

	require_once $handlerPath;
  
	$handler = new $handlerClass($webserviceObject, $current_user, $adb, $log);
	$meta = $handler->getMeta();
	$entityName = $meta->getObjectEntityName($productid);
	//crm-now: careful, 5.2.1 doesn't have the argument up front
	if (isset($GLOBALS['vtiger_current_version']) AND version_compare($GLOBALS['vtiger_current_version'], "5.3.0", "<")) {
		$types = vtws_listtypes($current_user);
	}
	else {
		$types = vtws_listtypes(null, $current_user);
	}
	
	if(!in_array($entityName,$types['types'])) {
		throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Permission to perform the operation is denied");
	}
	if($meta->hasReadAccess()!==true){
		throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Permission to read is denied");
	}

	if($entityName !== $webserviceObject->getEntityName()){
		throw new WebServiceException(WebServiceErrorCode::$INVALIDID,"Id specified is invalid");
	}
	
	if($meta->hasWriteAccess()!==true)
	{
		throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Permission to write is denied");
	}

	$idComponents = vtws_getIdComponents($productid);
	if(!$meta->exists($idComponents[1])){
		throw new WebServiceException(WebServiceErrorCode::$RECORDNOTFOUND,"Record you are trying to access was not found");
	}

	// Process petition
	$arr_relids = json_decode($relids, true);

	//get crm object (webservice)
	// $tabid = $meta->getTabId();
	// $crmObject = new VtigerCRMObject($tabid, true);
	// $crmObject->setObjectId($idComponents[1]);
	// //get crm object (module)
	// $document = $crmObject->getInstance();
	$arr_metas = array();
	
	if (!$preserve) {
		//keep entries that the user has no permission to delete
		$kquery = "SELECT vtiger_seproductsrel.crmid, vtiger_ws_entity.id, vtiger_crmentity.setype FROM vtiger_seproductsrel
				   INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_seproductsrel.crmid
				   INNER JOIN vtiger_ws_entity ON vtiger_ws_entity.name = vtiger_crmentity.setype
				   WHERE vtiger_seproductsrel.crmid = ?";
		$kresult = $adb->pquery($kquery, array($idComponents[1]));
		if (!$kresult) {
			throw new WebServiceException(WebServiceErrorCode::$DATABASEQUERYERROR,$adb->database->ErrorMsg());
		}
		$arr_keep = array();
		while ($row = $adb->fetch_array($kresult)) {
			$wsoId = $row['id'].'x'.$row['crmid'];
			if (!isset($arr_metas[$row['setype']])) {
				$handler = vtws_getModuleHandlerFromId($wsoId, $current_user);
				$arr_metas[$row['setype']] = $handler->getMeta();
			}
			$kMeta = $arr_metas[$row['setype']];
			if (!($kMeta->hasPermission(EntityMeta::$UPDATE, $wsoId) && $kMeta->hasPermission(EntityMeta::$DELETE, $wsoId))) {
				$arr_keep[] = $row['crmid'];
			}
		}
		if (count($arr_keep) > 0) {
			$str_keep = " AND crmid NOT IN (".generateQuestionMarks($arr_keep).")";
		}
		else {
			$str_keep = "";
		}
			
		//delete old relations, unfortunatly no routine given by object
		$query = "DELETE FROM vtiger_seproductsrel WHERE productid = ?".$str_keep;
		$arr_questions = array_merge(array($idComponents[1]), $arr_keep);
		if (!$adb->pquery($query, $arr_questions)) {
			throw new WebServiceException(WebServiceErrorCode::$DATABASEQUERYERROR,$adb->database->ErrorMsg());
		}
	}
	//fill new relations
	$arr_idToName = array();
	foreach ($arr_relids as $id) {
		$idComponents2 = vtws_getIdComponents(trim($id));
		if (!isset($arr_idToName[$idComponents2[0]])) {
			$mquery = "SELECT name FROM vtiger_ws_entity WHERE id = ?;";
			$mres = $adb->pquery($mquery, array($idComponents2[0]));
			$arr_idToName[$idComponents2[0]] = $adb->query_result($mres, 0, 'name');
		}
		$mod = $arr_idToName[$idComponents2[0]];
		if (!isset($arr_metas[$mod])) {
			$handler = vtws_getModuleHandlerFromId($id, $current_user);
			$arr_metas[$mod] = $handler->getMeta();
		}
		$meta = $arr_metas[$mod];
		if ($meta->hasPermission(EntityMeta::$RETRIEVE, $id)) {
			$uquery = "INSERT INTO vtiger_seproductsrel VALUES (?, ?, ?);";
			if (!$adb->pquery($uquery, array($idComponents2[1], $idComponents[1], $mod))) {
				throw new WebServiceException(WebServiceErrorCode::$DATABASEQUERYERROR,$adb->database->ErrorMsg());
			}
		}
	}
	
	// Format return array
	$result = array('relids' => json_encode($arr_relids));

	VTWS_PreserveGlobal::flush();
	return $result;
}
?>
<?php
/************************************************************************************************************************************************************ 
CWC - CRM Word Connector
Description: CWC Campaign Module
Update Document Relations for given Document id and related ids
  Parameters:
    -string docid: is the document id obtained by webservice call to document entity
    -string relids: is a string that contains the values of related ids, separated by commas format "1x1,1x2,1x3..."
	-boolean preserve: is a var that dictates the deletion behavior
  Returns:
    - array('relids'=>list of relids as provided)
  Comments:
    - this function respects the vtiger CRM profile privilege system and returns only entities accessible by the user accessing the rest functionality
Copyright (C) crm-now GmbH
All Rights Reserved.
************************************************************************************************************************************************************ */

function vtws_update_document_relations($docid, $relids, $preserve=true)
{
	global $log, $adb, $current_user;
	if (!isset($preserve) || $preserve == "" || strtolower($preserve) == "true") {
		$preserve=true;
	}
	else {
		$preserve=false;
	}

	$webserviceObject = VtigerWebserviceObject::fromId($adb, $docid);
	$handlerPath = $webserviceObject->getHandlerPath();
	$handlerClass = $webserviceObject->getHandlerClass();

	require_once $handlerPath;
  
	$handler = new $handlerClass($webserviceObject, $current_user, $adb, $log);
	$meta = $handler->getMeta();
	$entityName = $meta->getObjectEntityName($docid);
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

	if(!$meta->hasPermission(EntityMeta::$RETRIEVE,$docid)){
		throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Permission to read given object is denied");
	}
	
	if($meta->hasWriteAccess()!==true)
	{
		throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Permission to write is denied");
	}

	$idComponents = vtws_getIdComponents($docid);
	if(!$meta->exists($idComponents[1])){
		throw new WebServiceException(WebServiceErrorCode::$RECORDNOTFOUND,"Record you are trying to access was not found");
	}

	// Process petition
	$arr_relids = explode(",", $relids);
	
	if (count($arr_relids) < 1)	{
		//single id was given
		if ($relids != "") {
			$arr_relids[0] = $relids;
		}
	}

	//get crm object (webservice)
	$tabid = $meta->getTabId();
	$crmObject = new VtigerCRMObject($tabid, true);
	$crmObject->setObjectId($idComponents[1]);
	//get crm object (module)
	$document = $crmObject->getInstance();
	$arr_metas = array();
	
	if (!$preserve) {
		//keep entries that the user has no permission to delete
		$kquery = "SELECT vtiger_senotesrel.crmid, vtiger_ws_entity.id, vtiger_crmentity.setype FROM vtiger_senotesrel 
				   INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_senotesrel.crmid
				   INNER JOIN vtiger_ws_entity ON vtiger_ws_entity.name = vtiger_crmentity.setype
				   WHERE vtiger_senotesrel.notesid = ?";
		$kresult = $adb->pquery($kquery, array($idComponents[1]));
		if (!$kresult) {
			throw new WebServiceException(WebServiceErrorCode::$DATABASEQUERYERROR,$adb->database->ErrorMsg());
		}
		$arr_keep = array();
		while ($row = $adb->fetch_array($kresult))
		{
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
		$query = "DELETE FROM vtiger_senotesrel WHERE notesid = ?".$str_keep;
		$arr_questions = array_merge(array($idComponents[1]), $arr_keep);
		if (!$adb->pquery($query, $arr_questions)) {
			throw new WebServiceException(WebServiceErrorCode::$DATABASEQUERYERROR,$adb->database->ErrorMsg());
		}
	}
	//fill new relations
	foreach ($arr_relids as $id) {
		$mod = vtws_getName($id, $current_user);
		if (!isset($arr_metas[$mod])) {
			$handler = vtws_getModuleHandlerFromId($id, $current_user);
			$arr_metas[$mod] = $handler->getMeta();
		}
		$meta = $arr_metas[$mod];
		if ($meta->hasPermission(EntityMeta::$RETRIEVE, $id)) {
			$idComponents2 = vtws_getIdComponents(trim($id));
			$document->insertintonotesrel($idComponents2[1], $idComponents[1]);
		}
	}
	
	// Format return array
	$result = array('relids' => implode(',', $arr_relids));

	VTWS_PreserveGlobal::flush();
	return $result;
}
?>
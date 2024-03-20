<?php
/************************************************************************************************************************************************************ 
CWC - CRM Word Connector
Description: CWC Module
Get Document Relations for given Document ids
  Parameters:
    -array docids: are the document ids obtained by webservice call for document entity
  Returns:
    - array(docid1=>list of relids, docid2=>list of relids)
  Comments:
    - this function respects the vtiger CRM profile privilege system and returns only entities accessible by the user accessing the rest functionality
Copyright (C) crm-now GmbH
All Rights Reserved.
************************************************************************************************************************************************************ */

function vtws_get_document_relations($docids) {
	global $log, $adb, $current_user;
	$arr_docs = json_decode($docids);
	$arr_docs2 = array();
	
	$arr_return = array();
	
	foreach ($arr_docs AS $docid) {
		$webserviceObject = VtigerWebserviceObject::fromId($adb, $docid);
		$handlerPath = $webserviceObject->getHandlerPath();
		$handlerClass = $webserviceObject->getHandlerClass();

		require_once $handlerPath;
	  
		$handler = new $handlerClass($webserviceObject, $current_user, $adb, $log);
		$meta = $handler->getMeta();
		
		if($meta->hasReadAccess()!==true || !$meta->hasPermission(EntityMeta::$RETRIEVE,$docid)) {
			$arr_return['failed'][$docid] = "Permission to read given object is denied";
			continue;
		}
		$idComponents = vtws_getIdComponents($docid);
		if(!$meta->exists($idComponents[1])) {
			$arr_return['failed'][$docid] = "Record you are trying to access was not found";
			continue;
		}
		$arr_docs2[] = $idComponents[1];
	}
	
	if (count($arr_docs2) > 0) {
		$query = "SELECT vtiger_senotesrel.crmid, vtiger_ws_entity.id, vtiger_senotesrel.notesid, ws_ent2.id AS id2, vtiger_crmentity.setype FROM vtiger_senotesrel
				  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_senotesrel.crmid
				  INNER JOIN vtiger_ws_entity ON vtiger_ws_entity.name = vtiger_crmentity.setype
				  INNER JOIN vtiger_crmentity AS ent2 ON ent2.crmid = vtiger_senotesrel.notesid
				  INNER JOIN vtiger_ws_entity AS ws_ent2 ON ws_ent2.name = ent2.setype
				  WHERE (vtiger_senotesrel.notesid IN (".generateQuestionMarks($arr_docs2).") || vtiger_senotesrel.crmid IN (".generateQuestionMarks($arr_docs2).")) AND vtiger_crmentity.deleted = 0;";
				  
		$res = $adb->pquery($query, array($arr_docs2, $arr_docs2));
		if (!$res) {
			throw new WebServiceException(WebServiceErrorCode::$DATABASEQUERYERROR,$adb->database->ErrorMsg());
		}
		
		while ($row = $adb->fetch_row($res)) {
			$wsoId = $row['id'].'x'.$row['crmid'];
			$wsoId2 = $row['id2'].'x'.$row['notesid'];
			$arr_return['found'][$row['setype']][$wsoId][] = $wsoId2;
		}
	}
	
	VTWS_PreserveGlobal::flush();
	return $arr_return;
}
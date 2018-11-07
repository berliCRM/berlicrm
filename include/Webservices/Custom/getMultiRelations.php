<?php
/************************************************************************************************************************************************************ 
Description: Get Relations of an entity
Update Document Relations for given Document id and related ids
  Parameters:
    -string id: is the ws entity id 
  Returns:
    - array('relids'=>list of relids as provided)
  Comments:
	- this function provides only relationships located in vtiger_crmentityrel table
    - this function respects the vtiger CRM profile privilege system and returns only entities accessible by the user accessing the REST functionality
Copyright (C) crm-now GmbH
All Rights Reserved.
************************************************************************************************************************************************************ */

function berli_get_multi_relations($id, $user) {
	global $log;
	$log->debug("Entering berli_get_multi_relations(".$id.") method ...");
	$db = PearDatabase::getInstance();

	$webserviceObject = VtigerWebserviceObject::fromId($db,$id);
	$handlerPath = $webserviceObject->getHandlerPath();
	$handlerClass = $webserviceObject->getHandlerClass();
		
	require_once $handlerPath;
		
	$handler = new $handlerClass($webserviceObject,$user,$db,$log);
	$meta = $handler->getMeta();
	$entityName = $meta->getObjectEntityName($id);
	$types = vtws_listtypes(null, $user);
	if(!in_array($entityName,$types['types'])){
		throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Permission to perform the operation is denied");
	}
	if($meta->hasReadAccess()!==true){
		throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Permission to write is denied");
	}
	if($entityName !== $webserviceObject->getEntityName()){
		throw new WebServiceException(WebServiceErrorCode::$INVALIDID,"Id specified is incorrect");
	}
	
	if(!$meta->hasPermission(EntityMeta::$RETRIEVE,$id)){
		throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Permission to read given object is denied");
	}
	$idComponents = vtws_getIdComponents($id);
	if(!$meta->exists($idComponents[1])){
		throw new WebServiceException(WebServiceErrorCode::$RECORDNOTFOUND,"Record you are trying to access is not found");
	}
		
	// Process petition
	if ($entityName != 'Products') {
		$result = $db->pquery("select * from vtiger_crmentityrel where crmid=? and module = ?", array($idComponents[1], $entityName));
		if($result === false){
			throw new WebServiceException(WebServiceErrorCode::$RECORDNOTFOUND,"Record you are trying to access is not found");
		}
		$rowCount = $db->num_rows($result);
		$relModuleArr = array ();
		for($i = 0; $i < $rowCount; ++$i) {
			$relatedModule = $db->query_result($result,$i,'relmodule');
			$ws_rel_id = vtws_getWebserviceEntityId($relatedModule, $db->query_result($result,$i,'crmid'));
			if (multi_relations_check_permissions($ws_rel_id, $user) == true) {
				$relID[$relatedModule][$i] = $ws_rel_id;
			}
		}
		
		$result = $db->pquery("select * from vtiger_crmentityrel where relcrmid=? and relmodule = ?", array($idComponents[1], $entityName));
		if($result === false){
			throw new WebServiceException(WebServiceErrorCode::$RECORDNOTFOUND,"Record you are trying to access is not found");
		}
		$rowCount = $db->num_rows($result);
		for($i = 0; $i < $rowCount; ++$i) {
			$relatedModule = $db->query_result($result,$i,'module');
			$ws_rel_id = vtws_getWebserviceEntityId($relatedModule, $db->query_result($result,$i,'crmid'));
			if (multi_relations_check_permissions($ws_rel_id, $user) == true) {
				$relID[$relatedModule][$i] = $ws_rel_id;
			}
		}
	}
	else {
	// special for products
		$result = $db->pquery("select * from vtiger_seproductsrel where productid=? ", array($idComponents[1]));
		if($result === false){
			throw new WebServiceException(WebServiceErrorCode::$RECORDNOTFOUND,"Record you are trying to access is not found");
		}
		$rowCount = $db->num_rows($result);
		$relModuleArr = array ();
		for($i = 0; $i < $rowCount; ++$i) {
			$relatedModule = $db->query_result($result,$i,'setype');
			$ws_rel_id = vtws_getWebserviceEntityId($relatedModule, $db->query_result($result,$i,'crmid'));
			if (multi_relations_check_permissions($ws_rel_id, $user) == true) {
				$relID[$relatedModule][$i] = $ws_rel_id;
			}
		}
	}
	$rel = array();

	foreach  ($relID as $rel_module => $rel_entries) {
		$rel[$rel_module] = $rel_entries;
	}
	$result = $rel;

	VTWS_PreserveGlobal::flush();
	$log->debug("Leaving berli_get_multi_relations(".$id.") method ...");
	return $result;
}

function multi_relations_check_permissions ($wsid, $user) {
	global $log;
	$log->debug("Entering multi_relations_check_permissions(".$wsid.") method ...");
	$db = PearDatabase::getInstance();
	$rel_webserviceObject = VtigerWebserviceObject::fromId($db,$wsid);
	$rel_handlerPath = $rel_webserviceObject->getHandlerPath();
	$rel_handlerClass = $rel_webserviceObject->getHandlerClass();
	require_once $rel_handlerPath;
		
	$rel_handler = new $rel_handlerClass($rel_webserviceObject,$user,$db,$log);
	$rel_meta = $rel_handler->getMeta();
	$rel_entityName = $rel_meta->getObjectEntityName($wsid);
	$types = vtws_listtypes(null, $user);
	
	//validate access
	$idComponents = vtws_getIdComponents($wsid);
	if(in_array($rel_entityName,$types['types']) && $rel_meta->hasReadAccess()==true && $rel_entityName == $rel_webserviceObject->getEntityName() && $rel_meta->hasPermission(EntityMeta::$RETRIEVE,$wsid) && $rel_meta->exists($idComponents[1])){
		$log->debug("Leaving multi_relations_check_permissions(".$wsid.") method with TRUE...");
		return true;
	}
	else {
		$log->debug("Leaving multi_relations_check_permissions(".$wsid.") method with FALSE...");
		return false;
	}
}

?>
<?php
/************************************************************************************************************************************************************ 
Description: Get Relations of an entity
  Parameters:
    - string id: is the ws entity id
	- string relModule: all for not filter or module name for a filter
  Returns:
    - array($moduleName => list of relids as provided)
  Comments:
    - this function respects the vtiger CRM profile privilege system and returns only entities accessible by the user accessing the REST functionality
Copyright (C) crm-now GmbH
All Rights Reserved.
************************************************************************************************************************************************************ */

function berli_get_new_multi_relations($ids, $relModule = 'all', $user) {
	
	if (empty($relModule)) {
		$relModule = 'all';
	}
	
	$ids = json_decode($ids);
	if (!is_array($ids)) {
		$ids = array($ids);
	}
	
	
	$db = PearDatabase::getInstance();
	$arrRet = array();
	foreach ($ids AS $id) {
		$webserviceObject = VtigerWebserviceObject::fromId($db,$id);
		if (!isset($handlerPath)) {
			$handlerPath = $webserviceObject->getHandlerPath();
			$handlerClass = $webserviceObject->getHandlerClass();
				
			require_once $handlerPath;
				
			$handler = new $handlerClass($webserviceObject,$user,$db,$log);
			$meta = $handler->getMeta();
			$entityName = $meta->getObjectEntityName($id);
			$types = vtws_listtypes(null, $user);
		}
		if(!in_array($entityName,$types['types'])){
			throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Permission to perform the operation is denied");
		}
		if($meta->hasReadAccess()!==true){
			throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Permission to read is denied");
		}
		if($entityName !== $webserviceObject->getEntityName()){
			throw new WebServiceException(WebServiceErrorCode::$INVALIDID,"Id specified is incorrect");
		}
		
		if(!$meta->hasPermission(EntityMeta::$RETRIEVE,$id)){
			throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Permission to read given object is denied");
		}
		$idComponents = vtws_getIdComponents($id);
		if(!$meta->exists($idComponents[1])){
			throw new WebServiceException(WebServiceErrorCode::$RECORDNOTFOUND,"Record you are trying to access was not found");
		}
		
		// set global... it's missing?
		global $currentModule;
		$currentModule = $entityName;
		
		// start functionality
		// get relation functions
		$params = array();
		
		$query = "SELECT * FROM vtiger_relatedlists
				  INNER JOIN vtiger_tab ON vtiger_tab.tabid = vtiger_relatedlists.related_tabid
				  WHERE vtiger_relatedlists.tabid = ? AND vtiger_tab.presence IN (0,2)";
		$params[] = getTabid($entityName);
		if ($relModule != 'all') {
			$query .= " AND related_tabid = ?";
			$params[] = getTabid($relModule);
		}
		$res = $db->pquery($query, array($params));
		if (!$res) {
			throw new WebServiceException(WebServiceErrorCode::$QUERYSYNTAX, "Error with query: $query, parameter:".serialize($params));
		}
		
		$parentRecordModel = Vtiger_Record_Model::getInstanceById($idComponents[1], $entityName);
		
		while ($row = $db->fetch_row($res)) {
			// get queries for relations
			$label = html_entity_decode($row['label']);
			$relatedTabid = $row['related_tabid'];
			$relatedModuleName = Vtiger_Functions::getModuleName($relatedTabid);
			$relationListView = Vtiger_RelationListView_Model::getInstance($parentRecordModel, $relatedModuleName, $label);
			$query = trim($relationListView->getRelationQuery());
			
			// init return array for module name
			$arrRet[$id][$relatedModuleName][$label] = array();
			
			if (empty($query)) continue;
			
			$tmpWebserviceObject = VtigerWebserviceObject::fromName($db, $relatedModuleName);
			$tmpHandler = new $handlerClass($tmpWebserviceObject, $user, $db, $log);
			$tmpMeta = $tmpHandler->getMeta();
			$prefix = $tmpWebserviceObject->getEntityId();
			
			// check for access
			if($tmpMeta->hasReadAccess() === true) {
				$res2 = $db->pquery($query, array());
				if (!$res2) {
					throw new WebServiceException(WebServiceErrorCode::$QUERYSYNTAX, "Error with query2: $query");
				}
				
				$alreadySet = array();
				while ($row2 = $db->fetch_row($res2)) {
					$relCrmId = $prefix.'x'.$row2['crmid'];
					if ($tmpMeta->hasPermission(EntityMeta::$RETRIEVE, $relCrmId) && !isset($alreadySet[$relCrmId])) {
						$arrRet[$id][$relatedModuleName][$label][] = $relCrmId;
						$alreadySet[$relCrmId] = $relCrmId;
					}
				}
			}
		}
		set_time_limit(0);
	}
	
	return $arrRet;
}
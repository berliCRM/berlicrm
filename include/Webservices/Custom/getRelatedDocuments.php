<?php
/************************************************************************************************************************************************************ 
Description: Get Documents
get related ids for entities that use vtiger_crmentityrel
  Parameters:
    -string id: is the entity ID 
  Returns:
    - array('Documents'=>list of documents id and detailed information)
  Comments:
    - this function respects the vtiger CRM profile privilege system and returns only entities accessible by the user accessing the REST functionality
Copyright (C) crm-now GmbH
All Rights Reserved.
************************************************************************************************************************************************************ */

function berli_get_document_relations($id, $user) {
	include_once ('include/Webservices/Retrieve.php');
	global $log;
	$log->debug("Entering berli_get_document_relations(".$id.") method ...");
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
	$result = $db->pquery("select * from vtiger_notes
				inner join vtiger_senotesrel on vtiger_senotesrel.notesid= vtiger_notes.notesid
				inner join vtiger_crmentity on vtiger_crmentity.crmid= vtiger_notes.notesid and vtiger_crmentity.deleted=0
				inner join vtiger_crmentity crm2 on crm2.crmid=vtiger_senotesrel.crmid
                LEFT join vtiger_notescf on vtiger_notescf.notesid=vtiger_notes.notesid 
				left join vtiger_seattachmentsrel  on vtiger_seattachmentsrel.crmid =vtiger_notes.notesid
				left join vtiger_attachments on vtiger_seattachmentsrel.attachmentsid = vtiger_attachments.attachmentsid
				 where crm2.crmid=?", array($idComponents[1] ));
	
	if($result === false){
		throw new WebServiceException(WebServiceErrorCode::$RECORDNOTFOUND,"Query error");
	}
	$rowCount = $db->num_rows($result);
	for($i = 0; $i < $rowCount; ++$i) {
		$doc_id = $db->query_result($result,$i,'notesid');
		$ws_doc_id = vtws_getWebserviceEntityId('Documents', $doc_id);
		// $doc_webserviceObject = VtigerWebserviceObject::fromId($db,$ws_doc_id);
		// $doc_handlerPath = $doc_webserviceObject->getHandlerPath();
		// $doc_handlerClass = $doc_webserviceObject->getHandlerClass();
		// require_once $doc_handlerPath;
			
		// $doc_handler = new $doc_handlerClass($doc_webserviceObject,$user,$db,$log);
		// $doc_meta = $doc_handler->getMeta();
		// $doc_entityName = $doc_meta->getObjectEntityName($ws_doc_id);
		// $types = vtws_listtypes(null, $user);
		
		// //validate access
		// if(in_array($doc_entityName,$types['types']) && $doc_meta->hasReadAccess()==true && $doc_entityName == $doc_webserviceObject->getEntityName() && $doc_meta->hasPermission(EntityMeta::$RETRIEVE,$doc_id) && $doc_meta->exists($doc_id)){
			// $relID[$i]['id']  = $doc_id;
			// $relID [$i]['title']  = $db->query_result($result,$i,'title');
			// $relID[$i]['note_no']  = $db->query_result($result,$i,'note_no');
			// $relID [$i]['filename'] = $db->query_result($result,$i,'filename');
			// $relID [$i]['smownerid'] = $db->query_result($result,$i,'smownerid');
			// $relID [$i]['folderid']= $db->query_result($result,$i,'folderid');
			// $relID [$i]['modifiedtime'] = $db->query_result($result,$i,'modifiedtime');
		// }
		try {
			$ele = vtws_retrieve($ws_doc_id, $user);
			$relID[] = $ele;
		} catch (Exception $e) {
			
		}
	}
	 
	// Format return array
	$result = array('Documents' => json_encode($relID));

	VTWS_PreserveGlobal::flush();
	$log->debug("Leaving berli_get_document_relations(".$id.") method ...");
	return $result;
}	
?>
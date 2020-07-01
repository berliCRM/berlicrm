<?php
/************************************************************************************************************************************************************ 
CWC - CRM Word Connector
Description: CWC Campaign Module
Returns related entities of a given campaign record
  Parameters:
    - campaignid is the campaign id obtained by webservice call to campaigns entity
    - returnresults is a string that can take these values: Lead, Contact or Both
      if returnresults = Leads only related lead entities will be returned
      if returnresults = Contacts only related contact entities will be returned
      if returnresults = Accounts only related account entities will be returned
      if returnresults = Both (the default value) all related entities will be returned
  Returns:
    - array('Leads'=>{leadids},'Contacts'=>{contactids},'Accounts'=>{accountids}) where {leadids},{contactids} and {accountids} are strings of rest ids separated by commas: e.g. 4x12,4x34,...
  Comments:
    - this function respects the vtiger CRM profile privilege system and returns only entities accessible by the user accessing the rest functionality
Copyright (C) crm-now GmbH
All Rights Reserved.
************************************************************************************************************************************************************ */

function vtws_get_campaign_entities($campaignid, $returnresults='Both') {
  global $log, $adb, $current_user;
  
  if ($returnresults!='Leads' && $returnresults!='Contacts'  && $returnresults!='Accounts' && $returnresults!='Both') {
    $returnresults = 'Both';
  }

  $webserviceObject = VtigerWebserviceObject::fromId($adb, $campaignid);
  $handlerPath = $webserviceObject->getHandlerPath();
  $handlerClass = $webserviceObject->getHandlerClass();
  
  require_once $handlerPath;
  
  $handler = new $handlerClass($webserviceObject, $current_user, $adb, $log);
  $meta = $handler->getMeta();
  $entityName = $meta->getObjectEntityName($campaignid);
  //crm-now: careful, 5.2.1 doesn't have the argument up front
  if (isset($GLOBALS['vtiger_current_version']) AND version_compare($GLOBALS['vtiger_current_version'], "5.3.0", "<"))
	$types = vtws_listtypes($current_user);
  else
    $types = vtws_listtypes(null, $current_user);
	
  if(!in_array($entityName,$types['types'])){
    throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Permission to perform the operation is denied");
  }
  if($meta->hasReadAccess()!==true){
    throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Permission to read is denied");
  }
  
  if($entityName !== $webserviceObject->getEntityName()){
    throw new WebServiceException(WebServiceErrorCode::$INVALIDID,"Id specified is incorrect");
  }
  
  if(!$meta->hasPermission(EntityMeta::$RETRIEVE,$campaignid)){
    throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Permission to read given object is denied");
  }
  
  $idComponents = vtws_getIdComponents($campaignid);
  if(!$meta->exists($idComponents[1])){
    throw new WebServiceException(WebServiceErrorCode::$RECORDNOTFOUND,"Record you are trying to access is not found");
  }
  
  // Process petition
  // $leadsWsObject = VtigerWebserviceObject::fromName($adb, 'Leads');
  $contactsWsObject = VtigerWebserviceObject::fromName($adb, 'Contacts');
  // $accountsWsObject = VtigerWebserviceObject::fromName($adb, 'Accounts');
  // $leadsEntityId = $leadsWsObject->getEntityId();
  $contactsEntityId = $contactsWsObject->getEntityId();
  // $accountsEntityId = $accountsWsObject->getEntityId();
  // $nodes = array($idComponents[1]);
  // $leads = array();
  $contacts = Verteiler_Relation_Model::getContactIdsFromVerteiler($idComponents[1]);
  // $accounts = array();
  
  // Check access permissions
  // $leadsHandler = new $handlerClass($leadsWsObject, $current_user, $adb, $log);
  $contactsHandler = new $handlerClass($contactsWsObject, $current_user, $adb, $log);
  // $accountsHandler = new $handlerClass($accountsWsObject, $current_user, $adb, $log);
  // $leadsMeta = $leadsHandler->getMeta();
  $contactsMeta = $contactsHandler->getMeta();
  // $accountsMeta = $accountsHandler->getMeta();
  // $allowedLeads = array();
  // foreach($leads as $leadId) {
    // if ($leadsMeta->hasPermission(EntityMeta::$RETRIEVE, $leadId)) {
      // $allowedLeads[] = vtws_getId($leadsEntityId, $leadId);
    // }
  // }
  $allowedContacts = array();
  foreach($contacts as $contactId) {
    if ($contactsMeta->hasPermission(EntityMeta::$RETRIEVE, $contactId)) {
      $allowedContacts[] = vtws_getId($contactsEntityId, $contactId);
    }
  }
  // $allowedAccounts = array();
  // foreach($accounts as $accountsId) {
    // if ($accountsMeta->hasPermission(EntityMeta::$RETRIEVE, $accountsId)) {
		// $allowedAccounts[] = vtws_getId($accountsEntityId, $accountsId);
    // }
  // }
  
  // Format return array
  $results = array('Contacts' => implode(',', $allowedContacts));
  
  VTWS_PreserveGlobal::flush();
  return $results;
}
?>
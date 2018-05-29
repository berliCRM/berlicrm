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
  $leadsWsObject = VtigerWebserviceObject::fromName($adb, 'Leads');
  $contactsWsObject = VtigerWebserviceObject::fromName($adb, 'Contacts');
  $accountsWsObject = VtigerWebserviceObject::fromName($adb, 'Accounts');
  $leadsEntityId = $leadsWsObject->getEntityId();
  $contactsEntityId = $contactsWsObject->getEntityId();
  $accountsEntityId = $accountsWsObject->getEntityId();
  $nodes = array($idComponents[1]);
  $leads = array();
  $contacts = array();
  $accounts = array();
  while (!empty($nodes)) {
    $currentNodeId = array_shift($nodes);
    // Add all children items to the result list
    if ($returnresults=='Both') {
      $query = "SELECT crm.crmid, crm.setype
      FROM vtiger_crmentity crm
      LEFT JOIN vtiger_campaignleadrel rtl on rtl.leadid=crm.crmid
	  LEFT JOIN vtiger_campaigncontrel rtc on rtc.contactid=crm.crmid
	  LEFT JOIN vtiger_campaignaccountrel rtc2 on rtc2.accountid=crm.crmid
      WHERE crm.deleted=0 AND (rtl.campaignid={$currentNodeId} OR rtc.campaignid={$currentNodeId} OR rtc2.campaignid={$currentNodeId})";
    }
    else {
	  switch ($returnresults)
	  {
	    case 'Leads':
		  $rel_table = "vtiger_campaignleadrel";
		  $id_field = "leadid";
		  break;
		case 'Contacts':
		  $rel_table = "vtiger_campaigncontrel";
		  $id_field = "contactid";
		  break;
		case 'Accounts':
		  $rel_table = "vtiger_campaignaccountrel";
		  $id_field = "accountid";
		  break;
	  }
      $query = "SELECT crm.crmid, crm.setype
      FROM vtiger_crmentity crm
      INNER JOIN {$rel_table} rt ON rt.{$id_field}=crm.crmid
      WHERE crm.deleted=0 AND rt.campaignid='{$currentNodeId}'";
    }
    $res = $adb->query($query);
    while ($row=$adb->getNextRow($res)) {
      switch ($row['setype']) {
      case 'Leads':
        $leads[] = $row['crmid'];
        break;
      case 'Contacts':
        $contacts[] = $row['crmid'];
        break;
	  case 'Accounts':
			$accounts[] = $row['crmid'];
        break;
      }
    }
  }
  
  // Check access permissions
  $leadsHandler = new $handlerClass($leadsWsObject, $current_user, $adb, $log);
  $contactsHandler = new $handlerClass($contactsWsObject, $current_user, $adb, $log);
  $accountsHandler = new $handlerClass($accountsWsObject, $current_user, $adb, $log);
  $leadsMeta = $leadsHandler->getMeta();
  $contactsMeta = $contactsHandler->getMeta();
  $accountsMeta = $accountsHandler->getMeta();
  $allowedLeads = array();
  foreach($leads as $leadId) {
    if ($leadsMeta->hasPermission(EntityMeta::$RETRIEVE, $leadId)) {
      $allowedLeads[] = vtws_getId($leadsEntityId, $leadId);
    }
  }
  $allowedContacts = array();
  foreach($contacts as $contactId) {
    if ($contactsMeta->hasPermission(EntityMeta::$RETRIEVE, $contactId)) {
      $allowedContacts[] = vtws_getId($contactsEntityId, $contactId);
    }
  }
  $allowedAccounts = array();
  foreach($accounts as $accountsId) {
    if ($accountsMeta->hasPermission(EntityMeta::$RETRIEVE, $accountsId)) {
		$allowedAccounts[] = vtws_getId($accountsEntityId, $accountsId);
    }
  }
  
  // Format return array
  $results = array( 'Leads' => implode(',', $allowedLeads), 'Contacts' => implode(',', $allowedContacts) , 'Accounts' => implode(',', $allowedAccounts) );
  
  VTWS_PreserveGlobal::flush();
  return $results;
}
?>
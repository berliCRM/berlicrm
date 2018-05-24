<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

require_once 'modules/com_vtiger_workflow/VTEventHandler.inc';
require_once 'modules/Emails/mail.php';
require_once 'modules/HelpDesk/HelpDesk.php';

class ModCommentsHandler extends VTEventHandler {

	function handleEvent($eventName, $data) {

		if($eventName == 'vtiger.entity.beforesave') {
			// Entity is about to be saved, take required action
		}

		if($eventName == 'vtiger.entity.aftersave') {
			$db = PearDatabase::getInstance();

			$relatedToId = $data->get('related_to');
			if ($relatedToId) {
				$moduleName = getSalesEntityType($relatedToId);
				$focus = CRMEntity::getInstance($moduleName);
				$focus->retrieve_entity_info($relatedToId, $moduleName);
				$focus->id = $relatedToId;
				$fromPortal = $data->get('from_portal');
				if ($fromPortal) {
					$focus->column_fields['from_portal'] = $fromPortal;
				}
				$entityData = VTEntityData::fromCRMEntity($focus);

				$wfs = new VTWorkflowManager($db);
				$relatedToEventHandler = new VTWorkflowEventHandler();
				$relatedToEventHandler->workflows = $wfs->getWorkflowsForModuleSupportingComments($entityData->getModuleName());

				$wsId = vtws_getWebserviceEntityId($entityData->getModuleName(), $entityData->getId());
				$fromPortal = $entityData->get('from_portal');

				$util = new VTWorkflowUtils();
				$entityCache = new VTEntityCache($util->adminUser());

				$entityCacheData = $entityCache->forId($wsId);
				$entityCacheData->set('from_portal', $fromPortal);
				$entityCache->cache[$wsId] = $entityCacheData;

				$relatedToEventHandler->handleEvent($eventName, $entityData, $entityCache);
				$util->revertUser();
			}
		}
	}
}


function CustomerCommentFromPortal($entityData) {
	$adb = PearDatabase::getInstance();

	$data = $entityData->getData();
	$customerWSId = $data['customer'];

	$relatedToWSId = $data['related_to'];
	$relatedToId = explode('x', $relatedToWSId);
	$moduleName = getSalesEntityType($relatedToId[1]);

	if($moduleName == 'HelpDesk' && !empty($customerWSId)) {
		$ownerIdInfo = getRecordOwnerId($relatedToId[1]);
		if(!empty($ownerIdInfo['Users'])) {
			$ownerId = $ownerIdInfo['Users'];
			$ownerName = getOwnerName($ownerId);
			$toEmail = getUserEmailId('id',$ownerId);
		}
		if(!empty($ownerIdInfo['Groups'])) {
			$ownerId = $ownerIdInfo['Groups'];
			$groupInfo = getGroupName($ownerId);
			$ownerName = $groupInfo[0];
			$toEmail = implode(',', getDefaultAssigneeEmailIds($ownerId));
		}
		$subject = getTranslatedString('LBL_RESPONDTO_TICKETID', $moduleName)."##". $relatedToId[1]."## ". getTranslatedString('LBL_CUSTOMER_PORTAL', $moduleName);
		$contents = getTranslatedString('Dear', $moduleName)." ".$ownerName.","."<br><br>"
					.getTranslatedString('LBL_CUSTOMER_COMMENTS', $moduleName)."<br><br>
					<b>".$data['commentcontent']."</b><br><br>"
					.getTranslatedString('LBL_RESPOND', $moduleName)."<br><br>"
					.getTranslatedString('LBL_REGARDS', $moduleName)."<br>"
					.getTranslatedString('LBL_SUPPORT_ADMIN', $moduleName);

		$customerId = explode('x', $customerWSId);

		$result = $adb->pquery("SELECT email FROM vtiger_contactdetails WHERE contactid=?", array($customerId[0]));
		$fromEmail = $adb->query_result($result,0,'email');

		send_mail('HelpDesk', $toEmail,'', $fromEmail, $subject, $contents);
	}
}

function TicketOwnerComments($entityData) {
	global $HELPDESK_SUPPORT_NAME, $HELPDESK_SUPPORT_EMAIL_ID;
	$adb = PearDatabase::getInstance();

	//if commented from portal by the customer, then ignore this
	$customer = $entityData->get('customer');
	if(!empty($customer)) return;

	$wsParentId = $entityData->get('related_to');
	$parentIdParts = explode('x', $wsParentId);
	$parentId = $parentIdParts[1];
	$moduleName = getSalesEntityType($parentId);

	$isNew = $entityData->isNew();

	if($moduleName == 'HelpDesk') {
		$ticketFocus = CRMEntity::getInstance($moduleName);
		$ticketFocus->retrieve_entity_info($parentId, $moduleName);
		$ticketFocus->id = $parentId;

		if(!$isNew) {
			$reply = 'Re : ';
		} else {
			$reply = '';
		}

		$subject = $ticketFocus->column_fields['ticket_no'] . ' [ '.getTranslatedString('LBL_TICKET_ID', $moduleName)
							.' : '.$parentId.' ] '.$reply.$ticketFocus->column_fields['ticket_title'];

		$emailOptOut = 0;
		$contactId = $ticketFocus->column_fields['contact_id'];
		$accountId = $ticketFocus->column_fields['parent_id'];
		//To get the emailoptout vtiger_field value and then decide whether send mail about the tickets or not
		if(!empty($contactId)) {
			$result = $adb->pquery('SELECT email, emailoptout FROM vtiger_contactdetails WHERE contactid=?',
										array($contactId));
			$emailOptOut = $adb->query_result($result,0,'emailoptout');
			$parentEmail = $contactMailId = $adb->query_result($result,0,'email');
			$displayValueArray = getEntityName('Contacts', $contactId);
			if (!empty($displayValueArray)) {
				foreach ($displayValueArray as $key => $value) {
					$contactName = $value;
				}
			}
			$parentName = $contactName;

			//Get the status of the vtiger_portal user. if the customer is active then send the vtiger_portal link in the mail
			if($parentEmail != '') {
				$sql = "SELECT * FROM vtiger_portalinfo WHERE user_name=?";
				$isPortalUser = $adb->query_result($adb->pquery($sql, array($parentEmail)),0,'isactive');
			}
		} else if(!empty($accountId)) {
			$result = $adb->pquery("SELECT accountname, emailoptout, email1 FROM vtiger_account WHERE accountid=?",
										array($accountId));
			$emailOptOut = $adb->query_result($result,0,'emailoptout');
			$parentEmail = $adb->query_result($result,0,'email1');
			$parentName = $adb->query_result($result,0,'accountname');

		}
		//added condition to check the emailoptout
		if($emailOptOut == 0) {
			$entityData = VTEntityData::fromCRMEntity($ticketFocus);

			if($isPortalUser == 1){
				$bodysubject = getTranslatedString('Ticket No', $moduleName) .": " . $ticketFocus->column_fields['ticket_no']
					. "<br>" . getTranslatedString('LBL_TICKET_ID', $moduleName).' : '.$parentId.'<br> '
					.getTranslatedString('LBL_SUBJECT', $moduleName).$ticketFocus->column_fields['ticket_title'];

				$emailBody = $bodysubject.'<br><br>'.HelpDesk::getPortalTicketEmailContents($entityData);
			} else {
				$emailBody = HelpDesk::getTicketEmailContents($entityData);
			}

			send_mail('HelpDesk', $parentEmail, $HELPDESK_SUPPORT_NAME, $HELPDESK_SUPPORT_EMAIL_ID, $subject, $emailBody);
		}
	}
}
<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

vimport('~~/modules/WSAPP/Handlers/vtigerCRMHandler.php');
vimport('~~/include/Webservices/Utils.php');
class Google_Vtiger_Handler extends vtigerCRMHandler {

	public function translateTheReferenceFieldIdsToName($records, $module, $user) {
		$db = PearDatabase::getInstance();
		global $current_user;
		$current_user = $user;
		$handler = vtws_getModuleHandlerFromName($module, $user);
		$meta = $handler->getMeta();
		$referenceFieldDetails = $meta->getReferenceFieldDetails();
		foreach ($referenceFieldDetails as $referenceFieldName => $referenceModuleDetails) {
			if($module == 'Events' && $referenceFieldName == "contact_id"){
				// to set all related Contacts of Event records
				foreach($records as $index => $record){
					$id = $record['id'];
					$idComp = vtws_getIdComponents($id);
				   $recordIds[] =  $idComp[1];
				}
				$eventRecordModel = new Events_Record_Model();
				$contactsInfos =  $eventRecordModel->getRelatedContactInfoFromIds($recordIds);

				foreach($records as $index => $record){
					$id = $record['id'];
					$idComp = vtws_getIdComponents($id);
					if($contactsInfos[$idComp[1]]){
						$records[$index]['attendees'] = $contactsInfos[$idComp[1]];
					}
				}
			}else{
				$referenceFieldIds = array();
				$referenceModuleIds = array();
				$referenceIdsName = array();
				foreach ($records as $recordDetails) {
					$referenceWsId = $recordDetails[$referenceFieldName];
					if (!empty($referenceWsId)) {
						$referenceIdComp = vtws_getIdComponents($referenceWsId);
						$webserviceObject = VtigerWebserviceObject::fromId($db, $referenceIdComp[0]);
						if ($webserviceObject->getEntityName() == 'Currency') {
							continue;
						}
						$referenceModuleIds[$webserviceObject->getEntityName()][] = $referenceIdComp[1];
						$referenceFieldIds[] = $referenceIdComp[1];
					}
				}

				foreach ($referenceModuleIds as $referenceModule => $idLists) {
					$nameList = getEntityName($referenceModule, $idLists);
					foreach ($nameList as $key => $value)
						$referenceIdsName[$key] = $value;
				}
				$recordCount = count($records);
				for ($i = 0; $i < $recordCount; $i++) {
					$record = $records[$i];
					if (!empty($record[$referenceFieldName])) {
						$wsId = vtws_getIdComponents($record[$referenceFieldName]);
						$record[$referenceFieldName] = decode_html($referenceIdsName[$wsId[1]]);
					}
					$records[$i] = $record;
				}
			}
		}
		return $records;
	}

	public function put($recordDetails, $user) {
		global $current_user;
		$current_user = $user;
		$this->user = $user;
		$recordDetails = $this->syncToNativeFormat($recordDetails);
		$createdRecords = $recordDetails['created'];
		$updatedRecords = $recordDetails['updated'];
		$deletedRecords = $recordDetails['deleted'];
		$recordDetails['skipped'] = array();
		$updateDuplicateRecords = array();

		if (count($createdRecords) > 0) {
			$createdRecords = $this->translateReferenceFieldNamesToIds($createdRecords, $user);
			$createdRecords = $this->fillNonExistingMandatoryPicklistValues($createdRecords);
			$createdRecords = $this->fillMandatoryFields($createdRecords, $user);
		}
		foreach ($createdRecords as $index => $record) {
			unset($_REQUEST['contactidlist']);
			if($record['module'] == 'Events' && isset($record['contactidlist'])) {
				$_REQUEST['contactidlist'] = $record['contactidlist'];
			}

			try { 
				$createdRecords[$index] = vtws_create($record['module'], $record, $this->user);
			} catch (DuplicateException $e) {
				$skipped = true;
				$duplicateRecordIds = $e->getDuplicateRecordIds();
				$duplicatesResult = $this->triggerSyncActionForDuplicate($record, $duplicateRecordIds);

				if ($duplicatesResult) {
					$updateDuplicateRecords[$index] = $duplicatesResult;
					$skipped = false;
				}
				if ($skipped) {
					$recordDetails['skipped'][] = array('record' => $createdRecords[$index],
														'messageidentifier' => '',
														'message' => $e->getMessage());
				}
				unset($createdRecords[$index]);
				continue;
			} catch (Exception $e) {
				$recordDetails['skipped'][] = array('record' => $createdRecords[$index],
													'messageidentifier' => '',
													'message' => $e->getMessage());
				unset($createdRecords[$index]);
				continue;
			}
		}

		if (count($updatedRecords) > 0) {
			$updatedRecords = $this->translateReferenceFieldNamesToIds($updatedRecords, $user);
		}

		$crmIds = array();
		foreach ($updatedRecords as $index => $record) {
			$webserviceRecordId = $record["id"];
			$recordIdComp = vtws_getIdComponents($webserviceRecordId);
			$crmIds[] = $recordIdComp[1];
		}
		$assignedRecordIds = array();
		if ($this->isClientUserSyncType()|| $this->isClientUserAndGroupSyncType()) {
			$assignedRecordIds = wsapp_checkIfRecordsAssignToUser($crmIds, $this->user->id);
			// To check if the record assigned to group
			if($this->isClientUserAndGroupSyncType()){
				$groupIds = $this->getGroupIds($this->user->id);
				foreach ($groupIds as $group) {
					$groupRecordId = wsapp_checkIfRecordsAssignToUser($crmIds, $group);
					$assignedRecordIds = array_merge($assignedRecordIds, $groupRecordId);
				}
			}
			//  End
		}
		foreach ($updatedRecords as $index => $record) {
			$webserviceRecordId = $record["id"];
			//While Updating Vtiger Record, should not update these values for event
			if($record['module'] == 'Events') {
				unset($record['eventstatus']);
				unset($record['activitytype']);
				unset($record['duration_hours']);
			}
			unset($_REQUEST['contactidlist']);
			if($record['module'] == 'Events') {
				$_REQUEST['contactidlist'] = $record['contactidlist'];
			}
			$recordIdComp = vtws_getIdComponents($webserviceRecordId);
			try {
				if (in_array($recordIdComp[1], $assignedRecordIds)) {
					$updatedRecords[$index] = vtws_revise($record, $this->user);
				} else if (!$this->isClientUserSyncType()) {
					$updatedRecords[$index] = vtws_revise($record, $this->user);
				} else {
					$this->assignToChangedRecords[$index] = $record;
				}
			} catch (DuplicateException $e) {
				$skipped = true;
				$duplicateRecordIds = $e->getDuplicateRecordIds();
				$duplicatesResult = $this->triggerSyncActionForDuplicate($record, $duplicateRecordIds);

				if ($duplicatesResult) {
					$updateDuplicateRecords[$index] = $duplicatesResult;
					$skipped = false;
				}
				if ($skipped) {
					$recordDetails['skipped'][] = array('record' => $updatedRecords[$index],
														'messageidentifier' => '',
														'message' => $e->getMessage());
				}
				unset($updatedRecords[$index]);
				continue;
			} catch (Exception $e) {
				$recordDetails['skipped'][] = array('record' => $updatedRecords[$index], 
													'messageidentifier' => '', 
													'message' => $e->getMessage());
				unset($updatedRecords[$index]);
				continue;
			}
		}
		foreach ($updateDuplicateRecords as $index => $record) {
			$updatedRecords[$index] = $record;
		}

		$hasDeleteAccess = null;
		$deletedCrmIds = array();
		foreach ($deletedRecords as $index => $record) {
			$webserviceRecordId = $record;
			$recordIdComp = vtws_getIdComponents($webserviceRecordId);
			$deletedCrmIds[] = $recordIdComp[1];
		}
		$assignedDeletedRecordIds = wsapp_checkIfRecordsAssignToUser($deletedCrmIds, $this->user->id);

		// To get record id's assigned to group of the current user
		if($this->isClientUserAndGroupSyncType()){
		   foreach ($groupIds as $group) {
				   $groupRecordId = wsapp_checkIfRecordsAssignToUser($deletedCrmIds, $group);
				   $assignedDeletedRecordIds = array_merge($assignedDeletedRecordIds, $groupRecordId);
		   }
		}
		// End

		foreach ($deletedRecords as $index => $record) {
			$idComp = vtws_getIdComponents($record);
			if (empty($hasDeleteAccess)) {
				$handler = vtws_getModuleHandlerFromId($idComp[0], $this->user);
				$meta = $handler->getMeta();
				$hasDeleteAccess = $meta->hasDeleteAccess();
			}
			if ($hasDeleteAccess) {
				if (in_array($idComp[1], $assignedDeletedRecordIds)) {
					try {
						vtws_delete($record, $this->user);
					} catch (Exception $e) {
						$recordDetails['skipped'][] = array('record' => $deletedRecords[$index], 
													'messageidentifier' => '', 
													'message' => $e->getMessage());
						continue;
					}
				}
			}
		}

		$recordDetails['created'] = $createdRecords;
		$recordDetails['updated'] = $updatedRecords;
		$recordDetails['deleted'] = $deletedRecords;
		return $this->nativeToSyncFormat($recordDetails);
	}
}

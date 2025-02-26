<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Verteiler_RelationAjax_Action extends Vtiger_RelationAjax_Action {
    
    public function __construct() {
		parent::__construct();
		$this->exposeMethod('addRelationsFromOtherVerteiler');
		$this->exposeMethod('addRelationsFromRelatedModuleViewId');
		$this->exposeMethod('massDeleteRelation');
		$this->exposeMethod('listRelatedEntities');
	}

    /**
     * Function to copy relations from another verteiler, returns number of relations added
     * @param Vtiger_Request $request
     */
	public function addRelationsFromOtherVerteiler(Vtiger_Request $request) {
        $sourceRecordId = $request->get('sourceRecord');
        $copyFromId = $request->get('verteilerId');
        $sourceModuleModel = Vtiger_Module_Model::getInstance("Verteiler");
        $relatedModuleModel = Vtiger_Module_Model::getInstance("Contacts");
        $relationModel = Vtiger_Relation_Model::getInstance($sourceModuleModel, $relatedModuleModel);

        require_once("modules/Verteiler/models/Relation.php");
        $contactIds = Verteiler_Relation_Model::getContactIdsFromVerteiler($copyFromId);

        foreach ($contactIds as $cid) {
            $relationModel->addRelation($sourceRecordId, $cid);
        }

        $response = new Vtiger_Response();
        $response->setResult(array(count($contactIds)));
        $response->emit();
    }

 
    /**
	 * Function to add relations using related module viewid, returns number of relations added (no output breaks jquery > 1.7)
	 * @param Vtiger_Request $request
	 */
	public function addRelationsFromRelatedModuleViewId(Vtiger_Request $request) {
		$sourceRecordId = $request->get('sourceRecord');
		$relatedModuleName = $request->get('relatedModule');
        $relatedRecordIdsList = array();
		$viewId = $request->get('viewId');
		if ($viewId) {
			$sourceModuleModel = Vtiger_Module_Model::getInstance($request->getModule());
			$relatedModuleModel = Vtiger_Module_Model::getInstance($relatedModuleName);

			$relationModel = Vtiger_Relation_Model::getInstance($sourceModuleModel, $relatedModuleModel);
			$emailEnabledModulesInfo = $relationModel->getEmailEnabledModulesInfoForDetailView();

			if (array_key_exists($relatedModuleName, $emailEnabledModulesInfo)) {
				$fieldName = $emailEnabledModulesInfo[$relatedModuleName]['fieldName'];

				$db = PearDatabase::getInstance();
				$currentUserModel = Users_Record_Model::getCurrentUserModel();

				$queryGenerator = new QueryGenerator($relatedModuleName, $currentUserModel);
				$queryGenerator->initForCustomViewById($viewId);

				$query = $queryGenerator->getQuery();
				$result = $db->pquery($query, array());

				$numOfRows = $db->num_rows($result);
				for ($i=0; $i<$numOfRows; $i++) {
					$relatedRecordIdsList[] = $db->query_result($result, $i, $fieldName);
				}
				if(!empty($relatedRecordIdsList)){
					foreach($relatedRecordIdsList as $relatedRecordId) {
						$relationModel->addRelation($sourceRecordId, $relatedRecordId);
					}
				}
			}
		}
        $response = new Vtiger_Response();
        $response->setResult(array(count($relatedRecordIdsList)));
        $response->emit();
	}
    
	/**
	 * Function to delete a relation between Verteiler and Contact records.
	 * If the relation is tied to a specific user (added_by_user_id), it checks for that first.
	 * If no specific user is found, it deletes the relation regardless of the user.
	 * Also tracks the "unlink" action using ModTracker.
	 * 
	 * @param Vtiger_Request $request The request object containing src_record, related_record_list, and added_by_user_id.
	 */
    function deleteRelation($request) {
        $adb = PearDatabase::getInstance();
        $src_record = $request->get("src_record");
        $related_records = $request->get("related_record_list");
        $added_by_user_id = $request->get("added_by_user_id");
        $related_record = $related_records[0];
        $sql = 'DELETE FROM vtiger_verteilercontrel WHERE verteilerid=? AND contactid=? AND addedbyuserid=?';
        $result = $adb->pquery($sql, array($src_record, $related_record, $added_by_user_id));

        // modtracking "unlink"
        require_once "modules/ModTracker/ModTracker.php";
        ModTracker::trackRelation("Verteiler", $src_record, "Contacts", $related_record, ModTracker::$UNLINK);
        $response = new Vtiger_Response();
        $response->setResult(true);
        $response->emit();
    }
    
	/**
	 * Function to mass delete relations between a Verteiler and multiple Contact records.
	 * It checks for each relation if it was added by a specific user.
	 * If no user is found, it deletes the relation regardless of the user.
	 * Tracks each "unlink" action using ModTracker.
	 * 
	 * @param Vtiger_Request $request The request object containing src_record and related_records.
	 */
	function massDeleteRelation($request) {
		$adb = PearDatabase::getInstance();
		
		// Get the source record (Verteiler) ID
		$src_record = $request->get("src_record");
		
		// Get the list of related records to be deleted
		$related_records = $request->get("related_records");

		// Loop through each related record
		foreach ($related_records as $related_record) {
			// Try to delete the relation considering the user who added it
			$sql = 'DELETE FROM vtiger_verteilercontrel WHERE verteilerid=? AND contactid=? AND addedbyuserid=?';
			$result = $adb->pquery($sql, array($src_record, $related_record[0], $related_record[1]));

			// modtracking "unlink"
			require_once "modules/ModTracker/ModTracker.php";
			ModTracker::trackRelation("Verteiler", $src_record, "Contacts", $related_record[0], ModTracker::$UNLINK);
		}

		// Prepare the response
		$response = new Vtiger_Response();
		$response->setResult(true);
		$response->emit();
	}
}
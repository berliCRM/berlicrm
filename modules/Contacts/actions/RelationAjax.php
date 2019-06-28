<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Contacts_RelationAjax_Action extends Vtiger_RelationAjax_Action {

	function addRelation($request) {
        global $current_user,$adb;
		$relatedModule = $request->get('related_module');
        if ($relatedModule == "Verteiler") {
            $sourceRecordId = $request->get('src_record');
            $relatedRecordIdList = $request->get('related_record_list');
            $verteilerModel = Vtiger_Module_Model::getInstance("Verteiler");
            $contactsModel = Vtiger_Module_Model::getInstance("Contacts");
            $relationModel = Vtiger_Relation_Model::getInstance($verteilerModel, $contactsModel);
            foreach ($relatedRecordIdList as $vid) {
                $relationModel->addRelation($vid, $sourceRecordId);
            }
            $response = new Vtiger_Response();
            $response->setResult(true);
            $response->emit();
        }
        else {
            parent::addRelation($request);
        }
	}


	function deleteRelation($request) {
        global $current_user,$adb;
		$relatedModule = $request->get('related_module');
        if ($relatedModule == "Verteiler") {
            $src_record = $request->get("src_record");
            $related_records = $request->get("related_record_list");
            $related_record = $related_records[0];
            $sql = 'DELETE FROM vtiger_verteilercontrel WHERE contactid=? AND verteilerid=?';
            $adb->pquery($sql, array($src_record, $related_record));
            // modtracking "unlink"
            require_once "modules/ModTracker/ModTracker.php";
            ModTracker::trackRelation("Verteiler", $related_record, "Contacts", $src_record, ModTracker::$UNLINK);
            $response = new Vtiger_Response();
            $response->setResult(true);
            $response->emit();
        }
        else {
            parent::deleteRelation($request);
        }
	}
}

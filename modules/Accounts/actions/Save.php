<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Accounts_Save_Action extends Vtiger_Save_Action {

    /**
     * Function to save record - and to copy addresses to related contacts on request
     * @param <Vtiger_Request> $request - values of the record
     * @return <RecordModel> - record Model of saved record
     */
    public function saveRecord($request) {

        $recordModel = parent::saveRecord($request);

        if ($request->get("copytorelatedcontacts") == "on") {
            $record = (int) $request->get("record");
            if ($record>0) {
                $db = PearDatabase::getInstance();
                $query = "SELECT contactid FROM vtiger_contactdetails
                    INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_contactdetails.contactid
                    WHERE vtiger_crmentity.deleted = 0 AND vtiger_contactdetails.accountid = ?";
                $result = $db->pquery($query,array($record));
                if ($db->num_rows($result) > 0) {
                    for ($i=0; $i<$db->num_rows($result); $i++) {
                        $contactid = $db->query_result($result, $i, "contactid");
                        $ContactRecordModel = Vtiger_Record_Model::getInstanceById($contactid, 'Contacts');
                        $ContactRecordModel->set('mode', 'edit');
                        $ContactRecordModel->set('mailingcity', $recordModel->get('bill_city'));
                        $ContactRecordModel->set('mailingstreet',$recordModel->get('bill_street'));
                        $ContactRecordModel->set('mailingcountry',$recordModel->get('bill_country'));
                        $ContactRecordModel->set('mailingstate',$recordModel->get('bill_state'));
                        $ContactRecordModel->set('mailingpobox',$recordModel->get('bill_pobox'));
                        $ContactRecordModel->set('mailingzip',$recordModel->get('bill_code'));
                        $ContactRecordModel->set('othercity',$recordModel->get('ship_city'));
                        $ContactRecordModel->set('otherstreet',$recordModel->get('ship_street'));
                        $ContactRecordModel->set('othercountry',$recordModel->get('ship_country'));
                        $ContactRecordModel->set('otherstate',$recordModel->get('ship_state'));
                        $ContactRecordModel->set('otherpobox',$recordModel->get('ship_pobox'));
                        $ContactRecordModel->set('otherzip',$recordModel->get('ship_code'));
                        $ContactRecordModel->convertToUserFormat();
                        $ContactRecordModel->save();
                    }
                }
            }
        }
        return $recordModel;
    }
}

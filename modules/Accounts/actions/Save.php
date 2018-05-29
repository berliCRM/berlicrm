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
                $q = "UPDATE vtiger_contactdetails, vtiger_contactaddress as a, vtiger_accountshipads as b, vtiger_accountbillads as c SET
                a.mailingcity = c.bill_city,
                a.mailingstreet = c.bill_street,
                a.mailingcountry = c.bill_country,
                a.mailingstate = c.bill_state,
                a.mailingpobox = c.bill_pobox,
                a.mailingzip = c.bill_code,
                a.othercity = b.ship_city,
                a.otherstreet = b.ship_street,
                a.othercountry = b.ship_country,
                a.otherstate = b.ship_state,
                a.otherpobox = b.ship_pobox,
                a.otherzip = b.ship_code
                WHERE contactid = contactaddressid AND b.accountaddressid = accountid AND c.accountaddressid = accountid AND accountid = ?";
                $db = PearDatabase::getInstance();
                $db->pquery($q,array($record));
            }
        }
		return $recordModel;
	}
}

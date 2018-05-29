<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Campaigns_Record_Model extends Vtiger_Record_Model {

    public function save() {
        parent::save();

        // Copy related contacts, leads and accounts from record $_REQUEST["copy_source"] to new record $newid, resetting their status
        $newid = (int) $this->getId();
        if ($_REQUEST["mode"]=="copyRelated" && $newid > 0) {
                $db = PearDatabase::getInstance();

                // contacts
                $q = "INSERT INTO vtiger_campaigncontrel (campaignid, contactid, campaignrelstatusid)
                        SELECT ?, contactid, 1 FROM vtiger_campaigncontrel WHERE campaignid = ?";
                $db->pquery($q,array($newid, $_REQUEST["copy_source"]));

                // leads
                $q = "INSERT INTO vtiger_campaignleadrel (campaignid, leadid, campaignrelstatusid)
                        SELECT ?, leadid, 1 FROM vtiger_campaignleadrel WHERE campaignid = ?";
                $db->pquery($q,array($newid, $_REQUEST["copy_source"]));

                // accounts
                $q = "INSERT INTO vtiger_campaignaccountrel (campaignid, accountid, campaignrelstatusid)
                        SELECT ?, accountid, 1 FROM vtiger_campaignaccountrel WHERE campaignid = ?";
                $db->pquery($q,array($newid, $_REQUEST["copy_source"]));
            }
    }

	/**
	 * Function to get selected ids list of related module for send email
	 * @param <String> $relatedModuleName
	 * @param <array> $excludedIds
	 * @return <array> List of selected ids
	 */
	public function getSelectedIdsList($relatedModuleName, $excludedIds = false) {
		$db = PearDatabase::getInstance();

		switch($relatedModuleName) {
			case "Leads"		: $tableName = "vtiger_campaignleadrel";		$fieldName = "leadid";		break;
			case "Accounts"		: $tableName = "vtiger_campaignaccountrel";		$fieldName = "accountid";	break;
			case 'Contacts'		: $tableName = "vtiger_campaigncontrel";		$fieldName = "contactid";	break;
		}

		$query = "SELECT $fieldName FROM $tableName
					INNER JOIN vtiger_crmentity ON $tableName.$fieldName = vtiger_crmentity.crmid AND vtiger_crmentity.deleted = ?
					WHERE campaignid = ?";
		if ($excludedIds) {
			$query .= " AND $fieldName NOT IN (". implode(',', $excludedIds) .")";
		}

		$result = $db->pquery($query, array(0, $this->getId()));
		$numOfRows = $db->num_rows($result);

		$selectedIdsList = array();
		for ($i=0; $i<$numOfRows; $i++) {
			$selectedIdsList[] = $db->query_result($result, $i, $fieldName);
		}
		return $selectedIdsList;
	}
}


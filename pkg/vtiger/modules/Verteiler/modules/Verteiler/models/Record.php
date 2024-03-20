<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Verteiler_Record_Model extends Vtiger_Record_Model {

    /**
	 * Function to get selected ids list of related module for send email
	 * @param <String> $relatedModuleName
	 * @param <array> $excludedIds
	 * @return <array> List of selected ids
	 */
	public function getSelectedIdsList($relatedModuleName = 'Contacts', $excludedIds = false) {
		$adb = PearDatabase::getInstance();

		$query = "SELECT DISTINCT contactid FROM vtiger_verteilercontrel INNER JOIN vtiger_crmentity ON crmid=contactid WHERE verteilerid = ? AND deleted =0";
		$result = $adb->pquery($query, array($this->getId()));

		$selectedIdsList = array();
        while ($row = $adb->fetchByAssoc($result,-1,false)) {
            $selectedIdsList[] = $row["contactid"];
        }

		return $selectedIdsList;
	}
	
	/**
	 * Function to count the number of related contacts
	 * @param <String> Verteiler ID
	 * @return <String> number of Contacts
	 */
	public function getNumVerteiler ($recordId ) {
		$db = PearDatabase::getInstance();
        $query = "SELECT COUNT(*) as totalrel from vtiger_verteilercontrel
				INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_verteilercontrel.contactid WHERE verteilerid = ? AND deleted = 0 ";
        $res=$db->pquery($query,array($recordId));
		$totalrel = $db->query_result($res, 0, "totalrel");
		return $totalrel;
	}

}
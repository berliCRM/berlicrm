<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Verteiler_Relation_Model extends Vtiger_Relation_Model {

	/**
	 * Function to get Email enabled modules list for detail view of record
	 * @return <array> List of modules
	 */
	public function getEmailEnabledModulesInfoForDetailView() {
		return array(
				'Contacts' => array('fieldName' => 'contactid', 'tableName' => 'vtiger_verteilercontrel')
		);
	}

    // returns array of contactids on verteiler with ID $verteilerId
    public static function getContactIdsFromVerteiler($verteilerId) {
        global $adb;
        
        $q = "SELECT vtiger_crmentity.crmid FROM vtiger_contactdetails
            INNER JOIN vtiger_verteilercontrel ON vtiger_verteilercontrel.contactid = vtiger_contactdetails.contactid
            INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_contactdetails.contactid
            WHERE vtiger_verteilercontrel.verteilerid = ? AND vtiger_crmentity.deleted=0";
        
        $contactIds = array();
        $res = $adb->pquery($q,array($verteilerId));
        while ($row=$adb->fetchByAssoc($res,-1,false)) {
            $contactIds[] = $row["crmid"];
        }
        return $contactIds;
    }
}
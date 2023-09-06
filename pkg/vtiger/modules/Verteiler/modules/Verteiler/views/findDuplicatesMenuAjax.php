<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Verteiler_findDuplicatesMenuAjax_View extends Vtiger_IndexAjax_View {

	public function process(Vtiger_Request $request) {
        global $adb;
        $viewer = $this->getViewer ($request);
        $moduleName = $request->getModule();
        $record= $request->get("record");
        
        $q = "SELECT contactid, COUNT(*) as c FROM vtiger_verteilercontrel WHERE verteilerid = ? GROUP BY contactid";
        $res = $adb->pquery($q,array($record));

        $duplicateContactIDs = array();
        
        while ($row = $adb->fetchByAssoc($res,-1,false)) {
            $duplicateContactIDs[] = $row["contactid"];
        }

        $duplicateContacts = array();
        $verteilerEmailAdressesArr = array();
        
        if (count($duplicateContactIDs)>0) {
            $query = "SELECT vtiger_crmentity.crmid, vtiger_contactdetails.firstname, vtiger_contactdetails.lastname, vtiger_contactdetails.phone, vtiger_contactdetails.accountid, vtiger_contactdetails.title, vtiger_contactdetails.email, vtiger_crmentity.smownerid, vtiger_contactaddress.mailingcity, vtiger_contactaddress.mailingcountry, users2.user_name as added_by_user_name, users2.id as added_by_user_id, vtiger_verteilercontrel.parent, vtiger_account.accountname FROM vtiger_contactdetails INNER JOIN vtiger_verteilercontrel ON vtiger_verteilercontrel.contactid = vtiger_contactdetails.contactid INNER JOIN vtiger_contactaddress ON vtiger_contactdetails.contactid = vtiger_contactaddress.contactaddressid INNER JOIN vtiger_contactsubdetails ON vtiger_contactdetails.contactid = vtiger_contactsubdetails.contactsubscriptionid INNER JOIN vtiger_customerdetails ON vtiger_contactdetails.contactid = vtiger_customerdetails.customerid INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_contactdetails.contactid LEFT JOIN vtiger_contactscf ON vtiger_contactdetails.contactid = vtiger_contactscf.contactid LEFT JOIN vtiger_groups ON vtiger_groups.groupid=vtiger_crmentity.smownerid LEFT JOIN vtiger_users ON vtiger_crmentity.smownerid=vtiger_users.id LEFT JOIN vtiger_account ON vtiger_account.accountid = vtiger_contactdetails.accountid LEFT JOIN vtiger_users AS users2 ON users2.id = vtiger_verteilercontrel.addedbyuserid WHERE vtiger_verteilercontrel.verteilerid = ? AND vtiger_crmentity.deleted=0";
            
            $res = $adb->pquery($query,array($record));
            while ($row = $adb->fetchByAssoc($res,-1,false)) {
                $verteilerEmailAdressesArr[] = $row;
            }
            $foundDuplicatesIds = array(); 
            for( $i = (count($verteilerEmailAdressesArr)-1) ; $i >=0; $i-- ){
                for( $j = 0 ; $j < $i; $j++ ){
                    if( strtolower(trim(($verteilerEmailAdressesArr[$i])['email'])) == strtolower(trim(($verteilerEmailAdressesArr[$j])['email']))  ){
                        $foundDuplicatesIds[] = ($verteilerEmailAdressesArr[$i])['crmid'];
                        $foundDuplicatesIds[] = ($verteilerEmailAdressesArr[$j])['crmid'];
                    }
                }
            }
            // DISTINCT 
            $foundDuplicatesIds = array_unique($foundDuplicatesIds);
            for( $i = (count($verteilerEmailAdressesArr)-1) ; $i >=0; $i-- ){
                if( in_array( ($verteilerEmailAdressesArr[$i])['crmid'] , $foundDuplicatesIds )  ){
                    $duplicateContacts[] = $verteilerEmailAdressesArr[$i];
                }
            }
        }

        $viewer->assign("RECORD",$record);
        $viewer->assign("MODULE",$moduleName);
        $viewer->assign("DUPLICATECONTACTS",$duplicateContacts);
        echo $viewer->view('findDuplicatesMenu.tpl', $moduleName, true);
    }
}

<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Verteiler_deleteDuplicates_Action extends Vtiger_Action_Controller {

	function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$record = $request->get('record');

		$currentUserPrivilegesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		if(!$currentUserPrivilegesModel->isPermitted($moduleName, 'Delete', $record)) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
	}

	public function process(Vtiger_Request $request) {
        global $adb;
        $record = $request->get('record');
        
        if ($request->get('autodelete') == 1) {
            $q = "SELECT contactid, COUNT(*) as c FROM vtiger_verteilercontrel WHERE verteilerid = ? GROUP BY contactid HAVING c > 1";
            $res = $adb->pquery($q,array($record));
            $duplicateContactIDs = array();
            while ($row = $adb->fetchByAssoc($res,-1,false)) {
                $q2 = "DELETE from vtiger_verteilercontrel WHERE verteilerid = ? AND contactid = ? LIMIT ?";
                $adb->pquery($q2,array($record,$row["contactid"],$row["c"]-1));
            }
        }
        else {            
            $del = $request->get('del');
            foreach ($del as $contactid => $users) {
                $userids = array_keys($users);
                foreach ($userids as $userid) {
                    $q2 = "DELETE from vtiger_verteilercontrel WHERE verteilerid = ? AND contactid = ? AND addedbyuserid = ?";
                    $adb->pquery($q2,array($record,$contactid,$userid));
                }
            }
        }

        header("location: index.php?module=Verteiler&relatedModule=Contacts&view=Detail&record=$record&mode=showRelatedList&tab_label=Contacts");
    }
}
<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class Verteiler_VerteilerListOverlay_View extends Vtiger_IndexAjax_View {

    function process(Vtiger_Request $request) {
        global $adb;
        $viewer = $this->getViewer ($request);
		$moduleName = $request->getModule();
        
        $Verteiler=array();
        $query = "SELECT * from vtiger_verteiler INNER JOIN vtiger_crmentity ON verteilerid=crmid WHERE deleted = 0 ORDER BY verteilername";
        $res=$adb->pquery($query,array());
        while ($row=$adb->fetchByAssoc($res,-1,false)) {
            $Verteiler[$row['verteilerid']]=$row['verteilername'];
        }
        $viewer->assign('VERTEILER', $Verteiler);
        $viewer->assign('MODULE', $moduleName);
        $viewer->view('VerteilerListOverlay.tpl', $moduleName);
        
    }
    
}
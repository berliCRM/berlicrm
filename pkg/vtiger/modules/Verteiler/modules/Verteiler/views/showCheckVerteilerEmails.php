<?php
/*+*******************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ********************************************************************************/

require_once('include/utils/utils.php');

class Verteiler_showCheckVerteilerEmails_View extends Vtiger_View_Controller {

	function loginRequired() {
		return true;
	}

    public function __construct() {
	}
	
	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		$userPrivilegesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		$permission = $userPrivilegesModel->hasModulePermission($moduleModel->getId());

		if(!$permission) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
	}

	function preProcess(Vtiger_Request $request, $display = true) {
	}

	public function process(Vtiger_Request $request) {

		//get recordid from Database		
		$db = PearDatabase::getInstance();
		$module = $request->getModule();
		$moduleName = $request->get('module');
		$recordid = $request->get('recordid');

		//get email and contactid from Database
		$emailQuery = "SELECT vtiger_contactdetails.contactid, email FROM vtiger_verteilercontrel 
		INNER JOIN vtiger_contactdetails ON vtiger_contactdetails.contactid = vtiger_verteilercontrel.contactid
		INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_contactdetails.contactid
		WHERE verteilerid = ? AND deleted = 0 GROUP BY contactid;";
		$res = $db->pquery($emailQuery,array($recordid));
		
		$contactIds = array();
		$falseEmailContactIDs = [];
		while ($row = $db->fetch_row($res)) {
			$contactIds = $row['email'];
			
			//check emails for validation
			$vresult = validateEmailId($contactIds);
			if ($vresult == false) {
				$falseEmailContactIDs[$row['contactid']] = $contactIds;
			}
		}
		
		$falseEmailContactNummber=count($falseEmailContactIDs);

    	$viewer = $this->getViewer ($request);
        $viewer->assign('FALSEEMAILS', $falseEmailContactIDs); $viewer->assign('MODULE', $module);
		$viewer->assign('ALLVALID', $falseEmailContactNummber);
		
		if ($falseEmailContactNummber == 0) {
			echo "ok";
		}
		else {
			$viewer->view('showCheckVerteilerEmails.tpl', $moduleName);			
		}
	}
}

?>
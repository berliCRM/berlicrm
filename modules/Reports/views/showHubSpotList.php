<?php
class Reports_showHubSpotList_View extends Vtiger_View_Controller {
	public $log_text = array();
	
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
		global $adb;
		if(!vtlib_isModuleActive('berliHubSpot')) {
			$response = new Vtiger_Response();
			$response->setResult(json_encode('NOHUBSPOT'));
			$response->emit();
			return;
		}
		$viewer = new Vtiger_Viewer();
		$module = $request->getModule();
		$moduleName = $request->get('modulename');
		$reportid = $request->get('reportid');


		//list of campaigns
		$query = "SELECT vtiger_berlihubspot.hubspotid, vtiger_berlihubspot.hubspotname from vtiger_berlihubspot
							inner join vtiger_crmentity on vtiger_crmentity.crmid = vtiger_berlihubspot.hubspotid
							where vtiger_crmentity.deleted=0 order by hubspotname";
		$result = $adb->pquery($query, array());
		$numMClist = $adb->num_rows($result);
		$list_hubspot = array();
		if ($numMClist >0){
			for($i=0;$i < $adb->num_rows($result);$i++) {
				$list_hubspot [$adb->query_result($result,$i,'hubspotid')] = $adb->query_result($result,$i,'hubspotname');
			}
		}

		$viewer->assign('HUBSPOTLIST',$list_hubspot);
		$viewer->assign('NUMMCLIST',$numMClist);
		$viewer->assign('MODULE',$module);
		$viewer->assign('MODULENAME',$moduleName);
		$viewer->assign('REPORTID',$reportid);
		$viewer->view('ShowHubSpotListforReports.tpl', $module);
	}
}

?>
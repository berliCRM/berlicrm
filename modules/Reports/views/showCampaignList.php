<?php
class Reports_showCampaignList_View extends Vtiger_View_Controller {
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
		if(!vtlib_isModuleActive('Campaigns')) {
			$response = new Vtiger_Response();
			$response->setResult(json_encode('NOCAMPAIGN'));
			$response->emit();
			return;
		}
		$viewer = new Vtiger_Viewer();
		$module = $request->getModule();
		$moduleName = $request->get('modulename');
		$reportid = $request->get('reportid');
		//list of campaigns
		$query = "SELECT vtiger_campaign.campaignid, vtiger_campaign.campaignname from vtiger_campaign
					inner join vtiger_crmentity on vtiger_crmentity.crmid = vtiger_campaign.campaignid
					where vtiger_crmentity.deleted=0 order by campaignname";
		$result = $adb->pquery($query, array());
		$numMClist = $adb->num_rows($result);
		$list_campaigns = array();
		if ($numMClist >0){
			for($i=0;$i < $adb->num_rows($result);$i++) {
				$list_campaigns [$adb->query_result($result,$i,'campaignid')] = $adb->query_result($result,$i,'campaignname');
			}
		}

		$viewer->assign('CAMPAIGNLIST',$list_campaigns);
		$viewer->assign('NUMMCLIST',$numMClist);
		$viewer->assign('MODULE',$module);
		$viewer->assign('MODULENAME',$moduleName);
		$viewer->assign('REPORTID',$reportid);
		$viewer->view('ShowCampaignListforReports.tpl', $module);
	}
}

?>
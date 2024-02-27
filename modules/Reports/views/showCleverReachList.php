<?php
class Reports_showCleverReachList_View extends Vtiger_View_Controller {
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
		if(!vtlib_isModuleActive('berliCleverReach')) {
			$response = new Vtiger_Response();
			$response->setResult(json_encode('NOCLEVERREACH'));
			$response->emit();
			return;
		}
		$viewer = new Vtiger_Viewer();
		$module = $request->getModule();
		$moduleName = $request->get('modulename');
		$reportid = $request->get('reportid');


		//list of campaigns
		$query = "SELECT vtiger_berlicleverreach.cleverreachid, vtiger_berlicleverreach.cleverreachname from vtiger_berlicleverreach
							inner join vtiger_crmentity on vtiger_crmentity.crmid = vtiger_berlicleverreach.cleverreachid
							where vtiger_crmentity.deleted=0 order by cleverreachname";
		$result = $adb->pquery($query, array());
		$numMClist = $adb->num_rows($result);
		$list_cleverreach = array();
		if ($numMClist >0){
			for($i=0;$i < $adb->num_rows($result);$i++) {
				$list_cleverreach [$adb->query_result($result,$i,'cleverreachid')] = $adb->query_result($result,$i,'cleverreachname');
			}
		}
		
		$viewer->assign('CLEVERREACHLIST',$list_cleverreach);
		$viewer->assign('NUMMCLIST',$numMClist);
		$viewer->assign('MODULE',$module);
		$viewer->assign('MODULENAME',$moduleName);
		$viewer->assign('REPORTID',$reportid);
		$viewer->view('ShowCleverReachListforReports.tpl', $module);
	}
}

?>
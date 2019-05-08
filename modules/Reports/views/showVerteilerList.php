<?php
class Reports_showVerteilerList_View extends Vtiger_View_Controller {
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
		$db = PearDatabase::getInstance();
		if(!vtlib_isModuleActive('Verteiler')) {
			$response = new Vtiger_Response();
			$response->setResult(json_encode('NOCLEVERREACH'));
			$response->emit();
			return;
		}
		$viewer = new Vtiger_Viewer();
		$module = $request->getModule();
		$moduleName = $request->get('modulename');
		$reportid = $request->get('reportid');
		//list of Verteiler
		$query = "SELECT vtiger_verteiler.verteilerid, vtiger_verteiler.verteilername from vtiger_verteiler
					inner join vtiger_crmentity on vtiger_crmentity.crmid = vtiger_verteiler.verteilerid
					where vtiger_crmentity.deleted=0 order by verteilername";
		$result = $db->pquery($query, array());
		$numVElist = $db->num_rows($result);
		$list_verteiler = array();
		if ($numVElist >0){
			for($i=0;$i < $db->num_rows($result);$i++) {
				$list_verteiler [$db->query_result($result,$i,'verteilerid')] = $db->query_result($result,$i,'verteilername');
			}
		}

		$viewer->assign('VERTEILERLIST',$list_verteiler);
		$viewer->assign('NUMVELIST',$numVElist);
		$viewer->assign('MODULE',$module);
		$viewer->assign('MODULENAME',$moduleName);
		$viewer->assign('REPORTID',$reportid);
		$viewer->view('ShowVerteilerListforReports.tpl', $module);
	}
}

?>